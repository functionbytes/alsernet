@component('mail::message')
# Alerta de salud: cuentas sociales

Hola {{ $name }},

Se detectaron los siguientes problemas en las cuentas sociales:

@foreach (array_slice($issues, 0, 5) as $issue)
- {{ $issue }}
@endforeach

@if (count($issues) > 5)
... y {{ count($issues) - 5 }} más.
@endif

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Ver cuentas
@endcomponent

Saludos,<br>
{{ config('app.name') }}
@endcomponent
