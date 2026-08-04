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
        'idPrefix' => 'ohr',
        'storeRoute' => route('settings.helpdesk.off-hours-responses.store'),
        'updateRouteName' => 'settings.helpdesk.off-hours-responses.update',
        'destroyRouteName' => 'settings.helpdesk.off-hours-responses.destroy',
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
$(document).ready(function () {
    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
