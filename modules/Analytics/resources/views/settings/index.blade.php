@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Google Analytics GA4'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <form method="POST" action="{{ route('settings.analytics.update') }}" id="analyticsForm">
            @csrf
            @method('PUT')

            <div class="card">
                <!-- Tabs Navigation -->
                <div class="card-header border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" id="analyticsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="configuration-tab" data-bs-toggle="tab"
                                    data-bs-target="#configuration" type="button" role="tab">
                                <i class="fas fa-cog me-2"></i>Configuración
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="credentials-tab" data-bs-toggle="tab"
                                    data-bs-target="#credentials" type="button" role="tab">
                                <i class="fas fa-key me-2"></i>Credenciales
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="info-tab" data-bs-toggle="tab"
                                    data-bs-target="#info" type="button" role="tab">
                                <i class="fas fa-book me-2"></i>Información
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="analyticsTabsContent">

                        <!-- Tab 1: Configuration -->
                        <div class="tab-pane fade show active" id="configuration" role="tabpanel">
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Estado del servicio
                                </h6>
                                <p class="text-muted small mb-3">
                                    Habilita o deshabilita el seguimiento de Google Analytics GA4 en tu sitio web.
                                </p>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="googleAnalyticsEnable"
                                                   name="google_analytics_enable" value="1"
                                                   {{ $settings['google_analytics_enable'] ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="googleAnalyticsEnable">
                                                Habilitar Google Analytics
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">Activa el seguimiento en tiempo real</small>
                                    </div>
                                </div>

                                <div class="mt-3 alert alert-info border-0">
                                    <small>
                                        <strong>Información:</strong> Necesitas una cuenta de Google Analytics configurada.
                                        <a href="https://analytics.google.com" target="_blank" class="fw-semibold">Crear una cuenta →</a>
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-0">
                                <h6 class="mb-1 fw-bold text-dark">
                                    ID de la propiedad
                                </h6>
                                <p class="text-muted small mb-3">
                                    Ingresa el ID único de tu propiedad GA4.
                                </p>

                                <div class="mb-3">
                                    <label for="propertyId" class="form-label fw-bold">GA4 Property ID</label>
                                    <input type="text" class="form-control" id="propertyId"
                                           name="google_analytics_property_id"
                                           placeholder="123456789"
                                           value="{{ $settings['google_analytics_property_id'] }}"
                                           pattern="[0-9]+">
                                    <small class="text-muted d-block mt-2">Número de 9-10 dígitos encontrado en Admin → Properties & apps</small>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Credentials -->
                        <div class="tab-pane fade" id="credentials" role="tabpanel">
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    JSON de credenciales
                                </h6>
                                <p class="text-muted small mb-3">
                                    Pega aquí el archivo de credenciales de servicio de Google Cloud Console.
                                </p>

                                <div class="mb-3">
                                    <label for="credentials" class="form-label fw-bold">Credenciales (JSON)</label>
                                    <textarea class="form-control font-monospace" id="credentials"
                                              name="google_analytics_credentials"
                                              rows="10"
                                              placeholder='{"type": "service_account", "project_id": "...", ...}'>{{ $settings['google_analytics_credentials'] }}</textarea>
                                    <small class="text-muted d-block mt-2">Contenido completo del archivo JSON descargado desde Google Cloud Console</small>
                                </div>

                                <button type="button" class="btn btn-sm btn-outline-primary" id="validateBtn">
                                    <i class="fas fa-check me-1"></i>Validar credenciales
                                </button>
                            </div>

                            <hr class="my-4">

                            <div class="mb-0">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Pasos para obtener credenciales
                                </h6>

                                <div class="alert alert-warning border-0">
                                    <small>
                                        <strong>Sigue estos pasos:</strong><br>
                                        1. Ve a <a href="https://console.cloud.google.com" target="_blank" class="fw-semibold">Google Cloud Console</a><br>
                                        2. Crea una nueva cuenta de servicio<br>
                                        3. Genera una clave privada en formato JSON<br>
                                        4. Descarga el archivo y copia su contenido aquí<br>
                                        5. Haz clic en "Validar credenciales" para verificar
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Information -->
                        <div class="tab-pane fade" id="info" role="tabpanel">
                            <div class="mb-4">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Métricas disponibles
                                </h6>
                                <p class="text-muted small mb-3">
                                    Estas son las métricas que puedes rastrear con GA4.
                                </p>

                                <ul class="small text-muted ps-3 mb-4">
                                    <li class="mb-2"><strong>Sessions (Sesiones):</strong> Número total de sesiones en tu sitio</li>
                                    <li class="mb-2"><strong>Users (Usuarios):</strong> Cantidad de usuarios únicos</li>
                                    <li class="mb-2"><strong>Page Views (Vistas):</strong> Total de páginas visitadas</li>
                                    <li class="mb-2"><strong>Bounce Rate (Rebote):</strong> Porcentaje de sesiones con una sola página</li>
                                    <li class="mb-2"><strong>Engagement Duration:</strong> Tiempo promedio de interacción</li>
                                </ul>
                            </div>

                            <hr class="my-4">

                            <div class="mb-0">
                                <h6 class="mb-1 fw-bold text-dark">
                                    Dimensiones disponibles
                                </h6>
                                <p class="text-muted small mb-3">
                                    Dimensiones para segmentar y agrupar tus datos.
                                </p>

                                <ul class="small text-muted ps-3">
                                    <li class="mb-2"><strong>Date (Fecha):</strong> Agrupación por fecha</li>
                                    <li class="mb-2"><strong>Page Title:</strong> Título de las páginas visitadas</li>
                                    <li class="mb-2"><strong>Browser:</strong> Navegador utilizado</li>
                                    <li class="mb-2"><strong>Country (País):</strong> Ubicación geográfica</li>
                                    <li class="mb-2"><strong>Session Source:</strong> Origen de la sesión (referrer)</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-1">
                        <i class="fas fa-save me-1"></i>Guardar configuración
                    </button>
                    <a href="{{ route('settings.analytics.index') }}" class="btn btn-secondary w-100">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>

            </div>

        </form>

    </div>

    @push('scripts')
        <script>
            (function() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const validateBtn = document.getElementById('validateBtn');
                const analyticsForm = document.getElementById('analyticsForm');
                const propertyIdInput = document.getElementById('propertyId');
                const credentialsInput = document.getElementById('credentials');

                // Validate credentials
                validateBtn?.addEventListener('click', function() {
                    if (!propertyIdInput.value || !credentialsInput.value) {
                        toastr.warning('Por favor, completa los campos obligatorios', 'Validación');
                        return;
                    }

                    fetch('{{ route("settings.analytics.validate-credentials") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            property_id: propertyIdInput.value,
                            credentials: credentialsInput.value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            toastr.success(data.message, 'Validación exitosa');
                        } else {
                            toastr.error(data.message, 'Error de validación');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('Error al validar credenciales', 'Error');
                    });
                });

                // Form submission
                analyticsForm?.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            toastr.success(data.message, 'Guardado exitoso');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(data.message, 'Error al guardar');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('Error al guardar configuración', 'Error');
                    });
                });
            })();
        </script>
    @endpush

@endsection
