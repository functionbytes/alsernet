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

    protected Subscriber $subscriber;
    protected array $listIds;

    public function __construct(Subscriber $subscriber, array $mailingLists)
    {
        $this->subscriber = $subscriber;
        $this->listIds = $mailingLists;
    }

    public function handle(): void
    {
        try {
            if ($this->subscriber->categories->isEmpty() || $this->subscriber->parties) {
                $this->subscriber->removeAllSubscriptions();
                Log::info("Suscriptor ID {$this->subscriber->id} eliminado de TODAS las listas de mailing.");
            } else {
                $this->subscriber->removeSpecificLists($this->listIds);
                Log::info("Suscriptor ID {$this->subscriber->id} eliminado de listas: " . implode(', ', $this->listIds));
            }
        } catch (\Exception $e) {
            Log::error("Error al eliminar Suscriptor ID {$this->subscriber->id} de las listas: {$e->getMessage()}");
        }
    }
}
