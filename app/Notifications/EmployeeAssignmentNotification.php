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
            ->view('emails.notification', [
                'greeting' => 'Hello ' . ($notifiable->name ?? 'there') . ',',
                'lines' => [
                    "{$this->employee->full_name} has been assigned to you.",
                    'Employee ID: ' . $this->employee->employee_number,
                    'Position: ' . ($this->employee->job_title ?: 'Not specified'),
                    'Department: ' . ($this->employee->department?->name ?? 'Not specified'),
                    'Team: ' . ($this->employee->team?->name ?? 'Not specified'),
                ],
                'action' => ['label' => 'Open LeaveFlow', 'url' => route('dashboard')],
                'salutation' => 'Regards, LeaveFlow',
            ]);
    }
}
