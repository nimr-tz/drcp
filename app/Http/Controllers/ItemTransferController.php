<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Events\ItemTransferred;
use App\Models\FollowUpItem;
use App\Models\ItemHistory;
use App\Models\User;
use App\Notifications\ItemClosedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ItemTransferController extends Controller
{
    public function transfer(Request $request, FollowUpItem $item): RedirectResponse
    {
        $this->authorize('update', $item);

        if ($item->status->isTerminal()) {
            return back()->withErrors(['status' => 'Closed items cannot be transferred.']);
        }

        $validated = $request->validate([
            'current_owner_id' => ['required', 'exists:users,id'],
            'status' => ['required', Rule::in(FollowUpItem::STATUSES)],
            'next_action' => ['required', 'string', 'max:255'],
            'next_follow_up_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $toUser = User::findOrFail($validated['current_owner_id']);

        if ($validated['status'] === ItemStatus::AtDirector->value && $this->cannot('routeToDirector', FollowUpItem::class)) {
            return back()->withErrors([
                'current_owner_id' => 'Items at the director desk must be updated through the secretary.',
            ]);
        }

        $newStatus = ItemStatus::from($validated['status']);
        if (! $item->status->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => "Cannot move an item from '{$item->status->label()}' to '{$newStatus->label()}'.",
            ]);
        }

        $fromUserId = $item->current_owner_id;
        $oldStatus = $item->status;

        $item->update([
            'current_owner_id' => $toUser->id,
            'section_id' => $toUser->section_id ?: $item->section_id,
            'status' => $validated['status'],
            'next_action' => $validated['next_action'],
            'next_follow_up_date' => $validated['next_follow_up_date'],
            'last_updated_at' => now(),
        ]);

        ItemHistory::create([
            'follow_up_item_id' => $item->id,
            'user_id' => Auth::id(),
            'event_type' => 'transfer',
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUser->id,
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'action_summary' => "Sent to {$toUser->name}",
            'notes' => trim("Task: {$validated['next_action']}".(($validated['notes'] ?? null) ? " Notes: {$validated['notes']}" : '')),
        ]);

        ItemTransferred::dispatch($item, $toUser, $validated['next_action'], $validated['notes'] ?? null);

        return back()->with('status', 'Ownership transferred successfully.');
    }

    public function close(Request $request, FollowUpItem $item): RedirectResponse
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['completed', 'closed'])],
            'closure_note' => ['required', 'string'],
        ]);

        $oldStatus = $item->status;

        $item->update([
            'status' => $validated['status'],
            'closure_note' => $validated['closure_note'],
            'closed_at' => now(),
            'last_updated_at' => now(),
        ]);

        ItemHistory::create([
            'follow_up_item_id' => $item->id,
            'user_id' => Auth::id(),
            'event_type' => 'closed',
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'action_summary' => 'Item closed',
            'notes' => $validated['closure_note'],
        ]);

        $closedNotification = new ItemClosedNotification($item, $validated['closure_note']);

        $notifyIds = collect();
        if ($item->current_owner_id !== Auth::id()) {
            $item->currentOwner?->notify($closedNotification);
            $notifyIds->push($item->current_owner_id);
        }

        User::where('role', UserRole::Secretary->value)
            ->where('id', '!=', Auth::id())
            ->whereNotIn('id', $notifyIds)
            ->get()
            ->each->notify($closedNotification);

        return back()->with('status', 'Item closed successfully.');
    }
}
