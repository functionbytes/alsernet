@component('mail::message')
# ⚠️ ALERTA DE INCUMPLIMIENTO SLA

Se ha detectado un incumplimiento de los plazos legales establecidos en la Ley 1437/2011 (CPACA) para el siguiente peticiones:

@component('mail::panel')
## Radicado: **{{ $radicado }}**
**URGENCIA:** <span style="color: red;">{{ $urgency }}</span>
@endcomponent

## Información del peticiones

**Tipo:** {{ $tipo }}
**Categoría:** {{ $categoria }}
**Ciudadano:** {{ $ciudadano }}
**Asunto:** {{ $asunto }}
**Fecha de radicación:** {{ $fechaRadicacion }}
**Días transcurridos:** **{{ $diasTranscurridos }} días**

---

## Detalles del Incumplimiento

**Tipo de breach:** {{ $breachType }}
**Incumplido desde:** {{ $breachedAt }}
**Nivel de escalamiento:** {{ $escalationLevel }}

---

## ⚠️ IMPLICACIONES LEGALES

Según la **Ley 1437 de 2011 (Art. 86)**, el incumplimiento de los términos para dar respuesta puede generar:

- **Silencio administrativo positivo** (en algunos casos)
- **Acciones de tutela** por parte del ciudadano
- **Sanciones disciplinarias** a los responsables
- **Procesos de responsabilidad fiscal**

@component('mail::button', ['url' => $manageUrl, 'color' => 'error'])
🚨 GESTIONAR INMEDIATAMENTE
@endcomponent

---

## Acciones Requeridas

1. Revisar el caso **INMEDIATAMENTE**
2. Dar respuesta definitiva al ciudadano
3. Documentar las razones del retraso
4. Implementar acciones correctivas

---

**IMPORTANTE:** Este es un correo automático del Sistema de Gestión peticiones. El incumplimiento de plazos puede generar consecuencias legales graves.

Sistema de Gestión peticiones
{{ config('app.name') }}
@endcomponent
