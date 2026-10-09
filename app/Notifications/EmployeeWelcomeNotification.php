<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $password) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your LeaveFlow account is ready')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your employee account has been created.')
            ->line('Login email: ' . $notifiable->email)
            ->line('Temporary password: ' . $this->password)
            ->line('Please sign in and change your password immediately.')
            ->action('Sign in to LeaveFlow', route('login'));
    }
}
