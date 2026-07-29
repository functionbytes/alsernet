<?php

namespace Modules\Supplier\Models\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Content\ContentLogFactory;
use Modules\Supplier\Models\Ai\AiContent;

class ContentLog extends Model
{
    use HasFactory;

    protected $table = 'supplier_content_logs';

    public const UPDATED_AT = null;

    protected $fillable = [
        'content_id',
        'action',
        'previous_status',
        'new_status',
        'user_id',
        'details',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
            'content_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    protected static function newFactory(): ContentLogFactory
    {
        return ContentLogFactory::new();
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(AiContent::class, 'content_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeForContent($query, int $contentId)
    {
        return $query->where('content_id', $contentId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function isAutomated(): bool
    {
        return is_null($this->user_id);
    }

    public function wasPerformedBy(?int $userId): bool
    {
        return $this->user_id === $userId;
    }

    public function getStatusChangeDescription(): ?string
    {
        if ($this->previous_status && $this->new_status) {
            return "Changed from {$this->previous_status} to {$this->new_status}";
        }

        return null;
    }
}
