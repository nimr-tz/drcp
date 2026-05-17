<?php

namespace App\Notifications;

use App\Models\FollowUpItem;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ItemTransferredNotification extends Notification
{
    public function __construct(
        private readonly FollowUpItem $item,
        private readonly string $task,
        private readonly ?string $notes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Action required: {$this->item->reference_code} — {$this->item->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A follow-up item has been transferred to you.')
            ->line("**{$this->item->reference_code}** — {$this->item->title}")
            ->line("Your task: {$this->task}");

        if ($this->notes) {
            $mail->line("Notes: {$this->notes}");
        }

        return $mail
            ->action('View item', route('items.show', $this->item))
            ->line('Please log in to review the item and update its status.');
    }
}
