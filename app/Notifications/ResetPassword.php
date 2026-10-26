<?php

namespace App\Notifications;

use App\Notifications\Concerns\SpeaksLoudMail;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable, SpeaksLoudMail;

    public function __construct($token)
    {
        parent::__construct($token);

        $this->onQueue('mail');
        $this->afterCommit();
    }

    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return $this->withSpeakLoudVoice(
            (new MailMessage)
                ->subject('Reset your SpeakLoud password')
                ->line('You requested a password reset. Click the button below to choose a new password.')
                ->action('Reset password', $url)
                ->line('This link expires in '.config('auth.passwords.users.expire').' minutes.')
                ->line('If you did not request a reset, you can ignore this email.'),
            $notifiable,
        );
    }
}
