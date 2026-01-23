@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Google Analytics GA4'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <form method="POST" action="{{ route('settings.analytics.update') }}" id="analyticsForm">
            @csrf
            @method('PUT')

            <!-- Main Settings Card -->
            <div class="card">
                <!-- Header Section -->
                <div class="card-header p-4 border-bottom">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-chart-line me-2"></i>Configuración de Google Analytics
                        </h5>
                        <p class="small mb-0">Integra Google Analytics GA4 para rastrear el tráfico y comportamiento de visitantes en tiempo real</p>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Enable/Disable Toggle -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Estado del servicio</h6>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="googleAnalyticsEnable"
                                               name="google_analytics_enable" value="1"
                                               {{ $settings['google_analytics_enable'] ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="googleAnalyticsEnable">
                                            Habilitar Google Analytics
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-2">Activa el seguimiento de Google Analytics en tu sitio web</small>

                                    <div class="mt-3 alert alert-info border-0 bg-info-subtle text-info">
                                        <small>
                                            <strong>Información:</strong> Necesitas una cuenta de Google Analytics configurada.
                                            <a href="https://analytics.google.com" target="_blank" class="text-info fw-semibold">Crear una cuenta →</a>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Property ID -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">ID de la propiedad</h6>

                                    <div class="mb-0">
                                        <label for="propertyId" class="form-label fw-semibold">GA4 Property ID</label>
                                        <input type="text" class="form-control" id="propertyId"
                                               name="google_analytics_property_id"
                                               placeholder="123456789"
                                               value="{{ $settings['google_analytics_property_id'] }}"
                                               pattern="[0-9]+">
                                        <small class="text-muted d-block mt-2">Número de 9-10 dígitos encontrado en Admin → Properties & apps</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credentials Section -->
                    <div class="row mt-2">
                        <div class="col-12 mb-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">
                                        <i class="fas fa-key me-2"></i>Credenciales de servicio
                                    </h6>

                                    <div class="mb-3">
                                        <label for="credentials" class="form-label fw-semibold">JSON de credenciales</label>
                                        <textarea class="form-control font-monospace" id="credentials"
                                                  name="google_analytics_credentials"
                                                  rows="8"
                                                  placeholder='{"type": "service_account", "project_id": "...", ...}'>{{ $settings['google_analytics_credentials'] }}</textarea>
                                        <small class="text-muted d-block mt-2">Pega aquí el contenido completo del archivo JSON de credenciales descargado desde Google Cloud Console</small>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-outline-primary" id="validateBtn">
                                        <i class="fas fa-check me-1"></i>Validar credenciales
                                    </button>

                                    <div class="mt-3 alert alert-warning border-0 bg-warning-subtle text-warning">
                                        <small>
                                            <strong>Pasos para obtener credenciales:</strong><br>
                                            1. Ve a <a href="https://console.cloud.google.com" target="_blank" class="text-warning fw-semibold">Google Cloud Console</a><br>
                                            2. Crea una cuenta de servicio<br>
                                            3. Crea una clave JSON<br>
                                            4. Descarga el archivo y copia su contenido aquí
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer bg-light">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Guardar configuración
                        </button>
                        <a href="{{ route('settings.analytics.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                    </div>
                </div>

            </div>

        </form>

        <!-- Documentation Card -->
        <div class="card mt-4 border-0 bg-light">
            <div class="card-body">
                <h6 class="mb-3 fw-bold">
                    <i class="fas fa-book me-2"></i>Información útil
                </h6>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-semibold text-muted">Métricas disponibles:</h6>
                        <ul class="small text-muted ps-3">
                            <li>Sessions (Sesiones)</li>
                            <li>Users (Usuarios)</li>
                            <li>Page Views (Vistas de página)</li>
                            <li>Bounce Rate (Tasa de rebote)</li>
                            <li>Engagement Duration (Duración de engagement)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold text-muted">Dimensiones disponibles:</h6>
                        <ul class="small text-muted ps-3">
                            <li>Date (Fecha)</li>
                            <li>Page Title (Título de página)</li>
                            <li>Browser (Navegador)</li>
                            <li>Country (País)</li>
                            <li>Session Source (Fuente de sesión)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            document.getElementById('validateBtn').addEventListener('click', function() {
                const propertyId = document.getElementById('propertyId').value;
                const credentials = document.getElementById('credentials').value;

                if (!propertyId || !credentials) {
                    alert('Por favor, completa los campos de Property ID y credenciales');
                    return;
                }

                fetch('{{ route("settings.analytics.validate-credentials") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        property_id: propertyId,
                        credentials: credentials
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        alert('✓ ' + data.message);
                    } else {
                        alert('✗ ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al validar credenciales');
                });
            });

            // Form submission
            document.getElementById('analyticsForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        alert('✓ ' + data.message);
                        // Opcional: recargar después de 1 segundo
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('✗ ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al guardar configuración');
                });
            });
        </script>
    @endpush

@endsection
