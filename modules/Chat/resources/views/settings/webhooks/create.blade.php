@extends('layouts.theme')

@section('title', 'Nuevo webhook')

@section('content')

    @include('core::components.alerts')

    <div class="card w-100">

        <form id="formWebhook" action="{{ route('settings.chat.webhooks.store') }}" method="POST">

            @csrf

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear nuevo webhook</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Configura un nuevo webhook para recibir notificaciones de eventos en tiempo real.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre, URL y tipo de webhook.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Ej: Notificación principal">
                            <small class="form-text text-muted">Nombre descriptivo del webhook</small>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                URL
                                <span class="text-danger">*</span>
                            </label>
                            <input type="url" name="url" class="form-control @error('url') is-invalid @enderror" value="{{ old('url') }}" required placeholder="https://example.com/webhook">
                            <small class="form-text text-muted">URL donde se enviarán las notificaciones</small>
                            @error('url')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tipo de webhook
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2 select2 @error('webhook_type') is-invalid @enderror" id="webhook_type" name="webhook_type" required>
                                <option value="{{ $webhookTypes['account'] }}" {{ old('webhook_type', $webhookTypes['account']) == $webhookTypes['account'] ? 'selected' : '' }}>
                                    Nivel de cuenta (todos los buzones)
                                </option>
                                <option value="{{ $webhookTypes['inbox'] }}" {{ old('webhook_type') == $webhookTypes['inbox'] ? 'selected' : '' }}>
                                    Nivel de buzón (solo un buzón específico)
                                </option>
                            </select>
                            @error('webhook_type')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6" id="inbox-field" style="display: none;">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Buzón</label>
                            <select class="form-control select2 select2 @error('inbox_id') is-invalid @enderror" id="inbox_id" name="inbox_id">
                                <option value="">Seleccionar buzón...</option>
                                @foreach($inboxes as $inbox)
                                    <option value="{{ $inbox->id }}" {{ old('inbox_id') == $inbox->id ? 'selected' : '' }}>
                                        {{ $inbox->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('inbox_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Events -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Eventos suscritos</h6>
                        <p class="text-muted small mb-3">Selecciona qué eventos dispararán este webhook.</p>
                    </div>

                    @php
                        $events = [
                            'conversation_created' => 'Conversación creada',
                            'conversation_updated' => 'Conversación actualizada',
                            'conversation_status_changed' => 'Estado de conversación cambiado',
                            'message_created' => 'Mensaje creado',
                            'message_updated' => 'Mensaje actualizado',
                            'conversation_opened' => 'Conversación abierta',
                            'conversation_resolved' => 'Conversación resuelta',
                            'customer_created' => 'Cliente creado',
                            'customer_updated' => 'Cliente actualizado',
                        ];
                    @endphp

                    @foreach($events as $value => $label)
                        <div class="col-md-6 col-lg-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="subscriptions[]" id="event_{{ $value }}" value="{{ $value }}" {{ in_array($value, old('subscriptions', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="event_{{ $value }}">
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                    @endforeach

                    @error('subscriptions')
                        <div class="col-12">
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        </div>
                    @enderror

                    <div class="col-12">
                        <div class="alert bg-light border-0 mt-4 mb-0">
                            <h6 class="mb-1 fw-semibold">Información</h6>
                            <p class="mb-0 small">Los webhooks enviarán una solicitud POST a la URL configurada con los datos del evento en formato JSON. Asegúrese de que su endpoint pueda recibir y procesar estas solicitudes.</p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.webhooks.index') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        minimumResultsForSearch: Infinity
    });

    const webhookType = document.getElementById('webhook_type');
    const inboxField = document.getElementById('inbox-field');
    const inboxSelect = document.getElementById('inbox_id');

    function toggleInboxField() {
        if (webhookType.value == '{{ $webhookTypes["inbox"] }}') {
            inboxField.style.display = 'block';
            inboxSelect.required = true;
        } else {
            inboxField.style.display = 'none';
            inboxSelect.required = false;
            inboxSelect.value = '';
        }
    }

    webhookType.addEventListener('change', toggleInboxField);
    toggleInboxField();

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
