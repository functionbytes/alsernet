<?php

namespace App\Jobs;

use App\Models\Subscriber\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AddSuscriberListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Subscriber $subscriber;
    protected array $listIds;

    public function __construct(Subscriber $subscriber, array $mailingLists)
    {

        $this->subscriber = $subscriber;
        $this->listIds = array_filter($mailingLists); // Asegurar que solo se almacenen valores válidos
    }

    public function handle(): void
    {
        if (empty($this->listIds)) {
            Log::warning("No hay listas para añadir al suscriptor ID {$this->subscriber->id}.");
            return;
        }

        try {
            $this->subscriber->addToLists($this->listIds);
            Log::info("Suscriptor ID {$this->subscriber->id} añadido correctamente a las listas: " . implode(', ', $this->listIds));
        } catch (\Exception $e) {
            Log::error("Error al añadir suscriptor ID {$this->subscriber->id} a las listas. Excepción: " . $e->getMessage(), [
                'subscriber_id' => $this->subscriber->id,
                'list_ids' => $this->listIds,
                'exception' => $e
            ]);
        }
    }
}
