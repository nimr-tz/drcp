<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'follow_up_item_id',
        'user_id',
        'event_type',
        'from_user_id',
        'to_user_id',
        'old_status',
        'new_status',
        'action_summary',
        'notes',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(FollowUpItem::class, 'follow_up_item_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
