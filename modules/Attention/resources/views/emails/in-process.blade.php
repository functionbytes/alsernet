@component('mail::message')
# Su peticiones está en proceso

Estimado/a **{{ $nombreCompleto }}**,

Le informamos que su {{ $tipo }} con radicado **{{ $radicado }}** ha sido asignado y se encuentra en proceso de atención.

@component('mail::panel')
## Radicado: **{{ $radicado }}**
**Estado:** En Proceso
@endcomponent

## Detalles de su solicitud

**Tipo:** {{ $tipo }}
**Asunto:** {{ $asunto }}
**Fecha de radicación:** {{ $fechaRadicacion }}
**Departamento encargado:** {{ $departamento }}

## ¿Qué significa esto?

Su solicitud ha sido asignada a nuestro equipo de {{ $departamento }} y está siendo revisada. Pronto recibirá una respuesta.

@component('mail::button', ['url' => $trackingUrl, 'color' => 'primary'])
Ver Estado Actual
@endcomponent

---

### Seguimiento

Puede consultar el estado de su peticiones en cualquier momento usando el número de radicado. Le notificaremos cuando haya novedades o cuando su caso sea resuelto.

---

**Nota:** Este es un correo automático. Por favor no responda a este mensaje.

Atentamente,
{{ config('app.name') }}
@endcomponent
