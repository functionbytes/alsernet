<?php

namespace Modules\Document\Traits;

/**
 * Trait PreventsDuplicateEventExecution
 *
 * Previene que un listener se ejecute múltiples veces para el mismo evento
 * dentro del mismo request.
 *
 * Uso en listeners:
 *
 *   class MyListener
 *   {
 *       use PreventsDuplicateEventExecution;
 *
 *       public function handle($event): void
 *       {
 *           // Este método se ejecutará solo una vez por request
 *           if ($this->preventDuplicateExecution($event)) {
 *               return;
 *           }
 *
 *           // Tu lógica aquí
 *       }
 *   }
 */
trait PreventsDuplicateEventExecution
{
    /**
     * Prevenir ejecución duplicada de listeners
     *
     * @param  object  $event  El evento siendo procesado
     * @return bool true si ya se ejecutó, false si es primera vez
     */
    protected function preventDuplicateExecution(object $event): bool
    {
        // Generar un ID único para este listener + evento
        $executionId = $this->getExecutionId($event);

        // Verificar en memoria si ya se ejecutó en este request
        static $executed = [];

        // Logging para interceptar dónde se ejecutan los eventos
        $isFirstExecution = ! isset($executed[$executionId]);

        if ($isFirstExecution) {
            \Log::info('Event listener execution - FIRST TIME', [
                'listener' => static::class,
                'event' => $event::class,
                'execution_id' => $executionId,
                'request_path' => request()->path() ?? 'N/A',
                'request_method' => request()->method() ?? 'N/A',
            ]);
        } else {
            \Log::warning('Event listener execution - DUPLICATE (SKIPPED)', [
                'listener' => static::class,
                'event' => $event::class,
                'execution_id' => $executionId,
                'request_path' => request()->path() ?? 'N/A',
                'request_method' => request()->method() ?? 'N/A',
            ]);
        }

        if (isset($executed[$executionId])) {
            return true; // Ya se ejecutó, saltar
        }

        // Marcar como ejecutado
        $executed[$executionId] = true;

        return false; // Primera vez, continuar
    }

    /**
     * Generar ID único para rastrear ejecuciones
     */
    private function getExecutionId(object $event): string
    {
        $listenerClass = static::class;
        $eventClass = $event::class;

        // Intentar obtener ID del modelo si tiene one
        $modelId = '';
        if (method_exists($event, 'getModel')) {
            $model = $event->getModel();
            if ($model && method_exists($model, 'getKey')) {
                $modelId = ':'.$model->getKey();
            }
        } elseif (isset($event->model) && method_exists($event->model, 'getKey')) {
            $modelId = ':'.$event->model->getKey();
        } elseif (isset($event->document) && method_exists($event->document, 'getKey')) {
            $modelId = ':'.$event->document->getKey();
        }

        return "{$listenerClass}@{$eventClass}{$modelId}";
    }
}
