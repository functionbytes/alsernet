<?php

namespace Modules\Campaign\Domain\Automation;

use Modules\Campaign\Domain\Automation\Enum\NodeType;

/**
 * Immutable value object for a flow node.
 *
 * `data` is a free-form associative array whose schema is type-specific.
 * Per-type schema validation lives in the HTTP FormRequests, not here —
 * domain layer only enforces graph-shape invariants.
 */
final class Node
{
    public function __construct(
        public readonly string $id,
        public readonly NodeType $type,
        public readonly array $data = [],
    ) {}

    public function with(array $patch): self
    {
        return new self($this->id, $this->type, array_replace($this->data, $patch));
    }

    public function withTitle(string $title): self
    {
        return $this->with(['title' => $title]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'data' => $this->data,
        ];
    }

    public static function fromArray(array $a): self
    {
        if (! isset($a['id'], $a['type'])) {
            throw new \InvalidArgumentException('Node array missing id/type');
        }
        $type = NodeType::tryFrom($a['type']);
        if ($type === null) {
            throw new \InvalidArgumentException("Unknown node type: {$a['type']}");
        }

        return new self((string) $a['id'], $type, $a['data'] ?? []);
    }
}
