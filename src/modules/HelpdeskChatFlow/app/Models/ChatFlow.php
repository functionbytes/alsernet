<?php

namespace Modules\HelpdeskChatFlow\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
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
        'published_nodes',
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
            'published_nodes' => 'array',
            'published_at' => 'datetime',
        ];
    }

    const TRIGGER_TYPES = ['conversation_start', 'keyword', 'manual', 'no_agent'];

    const STATUSES = ['draft', 'active', 'archived'];

    const NODE_TYPES = ['start', 'message', 'quick_replies', 'collect_input', 'identify_customer', 'request_documents', 'branches', 'branchItem', 'action', 'delay', 'ai_response', 'ai_agent', 'order_lookup', 'http_request', 'rich_message', 'send_file', 'document_link', 'csat', 'business_hours', 'add_tag', 'set_attribute', 'go_to_step', 'transfer', 'close', 'end'];

    /**
     * Cache key for the cheap "any active flows?" gate used by the global
     * ConversationItem observer to skip per-message queries when no bot exists.
     */
    public const ACTIVE_FLOWS_CACHE_KEY = 'chatflow:has_active_flows';

    protected static function booted(): void
    {
        $forget = static fn () => Cache::forget(self::ACTIVE_FLOWS_CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * Whether at least one chat flow is currently active. Cached short-term so the
     * helpdesk-wide ConversationItem observer can bail out before running 2-3
     * queries on every inbound message when no chatbot is configured. The cache is
     * busted whenever any flow is saved/deleted, so activation takes effect at once.
     */
    public static function hasActiveFlowsCached(): bool
    {
        return Cache::remember(
            self::ACTIVE_FLOWS_CACHE_KEY,
            now()->addSeconds(60),
            static fn () => static::query()->where('status', 'active')->exists(),
        );
    }

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

    /**
     * Node tree the runtime engine must execute: always the last PUBLISHED
     * snapshot so editing a live flow's draft (`nodes`) never changes what
     * customers are running mid-conversation. Falls back to the draft only for
     * flows that predate the published snapshot (backfilled by migration), so
     * existing flows keep executing without interruption.
     *
     * @return array<int, array<string, mixed>>
     */
    public function runtimeNodes(): array
    {
        return $this->published_nodes ?? $this->nodes ?? [];
    }

    /**
     * Whether the working draft differs from the published snapshot — i.e. there
     * are edits not yet live for customers.
     */
    public function hasUnpublishedChanges(): bool
    {
        if ($this->published_nodes === null) {
            return false;
        }

        return $this->nodes !== $this->published_nodes;
    }

    public function getStartNode(): ?array
    {
        $nodes = collect($this->runtimeNodes());

        return $nodes->firstWhere('type', 'start')
            ?? $nodes->first(fn ($n) => ($n['parentId'] ?? null) === null);
    }

    public function getNodeById(string $nodeId): ?array
    {
        return collect($this->runtimeNodes())->firstWhere('id', $nodeId);
    }

    public function getChildren(string $parentId): array
    {
        return collect($this->runtimeNodes())
            ->filter(fn ($n) => ($n['parentId'] ?? null) === $parentId
                && ($n['type'] ?? '') !== 'branchItem')
            ->values()
            ->toArray();
    }

    public function getBranchItems(string $branchesNodeId): array
    {
        return collect($this->runtimeNodes())
            ->filter(fn ($n) => ($n['parentId'] ?? null) === $branchesNodeId
                && ($n['type'] ?? '') === 'branchItem')
            ->values()
            ->toArray();
    }
}
