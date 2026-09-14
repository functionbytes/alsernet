<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;

class AiAgentSession extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_sessions';

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'ai_agent_id',
        'conversation_id',
        'customer_id',
        'flow_id',
        'status',
        'current_node_id',
        'context',
        'metadata',
        'started_at',
        'ended_at',
    ];

    // ==================== Relationships ====================

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiAgentSessionMessage::class, 'session_id');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== Accessors ====================

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                'active' => 'Activo',
                'completed' => 'Completado',
                'failed' => 'Falló',
                'paused' => 'Pausado',
                default => $this->status,
            },
        );
    }

    protected function duration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $start = $this->started_at ?? $this->created_at;
                $end = $this->ended_at ?? now();

                return $end->diffInSeconds($start);
            },
        );
    }

    // ==================== Methods ====================

    public function complete(): static
    {
        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return $this;
    }

    public function fail(?string $error = null): static
    {
        $this->update([
            'status' => 'failed',
            'ended_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], ['error' => $error]),
        ]);

        return $this;
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    public function setContextValue(string $key, mixed $value): static
    {
        $context = $this->context ?? [];
        $context[$key] = $value;
        $this->update(['context' => $context]);

        return $this;
    }
}
