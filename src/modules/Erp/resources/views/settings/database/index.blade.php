@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Oracle Database'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Oracle Database Settings Card -->
        <div class="card">
            <div class="card-body">

                <!-- Connection Status -->
                <div id="connectionStatus" class="mb-3" style="display: none;">
                    <div id="connectionMessage"></div>
                </div>

                <!-- Información del Servidor -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">Información del servidor</h5>
                            <p class="text-muted small mb-0">Parámetros de conexión al servidor Oracle Database.</p>
                        </div>
                        <div class="btn-group" role="group">
                            <a href="{{ route('settings.erp.database.edit') }}" class="btn btn-secondary" title="Editar configuración">
                                <i class="fa fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-secondary" id="testConnectionBtn" title="Probar conexión">
                                <i class="fa fa-plug"></i>
                            </button>
                            <a href="{{ route('settings.erp.index') }}" class="btn btn-secondary" title="Volver">
                                <i class="fa fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card card-body h-100 bg-light-secondary">
                                <h5 class="text-dark">Host:</h5>
                                <p class="text-muted mb-0">{{ config('database.connections.oracle.host') ?? 'N/A' }}</p>
                                <small class="text-muted">Dirección IP o nombre de host del servidor</small>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card card-body h-100 bg-light-secondary">
                                <h5 class="text-dark">Puerto:</h5>
                                <p class="text-muted mb-0">{{ config('database.connections.oracle.port') ?? 'N/A' }}</p>
                                <small class="text-muted">Puerto de conexión (por defecto: 1521)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Base de Datos -->
                <div class="mb-4">
                    <h5 class="mb-1">Base de datos</h5>
                    <p class="text-muted small mb-3">Configuración de la base de datos y schema.</p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card card-body h-100">
                                <h6 class="mb-3">Información de la Base de Datos</h6>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Database:</label>
                                    <p class="text-muted mb-0">{{ config('database.connections.oracle.database') ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Service Name:</label>
                                    <p class="text-muted mb-0">{{ config('database.connections.oracle.service_name') ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label fw-semibold text-dark">Schema:</label>
                                    <p class="text-muted mb-0">{{ config('database.connections.oracle.prefix_schema') ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card card-body h-100">
                                <h6 class="mb-3">Credenciales y Configuración</h6>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Usuario:</label>
                                    <p class="text-muted mb-0">{{ config('database.connections.oracle.username') ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">Contraseña:</label>
                                    <p class="text-muted mb-0">{{ config('database.connections.oracle.password') ? '••••••••' : 'N/A' }}</p>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label fw-semibold text-dark">Charset:</label>
                                    <p class="text-muted mb-0">{{ config('database.connections.oracle.charset') ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
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

    // Test Oracle Connection
    if (testConnectionBtn) {
        testConnectionBtn.addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Probando...';

            fetch('{{ route("settings.erp.database.check-connection") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Response text:', text);
                        throw new Error('Error HTTP: ' + response.status + ' - ' + text.substring(0, 100));
                    });
                }

                return response.text().then(text => {
                    console.log('Response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response was:', text.substring(0, 500));
                        throw new Error('Respuesta no es JSON válido');
                    }
                });
            })
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
});
</script>
@endpush

@endsection
