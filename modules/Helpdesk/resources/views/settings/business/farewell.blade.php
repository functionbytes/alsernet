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
        'idPrefix' => 'farewell',
        'storeRoute' => route('settings.helpdesk.conversation-farewells.store'),
        'updateRouteName' => 'settings.helpdesk.conversation-farewells.update',
        'destroyRouteName' => 'settings.helpdesk.conversation-farewells.destroy',
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
