<?php

namespace App\Notifications;

use App\Models\FollowUpItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ItemClosedNotification extends Notification implements ShouldQueue
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
            ->subject("Item closed: {$this->item->reference_code} — {$this->item->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line('The following follow-up item has been closed.')
            ->line("**{$this->item->reference_code}** — {$this->item->title}");

        if ($this->notes) {
            $mail->line("Closing notes: {$this->notes}");
        }

        return $mail
            ->action('View item', route('items.show', $this->item))
            ->line('No further action is required.');
    }
}
