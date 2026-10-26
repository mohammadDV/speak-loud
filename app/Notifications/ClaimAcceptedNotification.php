<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Claim;
use App\Models\User;
use App\Notifications\Concerns\FormatsClaimMail;
use App\Notifications\Concerns\SpeaksLoudMail;
use App\Support\UserWantsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimAcceptedNotification extends Notification implements ShouldQueue
{
    use FormatsClaimMail, Queueable, SpeaksLoudMail;

    public function __construct(public Claim $claim)
    {
        $this->onQueue('mail');
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $channel !== 'mail'
            || ($notifiable instanceof User
                && UserWantsNotification::email($notifiable, NotificationType::ClaimAccepted));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $receiverName = $this->counterpartName($this->claim->receiver);
        $scheduleContext = $this->scheduleContext($this->claim);

        $mail = (new MailMessage)
            ->subject('Your claim was accepted')
            ->line("{$receiverName} accepted your claim.");

        if ($scheduleContext !== null) {
            $mail->line("Schedule: {$scheduleContext}");
        }

        return $this->withSpeakLoudVoice(
            $mail
                ->action('View claims', $this->claimsUrl())
                ->line('Open SpeakLoud to continue the conversation.'),
            $notifiable,
        );
    }
}
