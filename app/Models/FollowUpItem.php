<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUpItem extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'letter',
        'call',
        'meeting',
        'visit',
        'internal_task',
        'reminder',
        'other',
    ];

    /** @deprecated Use ItemStatus enum directly. Kept for blade views that iterate statuses. */
    public const STATUSES = [
        'new',
        'assigned',
        'in_progress',
        'waiting_external_response',
        'at_director',
        'returned_for_action',
        'completed',
        'closed',
    ];

    protected $fillable = [
        'reference_code',
        'item_type',
        'title',
        'description',
        'eoffice_reference',
        'source',
        'received_at',
        'priority',
        'status',
        'current_owner_id',
        'section_id',
        'next_action',
        'next_follow_up_date',
        'due_date',
        'last_updated_at',
        'closed_at',
        'closure_note',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'next_follow_up_date' => 'date',
            'due_date' => 'date',
            'last_updated_at' => 'datetime',
            'closed_at' => 'datetime',
            'status' => ItemStatus::class,
        ];
    }

    public function currentOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_owner_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ItemHistory::class)->latest('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ItemDocument::class)->latest('created_at');
    }

    public function isOpen(): bool
    {
        return ! $this->status->isTerminal();
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->due_date->isPast() && $this->isOpen();
    }

    public function needsAttention(int $daysWithoutUpdate = 3): bool
    {
        if (! $this->isOpen()) {
            return false;
        }

        return $this->last_updated_at === null
            || $this->last_updated_at->lt(now()->subDays($daysWithoutUpdate));
    }

    public function hasPrimaryDocument(): bool
    {
        return $this->documents->contains(fn (ItemDocument $document) => $document->is_primary);
    }

    public function hasSignedResponse(): bool
    {
        return $this->documents->contains(fn (ItemDocument $document) => $document->category === 'signed_response');
    }
}
