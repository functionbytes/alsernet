<?php

namespace Modules\HelpdeskChatFlow\Tests\Support;

use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * In-memory session double that, unlike InMemoryChatFlowSession, follows
 * chat_flow_id / setRelation('chatFlow') changes and keeps a real context, so
 * procedure calls (flow switching + return stack) can be tested without a database.
 */
class FlowSwitchingChatFlowSession extends ChatFlowSession
{
    /** @var array<string, mixed> */
    private array $bag;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(private ChatFlow $flow, array $context = [])
    {
        $this->bag = [
            'id' => 1,
            'status' => 'active',
            'chat_flow_id' => $flow->id,
            'current_node_id' => null,
            'context' => $context,
        ];
    }

    public function isActive(): bool
    {
        return $this->bag['status'] === 'active';
    }

    public function getAttribute($key): mixed
    {
        return $key === 'chatFlow' ? $this->flow : ($this->bag[$key] ?? null);
    }

    public function setRelation($relation, $value): static
    {
        if ($relation === 'chatFlow') {
            $this->flow = $value;
        }

        return $this;
    }

    public function setContextValue(string $key, mixed $value): void
    {
        $this->bag['context'][$key] = $value;
    }

    public function setContextValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->bag['context'][$key] = $value;
        }
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->bag['context'][$key] ?? $default;
    }

    public function withBufferedContext(callable $callback): mixed
    {
        return $callback();
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->bag = array_merge($this->bag, $attributes);

        return true;
    }
}
