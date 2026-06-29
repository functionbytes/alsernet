{{-- Sección: Configuración de Emails del Módulo Document --}}
<div class="tab-pane fade show" id="emailSettingsTab" role="tabpanel">
    <div class="card">
        <div class="card-header bg-light p-3">
            <h6 class="mb-0 fw-bold">
                <i class="fas fa-envelope me-2 text-primary"></i>Configuración de Emails
            </h6>
        </div>
        <div class="card-body">
            <form id="emailSettingsForm" method="POST" action="{{ route('settings.documents.configurations.update') }}">
                @csrf

                {{-- Notificaciones de Solicitud Inicial --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-paper-plane me-2"></i>Solicitud Inicial de Documentos
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enableInitialRequest"
                                   name="enable_initial_request" value="1"
                                   {{ config('documents.notifications.enable_initial_request', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableInitialRequest">
                                Habilitar notificación de solicitud inicial
                            </label>
                        </div>
                        <div class="form-group mb-0">
                            <label for="initialRequestMessage" class="form-label">Descripción:</label>
                            <textarea class="form-control" id="initialRequestMessage"
                                      name="initial_request_message" rows="3"
                                      placeholder="Descripción para el usuario">{{ config('documents.notifications.initial_request_message', '') }}</textarea>
                            <small class="text-muted">Se mostrará en la interfaz de gestión de documentos</small>
                        </div>
                    </div>
                </div>

                {{-- Notificaciones de Recordatorio --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-bell me-2"></i>Recordatorios
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enableReminder"
                                   name="enable_reminder" value="1"
                                   {{ config('documents.notifications.enable_reminder', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableReminder">
                                Habilitar notificación de recordatorio
                            </label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="reminderDays" class="form-label">Días para recordar:</label>
                                <input type="number" class="form-control" id="reminderDays"
                                       name="reminder_days" min="1" max="365"
                                       value="{{ config('documents.notifications.reminder_days', 7) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="reminderMessage" class="form-label">Descripción:</label>
                                <input type="text" class="form-control" id="reminderMessage"
                                       name="reminder_message"
                                       placeholder="Ej: Recordatorio para completar carga de documentos"
                                       value="{{ config('documents.notifications.reminder_message', '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notificaciones de Documentos Faltantes --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-exclamation-circle me-2"></i>Documentos Faltantes
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enableMissingDocs"
                                   name="enable_missing_docs" value="1"
                                   {{ config('documents.notifications.enable_missing_docs', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableMissingDocs">
                                Habilitar notificación de documentos faltantes
                            </label>
                        </div>
                        <div class="form-group mb-0">
                            <label for="missingDocsMessage" class="form-label">Descripción:</label>
                            <textarea class="form-control" id="missingDocsMessage"
                                      name="missing_docs_message" rows="3"
                                      placeholder="Se notificará al cliente sobre documentos faltantes">{{ config('documents.notifications.missing_docs_message', '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Notificaciones de Confirmación de Carga --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-check-circle me-2"></i>Confirmación de Carga
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="enableUploadConfirmation"
                                   name="enable_upload_confirmation" value="1"
                                   {{ config('documents.notifications.enable_upload_confirmation', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableUploadConfirmation">
                                Habilitar confirmación de carga
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Notificaciones de Aprobación/Rechazo --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-certificate me-2"></i>Aprobación y Rechazo
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableApproval"
                                           name="enable_approval" value="1"
                                           {{ config('documents.notifications.enable_approval', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableApproval">
                                        Habilitar notificación de aprobación
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableRejection"
                                           name="enable_rejection" value="1"
                                           {{ config('documents.notifications.enable_rejection', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enableRejection">
                                        Habilitar notificación de rechazo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Correos Personalizados --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-pen-fancy me-2"></i>Correos Personalizados
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="enableCustomEmail"
                                   name="enable_custom_email" value="1"
                                   {{ config('documents.notifications.enable_custom_email', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableCustomEmail">
                                Permitir envío de correos personalizados
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Botones de Acción --}}
                <div class="d-flex gap-2 justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar cambios
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Restablecer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('emailSettingsForm');

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Enviar los datos por AJAX
            const formData = new FormData(this);

            // Agregar token CSRF al FormData para compatibilidad con multipart/form-data
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            fetch('{{ route('settings.documents.configurations.update') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Configuración de emails actualizada correctamente', 'Éxito');
                } else {
                    toastr.error('Error al actualizar la configuración', 'Error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Error al procesar la solicitud', 'Error');
            });
        });
    }
});
</script>
@endpush
