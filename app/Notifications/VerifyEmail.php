<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify your SpeakLoud email')
            ->line('Thanks for signing up! Please verify your email address to start using SpeakLoud.')
            ->action('Verify email address', $verificationUrl)
            ->line('If you did not create an account, you can ignore this email.');
    }
}
