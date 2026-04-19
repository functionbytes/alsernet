<?php

namespace Modules\Page\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Services\FormPageCacheInvalidator;

/**
 * Listener opcional: invalida caché de Pages que embeben un formulario
 * cuando llega un submit (útil para contadores en tiempo real como
 * "N respuestas recibidas" visible en la Page).
 *
 * Se ejecuta async en la cola para no bloquear el submit.
 */
class InvalidatePagesOnFormSubmitted implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 2;

    public int $backoff = 10;

    public function handle(object $event): void
    {
        if (! isset($event->form)) {
            return;
        }

        if (! class_exists(FormPageCacheInvalidator::class)) {
            return;
        }

        app(FormPageCacheInvalidator::class)
            ->invalidateByIds($event->form->id, $event->form->slug);
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('InvalidatePagesOnFormSubmitted listener failed', [
            'form_id' => $event->form->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
