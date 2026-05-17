<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\FollowUpItem;
use App\Models\ItemDocument;
use App\Models\ItemHistory;
use App\Models\Section;
use App\Models\User;
use App\Notifications\ItemUpdatedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class FollowUpItemController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $items = FollowUpItem::query()
            ->with(['currentOwner.section', 'section', 'documents'])
            ->when($user->isHeadOfSection(), function ($query) use ($user): void {
                $query->where(function ($inner) use ($user): void {
                    $inner->where('current_owner_id', $user->id)
                        ->orWhere('section_id', $user->section_id);
                });
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('reference_code', 'like', "%{$search}%")
                        ->orWhere('eoffice_reference', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('item_type'), fn ($q) => $q->where('item_type', (string) $request->string('item_type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->string('status')))
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->integer('section_id')))
            ->when($request->filled('current_owner_id'), fn ($q) => $q->where('current_owner_id', $request->integer('current_owner_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('received_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('received_at', '<=', $request->date('date_to')))
            ->latest('received_at')
            ->paginate(12)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'sections' => Section::orderBy('name')->get(),
            'owners' => User::orderBy('name')->get(),
            'statuses' => FollowUpItem::STATUSES,
            'types' => FollowUpItem::TYPES,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', FollowUpItem::class);

        return view('items.create', [
            'types' => FollowUpItem::TYPES,
            'statuses' => array_filter(FollowUpItem::STATUSES, fn (string $s) => $s !== 'closed'),
            'sections' => Section::orderBy('name')->get(),
            'owners' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', FollowUpItem::class);

        $validated = $request->validate($this->itemRules());
        $validated['reference_code'] = $this->nextReferenceCode();
        $validated['last_updated_at'] = now();

        $owner = User::findOrFail($validated['current_owner_id']);

        if ($validated['status'] === ItemStatus::AtDirector->value && $this->cannot('routeToDirector', FollowUpItem::class)) {
            return back()->withInput()->withErrors([
                'current_owner_id' => 'Items at the director desk must be updated through the secretary.',
            ]);
        }

        $item = FollowUpItem::create($validated);

        ItemHistory::create([
            'follow_up_item_id' => $item->id,
            'user_id' => Auth::id(),
            'event_type' => 'created',
            'to_user_id' => $item->current_owner_id,
            'new_status' => $item->status,
            'action_summary' => 'Item created',
            'notes' => $request->input('history_notes') ?: "Task: {$item->next_action}",
        ]);

        return redirect()
            ->route('items.show', $item)
            ->with('status', 'Follow-up item created successfully.');
    }

    public function show(FollowUpItem $item): View
    {
        $this->authorize('view', $item);

        $item->load([
            'currentOwner.section',
            'section',
            'histories.actor',
            'histories.fromUser',
            'histories.toUser',
            'documents.uploadedBy',
        ]);

        return view('items.show', [
            'item' => $item,
            'statuses' => FollowUpItem::STATUSES,
            'owners' => User::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'documentCategories' => ItemDocument::CATEGORIES,
            'documentGroups' => $item->documents
                ->groupBy(fn (ItemDocument $d) => $d->category.'|'.$d->document_label)
                ->map(function ($documents) {
                    $sorted = $documents->sortByDesc('version_number')->values();

                    return ['latest' => $sorted->first(), 'versions' => $sorted];
                })
                ->values(),
        ]);
    }

    public function update(Request $request, FollowUpItem $item): RedirectResponse
    {
        $this->authorize('update', $item);

        if ($item->status->isTerminal()) {
            return back()->withErrors([
                'status' => 'Closed items cannot be updated. Reopen with a new item if more action is needed.',
            ]);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(FollowUpItem::STATUSES)],
            'section_id' => ['nullable', 'exists:sections,id'],
            'next_action' => ['required', 'string', 'max:255'],
            'next_follow_up_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['status'] === ItemStatus::AtDirector->value) {
            $validated['current_owner_id'] = User::query()->where('role', UserRole::Secretary->value)->value('id') ?? $item->current_owner_id;
        }

        $newStatus = ItemStatus::from($validated['status']);
        if (! $item->status->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => "Cannot move an item from '{$item->status->label()}' to '{$newStatus->label()}'.",
            ]);
        }

        $oldStatus = $item->status;
        $item->update(array_merge($validated, ['last_updated_at' => now()]));

        ItemHistory::create([
            'follow_up_item_id' => $item->id,
            'user_id' => Auth::id(),
            'event_type' => 'updated',
            'old_status' => $oldStatus,
            'new_status' => $item->status,
            'action_summary' => 'Follow-up updated',
            'notes' => trim('Task: '.$item->next_action.(($validated['notes'] ?? null) ? ' Notes: '.$validated['notes'] : '')),
        ]);

        if ($item->current_owner_id !== Auth::id()) {
            $item->currentOwner?->notify(new ItemUpdatedNotification($item, $validated['notes'] ?? null));
        }

        return back()->with('status', 'Item updated successfully.');
    }

    private function itemRules(): array
    {
        return [
            'item_type' => ['required', Rule::in(FollowUpItem::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'eoffice_reference' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'received_at' => ['required', 'date'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['required', Rule::in(FollowUpItem::STATUSES)],
            'current_owner_id' => ['required', 'exists:users,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'next_action' => ['required', 'string', 'max:255'],
            'next_follow_up_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
        ];
    }

    private function nextReferenceCode(): string
    {
        // Use a DB-level lock so concurrent inserts cannot read the same max sequence.
        return DB::transaction(function (): string {
            $year = now()->format('Y');
            $max = (int) FollowUpItem::withTrashed()
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->max(DB::raw("CAST(SUBSTR(reference_code, -4) AS INTEGER)"));

            return sprintf('DRCP-%s-%04d', $year, $max + 1);
        });
    }
}
