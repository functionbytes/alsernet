@extends('layouts.theme')

@section('title', 'Despedida')

@section('page_header')
    @include('core::components.card', ['title' => 'Despedida'])
@endsection

@section('content')

    @include('core::components.alerts')

    @include('helpdesk::settings.business._auto-reply-section', [
        'sectionTitle' => 'Despedida',
        'sectionDescription' => 'Se envía automáticamente al cerrar una conversación (cualquier canal), sin importar el horario. Un mensaje con idioma propio se envía tal cual; el genérico ("Automático") se traduce al vuelo al idioma detectado del cliente.',
        'items' => $items,
        'stats' => $stats,
        'idPrefix' => 'farewell',
        'storeRoute' => route('settings.helpdesk.conversation-farewells.store'),
        'updateRouteName' => 'settings.helpdesk.conversation-farewells.update',
        'destroyRouteName' => 'settings.helpdesk.conversation-farewells.destroy',
        'bulkRouteName' => 'settings.helpdesk.conversation-farewells.bulk-action',
        'indexRouteName' => 'settings.helpdesk.business.farewell',
        'itemNoun' => 'mensaje',
        'deleteTitle' => 'Eliminar mensaje de despedida',
        'editTitle' => 'Editar mensaje de despedida',
        'errorBag' => 'farewell',
        'emptyMessage' => 'No hay mensajes configurados — mientras no haya ninguno activo, no se envía ninguna despedida automática.',
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
