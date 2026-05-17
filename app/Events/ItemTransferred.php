<?php

namespace App\Events;

use App\Models\FollowUpItem;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemTransferred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly FollowUpItem $item,
        public readonly User $toUser,
        public readonly string $task,
        public readonly ?string $notes,
    ) {}
}
