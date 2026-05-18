<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Models\FollowUpItem;
use App\Models\ItemDocument;
use App\Models\ItemHistory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        return match(true) {
            $user->isSecretary()     => $this->secretaryDashboard($user),
            $user->isDirector()      => $this->directorDashboard($user),
            $user->isHeadOfSection() => $this->headOfSectionDashboard($user),
            default                  => $this->secretaryDashboard($user),
        };
    }

    private function secretaryDashboard(User $user): View
    {
        $staleThreshold = now()->subDays(3);

        // Status pipeline counts (active items only)
        $pipelineRaw = FollowUpItem::query()
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $pipeline = [
            'new'                       => (int) ($pipelineRaw['new'] ?? 0),
            'assigned'                  => (int) ($pipelineRaw['assigned'] ?? 0),
            'in_progress'               => (int) ($pipelineRaw['in_progress'] ?? 0),
            'waiting_external_response' => (int) ($pipelineRaw['waiting_external_response'] ?? 0),
            'at_director'               => (int) ($pipelineRaw['at_director'] ?? 0),
            'returned_for_action'       => (int) ($pipelineRaw['returned_for_action'] ?? 0),
        ];
        $totalActive = array_sum($pipeline);

        // Overdue items
        $overdueItems = FollowUpItem::query()
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')
            ->get();

        $overdueCount      = $overdueItems->count();
        $overdueHigh       = $overdueItems->where('priority', 'high')->count();
        $overdueMedium     = $overdueItems->where('priority', 'medium')->count();
        $oldestOverdueDays = $overdueItems->first()
            ? (int) today()->diffInDays($overdueItems->first()->due_date)
            : 0;
        $criticalItems     = $overdueItems->take(2);

        // Stale (no update in 3+ days)
        $staleBase = fn () => FollowUpItem::query()
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->where(function (Builder $q) use ($staleThreshold): void {
                $q->whereNull('last_updated_at')->orWhere('last_updated_at', '<', $staleThreshold);
            });

        $staleCount    = $staleBase()->count();
        $staleSections = $staleBase()
            ->with('section')
            ->get()
            ->map(fn ($i) => $i->section?->name ?? 'General')
            ->unique()
            ->values()
            ->join(', ');

        // At Director – longest wait via item_histories subquery
        $atDirectorCount       = $pipeline['at_director'];
        $longestAtDirectorDays = 0;
        if ($atDirectorCount > 0) {
            $longestAtDirectorDays = (int) (FollowUpItem::query()
                ->where('status', ItemStatus::AtDirector->value)
                ->addSelect(DB::raw(
                    "(SELECT DATEDIFF(NOW(), created_at) " .
                    "FROM item_histories WHERE follow_up_item_id = follow_up_items.id " .
                    "AND new_status = 'at_director' ORDER BY id DESC LIMIT 1) AS wait_days"
                ))
                ->orderBy('wait_days', 'desc')
                ->value('wait_days') ?? 0);
        }

        // Completion metrics (this week)
        $weekStart         = now()->startOfWeek();
        $completedThisWeek = FollowUpItem::query()
            ->whereIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', $weekStart)
            ->count();

        $completionRate = ($totalActive + $completedThisWeek) > 0
            ? (int) round($completedThisWeek / ($totalActive + $completedThisWeek) * 100)
            : 0;

        // Avg handling time (last 30 days)
        $avgHandlingDays = (float) (FollowUpItem::query()
            ->whereIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', now()->subDays(30))
            ->selectRaw("ROUND(AVG(DATEDIFF(closed_at, received_at)), 1) AS avg_days")
            ->value('avg_days') ?? 0);

        // On-time rate (non-overdue / total active)
        $onTimeRate = $totalActive > 0
            ? (int) round(($totalActive - $overdueCount) / $totalActive * 100)
            : 100;

        // Recent activity feed (latest 12 history entries)
        $activityFeed = ItemHistory::with(['item', 'actor'])
            ->latest()
            ->limit(12)
            ->get();

        // Document hygiene
        $missingMainDoc = FollowUpItem::query()
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->whereDoesntHave('documents', fn (Builder $q) => $q->where('is_primary', true))
            ->count();

        $missingSignedResponse = FollowUpItem::query()
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->where('item_type', 'letter')
            ->whereDoesntHave('documents', fn (Builder $q) => $q->where('category', 'signed_response'))
            ->count();

        $totalDocuments = ItemDocument::count();

        // Priority attention list
        $highlightedItems = FollowUpItem::query()
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->with(['currentOwner', 'section', 'documents'])
            ->orderByRaw("CASE WHEN due_date IS NOT NULL AND due_date < DATE('now') THEN 0 ELSE 1 END")
            ->orderBy('next_follow_up_date')
            ->limit(8)
            ->get();

        return view('dashboard.secretary', compact(
            'user', 'pipeline', 'totalActive',
            'overdueCount', 'overdueHigh', 'overdueMedium', 'oldestOverdueDays', 'criticalItems',
            'staleCount', 'staleSections',
            'atDirectorCount', 'longestAtDirectorDays',
            'completedThisWeek', 'completionRate', 'avgHandlingDays', 'onTimeRate',
            'activityFeed',
            'missingMainDoc', 'missingSignedResponse', 'totalDocuments',
            'highlightedItems',
        ));
    }

    private function directorDashboard(User $user): View
    {
        // Queue at director desk with computed wait time
        $queueItems = FollowUpItem::query()
            ->where('status', ItemStatus::AtDirector->value)
            ->addSelect('follow_up_items.*')
            ->addSelect(DB::raw(
                "(SELECT DATEDIFF(NOW(), ih.created_at) " .
                "FROM item_histories ih WHERE ih.follow_up_item_id = follow_up_items.id " .
                "AND ih.new_status = 'at_director' ORDER BY ih.id DESC LIMIT 1) AS days_waiting"
            ))
            ->addSelect(DB::raw(
                "(SELECT u.name FROM item_histories ih " .
                "JOIN users u ON u.id = ih.user_id " .
                "WHERE ih.follow_up_item_id = follow_up_items.id " .
                "AND ih.new_status = 'at_director' ORDER BY ih.id DESC LIMIT 1) AS routed_by_name"
            ))
            ->addSelect(DB::raw(
                "(SELECT ih.created_at FROM item_histories ih " .
                "WHERE ih.follow_up_item_id = follow_up_items.id " .
                "AND ih.new_status = 'at_director' ORDER BY ih.id DESC LIMIT 1) AS routed_at"
            ))
            ->with(['section', 'currentOwner'])
            ->orderBy('days_waiting', 'desc')
            ->get();

        $atDeskCount    = $queueItems->count();
        $highPriority   = $queueItems->where('priority', 'high')->count();
        $mediumPriority = $queueItems->where('priority', 'medium')->count();
        $avgWaitDays    = $atDeskCount > 0 ? round((float) $queueItems->avg('days_waiting'), 1) : 0;

        // Decisions this month: histories where director acted on at_director items
        $monthStart = now()->startOfMonth();
        $allDecisions14d = ItemHistory::query()
            ->where('old_status', ItemStatus::AtDirector->value)
            ->whereIn('new_status', [
                ItemStatus::Completed->value,
                ItemStatus::Closed->value,
                ItemStatus::ReturnedForAction->value,
            ])
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->get();

        $decisionsThisMonth = $allDecisions14d->filter(
            fn ($d) => $d->created_at->gte($monthStart)
        );

        $approvedCount  = $decisionsThisMonth->whereIn('new_status', [ItemStatus::Completed->value, ItemStatus::Closed->value])->count();
        $returnedCount  = $decisionsThisMonth->where('new_status', ItemStatus::ReturnedForAction->value)->count();
        $totalDecisions = $decisionsThisMonth->count();
        $approvalRate   = $totalDecisions > 0 ? (int) round($approvedCount / $totalDecisions * 100) : 0;

        // By originating section (current queue)
        $bySection = $queueItems
            ->groupBy(fn ($item) => $item->section?->name ?? 'General')
            ->map(fn ($items, $name) => ['name' => $name, 'count' => $items->count()])
            ->values()
            ->sortByDesc('count');

        // Decision rhythm: last 14 days (one query, grouped in PHP)
        $decisionsByDay = collect(range(13, 0))->map(function ($daysAgo) use ($allDecisions14d) {
            $date        = now()->subDays($daysAgo)->toDateString();
            $dayDecisions = $allDecisions14d->filter(
                fn ($d) => $d->created_at->toDateString() === $date
            );
            return [
                'date'     => $date,
                'label'    => now()->subDays($daysAgo)->format('d M'),
                'approved' => $dayDecisions->whereIn('new_status', [ItemStatus::Completed->value, ItemStatus::Closed->value])->count(),
                'returned' => $dayDecisions->where('new_status', ItemStatus::ReturnedForAction->value)->count(),
            ];
        });

        return view('dashboard.director', compact(
            'user',
            'queueItems', 'atDeskCount', 'highPriority', 'mediumPriority',
            'avgWaitDays',
            'approvedCount', 'returnedCount', 'totalDecisions', 'approvalRate',
            'bySection', 'decisionsByDay',
        ));
    }

    private function headOfSectionDashboard(User $user): View
    {
        // Guard: no section assigned
        if (! $user->section_id) {
            return view('dashboard.head_of_section', [
                'user'                   => $user,
                'officerWorkloads'       => collect(),
                'sectionActive'          => 0,
                'sectionOverdue'         => 0,
                'sectionPipeline'        => [],
                'myItemCount'            => 0,
                'myOverdue'              => 0,
                'myStale'                => 0,
                'completedThisMonth'     => 0,
                'completedLastMonth'     => 0,
                'completedChange'        => 0,
                'avgClosureDays'         => 0.0,
                'sectionMissingDoc'      => 0,
                'sectionMissingResponse' => 0,
                'sectionStale'           => 0,
                'sectionItems'           => collect(),
            ]);
        }

        $staleThreshold = now()->subDays(3);
        $monthStart     = now()->startOfMonth();

        // Section members with active assigned items
        $sectionMembers = User::query()
            ->where('section_id', $user->section_id)
            ->with(['assignedItems' => fn ($q) => $q->whereNotIn(
                'status', [ItemStatus::Completed->value, ItemStatus::Closed->value]
            )])
            ->get();

        // Done-this-month per user (single query)
        $doneThisMonthByUser = FollowUpItem::query()
            ->whereIn('current_owner_id', $sectionMembers->pluck('id'))
            ->whereIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', $monthStart)
            ->selectRaw('current_owner_id, COUNT(*) as count')
            ->groupBy('current_owner_id')
            ->pluck('count', 'current_owner_id');

        $officerWorkloads = $sectionMembers->map(function ($member) use ($doneThisMonthByUser) {
            $active  = $member->assignedItems->count();
            $overdue = $member->assignedItems->filter(fn ($i) => $i->isOverdue())->count();
            $done    = (int) ($doneThisMonthByUser[$member->id] ?? 0);
            $load    = min(100, (int) round($active / 15 * 100));
            return ['user' => $member, 'active' => $active, 'overdue' => $overdue, 'done' => $done, 'load' => $load];
        })->sortByDesc('active')->values();

        // Base builder factory for section active items
        $sectionBase = fn () => FollowUpItem::query()
            ->where('section_id', $user->section_id)
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value]);

        $sectionActive  = $sectionBase()->count();
        $sectionOverdue = $sectionBase()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->count();

        // Section pipeline
        $pipelineRaw = $sectionBase()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $sectionPipeline = [
            'assigned'                  => (int) ($pipelineRaw['assigned'] ?? 0),
            'in_progress'               => (int) ($pipelineRaw['in_progress'] ?? 0),
            'waiting_external_response' => (int) ($pipelineRaw['waiting_external_response'] ?? 0),
            'at_director'               => (int) ($pipelineRaw['at_director'] ?? 0),
            'returned_for_action'       => (int) ($pipelineRaw['returned_for_action'] ?? 0),
        ];

        // My items
        $myBase      = fn () => FollowUpItem::query()
            ->where('current_owner_id', $user->id)
            ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value]);
        $myItemCount = $myBase()->count();
        $myOverdue   = $myBase()->whereNotNull('due_date')->whereDate('due_date', '<', today())->count();
        $myStale     = $myBase()->where(function (Builder $q) use ($staleThreshold): void {
            $q->whereNull('last_updated_at')->orWhere('last_updated_at', '<', $staleThreshold);
        })->count();

        // Completed this month vs last month (section)
        $completedThisMonth = FollowUpItem::query()
            ->where('section_id', $user->section_id)
            ->whereIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', $monthStart)
            ->count();

        $completedLastMonth = FollowUpItem::query()
            ->where('section_id', $user->section_id)
            ->whereIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->whereNotNull('closed_at')
            ->whereBetween('closed_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->count();

        $completedChange = $completedLastMonth > 0
            ? (int) round(($completedThisMonth - $completedLastMonth) / $completedLastMonth * 100)
            : 0;

        // Avg closure time (section, last 30 days)
        $avgClosureDays = (float) (FollowUpItem::query()
            ->where('section_id', $user->section_id)
            ->whereIn('status', [ItemStatus::Completed->value, ItemStatus::Closed->value])
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', now()->subDays(30))
            ->selectRaw("ROUND(AVG(DATEDIFF(closed_at, received_at)), 1) AS avg_days")
            ->value('avg_days') ?? 0);

        // Document hygiene (section)
        $sectionMissingDoc = $sectionBase()
            ->whereDoesntHave('documents', fn (Builder $q) => $q->where('is_primary', true))
            ->count();
        $sectionMissingResponse = $sectionBase()
            ->where('item_type', 'letter')
            ->whereDoesntHave('documents', fn (Builder $q) => $q->where('category', 'signed_response'))
            ->count();
        $sectionStale = $sectionBase()
            ->where(function (Builder $q) use ($staleThreshold): void {
                $q->whereNull('last_updated_at')->orWhere('last_updated_at', '<', $staleThreshold);
            })
            ->count();

        // Section items in flight
        $sectionItems = $sectionBase()
            ->with(['currentOwner', 'section', 'documents'])
            ->orderByRaw("CASE WHEN due_date IS NOT NULL AND due_date < DATE('now') THEN 0 ELSE 1 END")
            ->orderBy('next_follow_up_date')
            ->limit(10)
            ->get();

        return view('dashboard.head_of_section', compact(
            'user',
            'officerWorkloads', 'sectionActive', 'sectionOverdue',
            'sectionPipeline',
            'myItemCount', 'myOverdue', 'myStale',
            'completedThisMonth', 'completedLastMonth', 'completedChange',
            'avgClosureDays',
            'sectionMissingDoc', 'sectionMissingResponse', 'sectionStale',
            'sectionItems',
        ));
    }
}
