@extends('layouts.theme')

@section('title', 'Configuración de Email')

@section('content')
    @include('core::components.card', ['title' => 'Configuración de Notificaciones por Email'])

    <div class="widget-content">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Notificaciones por Email</h5>
                        <p class="small mb-0 text-muted">Configura cómo y cuándo se envían notificaciones por correo electrónico</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Section -->
            @if($attentionStats)
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-primary mb-2">Total de casos</h6>
                                    <h4 class="mb-0 fw-bold">{{ number_format($attentionStats['total_cases'] ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">En proceso</h6>
                                    <h4 class="mb-0 fw-bold">{{ number_format($attentionStats['cases_in_process'] ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-info mb-2">Resueltos</h6>
                                    <h4 class="mb-0 fw-bold">{{ number_format($attentionStats['cases_resolved'] ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-warning mb-2">Tasa de resolución</h6>
                                    <h4 class="mb-0 fw-bold">{{ $attentionStats['resolution_rate'] ?? 0 }}%</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Configuration Form -->
            <div class="card-body">
                <form action="{{ route('settings.attention.configurations.email.update') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <!-- Enable Email Notifications -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enable_email_notifications" name="enable_email_notifications"
                                {{ ($settings['email']['enable_email_notifications']['value'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_email_notifications">
                                <strong>Habilitar notificaciones por email</strong>
                                <small class="d-block text-muted">Activa o desactiva todas las notificaciones por correo</small>
                            </label>
                        </div>
                    </div>

                    <hr>

                    <!-- Notification Triggers -->
                    <h6 class="fw-bold mb-3">Cuándo enviar notificaciones</h6>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify_on_received" name="notify_on_received"
                                    {{ ($settings['email']['notify_on_received']['value'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_on_received">
                                    Notificar al recibir
                                </label>
                                <small class="d-block text-muted ms-4">Se envía cuando se recibe un nuevo caso</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify_on_in_process" name="notify_on_in_process"
                                    {{ ($settings['email']['notify_on_in_process']['value'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_on_in_process">
                                    Notificar al procesar
                                </label>
                                <small class="d-block text-muted ms-4">Se envía cuando el caso está en proceso</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify_on_resolved" name="notify_on_resolved"
                                    {{ ($settings['email']['notify_on_resolved']['value'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_on_resolved">
                                    Notificar al resolver
                                </label>
                                <small class="d-block text-muted ms-4">Se envía cuando el caso es resuelto</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify_on_closed" name="notify_on_closed"
                                    {{ ($settings['email']['notify_on_closed']['value'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_on_closed">
                                    Notificar al cerrar
                                </label>
                                <small class="d-block text-muted ms-4">Se envía cuando el caso es cerrado</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify_assigned_user" name="notify_assigned_user"
                                    {{ ($settings['email']['notify_assigned_user']['value'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_assigned_user">
                                    Notificar al usuario asignado
                                </label>
                                <small class="d-block text-muted ms-4">Se envía cuando se asigna un caso a un usuario</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Email Templates -->
                    <h6 class="fw-bold mb-3">Plantillas de Email</h6>

                    <div class="alert alert-info border-0 bg-info-subtle mb-3">
                        <small>Selecciona las plantillas de correo a usar para cada evento. Las plantillas deben estar configuradas en el módulo Mailer.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mail_template_received_id" class="form-label">
                                Plantilla: Caso Recibido
                                <small class="text-muted d-block">Email enviado cuando se recibe un nuevo caso</small>
                            </label>
                            <select class="form-control select2-search" id="mail_template_received_id" name="mail_template_received_id" data-url="{{ route('settings.attention.templates.search') }}">
                                @if(($settings['email']['mail_template_received_id']['value'] ?? null))
                                    <option value="{{ $settings['email']['mail_template_received_id']['value'] }}" selected>
                                        Plantilla ({{ $settings['email']['mail_template_received_id']['value'] }})
                                    </option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="mail_template_in_process_id" class="form-label">
                                Plantilla: En Proceso
                                <small class="text-muted d-block">Email enviado cuando el caso está siendo procesado</small>
                            </label>
                            <select class="form-control select2-search" id="mail_template_in_process_id" name="mail_template_in_process_id" data-url="{{ route('settings.attention.templates.search') }}">
                                @if(($settings['email']['mail_template_in_process_id']['value'] ?? null))
                                    <option value="{{ $settings['email']['mail_template_in_process_id']['value'] }}" selected>
                                        Plantilla ({{ $settings['email']['mail_template_in_process_id']['value'] }})
                                    </option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="mail_template_resolved_id" class="form-label">
                                Plantilla: Resuelto
                                <small class="text-muted d-block">Email enviado cuando el caso es resuelto</small>
                            </label>
                            <select class="form-control select2-search" id="mail_template_resolved_id" name="mail_template_resolved_id" data-url="{{ route('settings.attention.templates.search') }}">
                                @if(($settings['email']['mail_template_resolved_id']['value'] ?? null))
                                    <option value="{{ $settings['email']['mail_template_resolved_id']['value'] }}" selected>
                                        Plantilla ({{ $settings['email']['mail_template_resolved_id']['value'] }})
                                    </option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="mail_template_closed_id" class="form-label">
                                Plantilla: Cerrado
                                <small class="text-muted d-block">Email enviado cuando el caso es cerrado</small>
                            </label>
                            <select class="form-control select2-search" id="mail_template_closed_id" name="mail_template_closed_id" data-url="{{ route('settings.attention.templates.search') }}">
                                @if(($settings['email']['mail_template_closed_id']['value'] ?? null))
                                    <option value="{{ $settings['email']['mail_template_closed_id']['value'] }}" selected>
                                        Plantilla ({{ $settings['email']['mail_template_closed_id']['value'] }})
                                    </option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="mail_template_assigned_id" class="form-label">
                                Plantilla: Asignado
                                <small class="text-muted d-block">Email enviado cuando se asigna un caso</small>
                            </label>
                            <select class="form-control select2-search" id="mail_template_assigned_id" name="mail_template_assigned_id" data-url="{{ route('settings.attention.templates.search') }}">
                                @if(($settings['email']['mail_template_assigned_id']['value'] ?? null))
                                    <option value="{{ $settings['email']['mail_template_assigned_id']['value'] }}" selected>
                                        Plantilla ({{ $settings['email']['mail_template_assigned_id']['value'] }})
                                    </option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <hr>

                    <!-- Test Email Connection -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Prueba de conexión</h6>
                        <div class="input-group">
                            <input type="email" class="form-control" id="test_email" placeholder="Ingresa tu email para probar" value="{{ Auth::user()->email ?? '' }}">
                            <button type="button" class="btn btn-outline-primary" id="test-email-btn">
                                <i class="fas fa-envelope me-1"></i> Enviar correo de prueba
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">Se enviará un email de prueba para verificar que la configuración funciona correctamente</small>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2 d-sm-flex gap-2 justify-content-end">
                        <a href="{{ route('settings.attention.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for template search
    $('.select2-search').select2({
        allowClear: true,
        placeholder: 'Buscar plantilla...',
        minimumInputLength: 0,
        ajax: {
            url: function() { return $(this).data('url'); },
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.results
                };
            }
        }
    });

    // Test email connection
    $('#test-email-btn').on('click', function() {
        const btn = $(this);
        const email = $('#test_email').val();

        if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            toastr.error('Por favor ingresa un email válido');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');

        $.ajax({
            url: '{{ route("settings.attention.configurations.email.test") }}',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                test_email: email
            },
            success: function(response) {
                toastr.success(response.message);
                btn.prop('disabled', false).html('<i class="fas fa-envelope me-1"></i> Enviar correo de prueba');
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error al enviar el correo de prueba';
                toastr.error(message);
                btn.prop('disabled', false).html('<i class="fas fa-envelope me-1"></i> Enviar correo de prueba');
            }
        });
    });
});
</script>
@endpush
