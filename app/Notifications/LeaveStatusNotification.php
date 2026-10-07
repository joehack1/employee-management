<?php

namespace App\Notifications;

use App\Models\LeaveBalance;
use App\Models\LeaveApplication;
use App\Models\PublicHoliday;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LeaveApplication $application,
        public string $title,
        public string $message,
        public string $type = 'info' // info, success, warning, danger
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'application_number' => $this->application->application_number,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'status' => $this->application->status,
            'leave_type' => $this->application->leaveType->name ?? 'Leave',
            'start_date' => $this->application->start_date->format('d M Y'),
            'end_date' => $this->application->end_date->format('d M Y'),
            'total_days' => $this->application->total_days,
            'url' => route('leave.show', $this->application->id),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->application->loadMissing(['employee', 'leaveType']);
        $employee = $this->application->employee;
        $leaveType = $this->application->leaveType;
        $recipientName = $notifiable->name ?? $employee?->first_name ?? 'there';
        $balance = $employee && $leaveType
            ? LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $this->application->start_date->year)
                ->first()
            : null;

        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting("Hello {$recipientName},")
            ->line($this->message)
            ->line('Leave summary')
            ->line('Leave type: ' . ($leaveType?->name ?? 'Leave'))
            ->line(sprintf(
                'Dates: %s to %s (%s working days)',
                $this->application->start_date->format('d M Y'),
                $this->application->end_date->format('d M Y'),
                $this->formatDays((float) $this->application->total_days)
            ));

        $leaveTypeMessage = match ($leaveType?->code) {
            'sick' => 'We are sorry to hear that you are unwell. Please take care, and we wish you a smooth and speedy recovery.',
            'annual' => 'We hope you enjoy your time away and return feeling rested.',
            'compassionate' => 'We are sorry for your loss. Please accept our sincere condolences, and take the time you need with your loved ones.',
            'maternity' => 'Wishing you and your family good health and happiness during this special time.',
            'paternity' => 'Wishing you and your family good health and happiness as you welcome this new chapter.',
            'emergency' => 'We hope everything is okay. Please let HR know if you need any support during this time.',
            'study' => 'We wish you success with your studies and exams.',
            'unpaid' => 'If you have questions about how this leave affects your balance or pay, please contact HR.',
            default => 'Please contact HR if you need any support or have questions about your leave.',
        };

        if ($leaveTypeMessage) {
            $mail->line($leaveTypeMessage);
        }

        if (
            $this->application->manual_attachment_expected
            && $employee
            && ($notifiable->id ?? null) === $employee->user_id
        ) {
            $documentDescription = $leaveType?->isMedicalLeave() ? 'medical documents' : 'supporting documents';
            $mail->line("You indicated that you will deliver your {$documentDescription} to HR personally. Please bring them with you when you return to work.");
        }

        if ($balance) {
            $mail->line(sprintf(
                'Your available %s balance after this request is %s days.',
                $leaveType->name,
                $this->formatDays($balance->available_days)
            ));
        } else {
            $mail->line('Your leave balance is not currently available in the system. Please contact HR if you need help confirming it.');
        }

        if (in_array($this->application->status, ['pending_team_lead', 'pending_manager', 'pending_hr', 'approved'], true)) {
            $returnDate = $this->expectedReturnDate();
            $returnLabel = $this->application->status === 'approved'
                ? 'Expected back at work'
                : 'Expected back at work if approved';
            $mail->line($returnLabel . ': ' . $returnDate->format('l, d M Y') . '.');
        }

        return $mail
            ->action('View Leave Application', route('leave.show', $this->application->id))
            ->salutation('Regards, HR');
    }

    private function expectedReturnDate(): Carbon
    {
        $workingDays = WorkingDay::whereBetween('day_of_week', [1, 5])
            ->where('is_working_day', true)
            ->pluck('day_of_week')
            ->map(fn ($day) => (int) $day)
            ->all();

        if (empty($workingDays)) {
            $workingDays = [1, 2, 3, 4, 5];
        }

        $date = $this->application->end_date->copy()->addDay();
        $daysChecked = 0;

        while ($daysChecked < 370) {
            $isWorkingDay = in_array($date->dayOfWeek, $workingDays, true);
            $isPublicHoliday = PublicHoliday::whereDate('date', $date->toDateString())->exists();

            if ($isWorkingDay && !$isPublicHoliday) {
                return $date;
            }

            $date->addDay();
            $daysChecked++;
        }

        return $date;
    }

    private function formatDays(float $days): string
    {
        return rtrim(rtrim(number_format($days, 2, '.', ''), '0'), '.');
    }
}
