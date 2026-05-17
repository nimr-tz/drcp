<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Models\FollowUpItem;
use App\Models\ItemDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $staleThreshold = now()->subDays(3);

        $base = FollowUpItem::query()
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->when($user->isHeadOfSection(), function (Builder $query) use ($user): void {
                $query->where(function (Builder $inner) use ($user): void {
                    $inner->where('current_owner_id', $user->id)
                        ->orWhere('section_id', $user->section_id);
                });
            });

        $stats = [
            'active' => (clone $base)->count(),

            'overdue' => (clone $base)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->count(),

            'stale' => (clone $base)
                ->where(function (Builder $q) use ($staleThreshold): void {
                    $q->whereNull('last_updated_at')
                        ->orWhere('last_updated_at', '<', $staleThreshold);
                })
                ->count(),

            'at_director' => (clone $base)
                ->where('status', ItemStatus::AtDirector->value)
                ->count(),

            'my_items' => (clone $base)
                ->where('current_owner_id', $user->id)
                ->count(),

            'high_priority' => (clone $base)
                ->where('priority', 'high')
                ->count(),

            'missing_main_document' => (clone $base)
                ->whereDoesntHave('documents', fn (Builder $q) => $q->where('is_primary', true))
                ->count(),

            'missing_signed_response' => (clone $base)
                ->where('item_type', 'letter')
                ->whereDoesntHave('documents', fn (Builder $q) => $q->where('category', 'signed_response'))
                ->count(),
        ];

        $highlightedItems = (clone $base)
            ->with(['currentOwner.section', 'section', 'documents'])
            ->orderByRaw("CASE WHEN due_date IS NOT NULL AND due_date < DATE('now') THEN 0 ELSE 1 END")
            ->orderBy('next_follow_up_date')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
            'highlightedItems' => $highlightedItems,
        ]);
    }
}
