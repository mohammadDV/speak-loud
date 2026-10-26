<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Notifications\Messages\MailMessage;

trait SpeaksLoudMail
{
    protected function speakLoudGreeting(object $notifiable): string
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

    protected function withSpeakLoudVoice(MailMessage $mail, object $notifiable): MailMessage
    {
        return $mail
            ->greeting($this->speakLoudGreeting($notifiable))
            ->salutation("Talk soon,\nThe SpeakLoud team");
    }
}
