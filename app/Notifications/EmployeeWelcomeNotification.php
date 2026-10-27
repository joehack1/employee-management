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
            ->view('emails.notification', [
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'lines' => [
                    'Your employee account has been created.',
                    'Login email: ' . $notifiable->email,
                    'Temporary password: ' . $this->password,
                    'Please sign in and change your password immediately.',
                ],
                'action' => ['label' => 'Sign in to LeaveFlow', 'url' => route('login')],
            ]);
    }
}
