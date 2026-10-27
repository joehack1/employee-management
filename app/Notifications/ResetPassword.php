<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends BaseResetPassword
{
    use Queueable;

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    protected function buildMailMessage($url): MailMessage
    {
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset Your Password - LeaveFlow')
            ->view('emails.notification', [
                'greeting' => 'Hello!',
                'lines' => [
                    'You are receiving this email because we received a password reset request for your account.',
                    'This password reset link will expire in ' . $expire . ' minutes.',
                    'If you did not request a password reset, no further action is required.',
                ],
                'action' => ['label' => 'Reset Password', 'url' => $url],
            ]);
    }
}
