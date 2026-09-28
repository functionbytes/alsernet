<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use InvalidArgumentException;

/**
 * Node type → handler map, built from every service tagged with TAG. A type
 * claimed by two handlers is a wiring bug, so it fails loudly.
 */
class NodeHandlerRegistry
{
    public const TAG = 'helpdeskchatflow.node-handlers';

    /** @var array<string, NodeHandler> */
    private array $byType = [];

    /**
     * @param  iterable<NodeHandler>  $handlers
     */
    public function __construct(iterable $handlers)
    {
        foreach ($handlers as $handler) {
            foreach ($handler->types() as $type) {
                if (isset($this->byType[$type])) {
                    throw new InvalidArgumentException("ChatFlow node type [{$type}] is handled by both ".get_class($this->byType[$type]).' and '.get_class($handler).'.');
                }

                $this->byType[$type] = $handler;
            }
        }
    }

    public function for(string $type): ?NodeHandler
    {
        return $this->byType[$type] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function types(): array
    {
        return array_keys($this->byType);
    }
}
