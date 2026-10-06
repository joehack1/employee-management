<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\LeaveCalculationService;
use App\Services\LeaveLedgerService;
use App\Services\LeaveWorkflowService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApplicationController extends Controller
{
    public function __construct(
        protected LeaveCalculationService $calculationService,
        protected LeaveWorkflowService $workflowService,
        protected LeaveLedgerService $ledgerService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'No employee profile linked to your user account.');
        }

        $query = LeaveApplication::with(['leaveType', 'approvals.approver'])
            ->where('employee_id', $employee->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(15);
        $leaveTypes = LeaveType::where('is_active', true)->get();

        return view('leave.index', compact('applications', 'leaveTypes', 'employee'));
    }

    public function create()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
        }

        $year = Carbon::now()->year;
        $leaveTypes = LeaveType::where('is_active', true)->get();
        $balances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        return view('leave.create', compact('employee', 'leaveTypes', 'balances'));
    }

    /**
     * Live AJAX endpoint for interactive date calculation and validations.
     */
    public function calculateAjax(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['error' => 'Employee profile not found'], 404);
        }

        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'is_half_day' => 'nullable',
            'half_day_type' => 'nullable|string',
            'is_emergency' => 'nullable',
        ]);

        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $isHalfDay = filter_var($request->is_half_day, FILTER_VALIDATE_BOOLEAN);
        $halfDayType = $request->half_day_type;
        $isEmergency = filter_var($request->is_emergency, FILTER_VALIDATE_BOOLEAN);

        $result = $this->calculationService->calculate(
            employee: $employee,
            leaveType: $leaveType,
            startDate: $startDate,
            endDate: $endDate,
            isHalfDay: $isHalfDay,
            halfDayType: $halfDayType,
            isEmergency: $isEmergency
        );

        // Fetch balance
        $balance = $this->ledgerService->getOrCreateBalance($employee, $leaveType, Carbon::parse($startDate)->year);
        $result['available_balance'] = $balance->available_days;
        $result['has_sufficient_balance'] = $leaveType->code === 'unpaid' || ($balance->available_days >= $result['total_days']);

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'No employee record linked.');
        }

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['nullable'],
            'half_day_type' => ['nullable', 'in:morning,afternoon'],
            'is_emergency' => ['nullable'],
            'reason' => ['required', 'string', 'min:5'],
            'manual_attachment_expected' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $medicalDocumentRequired = $leaveType->isMedicalLeave();
        $manualAttachmentExpected = $medicalDocumentRequired
            && filter_var($request->input('manual_attachment_expected'), FILTER_VALIDATE_BOOLEAN);

        if ($medicalDocumentRequired && !$request->hasFile('attachment') && !$manualAttachmentExpected) {
            return back()->withInput()->withErrors([
                'attachment' => 'A medical supporting document is required. Attach it here or confirm that you will deliver it manually to HR.',
            ]);
        }

        // Keep configurable attachment rules for non-medical leave types.
        if (!$medicalDocumentRequired && $leaveType->requires_attachment && !$request->hasFile('attachment')) {
            $daysDiff = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date'])) + 1;
            if ($daysDiff > $leaveType->attachment_required_after_days) {
                return back()->withInput()->withErrors([
                    'attachment' => "A supporting document is required for {$leaveType->name} requests longer than {$leaveType->attachment_required_after_days} days.",
                ]);
            }
        }

        try {
            $application = $this->workflowService->submitApplication(
                employee: $employee,
                leaveType: $leaveType,
                validatedData: $validated,
                attachmentFile: $request->file('attachment'),
                authUserId: $user->id
            );

            return redirect()->route('leave.show', $application->id)
                ->with('success', "Leave application {$application->application_number} submitted successfully!");
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $application = LeaveApplication::with([
            'employee.user',
            'employee.department',
            'employee.team',
            'leaveType',
            'applicationDays',
            'approvals.approver',
            'comments.user',
            'attachments.uploader',
            'rejectedByUser',
            'cancelledByUser',
        ])->findOrFail($id);

        $user = Auth::user();
        $employee = $user->employee;

        // Authorization check: Employee can view own, Team Lead can view supervisees, HR can view all
        $isOwner = $employee && $application->employee_id === $employee->id;
        $isTeamLead = $user->role === 'team_lead' && ($application->employee->team_lead_id === $user->id || $application->employee->team_id === $employee?->team_id);
        $isHr = $user->isHr();
        $isManager = $user->isManager();

        if (!$isOwner && !$isTeamLead && !$isHr && !$isManager) {
            abort(403, 'Unauthorized access to leave application.');
        }

        return view('leave.show', compact('application', 'isOwner', 'isTeamLead', 'isHr', 'isManager'));
    }

    public function requestCancellation(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $application = LeaveApplication::findOrFail($id);
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee || $application->employee_id !== $employee->id) {
            abort(403, 'Unauthorized');
        }

        try {
            $this->workflowService->requestCancellation($application, $request->cancellation_reason, $user->id);
            return back()->with('success', 'Cancellation request submitted. It will be reviewed by HR/Team Lead.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Employee record not found.');
        }

        $year = $request->input('year', Carbon::now()->year);

        $applications = LeaveApplication::with(['leaveType', 'approvals.approver'])
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $year)
            ->orderBy('start_date', 'desc')
            ->get();

        $transactions = $employee->transactions()
            ->with(['leaveType', 'creator'])
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->get();

        return view('leave.history', compact('employee', 'applications', 'transactions', 'year'));
    }
}
