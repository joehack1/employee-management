<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoverAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(public LeaveApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'You have been assigned leave cover',
            'message' => sprintf(
                'You are covering for %s from %s to %s.',
                $this->application->employee->full_name,
                $this->application->start_date->format('d M Y'),
                $this->application->end_date->format('d M Y')
            ),
            'type' => 'info',
            'application_id' => $this->application->id,
            'url' => route('leave.show', $this->application->id),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Leave cover assignment - LeaveFlow')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->toArray($notifiable)['message'])
            ->line('Please coordinate the handover with your colleague and team lead.')
            ->action('View leave details', route('leave.show', $this->application->id));
    }
}
