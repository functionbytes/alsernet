@extends('layouts.theme')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de correo saliente'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Configuración de correo saliente</h5>
                        <p class="small mb-0 text-muted">Gestiona el remitente y el servidor SMTP para el envío de correos de la aplicación</p>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown">
                            Acciones
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('settings.outgoing-email.edit') }}">Editar configuración</a>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item" id="testConnectionBtn">Probar conexión</button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#sendTestEmailModal">Enviar prueba</button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('settings.email.index') }}">Volver</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Connection Status --}}
            <div id="connectionStatus" class="card-body border-bottom" style="display:none;">
                <div id="connectionMessage"></div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Email remitente</h6>
                                <h5 class="mb-1 fw-bold text-truncate">{{ $settings['mail_from_address'] ?? 'N/A' }}</h5>
                                <small class="text-muted">Dirección de envío</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Nombre remitente</h6>
                                <h5 class="mb-1 fw-bold">{{ $settings['mail_from_name'] ?? 'N/A' }}</h5>
                                <small class="text-muted">Nombre visible</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Servidor SMTP</h6>
                                <h5 class="mb-1 fw-bold">{{ $settings['mail_host'] ?? 'N/A' }}</h5>
                                <small class="text-muted">Host de conexión</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Puerto</h6>
                                <h5 class="mb-1 fw-bold">{{ $settings['mail_port'] ?? 'N/A' }}</h5>
                                <small class="text-muted">Puerto SMTP</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detalles técnicos --}}
            <div class="card-body">
                <h6 class="fw-bold mb-1">Detalles técnicos</h6>
                <p class="text-muted mb-3">Parámetros de configuración del correo saliente</p>

                <div class="alert alert-warning border-0 py-2 mb-3">
                    <small><i class="fas fa-triangle-exclamation me-1"></i>
                        <strong>Advertencia:</strong> Cambiar estas configuraciones incorrectamente puede hacer que el envío de correos no funcione.
                    </small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="25%">Parámetro</th>
                                <th width="30%">Valor actual</th>
                                <th width="45%">Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>mail_mailer</code></td>
                                <td><strong>{{ $settings['mail_mailer'] ?? 'N/A' }}</strong></td>
                                <td class="text-muted">Driver de envío (smtp, sendmail, mailgun, etc.)</td>
                            </tr>
                            <tr>
                                <td><code>mail_host</code></td>
                                <td><strong>{{ $settings['mail_host'] ?? 'N/A' }}</strong></td>
                                <td class="text-muted">Dirección del servidor SMTP</td>
                            </tr>
                            <tr>
                                <td><code>mail_port</code></td>
                                <td><strong>{{ $settings['mail_port'] ?? 'N/A' }}</strong></td>
                                <td class="text-muted">Puerto de la conexión (25, 465, 587, 2525)</td>
                            </tr>
                            <tr>
                                <td><code>mail_encryption</code></td>
                                <td><strong>{{ strtoupper($settings['mail_encryption'] ?? 'N/A') }}</strong></td>
                                <td class="text-muted">Protocolo de encriptación (TLS, SSL o ninguno)</td>
                            </tr>
                            <tr>
                                <td><code>mail_username</code></td>
                                <td><strong>{{ $settings['mail_username'] ?? 'N/A' }}</strong></td>
                                <td class="text-muted">Usuario para autenticación SMTP</td>
                            </tr>
                            <tr>
                                <td><code>mail_password</code></td>
                                <td><strong>{{ $settings['mail_password'] ? '••••••••' : 'N/A' }}</strong></td>
                                <td class="text-muted">Contraseña para autenticación SMTP</td>
                            </tr>
                            <tr>
                                <td><code>mail_from_address</code></td>
                                <td><strong>{{ $settings['mail_from_address'] ?? 'N/A' }}</strong></td>
                                <td class="text-muted">Dirección desde la que se envían los correos</td>
                            </tr>
                            <tr>
                                <td><code>mail_from_name</code></td>
                                <td><strong>{{ $settings['mail_from_name'] ?? 'N/A' }}</strong></td>
                                <td class="text-muted">Nombre visible del remitente en los correos</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal Enviar Email de Prueba --}}
    <div class="modal fade" id="sendTestEmailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar correo de prueba</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="testEmailForm">
                        @csrf
                        <div class="alert alert-info border-0 mb-3">
                            <i class="fas fa-circle-info me-1"></i>
                            Asegúrate de tener la configuración SMTP actualizada antes de enviar la prueba.
                        </div>
                        <div class="mb-3">
                            <label for="testEmail" class="form-label fw-semibold">
                                Correo destino <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="testEmail" name="test_email"
                                   required placeholder="ejemplo@correo.com">
                            <small class="text-muted">Dirección donde recibirás el correo de prueba</small>
                        </div>
                        <div class="bg-light-secondary p-3 rounded">
                            <h6 class="mb-2 small fw-bold">Vista previa</h6>
                            <small class="text-muted">
                                <strong>De:</strong> {{ $settings['mail_from_name'] }} ({{ $settings['mail_from_address'] }})<br>
                                <strong>Servidor:</strong> {{ $settings['mail_host'] }}:{{ $settings['mail_port'] }}
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="sendTestEmailBtn">Enviar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
$(function () {

    function showConnectionResult(html) {
        $('#connectionMessage').html(html);
        $('#connectionStatus').show();
    }

    // Probar conexión
    $('#testConnectionBtn').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Probando...');

        $.ajax({
            url: '{{ route("settings.outgoing-email.test-connection") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                var type = data.success ? 'success' : 'danger';
                var label = data.success ? 'Conexión exitosa' : 'Error de conexión';
                showConnectionResult('<div class="alert alert-' + type + ' alert-dismissible mb-0"><strong>' + label + '</strong> ' + data.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            },
            error: function () {
                showConnectionResult('<div class="alert alert-danger alert-dismissible mb-0"><strong>Error</strong> No se pudo conectar al servidor.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Probar conexión');
            }
        });
    });

    // Enviar correo de prueba
    $('#sendTestEmailBtn').on('click', function () {
        var $input = $('#testEmail');
        var email  = $input.val().trim();

        if (!email || !$input[0].checkValidity()) {
            $input.addClass('is-invalid');
            return;
        }
        $input.removeClass('is-invalid');

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');

        $.ajax({
            url: '{{ route("settings.outgoing-email.send-test") }}',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({ test_email: email }),
            success: function (data) {
                bootstrap.Modal.getInstance(document.getElementById('sendTestEmailModal')).hide();
                var type  = data.success ? 'success' : 'danger';
                var label = data.success ? 'Email enviado' : 'Error al enviar';
                showConnectionResult('<div class="alert alert-' + type + ' alert-dismissible mb-0"><strong>' + label + '</strong> ' + data.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                if (data.success) { $input.val(''); }
            },
            error: function () {
                showConnectionResult('<div class="alert alert-danger alert-dismissible mb-0"><strong>Error</strong> No se pudo enviar el correo.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Enviar');
            }
        });
    });

    // Reset modal
    $('#sendTestEmailModal').on('hidden.bs.modal', function () {
        $('#testEmail').val('').removeClass('is-invalid');
    });
});
</script>
@endpush

@endsection
