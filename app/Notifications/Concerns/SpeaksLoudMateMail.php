<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Notifications\Messages\MailMessage;

trait SpeaksLoudMateMail
{
    protected function speakLoudMateGreeting(object $notifiable): string
    {
        $name = null;

        if ($notifiable instanceof User) {
            $notifiable->loadMissing('profile');
            $profile = $notifiable->profile;

            if ($profile instanceof UserProfile && filled($profile->display_name)) {
                $name = $profile->display_name;
            } elseif ($profile instanceof UserProfile && filled($profile->username)) {
                $name = $profile->username;
            }
        }

        $label = filled($name) ? $name : 'there';

        return "Hi {$label},";
    }

    protected function withSpeakLoudMateVoice(MailMessage $mail, object $notifiable): MailMessage
    {
        return $mail
            ->greeting($this->speakLoudMateGreeting($notifiable))
            ->salutation("Talk soon,\nThe SpeakLoudMate team");
    }
}
