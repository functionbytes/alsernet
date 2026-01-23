<?php

namespace App\Traits;

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
