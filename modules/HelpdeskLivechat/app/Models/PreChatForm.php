<?php

namespace Modules\HelpdeskLivechat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Inbox;

class PreChatForm extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_pre_chat_forms';

    protected $fillable = [
        'name',
        'inbox_id',
        'fields',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Find the applicable pre-chat form for a given inbox_id.
     * Prefers inbox-specific form; falls back to global (null inbox_id).
     */
    public static function findForInbox(?int $inboxId): ?self
    {
        if ($inboxId) {
            $form = static::active()->where('inbox_id', $inboxId)->first();
            if ($form) {
                return $form;
            }
        }

        return static::active()->whereNull('inbox_id')->first();
    }
}
