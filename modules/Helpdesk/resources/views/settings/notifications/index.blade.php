@extends('layouts.theme')

@section('title', 'Notificaciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Notificaciones'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.notifications.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Configuración de notificaciones</h5>
                        <small class="text-muted">Define qué eventos generan notificaciones y por qué canales se envían</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Eventos a notificar --}}
                        <h6 class="fw-semibold mb-1">Eventos a notificar</h6>
                        <p class="text-muted small mb-3">Selecciona qué eventos del sistema generarán notificaciones para los agentes.</p>
                        <div class="row g-3 mb-4">

                            @include('helpdesk::settings.notifications._toggle', [
                                'field' => 'notify_new_conversation',
                                'label' => 'Nueva conversación recibida',
                                'default' => true,
                            ])

                            @include('helpdesk::settings.notifications._toggle', [
                                'field' => 'notify_conversation_assigned',
                                'label' => 'Conversación asignada a un agente',
                                'default' => true,
                            ])

                            @include('helpdesk::settings.notifications._toggle', [
                                'field' => 'notify_conversation_resolved',
                                'label' => 'Conversación marcada como resuelta',
                                'default' => true,
                            ])

                            @include('helpdesk::settings.notifications._toggle', [
                                'field' => 'notify_new_message',
                                'label' => 'Nuevo mensaje en conversación activa',
                                'default' => true,
                            ])

                            @include('helpdesk::settings.notifications._toggle', [
                                'field' => 'notify_overdue_sla',
                                'label' => 'SLA vencido (tiempo de respuesta excedido)',
                                'default' => true,
                            ])

                        </div>

                        {{-- Canales de notificación --}}
                        <h6 class="fw-semibold mb-1">Canales de notificación</h6>
                        <p class="text-muted small mb-3">Elige por qué medios se entregarán las notificaciones a los agentes.</p>
                        <div class="row g-3 mb-4">

                            @include('helpdesk::settings.notifications._toggle', [
                                'field' => 'email_notifications_enabled',
                                'label' => 'Notificaciones por email',
                                'default' => true,
                            ])

                            @include('helpdesk::settings.notifications._toggle', [
                                'field' => 'browser_notifications_enabled',
                                'label' => 'Notificaciones del navegador',
                                'default' => false,
                            ])

                            @include('helpdesk::settings.notifications._toggle', [
                                'field' => 'notification_sound_enabled',
                                'label' => 'Sonido de notificaciones',
                                'default' => true,
                            ])

                        </div>

                        {{-- Resumen diario --}}
                        <h6 class="fw-semibold mb-1">Resumen diario</h6>
                        <p class="text-muted small mb-3">Envía un resumen diario por email con la actividad del helpdesk.</p>
                        <div class="row g-3">

                            @include('helpdesk::settings.notifications._toggle', [
                                'field' => 'daily_digest_enabled',
                                'label' => 'Activar resumen diario por email',
                                'default' => false,
                            ])

                            <div class="col-12 col-md-6">
                                <label class="form-label">Hora de envío</label>
                                <input type="time" name="daily_digest_time"
                                       class="form-control @error('daily_digest_time') is-invalid @enderror"
                                       value="{{ old('daily_digest_time', $settings['daily_digest_time'] ?? '08:00') }}">
                                <small class="form-text text-muted">Hora local del servidor</small>
                                @error('daily_digest_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar configuración</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre esta configuración</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Estas opciones controlan qué eventos disparan notificaciones a los agentes y por qué
                        canales se entregan, además del resumen diario por email.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas prácticas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Deja activo el SLA vencido para no perder tiempos de respuesta comprometidos</li>
                        <li class="mb-2">Demasiadas notificaciones por email pueden diluir las urgentes</li>
                        <li class="mb-0">El resumen diario es útil como respaldo cuando otras notificaciones están desactivadas</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
});
</script>
@endpush
