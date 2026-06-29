<?php

namespace Modules\HelpdeskChatFlow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Helpdesk\Models\Conversation;

class ChatFlowSession extends Model
{
    use HasFactory;

    /**
     * Nesting depth of open context buffers. While > 0, context mutations are
     * kept in-memory (on the cast `context` attribute) and the single
     * persisting UPDATE is deferred until the outermost flushContext(). A whole
     * engine run does ~11 context writes; buffering collapses them into one
     * UPDATE while keeping reads correct. Re-entrant so nested wraps are safe.
     */
    private int $contextBufferDepth = 0;

    /** Whether buffered context changes are still pending a DB write. */
    protected bool $contextDirty = false;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_chat_flow_sessions';

    protected $fillable = [
        'chat_flow_id',
        'conversation_id',
        'current_node_id',
        'status',
        'context',
        'trigger_type',
        'started_at',
        'ended_at',
        'last_customer_reply_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_customer_reply_at' => 'datetime',
        ];
    }

    // ==================== Relationships ====================

    public function chatFlow(): BelongsTo
    {
        return $this->belongsTo(ChatFlow::class, 'chat_flow_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ChatFlowExecution::class, 'session_id');
    }

    // ==================== Helpers ====================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function setContextValue(string $key, mixed $value): void
    {
        $this->setContextValues([$key => $value]);
    }

    /**
     * Sets multiple context keys in a single UPDATE, avoiding an N+1 write per
     * key when a node mutates several values (e.g. customer identification).
     *
     * While a context buffer is open (bufferContext()), the new values are
     * applied to the in-memory `context` so reads stay correct, but the DB
     * UPDATE is deferred to flushContext() — collapsing a run's many writes
     * into one.
     *
     * @param  array<string, mixed>  $values
     */
    public function setContextValues(array $values): void
    {
        if ($values === []) {
            return;
        }

        $context = $this->context ?? [];

        foreach ($values as $key => $value) {
            $context[$key] = $value;
        }

        if ($this->contextBufferDepth > 0) {
            $this->context = $context;
            $this->contextDirty = true;

            return;
        }

        $this->update(['context' => $context]);
    }

    /**
     * Run $callback with context writes buffered, flushing the single UPDATE
     * afterwards (even if the callback throws), so a whole engine run persists
     * its context once. Re-entrant: only the outermost call flushes.
     */
    public function withBufferedContext(callable $callback): mixed
    {
        $this->contextBufferDepth++;

        try {
            return $callback();
        } finally {
            $this->contextBufferDepth--;

            if ($this->contextBufferDepth === 0 && $this->contextDirty) {
                $this->contextDirty = false;
                $this->update(['context' => $this->context ?? []]);
            }
        }
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return ($this->context ?? [])[$key] ?? $default;
    }

    /**
     * Piggyback any pending buffered context onto unrelated column updates
     * (current_node_id, status, …) so every persisted row — and any listener
     * that re-reads the session — sees the up-to-date context, without adding
     * an extra write. The buffer's own flush UPDATE still passes `context`
     * directly and is exempt.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        if ($this->contextDirty && ! array_key_exists('context', $attributes)) {
            $attributes['context'] = $this->context ?? [];
            $this->contextDirty = false;
        }

        return parent::update($attributes, $options);
    }

    /**
     * Trigger/behaviour conditions configured on the parent flow.
     *
     * @return array<string, mixed>
     */
    public function flowConditions(): array
    {
        return $this->chatFlow?->trigger_conditions ?? [];
    }
}
