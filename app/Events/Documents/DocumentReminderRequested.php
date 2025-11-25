<?php

namespace App\Events\Documents;

use App\Models\Order\Document;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentReminderRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Document $document)
    {
    }
}
