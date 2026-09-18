{{--
    Panel de administración de las 4 páginas de "Business" (horarios, fuera
    de horario, bienvenida, despedida): toggles para activar/desactivar cada
    función sin perder la configuración de mensajes ya guardada. Cada
    descripción deja explícito si esa función envía un mensaje automático o
    no. Requiere $businessFeatures (inyectado por
    BusinessFeaturesController@index).
--}}
@php
$businessFeaturesFields = [
    'business_hours_enabled' => [
        'label' => 'Horarios de atención',
        'desc' => 'No envía ningún mensaje por sí sola: define los días y horas en que el negocio está "abierto". Si se desactiva, el sistema se considera siempre abierto y nunca se disparará la respuesta de fuera de horario.',
    ],
    'off_hours_enabled' => [
        'label' => 'Fuera de horario',
        'desc' => 'Sí envía un mensaje automático: el primer mensaje configurado, cuando llega una conversación nueva y el negocio está cerrado según el horario.',
    ],
    'greeting_enabled' => [
        'label' => 'Bienvenida',
        'desc' => 'Sí envía un mensaje automático: el primer mensaje configurado, cuando llega una conversación nueva durante el horario de atención.',
    ],
    'farewell_enabled' => [
        'label' => 'Despedida',
        'desc' => 'Sí envía un mensaje automático: el mensaje configurado, cuando se cierra una conversación.',
    ],
];
@endphp

<div class="card mb-4">
    <div class="card-header p-4 border-bottom border-light">
        <h5 class="mb-1 fw-bold">Panel de administración</h5>
        <p class="small mb-0 text-muted">Activa o desactiva cada función del panel de horarios sin perder la configuración de mensajes ya guardada.</p>
    </div>

    @if(($translationHealthy ?? true) === false)
        <div class="alert alert-warning mb-0 rounded-0 border-0" role="alert">
            La traducción automática de los mensajes "Automático" no está disponible ahora mismo. Mientras dure, esos mensajes se envían tal cual en el idioma base en vez de traducidos.
        </div>
    @endif

    <div class="card-body">
        <form method="POST" action="{{ route('settings.helpdesk.business.features.update') }}">
            @csrf

            @foreach($businessFeaturesFields as $field => $meta)
                <div class="d-flex justify-content-between align-items-start gap-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <label for="{{ $field }}" class="form-label fw-semibold mb-1">{{ $meta['label'] }}</label>
                        <p class="small text-muted mb-0">{{ $meta['desc'] }}</p>
                    </div>
                    <div class="flex-shrink-0 pt-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input @error($field) is-invalid @enderror" type="checkbox"
                                   id="{{ $field }}" name="{{ $field }}" value="1"
                                   {{ $businessFeatures[$field] ? 'checked' : '' }}>
                        </div>
                        @error($field)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach

            <div class="mt-3">
                <button type="submit" class="btn btn-primary w-100 mb-2">Guardar cambios</button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary w-100">Volver</a>
            </div>
        </form>
    </div>
</div>
