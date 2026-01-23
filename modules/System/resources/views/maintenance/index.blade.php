@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Mantenimiento del sistema'])

    @if ($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">

        <!-- Left Card: Maintenance Mode -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold text-dark">
                            Modo mantenimiento
                        </h6>
                        <span class="badge bg-primary">Sistema</span>
                    </div>
                </div>

                <div class="card-body pb-0">
                    <p class="text-muted mb-3">
                        Activa el modo mantenimiento para realizar actualizaciones o reparaciones sin que los usuarios
                        accedan al sistema.
                    </p>

                    <div class="alert alert-info alert-sm py-2 px-3 mb-4" role="alert">
                        <strong>¿Qué hace esto?</strong>
                    </div>

                    <ul class="list-unstyled ms-3 mb-4">
                        <li class="mb-2">
                            <strong>Bloqueo de acceso</strong>
                            <br>
                            <small class="text-muted">Los clientes verán una página de mantenimiento en lugar del
                                sistema.</small>
                        </li>
                        <li class="mb-2">
                            <strong>Llave secreta</strong>
                            <br>
                            <small class="text-muted">Genera una URL especial para que el personal autorizado pueda
                                acceder.</small>
                        </li>
                        <li class="mb-2">
                            <strong>Reversible</strong>
                            <br>
                            <small class="text-muted">Desactiva el modo mantenimiento cuando termines las
                                tareas.</small>
                        </li>
                    </ul>

                    <form id="formMaintenance" class="border-top pt-4">
                        @csrf

                        <!-- Toggle Switch -->
                        <div class="row align-items-center mb-4">
                            <div class="col-sm-9">
                                <h6 class="mb-1">Estado del modo mantenimiento</h6>
                                <p class="text-muted small mb-0">Activa o desactiva el modo mantenimiento</p>
                            </div>
                            <div class="col-sm-3 text-end">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode"
                                           id="maintenance_mode"
                                           @if(setting('maintenance_mode')=='true') checked @endif>
                                </div>
                            </div>
                        </div>

                        <!-- Secret Key Section (shown when enabled) -->
                        <div class="maintenance_mode @if(setting('maintenance_mode')=='false') d-none @endif">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Llave secreta de acceso</label>
                                <input type="text" id="maintenance_mode_value" name="maintenance_mode_value"
                                       value="{{ setting('maintenance_mode_value') ?? $secret }}" class="form-control"
                                       readonly>
                                <small class="text-muted">Usa esta clave para acceder al sistema durante el
                                    mantenimiento</small>
                            </div>

                            <div class="alert alert-warning alert-sm py-2 px-3" role="alert">
                                <p class="mb-2"><strong>¿Cómo utilizar la llave secreta?</strong></p>
                                <ol class="mb-0 ps-3 small">
                                    <li class="mb-1">La llave secreta permite acceder al sistema cuando está en modo
                                        mantenimiento.
                                    </li>
                                    <li class="mb-1">Accede usando esta URL: <br><code class="text-dark">{{ getUrl() }}
                                            /{{ setting('maintenance_mode_value') ?? $secret }}</code></li>
                                    <li class="mb-0">Comparte esta llave solo con personal autorizado.</li>
                                </ol>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-footer border-top">
                    <button type="submit" form="formMaintenance" class="btn btn-primary w-100">
                        Guardar configuración
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Card: Cache Operations -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold text-dark">
                            Limpieza de caché
                        </h6>
                        <span class="badge bg-black">Optimización</span>
                    </div>
                </div>

                <div class="card-body pb-0">
                    <p class="text-muted mb-3">
                        Ejecuta operaciones de limpieza y optimización para mantener el sistema funcionando
                        correctamente.
                    </p>

                    <div class="alert alert-info alert-sm py-2 px-3 mb-4" role="alert">
                        <strong>Operaciones disponibles</strong>
                    </div>

                    <div class="cache-operations">
                        <!-- Clear Cache -->
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-start gap-2">
                                <div class="text-bg-light-secondary rounded p-2 d-flex align-items-center justify-content-center"
                                     style="width: 36px; height: 36px;">
                                    <i class="fa fa-trash text-dark fs-6"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Cache del sistema</h6>
                                    <small class="text-muted">Ejecutar cuando no veas cambios después de
                                        actualizar</small>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache" type="button"
                                    data-url="{{ route('settings.system.cache.clear-cache') }}"
                                    data-title="Cache del sistema" title="Ejecutar">
                                <i class="fa-solid fa-play"></i>
                            </button>
                        </div>

                        <!-- Clear Views -->
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-start gap-2">
                                <div class="text-bg-light-secondary rounded p-2 d-flex align-items-center justify-content-center"
                                     style="width: 36px; height: 36px;">
                                    <i class="fa fa-eye text-dark fs-6"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Vistas compiladas</h6>
                                    <small class="text-muted">Limpiar vistas compiladas para mantenerlas
                                        actualizadas</small>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache" type="button"
                                    data-url="{{ route('settings.system.cache.clear-view-cache') }}"
                                    data-title="Vistas compiladas" title="Ejecutar">
                                <i class="fa-solid fa-play"></i>
                            </button>
                        </div>

                        <!-- Clear Config -->
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-start gap-2">
                                <div class="text-bg-light-secondary rounded p-2 d-flex align-items-center justify-content-center"
                                     style="width: 36px; height: 36px;">
                                    <i class="fa fa-gear text-dark fs-6"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Limpiar cache de configuración</h6>
                                    <small class="text-muted">Actualizar caché cuando cambies configuración</small>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache" type="button"
                                    data-url="{{ route('settings.system.cache.clear-config-cache') }}"
                                    data-title="Cache de configuración" title="Ejecutar">
                                <i class="fa-solid fa-play"></i>
                            </button>
                        </div>

                        <!-- Cache Config -->
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-start gap-2">
                                <div class="text-bg-light-secondary rounded p-2 d-flex align-items-center justify-content-center"
                                     style="width: 36px; height: 36px;">
                                    <i class="fa fa-database text-dark fs-6"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Cachear configuración</h6>
                                    <small class="text-muted">Cachear configuración para mejorar el rendimiento</small>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache" type="button"
                                    data-url="{{ route('settings.system.cache.cache-config') }}"
                                    data-title="Caché de configuración" title="Ejecutar">
                                <i class="fa-solid fa-play"></i>
                            </button>
                        </div>

                        <!-- Clear Routes -->
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-start gap-2">
                                <div class="text-bg-light-secondary rounded p-2 d-flex align-items-center justify-content-center"
                                     style="width: 36px; height: 36px;">
                                    <i class="fa fa-route text-dark fs-6"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Limpiar cache de rutas</h6>
                                    <small class="text-muted">Borrar enrutamiento de caché del sistema</small>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache" type="button"
                                    data-url="{{ route('settings.system.cache.clear-route-cache') }}"
                                    data-title="Enrutamiento" title="Ejecutar">
                                <i class="fa-solid fa-play"></i>
                            </button>
                        </div>

                        <!-- Clear Optimization -->
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-start gap-2">
                                <div class="text-bg-light-secondary rounded p-2 d-flex align-items-center justify-content-center"
                                     style="width: 36px; height: 36px;">
                                    <i class="fa fa-wand-magic-sparkles text-dark fs-6"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Limpiar optimización</h6>
                                    <small class="text-muted">Limpiar todos los archivos de optimización</small>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache" type="button"
                                    data-url="{{ route('settings.system.cache.clear-optimization') }}"
                                    data-title="Optimización" title="Ejecutar">
                                <i class="fa-solid fa-play"></i>
                            </button>
                        </div>

                        <!-- Composer Dump Autoload -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-start gap-2">
                                <div class="text-bg-light-secondary rounded p-2 d-flex align-items-center justify-content-center"
                                     style="width: 36px; height: 36px;">
                                    <i class="fa fa-box text-dark fs-6"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Composer dump-autoload</h6>
                                    <small class="text-muted">Regenerar autoload al agregar nuevas clases o
                                        cambios</small>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache" type="button"
                                    data-url="{{ route('settings.system.cache.composer-dump-autoload') }}"
                                    data-title="Composer dump-autoload" title="Ejecutar">
                                <i class="fa-solid fa-play"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-footer border-top">
                    <button class="btn btn-primary w-100" type="button" id="execute-all-btn"
                            data-url="{{ route('settings.system.cache.execute-all') }}">
                        Ejecutar todas las operaciones
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Results Area for Execute All -->
    <div id="execute-all-results" class="mt-3" style="display: none;"></div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Toastr configuration
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-bottom-right"
                };

                // ========== Maintenance Mode Form ==========
                const maintenanceToggle = document.getElementById('maintenance_mode');
                const maintenanceModeDiv = document.querySelector('.maintenance_mode');
                const maintenanceForm = document.getElementById('formMaintenance');

                // Toggle secret key section
                if (maintenanceToggle) {
                    maintenanceToggle.addEventListener('change', function () {
                        if (this.checked) {
                            maintenanceModeDiv.classList.remove('d-none');
                        } else {
                            maintenanceModeDiv.classList.add('d-none');
                        }
                    });
                }

                // Handle maintenance form submission
                if (maintenanceForm) {
                    maintenanceForm.addEventListener('submit', function (e) {
                        e.preventDefault();

                        const formData = new FormData(this);
                        const maintenanceMode = document.getElementById('maintenance_mode').checked;
                        const maintenanceModeValue = document.getElementById('maintenance_mode_value').value;

                        formData.set('maintenance_mode', maintenanceMode ? 'true' : 'false');
                        formData.set('maintenance_mode_value', maintenanceModeValue);

                        const submitButton = this.querySelector('button[type="submit"]');
                        const originalContent = submitButton.innerHTML;
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Guardando...';

                        fetch('{{ route('settings.system.maintenance.update') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalContent;

                                if (data.success) {
                                    toastr.success(data.message, 'Modo Mantenimiento');

                                    setTimeout(() => {
                                        window.location.href = '{{ route('manager.dashboard') }}';
                                    }, 2000);
                                } else {
                                    toastr.error(data.message || 'Error al guardar la configuración', 'Modo Mantenimiento');
                                }
                            })
                            .catch(error => {
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalContent;
                                console.error('Error:', error);
                                toastr.error('Error: ' + error.message, 'Modo Mantenimiento');
                            });
                    });
                }

                // ========== Cache Operations ==========
                // Handle individual cache button clicks
                document.querySelectorAll('.btn-clear-cache').forEach(button => {
                    button.addEventListener('click', function () {
                        executeCommand(
                            this.dataset.url,
                            this.dataset.title || 'Comando'
                        );
                    });
                });

                // Handle execute all button
                const executeAllBtn = document.getElementById('execute-all-btn');
                if (executeAllBtn) {
                    executeAllBtn.addEventListener('click', function () {
                        executeAll(this.dataset.url);
                    });
                }

                function executeCommand(url, title) {
                    const btn = event.target.closest('button');
                    const originalContent = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            btn.disabled = false;
                            btn.innerHTML = originalContent;

                            if (data.success) {
                                toastr.success(data.message, title);
                            } else {
                                toastr.error(data.message, title);
                            }
                        })
                        .catch(error => {
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                            console.error('Error:', error);
                            toastr.error('Error: ' + error.message, title);
                        });
                }

                function executeAll(url) {
                    const btn = document.getElementById('execute-all-btn');
                    const originalContent = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Ejecutando...';

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                            displayAllResults(data);
                        })
                        .catch(error => {
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                            console.error('Error:', error);
                            toastr.error('Error: ' + error.message, 'Ejecutar todo');
                        });
                }

                // ========== Display Results for Execute All ==========
                function displayAllResults(data) {
                    // Show main message with toastr
                    if (data.success) {
                        toastr.success(data.message, 'Operación completada');
                    } else {
                        toastr.warning(data.message, 'Operación completada con advertencias');
                    }

                    // Display detailed results in a table
                    if (data.results) {
                        const resultsContainer = document.getElementById('execute-all-results');

                        let html = '<div class="card"><div class="card-body">';
                        html += '<h6 class="fw-semibold mb-3">Detalle de comandos</h6>';
                        html += '<div class="table-responsive">';
                        html += '<table class="table table-hover table-sm mb-0">';
                        html += '<thead class="table-light"><tr><th>Comando</th><th>Estado</th><th>Mensaje</th></tr></thead>';
                        html += '<tbody>';

                        const commandNames = {
                            'composer_dump_autoload': 'Composer dump-autoload',
                            'cache_clear': 'Limpiar cache',
                            'config_cache': 'Caché de configuración',
                            'config_clear': 'Limpiar configuración',
                            'route_clear': 'Limpiar rutas',
                            'view_clear': 'Limpiar vistas',
                            'optimize_clear': 'Limpiar optimización'
                        };

                        Object.entries(data.results).forEach(([key, result]) => {
                            const statusBadge = result.success ?
                                '<span class="badge bg-success"><i class="fa fa-check"></i> Éxito</span>' :
                                '<span class="badge bg-danger"><i class="fa fa-circle-exclamation"></i> Error</span>';

                            html += `<tr>
                    <td><strong>${commandNames[key] || key}</strong></td>
                    <td>${statusBadge}</td>
                    <td><small>${result.message}</small></td>
                </tr>`;
                        });

                        html += '</tbody></table></div></div></div>';

                        resultsContainer.innerHTML = html;
                        resultsContainer.style.display = 'block';
                    }
                }
            });
        </script>
    @endpush

@endsection
