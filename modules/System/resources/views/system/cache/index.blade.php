@extends('layouts.theme')

@section('content')

    <!-- Notifications Container -->
    <div id="notifications-container"
         style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;"></div>

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card border shadow-none w-100">

                <div class="card-body p-4">
                    <h4 class="card-title">Mantenimiento del Sistema</h4>
                    <p class="card-subtitle mb-4">
                        Gestiona la limpieza de caché, configuración y optimización de la aplicación para mantener el
                        mejor rendimiento del sistema.
                    </p>

                    <div>
                        <!-- Borrar cache del sistema -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                    <i class="fa fa-trash text-dark d-block fs-7" width="22" height="22"></i>
                                </div>
                                <div>
                                    <h5 class="fs-4 fw-semibold">Borrar cache del sistema</h5>
                                    <p class="mb-0">Ejecute cuando no vea cambios después de actualizar datos.</p>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache d-flex align-items-center justify-content-center"
                                    type="button" data-url="{{ route('settings.system.cache.clear-cache') }}"
                                    data-title="Cache del sistema" title="Ejecutar" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-play fa-lg"></i>
                            </button>
                        </div>

                        <!-- Actualizar vistas compiladas -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                    <i class="fa fa-eyetext-dark d-block fs-7" width="22" height="22"></i>
                                </div>
                                <div>
                                    <h5 class="fs-4 fw-semibold">Actualizar vistas compiladas</h5>
                                    <p class="mb-0">Limpiar vistas compiladas para mantenerlas actualizadas.</p>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache d-flex align-items-center justify-content-center"
                                    type="button"
                                    data-url="{{ route('settings.system.cache.clear-view-cache') }}"
                                    data-title="Vistas compiladas" title="Ejecutar" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-play fa-lg"></i>
                            </button>
                        </div>

                        <!-- Limpiar cache de configuración -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                    <i class="ti ti-settings text-dark d-block fs-7" width="22" height="22"></i>
                                </div>
                                <div>
                                    <h5 class="fs-4 fw-semibold">Limpiar cache de configuración</h5>
                                    <p class="mb-0">Actualizar caché cuando cambie algo en la configuración.</p>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache d-flex align-items-center justify-content-center"
                                    type="button"
                                    data-url="{{ route('settings.system.cache.clear-config-cache') }}"
                                    data-title="Cache de configuración" title="Ejecutar"
                                    style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-play fa-lg"></i>
                            </button>
                        </div>

                        <!-- Cachear configuración -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                    <i class="fa fa-database text-dark d-block fs-7" width="22" height="22"></i>
                                </div>
                                <div>
                                    <h5 class="fs-4 fw-semibold">Cachear configuración</h5>
                                    <p class="mb-0">Cachear configuración para mejorar el rendimiento.</p>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache d-flex align-items-center justify-content-center"
                                    type="button" data-url="{{ route('settings.system.cache.cache-config') }}"
                                    data-title="Caché de configuración" title="Ejecutar"
                                    style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-play fa-lg"></i>
                            </button>
                        </div>

                        <!-- Limpiar enrutamiento -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                    <i class="fa fa-route text-dark d-block fs-7" width="22" height="22"></i>
                                </div>
                                <div>
                                    <h5 class="fs-4 fw-semibold">Limpiar enrutamiento</h5>
                                    <p class="mb-0">Borrar enrutamiento de caché del sistema.</p>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache d-flex align-items-center justify-content-center"
                                    type="button"
                                    data-url="{{ route('settings.system.cache.clear-route-cache') }}"
                                    data-title="Enrutamiento" title="Ejecutar" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-play fa-lg"></i>
                            </button>
                        </div>

                        <!-- Limpiar optimización -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                    <i class="fa fa-wand-magic-sparkles text-dark d-block fs-7" width="22"
                                       height="22"></i>
                                </div>
                                <div>
                                    <h5 class="fs-4 fw-semibold">Limpiar optimización</h5>
                                    <p class="mb-0">Limpiar todos los archivos de optimización del sistema.</p>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache d-flex align-items-center justify-content-center"
                                    type="button"
                                    data-url="{{ route('settings.system.cache.clear-optimization') }}"
                                    data-title="Optimización" title="Ejecutar" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-play fa-lg"></i>
                            </button>
                        </div>

                        <!-- Composer dump-autoload -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                    <i class="fa fa-box text-dark d-block fs-7" width="22" height="22"></i>
                                </div>
                                <div>
                                    <h5 class="fs-4 fw-semibold">Composer dump-autoload</h5>
                                    <p class="mb-0">Regenerar autoload al agregar nuevas clases o cambios.</p>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm btn-clear-cache d-flex align-items-center justify-content-center"
                                    type="button"
                                    data-url="{{ route('settings.system.cache.composer-dump-autoload') }}"
                                    data-title="Composer dump-autoload" title="Ejecutar"
                                    style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-play fa-lg"></i>
                            </button>
                        </div>

                        <!-- Ejecutar todos los comandos -->
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                    <i class="fa fa-person-running text-dark d-block fs-7" width="22" height="22"></i>
                                </div>
                                <div>
                                    <h5 class="fs-4 fw-semibold">Ejecutar todos los comandos</h5>
                                    <p class="mb-0">Ejecuta automáticamente todos los comandos en secuencia.</p>
                                </div>
                            </div>
                            <button class="btn btn-light btn-sm d-flex align-items-center justify-content-center"
                                    type="button" id="execute-all-btn"
                                    data-url="{{ route('settings.system.cache.execute-all') }}"
                                    title="Ejecutar todo" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-play fa-lg"></i>
                            </button>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Handle individual button clicks
                document.querySelectorAll('.btn-clear-cache').forEach(button => {
                    button.addEventListener('click', function () {
                        executeCommand(
                            this.dataset.url,
                            this.dataset.title || 'Comando'
                        );
                    });
                });

                // Handle execute all button
                document.getElementById('execute-all-btn').addEventListener('click', function () {
                    executeAll(this.dataset.url);
                });

                function executeCommand(url, title) {
                    const btn = event.target.closest('button');
                    const originalContent = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa fa-spinner animate-spin"></i>';

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

                            showNotification(data, title);
                        })
                        .catch(error => {
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                            console.error('Error:', error);
                            showErrorNotification('Error: ' + error.message, title);
                        });
                }

                function executeAll(url) {
                    const btn = document.getElementById('execute-all-btn');
                    const originalContent = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa fa-spinner animate-spin"></i>';

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
                            showErrorNotification('Error: ' + error.message, 'Ejecutar todo');
                        });
                }

                function showNotification(data, title) {
                    const className = data.success ? 'alert-success' : 'alert-danger';
                    const icon = data.success ? '<i class="fa fa-check></i>' : '<i class="fa fa-circle-exclamation"></i>';

                    const alertHtml = `
            <div class="alert ${className} alert-dismissible fade show d-flex align-items-start gap-3" role="alert">
                <div class="flex-shrink-0 fs-5">${icon}</div>
                <div class="flex-grow-1">
                    <strong>${title}</strong>
                    <p class="mb-0">${data.message}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

                    const container = document.getElementById('notifications-container');
                    container.innerHTML = alertHtml + container.innerHTML;

                    // Auto-hide after 6 seconds
                    setTimeout(() => {
                        const alert = container.querySelector('.alert');
                        if (alert) {
                            alert.classList.remove('show');
                            setTimeout(() => alert.remove(), 150);
                        }
                    }, 6000);
                }

                function showErrorNotification(message, title) {
                    const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-3" role="alert">
                <div class="flex-shrink-0 fs-5"><i class="fa fa-circle-exclamation"></i></div>
                <div class="flex-grow-1">
                    <strong>${title}</strong>
                    <p class="mb-0">${message}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

                    const container = document.getElementById('notifications-container');
                    container.innerHTML = alertHtml + container.innerHTML;
                }

                function displayAllResults(data) {
                    const container = document.getElementById('notifications-container');

                    const mainIcon = data.success ? '<i class="fa fa-circle-check"></i>' : '<i class="fa fa-triangle-exclamation"></i>';
                    let html = `<div class="alert ${data.success ? 'alert-success' : 'alert-warning'} alert-dismissible fade show d-flex align-items-start gap-3" role="alert">
            <div class="flex-shrink-0 fs-5">${mainIcon}</div>
            <div class="flex-grow-1">
                <strong>${data.message}</strong>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;

                    if (data.results) {
                        html += '<div class="mt-4"><h6 class="fw-semibold mb-3">Detalle de Comandos</h6><div class="table-responsive"><table class="table table-hover table-sm"><thead class="table-light"><tr><th>Comando</th><th>Estado</th><th>Mensaje</th></tr></thead><tbody>';

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
                                '<span class="badge bg-success"><i class="fa fa-check></i> Éxito</span>' :
                                '<span class="badge bg-danger"><i class="fa fa-circle-exclamation"></i> Error</span>';

                            html += `<tr>
                    <td><strong>${commandNames[key] || key}</strong></td>
                    <td>${statusBadge}</td>
                    <td><small>${result.message}</small></td>
                </tr>`;
                        });

                        html += '</tbody></table></div></div>';
                    }

                    container.innerHTML = html + container.innerHTML;
                }
            });
        </script>
    @endpush

@endsection
