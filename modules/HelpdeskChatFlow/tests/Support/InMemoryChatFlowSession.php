<?php

namespace Modules\HelpdeskChatFlow\Tests\Support;

use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * In-memory ChatFlowSession double for engine unit tests: captures context writes
 * and status updates without a database connection. Proxies attribute access to a
 * raw bag so Mockery strict-mode never intercepts magic __get/__set/__isset.
 *
 * Usage: new InMemoryChatFlowSession($flow, ['current_node_id' => 'n1', ...]);
 */
class InMemoryChatFlowSession extends ChatFlowSession
{
    /** @var array<int, array<string, mixed>> */
    public array $updates = [];

    /** @var array<string, mixed> */
    private array $ctxStore = [];

    /**
     * @param  array<string, mixed>  $bag  Seed attributes (status, current_node_id, conversation, context, …)
     */
    public function __construct(
        private readonly ChatFlow $flow,
        private array $bag = [],
    ) {
        // Intentionally skip the Eloquent\Model constructor so no DB connection
        // is needed; the overrides below cover everything the engine touches.
    }

    public function isActive(): bool
    {
        return ($this->bag['status'] ?? 'active') === 'active';
    }

    public function getAttribute($key): mixed
    {
        return match ($key) {
            'chatFlow' => $this->flow,
            'current_node_id' => $this->bag['current_node_id'] ?? null,
            'status' => $this->bag['status'] ?? 'active',
            'conversation' => $this->bag['conversation'] ?? null,
            default => $this->bag[$key] ?? null,
        };
    }

    public function setContextValue(string $key, mixed $value): void
    {
        $this->ctxStore[$key] = $value;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setContextValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->ctxStore[$key] = $value;
        }
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->ctxStore[$key] ?? $this->bag['context'][$key] ?? $default;
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->updates[] = $attributes;

        if (isset($attributes['status'])) {
            $this->bag['status'] = $attributes['status'];
        }

        return true;
    }

    /** Expose captured context writes for assertions. */
    public function getContextStore(): array
    {
        return $this->ctxStore;
    }
}
