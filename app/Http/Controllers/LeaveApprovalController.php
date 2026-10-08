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

        if ($user->isSuperAdmin()) {
            $applications = LeaveApplication::with(['employee.department', 'employee.team', 'leaveType', 'approvals.approver'])
                ->whereIn('status', ['pending_team_lead', 'pending_manager', 'pending_hr'])
                ->orderBy('is_emergency', 'desc')
                ->orderBy('created_at', 'asc')
                ->paginate(15);
            $viewTitle = 'All Pending Approvals';
            $approvalLevel = 'administrator';
            $isHrView = false;
        } elseif ($user->isManager()) {
            $managerEmployeeIds = Employee::managerEmployeeIds($user);
            $applications = LeaveApplication::with(['employee.department', 'employee.team', 'leaveType', 'approvals.approver'])
                ->whereIn('employee_id', $managerEmployeeIds)
                ->where('status', 'pending_manager')
                ->orderBy('is_emergency', 'desc')
                ->orderBy('created_at', 'asc')
                ->paginate(15);
            $viewTitle = 'Manager Approvals Queue';
            $approvalLevel = 'manager';
            $isHrView = false;
        } elseif ($user->isHr()) {
            // HR reviews requests routed directly to HR.
            $query = LeaveApplication::with(['employee.department', 'employee.team', 'leaveType', 'approvals.approver'])
                ->where('status', 'pending_hr');

            $applications = $query->orderBy('is_emergency', 'desc')->orderBy('created_at', 'asc')->paginate(15);
            $viewTitle = 'HR Approvals Queue';
            $isHrView = true;
            $approvalLevel = 'hr';
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
            $approvalLevel = 'team_lead';
        } else {
            abort(403, 'Unauthorized');
        }

        return view('approvals.pending', compact('applications', 'viewTitle', 'isHrView', 'approvalLevel'));
    }

    public function teamLeadApprove(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== 'team_lead' && !$user->isSuperAdmin()) {
            abort(403);
        }

        $application = LeaveApplication::where('status', 'pending_team_lead')->findOrFail($id);
        $comment = $request->input('comment');

        try {
            $this->workflowService->approveByTeamLead($application, $user->id, $comment);
            return back()->with('success', "Application {$application->application_number} approved. HR has been notified.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function teamLeadReject(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== 'team_lead' && !$user->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $application = LeaveApplication::where('status', 'pending_team_lead')->findOrFail($id);

        try {
            $this->workflowService->rejectByTeamLead($application, $user->id, $request->rejection_reason);
            return back()->with('success', "Application {$application->application_number} rejected.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function managerApprove(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isManager() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $query = LeaveApplication::where('status', 'pending_manager');
        if (!$user->isSuperAdmin()) {
            $query->whereIn('employee_id', Employee::managerEmployeeIds($user));
        }
        $application = $query->findOrFail($id);
        try {
            $this->workflowService->approveByManager($application, $user->id, $request->input('comment'));
            return $this->managerActionRedirect($request)->with('success', "Application {$application->application_number} approved.");
        } catch (Exception $e) {
            return $this->managerActionRedirect($request)->with('error', $e->getMessage());
        }
    }

    public function managerReject(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isManager() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:500']]);
        $query = LeaveApplication::where('status', 'pending_manager');
        if (!$user->isSuperAdmin()) {
            $query->whereIn('employee_id', Employee::managerEmployeeIds($user));
        }
        $application = $query->findOrFail($id);
        try {
            $this->workflowService->rejectByManager($application, Auth::id(), $request->rejection_reason);
            return $this->managerActionRedirect($request)->with('success', "Application {$application->application_number} rejected.");
        } catch (Exception $e) {
            return $this->managerActionRedirect($request)->with('error', $e->getMessage());
        }
    }

    private function managerActionRedirect(Request $request)
    {
        $destination = $request->input('return_to');
        $route = in_array($destination, ['dashboard', 'approvals.pending'], true)
            ? $destination
            : 'approvals.pending';

        return redirect()->route($route);
    }

    public function hrApprove(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isHr()) {
            abort(403, 'Unauthorized');
        }

        $application = LeaveApplication::where('status', 'pending_hr')->findOrFail($id);
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

        $application = LeaveApplication::where('status', 'pending_hr')->findOrFail($id);

        try {
            $this->workflowService->rejectByHr($application, $user->id, $request->rejection_reason);
            return back()->with('success', "Application {$application->application_number} rejected by HR.");
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
