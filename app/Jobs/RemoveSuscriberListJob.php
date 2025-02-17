<?php

namespace App\Jobs;

use App\Models\Subscriber\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoveSuscriberListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?Subscriber $subscriber;
    protected array $listIds;
    protected bool $removeAll;


    public function __construct($subscriber, array $mailingLists = [], bool $removeAll = false)
    {
        $this->subscriber = $subscriber instanceof Subscriber ? $subscriber : Subscriber::find($subscriber);
        $this->listIds = array_filter($mailingLists);
        $this->removeAll = $removeAll;
    }

    public function handle(): void
    {
        if (!optional($this->subscriber)->exists) {
            Log::error("RemoveSuscriberListJob: No se encontró el suscriptor.");
            return;
        }

        if ($this->removeAll) {
            if (method_exists($this->subscriber, 'removeAllSubscriptions')) {
                $this->subscriber->removeAllSubscriptions();
                Log::info("RemoveSuscriberListJob: Suscriptor ID {$this->subscriber->id} eliminado de TODAS las listas.");
            }
            return;
        }

        if (!empty($this->listIds) && method_exists($this->subscriber, 'removeSpecificLists')) {
            $this->subscriber->removeSpecificLists($this->listIds);
            Log::debug("RemoveSuscriberListJob: Suscriptor ID {$this->subscriber->id} eliminado de listas específicas: " . implode(', ', $this->listIds));
        }
    }
}
