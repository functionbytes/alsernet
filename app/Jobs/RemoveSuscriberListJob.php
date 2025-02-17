<?php

namespace App\Jobs;

use App\Models\Subscriber\Subscriber;
use Dflydev\DotAccessData\Data;
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
        $this->listIds = array_filter($mailingLists); // Asegurar que solo se almacenen valores válidos
    }

    public function handle(): void
    {
        try {
            if ($this->subscriber->subcategorie->isEmpty() || $this->subscriber->parties) {
                // Eliminar todas las suscripciones si no tiene categorías o si `parties` está activo
                $this->subscriber->removeAllSubscriptions();
                Log::info("Suscriptor ID {$this->subscriber->id} eliminado de TODAS las listas de mailing debido a la ausencia de categorías o configuración de 'parties'.");
            } elseif (!empty($this->listIds)) {
                // Solo eliminar listas específicas si hay listas definidas
                $this->subscriber->removeSpecificLists($this->listIds);
                Log::info("Suscriptor ID {$this->subscriber->id} eliminado de listas específicas: " . implode(', ', $this->listIds));
            } else {
                Log::warning("No se eliminaron listas para el Suscriptor ID {$this->subscriber->id} porque no se proporcionaron listas válidas.");
            }
        } catch (\Exception $e) {
            Log::error("Error al eliminar Suscriptor ID {$this->subscriber->id} de las listas. Excepción: " . $e->getMessage(), [
                'subscriber_id' => $this->subscriber->id,
                'list_ids' => $this->listIds,
                'exception' => $e
            ]);
        }
    }
}
