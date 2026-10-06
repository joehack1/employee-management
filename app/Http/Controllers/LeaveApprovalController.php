<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveComment;
use App\Services\LeaveWorkflowService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveApprovalController extends Controller
{
    public function __construct(
        protected LeaveWorkflowService $workflowService
    ) {}

    public function pending(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if ($user->isHr()) {
            // HR sees pending_hr and cancellation_requested
            $query = LeaveApplication::with(['employee.department', 'employee.team', 'leaveType', 'approvals.approver'])
                ->whereIn('status', ['pending_hr', 'cancellation_requested']);

            if ($request->boolean('include_team_lead')) {
                $query->orWhere('status', 'pending_team_lead');
            }

            $applications = $query->orderBy('is_emergency', 'desc')->orderBy('created_at', 'asc')->paginate(15);
            $viewTitle = 'HR Approvals Queue';
            $isHrView = true;
        } elseif ($user->role === 'team_lead') {
            // Team lead sees supervisees with pending_team_lead
            $teamMemberIds = Employee::where('team_lead_id', $user->id)
                ->orWhere(function ($q) use ($employee) {
                    if ($employee && $employee->team_id) {
                        $q->where('team_id', $employee->team_id);
                    }
                })
                ->pluck('id');

            $applications = LeaveApplication::with(['employee.department', 'employee.team', 'leaveType'])
                ->whereIn('employee_id', $teamMemberIds)
                ->where('status', 'pending_team_lead')
                ->orderBy('is_emergency', 'desc')
                ->orderBy('created_at', 'asc')
                ->paginate(15);

            $viewTitle = 'Team Lead Approvals Queue';
            $isHrView = false;
        } else {
            abort(403, 'Unauthorized');
        }

        return view('approvals.pending', compact('applications', 'viewTitle', 'isHrView'));
    }

    public function teamLeadApprove(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== 'team_lead' && !$user->isHr()) {
            abort(403);
        }

        $application = LeaveApplication::findOrFail($id);
        $comment = $request->input('comment');

        try {
            $this->workflowService->approveByTeamLead($application, $user->id, $comment);
            return back()->with('success', "Application {$application->application_number} approved and forwarded to HR.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function teamLeadReject(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== 'team_lead' && !$user->isHr()) {
            abort(403);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $application = LeaveApplication::findOrFail($id);

        try {
            $this->workflowService->rejectByTeamLead($application, $user->id, $request->rejection_reason);
            return back()->with('success', "Application {$application->application_number} rejected.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function hrApprove(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isHr()) {
            abort(403, 'Unauthorized');
        }

        $application = LeaveApplication::findOrFail($id);
        $comment = $request->input('comment');

        try {
            $this->workflowService->approveByHr($application, $user->id, $comment);
            return back()->with('success', "Application {$application->application_number} officially approved and days deducted.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function hrReject(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isHr()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $application = LeaveApplication::findOrFail($id);

        try {
            $this->workflowService->rejectByHr($application, $user->id, $request->rejection_reason);
            return back()->with('success', "Application {$application->application_number} rejected by HR.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveCancellation(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isHr()) {
            abort(403);
        }

        $application = LeaveApplication::findOrFail($id);

        try {
            $this->workflowService->approveCancellation($application, $user->id, $request->input('comment'));
            return back()->with('success', "Cancellation approved for {$application->application_number}. Days refunded to employee ledger.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addComment(Request $request, $id)
    {
        $request->validate(['comment' => 'required|string|min:2|max:1000']);
        $application = LeaveApplication::findOrFail($id);

        LeaveComment::create([
            'leave_application_id' => $application->id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        AuditLog::log('leave_comment_added', 'LeaveApplication', $application->id, "Comment added on {$application->application_number}");

        return back()->with('success', 'Comment added.');
    }
}
