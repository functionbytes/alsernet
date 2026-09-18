@extends('layouts.theme')

@section('title', 'Fuera de horario')

@section('page_header')
    @include('core::components.card', ['title' => 'Fuera de horario'])
@endsection

@section('content')

    @include('core::components.alerts')

    @include('helpdesk::settings.business._auto-reply-section', [
        'sectionTitle' => 'Fuera de horario',
        'sectionDescription' => 'Se responde automáticamente al primer mensaje de una conversación nueva mientras esté FUERA de horario. Un mensaje con idioma propio se envía tal cual; el genérico ("Automático") se traduce al vuelo al idioma detectado del cliente.',
        'items' => $items,
        'stats' => $stats,
        'idPrefix' => 'ohr',
        'storeRoute' => route('settings.helpdesk.off-hours-responses.store'),
        'updateRouteName' => 'settings.helpdesk.off-hours-responses.update',
        'destroyRouteName' => 'settings.helpdesk.off-hours-responses.destroy',
        'bulkRouteName' => 'settings.helpdesk.off-hours-responses.bulk-action',
        'indexRouteName' => 'settings.helpdesk.business.off-hours',
        'itemNoun' => 'mensaje',
        'deleteTitle' => 'Eliminar mensaje fuera de horario',
        'editTitle' => 'Editar mensaje fuera de horario',
        'errorBag' => 'offHours',
        'emptyMessage' => 'No hay mensajes configurados — mientras no haya ninguno activo, no se envía ninguna respuesta automática fuera de horario.',
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
