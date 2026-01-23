@extends('layouts.theme')


@section('title', 'Configuración de notificaciones')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary">
                    <h4 class="mb-0 text-white">
                        <i class="fa fa-cog me-2"></i>Configuración de notificaciones
                    </h4>
                </div>
                <div class="card-body">
                    <form id="notification-settings-form">
                        @csrf

                        <!-- Limpieza automática -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold">
                                    <i class="fa fa-broom me-2"></i>Limpieza automática
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Estado de limpieza automática</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="cleanup_enabled" name="cleanup_enabled" value="1" {{ $config['cleanup']['enabled'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="cleanup_enabled">
                                                Activar limpieza automática de notificaciones antiguas
                                            </label>
                                        </div>
                                        <small class="text-muted">Elimina automáticamente notificaciones antiguas según el período configurado</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="cleanup_days" class="form-label">Días para limpieza</label>
                                        <input type="number" class="form-control" id="cleanup_days" name="cleanup_days" value="{{ $config['cleanup']['days'] }}" min="1" max="365" required>
                                        <small class="text-muted">Notificaciones más antiguas que este número de días serán eliminadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notificaciones push -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold">
                                    <i class="fa fa-mobile-alt me-2"></i>Notificaciones push
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Estado de notificaciones push</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="push_enabled" name="push_enabled" value="1" {{ $config['push_notifications']['enabled'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="push_enabled">
                                                Activar notificaciones push
                                            </label>
                                        </div>
                                        <small class="text-muted">Permite enviar notificaciones push a dispositivos móviles</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="push_max_retries" class="form-label">Reintentos máximos</label>
                                        <input type="number" class="form-control" id="push_max_retries" name="push_max_retries" value="{{ $config['push_notifications']['max_retries'] }}" min="1" max="10" required>
                                        <small class="text-muted">Número de veces que se reintentará enviar una notificación push fallida</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Canales de notificación -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold">
                                    <i class="fa fa-share-alt me-2"></i>Canales de notificación
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="channel_database" name="channel_database" value="1" {{ $config['channels']['database'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_database">
                                                <i class="fa fa-database me-2"></i>Base de datos
                                            </label>
                                        </div>
                                        <small class="text-muted">Almacena notificaciones en la base de datos</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="channel_mail" name="channel_mail" value="1" {{ $config['channels']['mail'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_mail">
                                                <i class="fa fa-envelope me-2"></i>Correo electrónico
                                            </label>
                                        </div>
                                        <small class="text-muted">Envía notificaciones por correo electrónico</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="channel_push" name="channel_push" value="1" {{ $config['channels']['push'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="channel_push">
                                                <i class="fa fa-mobile-alt me-2"></i>Push
                                            </label>
                                        </div>
                                        <small class="text-muted">Envía notificaciones push a dispositivos móviles</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Retención de datos -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold">
                                    <i class="fa fa-clock me-2"></i>Retención de datos
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="retention_days" class="form-label">Días de retención</label>
                                        <input type="number" class="form-control" id="retention_days" name="retention_days" value="{{ $config['retention_days'] }}" min="1" max="365" required>
                                        <small class="text-muted">Período de tiempo que se conservarán las notificaciones antes de ser archivadas o eliminadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" onclick="window.location.reload()">
                                <i class="fa fa-undo me-2"></i>Restablecer
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save me-2"></i>Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#notification-backups-form').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            _token: $('input[name="_token"]').val(),
            cleanup_enabled: $('#cleanup_enabled').is(':checked') ? 1 : 0,
            cleanup_days: parseInt($('#cleanup_days').val()),
            push_enabled: $('#push_enabled').is(':checked') ? 1 : 0,
            push_max_retries: parseInt($('#push_max_retries').val()),
            channel_database: $('#channel_database').is(':checked') ? 1 : 0,
            channel_mail: $('#channel_mail').is(':checked') ? 1 : 0,
            channel_push: $('#channel_push').is(':checked') ? 1 : 0,
            retention_days: parseInt($('#retention_days').val())
        };

        // Validación frontend
        if (formData.cleanup_days < 1 || formData.cleanup_days > 365) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Los días de limpieza deben estar entre 1 y 365'
            });
            return;
        }

        if (formData.push_max_retries < 1 || formData.push_max_retries > 10) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Los reintentos máximos deben estar entre 1 y 10'
            });
            return;
        }

        if (formData.retention_days < 1 || formData.retention_days > 365) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Los días de retención deben estar entre 1 y 365'
            });
            return;
        }

        // Enviar datos
        $.ajax({
            url: '{{ route("backups.notifications.update") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.message || 'Configuración actualizada correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'Error al actualizar la configuración';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    });
});
</script>
@endpush
