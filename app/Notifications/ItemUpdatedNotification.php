<?php

namespace App\Notifications;

use App\Models\FollowUpItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ItemUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly FollowUpItem $item,
        private readonly ?string $notes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Item updated: {$this->item->reference_code} — {$this->item->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A follow-up item you are involved with has been updated.')
            ->line("**{$this->item->reference_code}** — {$this->item->title}")
            ->line('Status: ' . $this->item->status->label());

        if ($this->notes) {
            $mail->line("Notes: {$this->notes}");
        }

        return $mail
            ->action('View item', route('items.show', $this->item))
            ->line('Log in to review the latest changes.');
    }
}
