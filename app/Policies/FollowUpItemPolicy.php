<?php

namespace App\Policies;

use App\Enums\ItemStatus;
use App\Models\FollowUpItem;
use App\Models\User;

class FollowUpItemPolicy
{
    public function create(User $user): bool
    {
        return $user->isSecretary();
    }

    public function view(User $user, FollowUpItem $item): bool
    {
        return $user->isSecretary()
            || $user->isDirector()
            || $item->current_owner_id === $user->id
            || ($user->section_id !== null && $item->section_id === $user->section_id);
    }

    public function update(User $user, FollowUpItem $item): bool
    {
        return $user->isSecretary() || $item->current_owner_id === $user->id;
    }

    /**
     * Only the secretary may route items to the director desk.
     * Used by both FollowUpItemController and ItemTransferController.
     */
    public function routeToDirector(User $user): bool
    {
        return $user->isSecretary();
    }
}
