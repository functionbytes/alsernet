@extends('layouts.theme')

@section('title', 'Notificaciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Notificaciones'])
@endsection

@section('content')

@include('core::components.alerts')

<div class="card">
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Configuracion de notificaciones</h5>
                <p class="small mb-0 text-muted">Define que eventos generan notificaciones y por que canales se envian</p>
            </div>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('settings.helpdesk.notifications.update') }}">
            @csrf
            @method('PUT')

            {{-- Eventos a notificar --}}
            <h6 class="fw-semibold mb-1">Eventos a notificar</h6>
            <p class="text-muted small mb-3">Selecciona que eventos del sistema generaran notificaciones para los agentes.</p>
            <div class="row g-3 mb-4">
                @foreach([
                    'notify_new_conversation'       => 'Nueva conversacion recibida',
                    'notify_conversation_assigned'  => 'Conversacion asignada a un agente',
                    'notify_conversation_resolved'  => 'Conversacion marcada como resuelta',
                    'notify_new_message'            => 'Nuevo mensaje en conversacion activa',
                    'notify_overdue_sla'            => 'SLA vencido (tiempo de respuesta excedido)',
                ] as $field => $label)
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1"
                            {{ ($settings[$field] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                    </div>
                </div>
                @endforeach
            </div>

            <hr>

            {{-- Canales de notificacion --}}
            <h6 class="fw-semibold mb-1">Canales de notificacion</h6>
            <p class="text-muted small mb-3">Elige por que medios se entregaran las notificaciones a los agentes.</p>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="email_notifications_enabled"
                            name="email_notifications_enabled" value="1"
                            {{ ($settings['email_notifications_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="email_notifications_enabled">
                            <i class="fas fa-envelope me-1 text-muted"></i> Notificaciones por email
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="browser_notifications_enabled"
                            name="browser_notifications_enabled" value="1"
                            {{ ($settings['browser_notifications_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="browser_notifications_enabled">
                            <i class="fas fa-desktop me-1 text-muted"></i> Notificaciones del navegador
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notification_sound_enabled"
                            name="notification_sound_enabled" value="1"
                            {{ ($settings['notification_sound_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="notification_sound_enabled">
                            <i class="fas fa-volume-up me-1 text-muted"></i> Sonido de notificaciones
                        </label>
                    </div>
                </div>
            </div>

            <hr>

            {{-- Resumen diario --}}
            <h6 class="fw-semibold mb-1">Resumen diario</h6>
            <p class="text-muted small mb-3">Envia un resumen diario por email con la actividad del helpdesk.</p>
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="daily_digest_enabled"
                            name="daily_digest_enabled" value="1"
                            {{ ($settings['daily_digest_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="daily_digest_enabled">Activar resumen diario por email</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="daily_digest_time">Hora de envio</label>
                    <input type="time" class="form-control" id="daily_digest_time" name="daily_digest_time"
                        value="{{ old('daily_digest_time', $settings['daily_digest_time'] ?? '08:00') }}">
                    <small class="form-text text-muted">Hora local del servidor</small>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar configuracion
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
