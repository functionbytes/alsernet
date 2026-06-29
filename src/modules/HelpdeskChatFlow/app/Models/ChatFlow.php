<?php

namespace Modules\HelpdeskChatFlow\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskChatFlow\Database\Factories\ChatFlowFactory;

class ChatFlow extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_chat_flows';

    protected $fillable = [
        'uid',
        'name',
        'description',
        'inbox_id',
        'trigger_type',
        'trigger_conditions',
        'nodes',
        'status',
        'priority',
        'created_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'trigger_conditions' => 'array',
            'nodes' => 'array',
            'published_at' => 'datetime',
        ];
    }

    const TRIGGER_TYPES = ['conversation_start', 'keyword', 'manual', 'no_agent'];

    const STATUSES = ['draft', 'active', 'archived'];

    const NODE_TYPES = ['start', 'message', 'quick_replies', 'collect_input', 'identify_customer', 'request_documents', 'branches', 'branchItem', 'action', 'delay', 'ai_response', 'ai_agent', 'order_lookup', 'http_request', 'rich_message', 'send_file', 'document_link', 'csat', 'business_hours', 'add_tag', 'set_attribute', 'go_to_step', 'transfer', 'close', 'end'];

    // ==================== Factory ====================

    protected static function newFactory(): Factory
    {
        return ChatFlowFactory::new();
    }

    // ==================== Relationships ====================

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ChatFlowSession::class, 'chat_flow_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ChatFlowVersion::class, 'chat_flow_id')->latest();
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(ChatFlowTestCase::class, 'chat_flow_id');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForInbox($query, ?int $inboxId)
    {
        return $query->where(function ($q) use ($inboxId) {
            $q->whereNull('inbox_id')->orWhere('inbox_id', $inboxId);
        });
    }

    // ==================== Helpers ====================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function getStartNode(): ?array
    {
        $nodes = collect($this->nodes ?? []);

        return $nodes->firstWhere('type', 'start')
            ?? $nodes->first(fn ($n) => ($n['parentId'] ?? null) === null);
    }

    public function getNodeById(string $nodeId): ?array
    {
        return collect($this->nodes ?? [])->firstWhere('id', $nodeId);
    }

    public function getChildren(string $parentId): array
    {
        return collect($this->nodes ?? [])
            ->filter(fn ($n) => ($n['parentId'] ?? null) === $parentId
                && ($n['type'] ?? '') !== 'branchItem')
            ->values()
            ->toArray();
    }

    public function getBranchItems(string $branchesNodeId): array
    {
        return collect($this->nodes ?? [])
            ->filter(fn ($n) => ($n['parentId'] ?? null) === $branchesNodeId
                && ($n['type'] ?? '') === 'branchItem')
            ->values()
            ->toArray();
    }
}
