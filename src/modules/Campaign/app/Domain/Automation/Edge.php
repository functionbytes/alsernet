<?php

namespace Modules\Campaign\Domain\Automation;

use Modules\Campaign\Domain\Automation\Enum\Branch;

final class Edge
{
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly string $target,
        public readonly ?Branch $branch = null,
    ) {}

    public function withTarget(string $newTarget): self
    {
        return new self($this->id, $this->source, $newTarget, $this->branch);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'target' => $this->target,
            'branch' => $this->branch?->value,
        ];
    }

    public static function fromArray(array $a): self
    {
        if (! isset($a['id'], $a['source'], $a['target'])) {
            throw new \InvalidArgumentException('Edge array missing id/source/target');
        }
        $branch = isset($a['branch']) ? Branch::tryFrom($a['branch']) : null;

        return new self((string) $a['id'], (string) $a['source'], (string) $a['target'], $branch);
    }
}
