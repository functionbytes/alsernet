@component('mail::message')
# Nuevo comentario en {{ $comment->platform }}

Hola {{ $name }},

**{{ $comment->author_name }}** dejó un comentario en {{ $comment->platform }}.

@component('mail::panel')
{{ mb_substr($comment->body, 0, 240) }}
@endcomponent

**Urgencia:** {{ strtoupper($comment->urgency ?? 'low') }}

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Ver en bandeja social
@endcomponent

Saludos,<br>
{{ config('app.name') }}
@endcomponent
