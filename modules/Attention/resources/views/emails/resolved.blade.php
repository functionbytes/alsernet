@component('mail::message')
# Su PQRSF ha sido resuelto

Estimado/a **{{ $nombreCompleto }}**,

Nos complace informarle que su {{ $tipo }} con radicado **{{ $radicado }}** ha sido resuelto.

@component('mail::panel')
## Radicado: **{{ $radicado }}**
**Estado:** Resuelto
@endcomponent

## Detalles de su solicitud

**Tipo:** {{ $tipo }}
**Asunto:** {{ $asunto }}
**Fecha de radicación:** {{ $fechaRadicacion }}
**Fecha de resolución:** {{ $fechaResolucion }}
**Medio de respuesta:** {{ $tipoRespuesta }}

---

## Respuesta Oficial

{{ $resolucion }}

---

@component('mail::button', ['url' => $trackingUrl, 'color' => 'primary'])
Ver Detalles Completos
@endcomponent

## Su opinión es importante

Nos gustaría conocer su nivel de satisfacción con la atención recibida. Por favor, dedique un momento a calificar nuestro servicio:

@component('mail::button', ['url' => $surveyUrl, 'color' => 'success'])
Calificar Servicio
@endcomponent

Su retroalimentación nos ayuda a mejorar continuamente nuestros procesos de atención al ciudadano.

---

### Información adicional

Si considera que la respuesta no resuelve completamente su solicitud, puede:

- Presentar un recurso de reposición dentro de los 10 días hábiles siguientes
- Solicitar aclaraciones adicionales
- Radicar una nueva solicitud relacionada

Para cualquier gestión adicional, puede contactarnos a través de nuestros canales oficiales, siempre mencionando su número de radicado.

---

Agradecemos su comunicación y esperamos haber atendido satisfactoriamente su solicitud.

Atentamente,
{{ config('app.name') }}
@endcomponent
