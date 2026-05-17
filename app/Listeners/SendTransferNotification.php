<?php

namespace App\Listeners;

use App\Events\ItemTransferred;
use App\Notifications\ItemTransferredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class SendTransferNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(ItemTransferred $event): void
    {
        $event->toUser->notify(new ItemTransferredNotification(
            $event->item,
            $event->task,
            $event->notes,
        ));
    }

    public function failed(ItemTransferred $event, Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('Transfer notification failed', [
            'item_id' => $event->item->id,
            'to_user_id' => $event->toUser->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
