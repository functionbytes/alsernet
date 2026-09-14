@component('mail::message')
# Escalación requerida

Hola {{ $name }},

Un comentario de **{{ $comment->author_name }}** en {{ $comment->platform }} fue escalado y requiere atención humana.

@component('mail::panel')
{{ mb_substr($comment->body, 0, 240) }}
@endcomponent

@component('mail::button', ['url' => $url, 'color' => 'danger'])
Atender ahora
@endcomponent

Saludos,<br>
{{ config('app.name') }}
@endcomponent
