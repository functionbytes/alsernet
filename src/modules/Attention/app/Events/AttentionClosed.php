<?php

namespace Modules\Attention\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Attention\Models\Attention;

/**
 * Event fired when an Attention is closed
 */
class AttentionClosed
{
    use Dispatchable, SerializesModels;

    public Attention $attention;

    public ?int $closedBy;

    public ?string $closeReason;

    /**
     * Create a new event instance.
     */
    public function __construct(Attention $attention, ?int $closedBy = null, ?string $closeReason = null)
    {
        $this->attention = $attention;
        $this->closedBy = $closedBy;
        $this->closeReason = $closeReason;
    }
}
