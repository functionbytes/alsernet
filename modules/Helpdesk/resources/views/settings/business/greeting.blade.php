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
        'idPrefix' => 'greet',
        'storeRoute' => route('settings.helpdesk.conversation-greetings.store'),
        'updateRouteName' => 'settings.helpdesk.conversation-greetings.update',
        'destroyRouteName' => 'settings.helpdesk.conversation-greetings.destroy',
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
