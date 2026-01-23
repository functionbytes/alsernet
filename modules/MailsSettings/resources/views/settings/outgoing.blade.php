@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Correo Saliente'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Outgoing Email Settings Card -->
        <div class="card">
            <div class="card-body">

                <!-- Acciones -->
                <div class="mb-4 border-bottom pb-3">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <a href="{{ route('settings.outgoing-email.edit') }}" class="btn btn-primary w-100">
                                Editar configuración
                            </a>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-primary w-100" id="testConnectionBtn">
                                Probar conexión
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#sendTestEmailModal">
                                Enviar prueba
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('settings.email.index') }}" class="btn btn-outline-primary w-100">
                                Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Connection Status -->
                <div id="connectionStatus" class="mb-3" style="display: none;">
                    <div id="connectionMessage"></div>
                </div>

                <!-- Información del Remitente -->
                <div class="mb-4">
                    <h5 class="mb-1">Información del remitente</h5>
                    <p class="text-muted small mb-3">Configure la información del remitente para todos los correos electrónicos salientes de la aplicación.</p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card card-body h-100 bg-light-secondary">
                                <h5 class="text-dark">Email del remitente:</h5>
                                <p class="text-muted mb-0">{{ $settings['mail_from_address'] ?? 'N/A' }}</p>
                                <small class="text-muted">Todos los correos salientes se enviarán desde esta dirección</small>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card card-body h-100 bg-light-secondary">
                                <h5 class="text-dark">Nombre del remitente:</h5>
                                <p class="text-muted mb-0">{{ $settings['mail_from_name'] ?? 'N/A' }}</p>
                                <small class="text-muted">Todos los correos salientes usarán este nombre</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Método de Correo Saliente -->
                <div class="mb-4">
                    <h5 class="mb-1">Método de correo saliente</h5>
                    <p class="text-muted small mb-3">Configure qué método se debe utilizar para enviar correos electrónicos salientes de la aplicación.</p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card card-body h-100">
                                <h6 class="mb-3">Configuración del Servidor SMTP</h6>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Mailer:</label>
                                    <p class="text-muted mb-0">{{ $settings['mail_mailer'] ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Servidor:</label>
                                    <p class="text-muted mb-0">{{ $settings['mail_host'] ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Puerto:</label>
                                    <p class="text-muted mb-0">{{ $settings['mail_port'] ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label fw-semibold text-dark">Encriptación:</label>
                                    <p class="text-muted mb-0">{{ strtoupper($settings['mail_encryption'] ?? 'N/A') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card card-body h-100">
                                <h6 class="mb-3">Credenciales SMTP</h6>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Usuario:</label>
                                    <p class="text-muted mb-0">{{ $settings['mail_username'] ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Contraseña:</label>
                                    <p class="text-muted mb-0">{{ $settings['mail_password'] ? '••••••••' : 'N/A' }}</p>
                                </div>
                                <div class="mb-0">
                                    <p class="text-muted small">
                                        <i class="fa fa-circle-info"></i>
                                        Esta configuración se carga desde la base de datos en tiempo de ejecución.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Modal Enviar Email de Prueba -->
    <div class="modal fade" id="sendTestEmailModal" tabindex="-1" aria-labelledby="sendTestEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendTestEmailModalLabel">
                         Enviar correo electrónico de prueba
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="testEmailForm">
                        @csrf
                        <div class="alert alert-info border-0 bg-info-subtle text-info mb-3">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa fa-circle-info fs-5"></i>
                                <div>
                                    <strong>Importante:</strong> Asegúrese de tener la configuración SMTP actualizada antes de enviar el correo de prueba.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="testEmail" class="form-label fw-semibold">
                                Correo electrónico destino <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control"
                                   id="testEmail"
                                   name="test_email"
                                   required
                                   placeholder="ejemplo@correo.com">
                            <small class="text-muted">Ingrese la dirección de correo donde desea recibir el email de prueba</small>
                        </div>

                        <div class="bg-light-secondary p-3 rounded">
                            <h6 class="mb-2">Vista previa</h6>
                            <small class="text-muted">
                                <strong>Asunto:</strong>Correo de Prueba - Alsernet<br>
                                <strong>De:</strong> {{ $settings['mail_from_name'] }} ({{ $settings['mail_from_address'] }})<br>
                                <strong>Servidor:</strong> {{ $settings['mail_host'] }}:{{ $settings['mail_port'] }}
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="sendTestEmailBtn">
                        Enviar
                    </button>
                    <button type="button" class="btn btn-secondary w-100 " data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const testConnectionBtn = document.getElementById('testConnectionBtn');
    const connectionStatus = document.getElementById('connectionStatus');
    const connectionMessage = document.getElementById('connectionMessage');

    // Test SMTP Connection
    if (testConnectionBtn) {
        testConnectionBtn.addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Probando...';

            fetch('{{ route("settings.outgoing-email.test-connection") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                connectionStatus.style.display = 'block';
                if (data.success) {
                    connectionMessage.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-3" role="alert">
                            <div class="flex-shrink-0 fs-5"><i class="fa fa-circle-check"></i></div>
                            <div class="flex-grow-1">
                                <strong>Conexión exitosa</strong>
                                <p class="mb-0">${data.message}</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                } else {
                    connectionMessage.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-3" role="alert">
                            <div class="flex-shrink-0 fs-5"><i class="fa fa-circle-exclamation"></i></div>
                            <div class="flex-grow-1">
                                <strong>Error de conexión</strong>
                                <p class="mb-0">${data.message}</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                connectionStatus.style.display = 'block';
                connectionMessage.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-3" role="alert">
                        <div class="flex-shrink-0 fs-5"><i class="fa fa-circle-exclamation"></i></div>
                        <div class="flex-grow-1">
                            <strong>Error</strong>
                            <p class="mb-0">Error en la solicitud: ${error.message}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        });
    }

    // Send Test Email from Modal
    const sendTestEmailBtn = document.getElementById('sendTestEmailBtn');
    const sendTestEmailModal = document.getElementById('sendTestEmailModal');

    if (sendTestEmailBtn) {
        sendTestEmailBtn.addEventListener('click', function() {
            const testEmailInput = document.getElementById('testEmail');
            const email = testEmailInput.value.trim();

            // Validate email
            if (!email || !testEmailInput.checkValidity()) {
                testEmailInput.classList.add('is-invalid');
                return;
            }

            testEmailInput.classList.remove('is-invalid');

            const originalContent = sendTestEmailBtn.innerHTML;
            sendTestEmailBtn.disabled = true;
            sendTestEmailBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Enviando...';

            fetch('{{ route("settings.outgoing-email.send-test") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    test_email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                // Close modal
                const modalInstance = bootstrap.Modal.getInstance(sendTestEmailModal);
                if (modalInstance) {
                    modalInstance.hide();
                }

                // Show result in connection status area
                connectionStatus.style.display = 'block';
                if (data.success) {
                    connectionMessage.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-3" role="alert">
                            <div class="flex-shrink-0 fs-5"><i class="fa fa-circle-check"></i></div>
                            <div class="flex-grow-1">
                                <strong>Email enviado</strong>
                                <p class="mb-0">${data.message}</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    // Clear form on success
                    testEmailInput.value = '';
                } else {
                    connectionMessage.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-3" role="alert">
                            <div class="flex-shrink-0 fs-5"><i class="fa fa-circle-exclamation"></i></div>
                            <div class="flex-grow-1">
                                <strong>Error al enviar</strong>
                                <p class="mb-0">${data.message}</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                connectionStatus.style.display = 'block';
                connectionMessage.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-3" role="alert">
                        <div class="flex-shrink-0 fs-5"><i class="fa fa-circle-exclamation"></i></div>
                        <div class="flex-grow-1">
                            <strong>Error</strong>
                            <p class="mb-0">Error: ${error.message}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            })
            .finally(() => {
                sendTestEmailBtn.disabled = false;
                sendTestEmailBtn.innerHTML = originalContent;
            });
        });
    }

    // Reset modal on close
    if (sendTestEmailModal) {
        sendTestEmailModal.addEventListener('hidden.bs.modal', function() {
            const testEmailInput = document.getElementById('testEmail');
            if (testEmailInput) {
                testEmailInput.value = '';
                testEmailInput.classList.remove('is-invalid');
            }
        });
    }
});
</script>
@endpush

@endsection
