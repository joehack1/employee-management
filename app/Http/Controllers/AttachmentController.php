<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LeaveAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download($id)
    {
        $attachment = LeaveAttachment::with('leaveApplication.employee')->findOrFail($id);
        $application = $attachment->leaveApplication;
        $user = Auth::user();
        $employee = $user->employee;

        // Security check: only application owner, their team lead, or HR/admin can download
        $isOwner = $employee && $application->employee_id === $employee->id;
        $isTeamLead = $user->role === 'team_lead' && ($application->employee->team_lead_id === $user->id || $application->employee->team_id === $employee?->team_id);
        $isHr = $user->isHr();

        if (!$isOwner && !$isTeamLead && !$isHr) {
            abort(403, 'Unauthorized access to leave attachment.');
        }

        if (!Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found.');
        }

        AuditLog::log('attachment_downloaded', 'LeaveAttachment', $attachment->id, "Attachment {$attachment->original_name} downloaded");

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name);
    }
}
