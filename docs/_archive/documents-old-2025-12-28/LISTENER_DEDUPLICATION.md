# Prevención de Ejecución Duplicada de Listeners

## Problema
Algunos listeners se ejecutan múltiples veces para el mismo evento dentro del mismo request, causando que se creen múltiples jobs en la cola.

## Solución
Se proporciona el trait `PreventsDuplicateEventExecution` que deduplicación automáticamente las ejecuciones de listeners.

## Cómo Usar

### 1. Importar el Trait en tu Listener

```php
<?php

namespace App\Listeners;

use Modules\Documents\Traits\PreventsDuplicateEventExecution;

class MyListener
{
    use PreventsDuplicateEventExecution;
```

### 2. Agregar la Verificación al Inicio del Handle

```php
public function handle($event): void
{
    // Prevenir ejecución múltiple
    if ($this->preventDuplicateExecution($event)) {
        return; // Ya se ejecutó en este request, saltar
    }

    // Tu lógica aquí
    // Esto solo se ejecutará una vez por request
}
```

## Cómo Funciona

1. El trait genera un ID único basado en:
   - Nombre de la clase del listener
   - Nombre de la clase del evento
   - ID del modelo (si existe)

2. Almacena en memoria qué IDs de ejecución ya se procesaron

3. Si el mismo listener + evento + modelo intenta ejecutarse nuevamente, lo salta

## Ejemplo Completo

```php
<?php

namespace App\Listeners\Documents;

use App\Events\Document\DocumentUploaded;use App\Jobs\Documents\SendDocumentUploadedConfirmationJob;use Modules\Documents\Traits\PreventsDuplicateEventExecution;

class SendDocumentUploadConfirmation
{
    use PreventsDuplicateEventExecution;

    public function handle(DocumentUploaded $event): void
    {
        // Prevenir ejecución múltiple
        if ($this->preventDuplicateExecution($event)) {
            return;
        }

        $document = $event->document->fresh();

        if (!$document) {
            return;
        }

        // Tu lógica aquí
        SendDocumentUploadedConfirmationJob::dispatch($document);
    }
}
```

## Listeners que Deberían Usar Este Trait

Todos los listeners que:
- Despachan jobs a la cola
- Envían emails
- Realizan operaciones costosas
- Creen múltiples registros basados en eventos

## Listeners Existentes

Actualmente usando el trait:
- ✅ `SendDocumentUploadConfirmation` - Previene múltiples jobs de confirmación

Recomendado para futuros listeners:
- `SendDocumentUploadReminder`
- `SendDocumentUploadNotification`
- Cualquier otro listener que despache jobs

## Logging para Debugging

El trait registra automáticamente todos los eventos en los logs:

### Log de Primera Ejecución
```
[2025-12-16 17:51:52] local.INFO: Event listener execution - FIRST TIME {
  "listener": "App\\Listeners\\Documents\\SendDocumentUploadConfirmation",
  "event": "App\\Events\\Documents\\DocumentUploaded",
  "execution_id": "App\\Listeners\\Documents\\SendDocumentUploadConfirmation@App\\Events\\Documents\\DocumentUploaded:934",
  "request_path": "api/documents/upload",
  "request_method": "POST"
}
```

### Log de Ejecución Duplicada (Saltada)
```
[2025-12-16 17:51:52] local.WARNING: Event listener execution - DUPLICATE (SKIPPED) {
  "listener": "App\\Listeners\\Documents\\SendDocumentUploadConfirmation",
  "event": "App\\Events\\Documents\\DocumentUploaded",
  "execution_id": "App\\Listeners\\Documents\\SendDocumentUploadConfirmation@App\\Events\\Documents\\DocumentUploaded:934",
  "request_path": "api/documents/upload",
  "request_method": "POST"
}
```

## Verificar en Logs

Para ver exactamente dónde se ejecutan tus eventos:

```bash
tail -f storage/logs/laravel.log | grep "Event listener execution"
```

Esto te mostrará:
- ✅ Cuándo se ejecuta por primera vez (INFO)
- ⚠️ Cuándo se salta por ser duplicado (WARNING)
- El listener exacto que se ejecutó
- El evento que fue procesado
- El endpoint que lo disparó

## Notas Técnicas

- El trait usa `static` para almacenar el estado en memoria
- Solo funciona dentro del mismo request/proceso
- No requiere cambios en la BD
- Compatible con todos los tipos de eventos
- Registra automáticamente en los logs para debugging
