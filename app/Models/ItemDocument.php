<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemDocument extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'incoming_letter',
        'draft_response',
        'signed_response',
        'internal_minute',
        'supporting_file',
        'reference_document',
    ];

    protected $fillable = [
        'follow_up_item_id',
        'category',
        'document_label',
        'version_number',
        'is_primary',
        'user_id',
        'original_name',
        'stored_name',
        'storage_path',
        'mime_type',
        'size_bytes',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(FollowUpItem::class, 'follow_up_item_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
