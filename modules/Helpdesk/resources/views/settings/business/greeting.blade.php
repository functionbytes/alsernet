@extends('layouts.theme')

@section('title', 'Bienvenida')

@section('page_header')
    @include('core::components.card', ['title' => 'Bienvenida'])
@endsection

@section('content')

    @include('core::components.alerts')

    @include('helpdesk::settings.business._auto-reply-section', [
        'sectionTitle' => 'Bienvenida',
        'sectionDescription' => 'Se responde automáticamente al primer mensaje de una conversación nueva mientras esté EN horario (mutuamente excluyente con el de fuera de horario). Un mensaje con idioma propio se envía tal cual; el genérico ("Automático") se traduce al vuelo al idioma detectado del cliente.',
        'items' => $items,
        'stats' => $stats,
        'idPrefix' => 'greet',
        'storeRoute' => route('settings.helpdesk.conversation-greetings.store'),
        'updateRouteName' => 'settings.helpdesk.conversation-greetings.update',
        'destroyRouteName' => 'settings.helpdesk.conversation-greetings.destroy',
        'bulkRouteName' => 'settings.helpdesk.conversation-greetings.bulk-action',
        'indexRouteName' => 'settings.helpdesk.business.greeting',
        'itemNoun' => 'mensaje',
        'deleteTitle' => 'Eliminar mensaje de bienvenida',
        'editTitle' => 'Editar mensaje de bienvenida',
        'errorBag' => 'greeting',
        'emptyMessage' => 'No hay mensajes configurados — mientras no haya ninguno activo, no se envía ninguna bienvenida automática.',
        'offHoursChannels' => $offHoursChannels,
        'offHoursLanguages' => $offHoursLanguages,
    ])

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
window.HdSettingsPageConfig = {
    flashSuccess: @json(session('success')),
    flashError: @json(session('error')),
};
</script>
<script>window.HdSettingsCommonSkipAutoInit = true;</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/settings-standard-bootstrap.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-standard-bootstrap.js')) }}" defer></script>
@endpush
