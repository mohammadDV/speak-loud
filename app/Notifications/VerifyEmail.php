<?php

namespace App\Notifications;

use App\Notifications\Concerns\SpeaksLoudMateMail;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable, SpeaksLoudMateMail;

    public function __construct()
    {
        $this->onQueue('mail');
        $this->afterCommit();
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return $this->withSpeakLoudMateVoice(
            (new MailMessage)
                ->subject('Verify your SpeakLoudMate email')
                ->line('Thanks for signing up! Please verify your email address to start using SpeakLoudMate.')
                ->action('Verify email address', $verificationUrl)
                ->line('If you did not create an account, you can ignore this email.'),
            $notifiable,
        );
    }
}
