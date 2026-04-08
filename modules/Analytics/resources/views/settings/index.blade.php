@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Google Analytics GA4'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">
                <form method="POST" action="{{ route('settings.analytics.update') }}" id="analyticsForm">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-body">

                            {{-- Estado del servicio --}}
                            <h6 class="fw-bold text-dark mb-1">Estado del servicio</h6>
                            <p class="text-muted mb-3">Habilita o deshabilita el seguimiento de Google Analytics GA4 en tu sitio web.</p>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="googleAnalyticsEnable"
                                       name="google_analytics_enable" value="1"
                                       {{ $settings['google_analytics_enable'] ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="googleAnalyticsEnable">
                                    Habilitar Google Analytics
                                </label>
                            </div>
                            <small class="text-muted d-block mb-3">Activa el seguimiento en tiempo real</small>

                            <div class="alert alert-info border-0 mb-0">
                                <small>
                                    <strong>Información:</strong> Necesitas una cuenta de Google Analytics configurada.
                                    <a href="https://analytics.google.com" target="_blank" class="fw-semibold">Crear una cuenta →</a>
                                </small>
                            </div>

                        </div>

                        <div id="analytics-enabled-fields" class="{{ $settings['google_analytics_enable'] ? '' : 'd-none' }}">

                            <hr class="my-0">

                            {{-- Property ID --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">ID de la propiedad</h6>
                                <p class="text-muted mb-3">Ingresa el ID numérico de tu propiedad GA4, requerido para conectar con la API de datos.</p>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="propertyId" class="form-label fw-semibold">GA4 Property ID</label>
                                        <input type="text" class="form-control" id="propertyId"
                                               name="google_analytics_property_id"
                                               placeholder="123456789"
                                               value="{{ $settings['google_analytics_property_id'] }}"
                                               pattern="[0-9]+">
                                        <small class="text-muted d-block mt-1">
                                            ID numérico (9-10 dígitos). GA4 → Admin → Configuración de la propiedad.
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="measurementId" class="form-label fw-semibold">Measurement ID (seguimiento web)</label>
                                        <input type="text" class="form-control" id="measurementId"
                                               name="google_analytics_measurement_id"
                                               placeholder="G-XXXXXXXXXX"
                                               value="{{ $settings['google_analytics_measurement_id'] }}">
                                        <small class="text-muted d-block mt-1">
                                            Formato <code>G-XXXXXXX</code>. Úsalo para el script gtag.js del sitio.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-0">

                            {{-- Credenciales --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">Credenciales de servicio (JSON)</h6>
                                <p class="text-muted mb-3">Pega aquí el archivo de credenciales de Google Cloud Console.</p>

                                @if($credentialsInfo['configured'])
                                    <div class="alert alert-success border-0 mb-3 py-2">
                                        <small>
                                            <strong>Credenciales configuradas</strong><br>
                                            Proyecto: <code>{{ $credentialsInfo['project_id'] }}</code><br>
                                            Cuenta de servicio: <code>{{ $credentialsInfo['client_email'] }}</code>
                                        </small>
                                    </div>
                                @endif

                                @can('analytics.settings.update')
                                <label for="credentials" class="form-label fw-semibold">Credenciales (JSON)</label>
                                <textarea class="form-control font-monospace" id="credentials"
                                          name="google_analytics_credentials"
                                          rows="8"
                                          placeholder='{"type": "service_account", "project_id": "...", ...}'>{{ $credentialsJson }}</textarea>
                                <small class="text-muted d-block mt-2 mb-3">
                                    Contenido completo del archivo JSON descargado desde Google Cloud Console.
                                </small>

                                <button type="button" class="btn btn-outline-primary w-100" id="validateBtn">
                                    Validar credenciales
                                </button>
                                @endcan
                            </div>

                            <hr class="my-0">

                            {{-- Cache --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">Cache</h6>
                                <p class="text-muted mb-3">Tiempo que se almacenan en caché los datos del dashboard.</p>

                                <label for="cacheLifetime" class="form-label fw-semibold">Tiempo de caché (minutos)</label>
                                <input type="number" class="form-control" id="cacheLifetime"
                                       name="analytics_cache_lifetime"
                                       min="1" max="1440"
                                       value="{{ $settings['analytics_cache_lifetime'] }}">
                                <small class="text-muted d-block mt-2">Entre 1 y 1440 minutos (24h)</small>
                            </div>

                        </div>

                        @can('analytics.settings.update')
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>
                        @endcan
                    </div>

                </form>
            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Reportes programados</h6>
                        <p class="text-muted mb-3">Configura el envío automático de reportes por email con frecuencia y formato personalizados.</p>
                        <a href="{{ route('settings.analytics.schedules.index') }}" class="btn btn-primary w-100">
                            Gestionar reportes
                        </a>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Notificaciones</h6>
                        <p class="text-muted mb-3">Configura los destinatarios que reciben alertas cuando un reporte se envía o falla.</p>
                        <a href="{{ route('settings.analytics.notifications') }}" class="btn btn-outline-secondary w-100">
                            Configurar notificaciones
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Cómo obtener credenciales</h6>
                    </div>
                    <div class="card-body">

                        <h6 class="fw-semibold mb-2" >
                            Measurement ID (G-XXXXXXX)
                        </h6>
                        <ol class="text-muted ps-3 mb-3">
                            <li class="mb-1">Ve a <a href="https://analytics.google.com" target="_blank" class="fw-semibold">Google Analytics</a></li>
                            <li class="mb-1">Admin → Flujos de datos → selecciona tu sitio web</li>
                            <li>Copia el <strong>ID de medición</strong> (formato <code>G-XXXXXXX</code>)</li>
                        </ol>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2" >
                            Property ID (numérico)
                        </h6>
                        <ol class="text-muted ps-3 mb-3">
                            <li class="mb-1">Ve a <a href="https://analytics.google.com" target="_blank" class="fw-semibold">Google Analytics</a></li>
                            <li>Admin → Configuración de la propiedad → copia el <strong>ID de propiedad</strong> (ej: <code>284442383</code>)</li>
                        </ol>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2" >
                            Archivo JSON (cuenta de servicio)
                        </h6>
                        <ol class="text-muted  mb-0">
                            <li class="mb-1">Ve a <a href="https://console.cloud.google.com" target="_blank" class="fw-semibold">Google Cloud Console</a></li>
                            <li class="mb-1">IAM y administración → Cuentas de servicio → Crear</li>
                            <li class="mb-1">Asigna el rol <strong>Viewer</strong> en Analytics</li>
                            <li class="mb-1">Claves → Agregar clave → JSON → descarga el archivo</li>
                            <li class="mb-1">En GA4: Admin → Administración de acceso → añade el email de la cuenta de servicio</li>
                            <li>Pega el contenido del JSON en el campo de credenciales</li>
                        </ol>

                    </div>
                </div>
            </div>

        </div>

    </div>

    @push('scripts')
    <script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const validateBtn = document.getElementById('validateBtn');
        const analyticsForm = document.getElementById('analyticsForm');
        const propertyIdInput = document.getElementById('propertyId');
        const credentialsInput = document.getElementById('credentials');

        // Show/hide dependent fields
        const gaToggle = document.getElementById('googleAnalyticsEnable');
        const enabledFields = document.getElementById('analytics-enabled-fields');
        gaToggle?.addEventListener('change', function () {
            enabledFields.classList.toggle('d-none', !this.checked);
        });

        // Validate credentials
        validateBtn?.addEventListener('click', function () {
            if (!propertyIdInput.value || !credentialsInput.value) {
                toastr.warning('Completa el Property ID y las credenciales antes de validar.', 'Validación');
                return;
            }

            fetch('{{ route("settings.analytics.validate-credentials") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ property_id: propertyIdInput.value, credentials: credentialsInput.value })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status) {
                    toastr.success(data.message, 'Validación exitosa');
                } else {
                    toastr.error(data.message, 'Error de validación');
                }
            })
            .catch(() => toastr.error('Error al validar credenciales', 'Error'));
        });

        // Form submission
        analyticsForm?.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch(this.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new FormData(this)
            })
            .then(r => r.json())
            .then(data => {
                if (data.status) {
                    toastr.success(data.message, 'Guardado exitoso');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(data.message, 'Error al guardar');
                }
            })
            .catch(() => toastr.error('Error al guardar configuración', 'Error'));
        });
    })();
    </script>
    @endpush

@endsection
