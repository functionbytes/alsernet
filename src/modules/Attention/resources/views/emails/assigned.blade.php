@component('mail::message')
# Nuevo peticiones Asignado

Se le ha asignado un nuevo caso de atención al ciudadano para su gestión.

@component('mail::panel')
## Radicado: **{{ $radicado }}**
**Prioridad:** {{ $prioridad }}
@endcomponent

## Información del caso

**Tipo:** {{ $tipo }}
**Categoría:** {{ $categoria }}
**Ciudadano:** {{ $ciudadano }}
**Fecha de radicación:** {{ $fechaRadicacion }}

### Asunto
{{ $asunto }}

### Descripción
{{ \Illuminate\Support\Str::limit($descripcion, 300) }}

@if($asignadoA)
---
**Asignado a:** {{ $asignadoA }}
@endif

## Acciones requeridas

Por favor revise el caso y proceda con la gestión correspondiente. Es importante mantener actualizado el estado del peticiones para que el ciudadano pueda hacer seguimiento.

@component('mail::button', ['url' => $manageUrl, 'color' => 'success'])
Gestionar peticiones
@endcomponent

---

### Recordatorio de tiempos de respuesta

- **Petición:** 15 días hábiles
- **Queja/Reclamo:** 15 días hábiles
- **Sugerencia:** 15 días hábiles

Asegúrese de dar respuesta dentro de los términos legales establecidos.

---

Saludos,
Sistema de Gestión peticiones
{{ config('app.name') }}
@endcomponent
