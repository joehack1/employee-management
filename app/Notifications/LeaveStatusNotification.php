<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
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
        return ['database'];
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
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)
            ->action('View Leave Application', route('leave.show', $this->application->id));
    }
}
