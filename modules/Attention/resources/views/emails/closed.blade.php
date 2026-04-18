@component('mail::message')
# peticiones Cerrado

Estimado/a **{{ $nombreCompleto }}**,

Le informamos que su {{ $tipo }} con radicado **{{ $radicado }}** ha sido cerrado.

@component('mail::panel')
## Radicado: **{{ $radicado }}**
**Estado:** Cerrado
@endcomponent

## Resumen del caso

**Tipo:** {{ $tipo }}
**Asunto:** {{ $asunto }}
**Fecha de radicación:** {{ $fechaRadicacion }}
**Fecha de resolución:** {{ $fechaResolucion }}
**Fecha de cierre:** {{ $fechaCierre }}

---

@if($tieneCalificacion)
## Calificación recibida

Agradecemos que haya calificado nuestro servicio con **{{ $calificacion }} estrellas**.

Su opinión es muy valiosa y nos ayuda a mejorar continuamente.
@else
## Su opinión aún importa

Aunque el caso ha sido cerrado, aún puede compartir su experiencia con nosotros para ayudarnos a mejorar nuestros servicios.
@endif

---

## Información final

El proceso de atención de su solicitud ha finalizado. Todos los datos y documentos relacionados quedan archivados en nuestro sistema para consultas futuras.

@component('mail::button', ['url' => $trackingUrl, 'color' => 'primary'])
Ver Historial Completo
@endcomponent

---

### ¿Necesita ayuda adicional?

Si tiene una nueva consulta o solicitud relacionada con este tema, le invitamos a radicar un nuevo peticiones a través de nuestros canales oficiales:

- Portal web
- Presencial en nuestras oficinas
- Correo electrónico
- Línea telefónica
- WhatsApp

Al radicar una nueva solicitud, puede hacer referencia a este radicado anterior: **{{ $radicado }}**

---

Gracias por confiar en nosotros para la atención de sus solicitudes.

Atentamente,
{{ config('app.name') }}
@endcomponent
