<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(public Employee $employee) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Employee assigned to you',
            'message' => "{$this->employee->full_name} has been assigned to you.",
            'employee_id' => $this->employee->id,
            'employee_number' => $this->employee->employee_number,
            'url' => route('dashboard'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->employee->loadMissing(['department', 'team']);

        return (new MailMessage)
            ->subject('Employee assigned to you - LeaveFlow')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line("{$this->employee->full_name} has been assigned to you.")
            ->line('Employee ID: ' . $this->employee->employee_number)
            ->line('Position: ' . ($this->employee->job_title ?: 'Not specified'))
            ->line('Department: ' . ($this->employee->department?->name ?? 'Not specified'))
            ->line('Team: ' . ($this->employee->team?->name ?? 'Not specified'))
            ->action('Open LeaveFlow', route('dashboard'))
            ->salutation('Regards, LeaveFlow');
    }
}
