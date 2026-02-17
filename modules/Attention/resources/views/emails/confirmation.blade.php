@component('mail::message')
# PQRSF Recibido Exitosamente

Estimado ciudadano,

Hemos recibido su solicitud y se ha generado el siguiente número de radicado para el seguimiento:

@component('mail::panel')
## Radicado: **{{ $radicado }}**
@endcomponent

## Detalles de su solicitud

**Tipo:** {{ $type }}
**Asunto:** {{ $subject }}
**Fecha de radicación:** {{ $created_at }}

## ¿Cómo hacer seguimiento?

Puede consultar el estado de su PQRSF en cualquier momento utilizando el número de radicado:

@component('mail::button', ['url' => $tracking_url, 'color' => 'primary'])
Consultar Estado
@endcomponent

También puede hacer seguimiento enviando el número de radicado por WhatsApp o llamando a nuestras líneas de atención.

---

### Tiempos de respuesta

Le informamos que su solicitud será atendida en los siguientes tiempos máximos según la normativa vigente:

- **Petición:** 15 días hábiles
- **Queja/Reclamo:** 15 días hábiles
- **Sugerencia:** 15 días hábiles
- **Felicitación:** Acuse de recibo inmediato

Recibirá notificaciones por correo electrónico sobre los cambios en el estado de su PQRSF.

---

**Nota:** Este es un correo automático, por favor no responda a este mensaje. Si tiene alguna consulta, utilice el número de radicado para hacer seguimiento a través de nuestros canales oficiales.

Gracias por su comunicación,
{{ config('app.name') }}
@endcomponent
