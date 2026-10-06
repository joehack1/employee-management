<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Notifications\LeaveStatusNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendLeaveRemindersCommand extends Command
{
    protected $signature = 'leave:reminders';
    protected $description = 'Send automatic reminders for pending approvals, upcoming leaves, and return-to-work';

    public function handle()
    {
        $this->info('Starting automated leave reminders scan...');

        $now = Carbon::now();
        $tomorrow = Carbon::tomorrow()->toDateString();
        $sentCount = 0;

        // 1. Pending applications older than 24 hours awaiting Team Lead
        $staleTeamLeadApps = LeaveApplication::with(['employee.teamLead', 'leaveType'])
            ->where('status', 'pending_team_lead')
            ->where('submitted_at', '<=', $now->copy()->subHours(24))
            ->get();

        foreach ($staleTeamLeadApps as $app) {
            $teamLead = $app->employee->teamLead;
            if ($teamLead) {
                $teamLead->notify(new LeaveStatusNotification(
                    application: $app,
                    title: '⏳ Pending Approval Reminder (24h+)',
                    message: "Application {$app->application_number} from {$app->employee->full_name} has been awaiting your review for over 24 hours.",
                    type: 'warning'
                ));
                $sentCount++;
            }
        }

        // 2. Upcoming leave starting tomorrow
        $startingTomorrow = LeaveApplication::with(['employee.user', 'leaveType'])
            ->where('status', 'approved')
            ->where('start_date', $tomorrow)
            ->get();

        foreach ($startingTomorrow as $app) {
            if ($app->employee->user) {
                $app->employee->user->notify(new LeaveStatusNotification(
                    application: $app,
                    title: '📅 Upcoming Leave Reminder',
                    message: "Your {$app->leaveType->name} starts tomorrow ({$app->start_date->format('d M Y')}). Have a restful break!",
                    type: 'info'
                ));
                $sentCount++;
            }
        }

        // 3. Return reminder (leave ends tomorrow)
        $endingTomorrow = LeaveApplication::with(['employee.user', 'leaveType'])
            ->where('status', 'approved')
            ->where('end_date', $tomorrow)
            ->get();

        foreach ($endingTomorrow as $app) {
            if ($app->employee->user) {
                $app->employee->user->notify(new LeaveStatusNotification(
                    application: $app,
                    title: '🏢 Return to Work Reminder',
                    message: "Your {$app->leaveType->name} ends tomorrow. We look forward to welcoming you back to work on your next scheduled working day.",
                    type: 'info'
                ));
                $sentCount++;
            }
        }

        AuditLog::log('reminders_sent', 'System', 0, "Dispatched {$sentCount} automatic reminders.");

        $this->info("Completed. Dispatched {$sentCount} reminder notifications.");
        return 0;
    }
}
