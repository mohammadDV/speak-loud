<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Claim;
use App\Models\User;
use App\Notifications\Concerns\FormatsClaimMail;
use App\Support\UserWantsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimReceivedNotification extends Notification implements ShouldQueue
{
    use FormatsClaimMail, Queueable;

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
                && UserWantsNotification::email($notifiable, NotificationType::ClaimReceived));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->counterpartName($this->claim->sender);
        $scheduleContext = $this->scheduleContext($this->claim);

        $mail = (new MailMessage)
            ->subject('New claim on SpeakLoud')
            ->line("{$senderName} sent you a claim.");

        if ($scheduleContext !== null) {
            $mail->line("Schedule: {$scheduleContext}");
        }

        return $mail
            ->action('View claims', $this->claimsUrl())
            ->line('You can accept or decline from your Claims page.');
    }
}
