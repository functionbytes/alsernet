@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Información del Sistema'])

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">Información del sistema</h5>
                <p class="small mb-0 text-muted">Panel completo que muestra la configuración técnica de tu servidor, incluyendo versión de PHP, extensiones instaladas, paquetes Composer, drivers de base de datos y caché, permisos de directorios y estado general del sistema.</p>
            </div>

            <ul class="nav nav-tabs border-0 user-profile-tab" id="system-info-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                            id="environment-tab" data-bs-toggle="pill" data-bs-target="#environment"
                            type="button" role="tab" aria-controls="environment" aria-selected="true">
                        <span class="d-none d-md-block">Entorno</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="server-tab" data-bs-toggle="pill" data-bs-target="#server"
                            type="button" role="tab" aria-controls="server" aria-selected="false">
                        <span class="d-none d-md-block">Servidor</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="extensions-tab" data-bs-toggle="pill" data-bs-target="#extensions"
                            type="button" role="tab" aria-controls="extensions" aria-selected="false">
                        <span class="d-none d-md-block">Extensiones</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="packages-tab" data-bs-toggle="pill" data-bs-target="#packages"
                            type="button" role="tab" aria-controls="packages" aria-selected="false">
                        <span class="d-none d-md-block">Paquetes</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content p-4" id="system-info-content">

                {{-- Entorno --}}
                <div role="tabpanel" class="tab-pane fade active show" id="environment">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Información de la aplicación</h6>
                            <p class="text-muted mb-3">Datos del entorno de ejecución de la aplicación.</p>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Versión</small>
                                    <p class="mb-0 fw-semibold">{{ $environment['version'] ?? 'Desconocido' }}</p>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Versión del Framework</small>
                                    <p class="mb-0 fw-semibold">{{ $environment['framework_version'] ?? 'Desconocido' }}</p>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Zona Horaria</small>
                                    <p class="mb-0 fw-semibold">{{ $environment['timezone'] ?? 'Desconocido' }}</p>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">IP del Servidor</small>
                                    <div class="d-flex align-items-center gap-2">
                                        <span id="server-ip" class="fw-semibold">{{ $environment['server_ip'] ?? 'Desconocido' }}</span>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('server-ip')" title="Copiar">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Estado de directorios</h6>
                            <p class="text-muted mb-3">Permisos y estado de los directorios del sistema.</p>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">Modo de depuración</small>
                                    @if($environment['debug_mode'])
                                        <span class="badge bg-warning-subtle text-warning">Habilitado</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">Deshabilitado</span>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">Storage escribible</small>
                                    @if($environment['storage_writable'])
                                        <span class="badge bg-success-subtle text-success">Sí</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">No</span>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">Cache escribible</small>
                                    @if($environment['cache_writable'])
                                        <span class="badge bg-success-subtle text-success">Sí</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">No</span>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">Tamaño de la app</small>
                                    <p class="mb-0 fw-semibold">{{ $environment['app_size'] ?? 'Desconocido' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Servidor --}}
                <div role="tabpanel" class="tab-pane fade" id="server">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Información del servidor</h6>
                            <p class="text-muted mb-3">Especificaciones técnicas del servidor web.</p>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Versión PHP</small>
                                    <code>{{ $server['php_version'] ?? 'Desconocido' }}</code>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Límite de memoria</small>
                                    <code>{{ $server['memory_limit'] ?? 'Desconocido' }}</code>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Tiempo máx. de ejecución</small>
                                    <code>{{ $server['max_execution_time'] ?? 'Desconocido' }}s</code>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Software del servidor</small>
                                    <code>{{ $server['server_software'] ?? 'Desconocido' }}</code>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Sistema operativo</small>
                                    <code>{{ $server['operating_system'] ?? 'Desconocido' }}</code>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Configuración</h6>
                            <p class="text-muted mb-3">Drivers y conexiones configuradas en la aplicación.</p>
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <small class="text-muted d-block mb-1">Base de datos</small>
                                    <span class="badge bg-light-secondary text-dark">{{ $server['database_driver'] ?? 'Desconocido' }}</span>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block mb-1">Driver de caché</small>
                                    <span class="badge bg-light-secondary text-dark">{{ $server['cache_driver'] ?? 'Desconocido' }}</span>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block mb-1">Driver de sesión</small>
                                    <span class="badge bg-light-secondary text-dark">{{ $server['session_driver'] ?? 'Desconocido' }}</span>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block mb-1">Conexión de colas</small>
                                    <span class="badge bg-light-secondary text-dark">{{ $server['queue_connection'] ?? 'Desconocido' }}</span>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block mb-1">SSL instalado</small>
                                    @if($server['ssl_installed'])
                                        <span class="badge bg-success-subtle text-success">Sí</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">No</span>
                                    @endif
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block mb-1">URL fopen</small>
                                    @if($server['url_fopen_enabled'])
                                        <span class="badge bg-success-subtle text-success">Habilitado</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Deshabilitado</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Extensiones --}}
                <div role="tabpanel" class="tab-pane fade" id="extensions">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Estado de extensiones PHP</h6>
                            <p class="text-muted mb-3">{{ count($extensions) }} extensiones detectadas en el servidor.</p>

                            <div class="row g-2">
                                @foreach($extensions as $name => $enabled)
                                    <div class="col-md-4 col-lg-3">
                                        <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2">
                                            <span class="small fw-medium">{{ $name }}</span>
                                            @if($enabled)
                                                <span class="badge bg-success-subtle text-success"><i class="fas fa-check"></i></span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger"><i class="fas fa-times"></i></span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Paquetes --}}
                <div role="tabpanel" class="tab-pane fade" id="packages">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Paquetes instalados</h6>
                            <p class="text-muted mb-3">Lista completa de paquetes Composer con sus versiones y descripciones.</p>

                            @if(empty($packages))
                                <div class="alert alert-info border-0 mb-0">
                                    <i class="fas fa-info-circle me-1"></i> No se encontraron paquetes de Composer.
                                </div>
                            @else
                                <div class="input-group mb-3" >
                                    <span class="input-group-text bg-white border-end-1">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="packageSearch" placeholder="Buscar paquete...">
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="packagesTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Paquete</th>
                                                <th style="width:120px">Versión</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($packages as $vendor => $vendorPackages)
                                                @foreach($vendorPackages as $packageName => $package)
                                                    <tr class="package-row">
                                                        <td>{{ $vendor }}/{{ $packageName }}</td>
                                                        <td><code class="package-version">{{ $package['version'] }}</code></td>
                                                        <td><small class="text-muted package-description">{{ \Illuminate\Support\Str::limit($package['description'] ?: '—', 80) }}</small></td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-3">{{ count(array_merge(...array_values($packages))) }} paquetes instalados</small>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(elementId) {
            var text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text).then(function () {
                var btn = event.target.closest('button');
                var orig = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(function () { btn.innerHTML = orig; }, 2000);
            });
        }

        var packageSearch = document.getElementById('packageSearch');
        if (packageSearch) {
            packageSearch.addEventListener('keyup', function () {
                var q = this.value.toLowerCase();
                document.querySelectorAll('.package-row').forEach(function (row) {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }
    </script>
    @endpush

@endsection
