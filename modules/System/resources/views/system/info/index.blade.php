@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Información del Sistema'])

    <div class="widget-content searchable-container list">
        <!-- System Information Card - Alsernet Green (#90bb13) -->
        <div class="card">
            <!-- Header Section - Modernize Card Header -->
            <div class="card-header p-4 border-bottom ">
                <div>
                    <!-- Header Text -->
                    <h5 class="mb-1 fw-bold">Información del Sistema</h5>
                    <p class="small mb-0">Panel completo que muestra la configuración técnica de tu servidor, incluyendo
                        versión de PHP, extensiones instaladas, paquetes Composer, drivers de base de datos y caché,
                        permisos de directorios y estado general del sistema. Esta información es esencial para
                        diagnosticar problemas y verificar compatibilidad.</p>
                </div>
            </div>

            <!-- Navigation Pills - User Profile Tab Style -->
            <ul class="nav nav-pills user-profile-tab" id="system-info-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                            id="environment-tab" data-bs-toggle="pill" data-bs-target="#environment" type="button"
                            role="tab" aria-controls="environment" aria-selected="true">
                        <i class="fa fa-home me-2"></i>
                        <span class="d-none d-md-block">Entorno</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="server-tab" data-bs-toggle="pill" data-bs-target="#server" type="button" role="tab"
                            aria-controls="server" aria-selected="false" tabindex="-1">
                        <i class="fa fa-server me-2"></i>
                        <span class="d-none d-md-block">Servidor</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="extensions-tab" data-bs-toggle="pill" data-bs-target="#extensions" type="button"
                            role="tab" aria-controls="extensions" aria-selected="false" tabindex="-1">
                        <i class="fa fa-puzzle-piece me-2"></i>
                        <span class="d-none d-md-block">Extensiones</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="packages-tab" data-bs-toggle="pill" data-bs-target="#packages" type="button" role="tab"
                            aria-controls="packages" aria-selected="false" tabindex="-1">
                        <i class="fa fa-box me-2"></i>
                        <span class="d-none d-md-block">Paquetes</span>
                    </button>
                </li>
            </ul>

            <!-- Tab content - Card Body Style -->
            <div class="card-body">
                <div class="tab-content" id="system-info-content">

                    <!-- Environment Tab -->
                    <div role="tabpanel" class="tab-pane fade active show" id="environment">
                        <div class="row g-4">
                            <!-- Application Information Card -->
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-2 mb-4">
                                            <h6 class="mb-0 fw-bold">Información de la aplicación</h6>
                                        </div>

                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Versión</small>
                                            <p class="mb-0 fw-500">{{ $environment['version'] ?? 'Desconocido' }}</p>
                                        </div>
                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Versión del Framework</small>
                                            <p class="mb-0 fw-500">{{ $environment['framework_version'] ?? 'Desconocido' }}</p>
                                        </div>
                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Zona Horaria</small>
                                            <p class="mb-0 fw-500">{{ $environment['timezone'] ?? 'Desconocido' }}</p>
                                        </div>
                                        <div class="system-info-item">
                                            <small class="text-muted d-block">IP del Servidor</small>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="server-ip">{{ $environment['server_ip'] ?? 'Desconocido' }}</span>
                                                <button class="btn btn-sm btn-outline-secondary"
                                                        onclick="copyToClipboard('server-ip')"
                                                        title="Copiar al portapapeles">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Directory Status Card -->
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-2 mb-4">
                                            <h6 class="mb-0 fw-bold">Estado de directorios</h6>
                                        </div>

                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Modo de Depuración</small>
                                            <div class="mt-2">
                                                @if($environment['debug_mode'])
                                                    <span class="badge bg-danger-subtle text-danger">
                                                    <span class="material-symbols-rounded">warning</span> Habilitado
                                                </span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success">
                                                    <span class="material-symbols-rounded">check_circle</span> Deshabilitado
                                                </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Storage Escribible</small>
                                            <div class="mt-2">
                                                @if($environment['storage_writable'])
                                                    <span class="badge bg-success-subtle text-success">
                                                    <span class="material-symbols-rounded">check_circle</span> Sí
                                                </span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">
                                                    <span class="material-symbols-rounded">cancel</span> No
                                                </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Cache Escribible</small>
                                            <div class="mt-2">
                                                @if($environment['cache_writable'])
                                                    <span class="badge bg-success-subtle text-success">
                                                    <span class="material-symbols-rounded">check_circle</span> Sí
                                                </span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">
                                                    <span class="material-symbols-rounded">cancel</span> No
                                                </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="system-info-item">
                                            <small class="text-muted d-block">Tamaño de la App</small>
                                            <p class="mb-0 fw-500">{{ $environment['app_size'] ?? 'Desconocido' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Server Tab -->
                    <div role="tabpanel" class="tab-pane fade" id="server">
                        <div class="row g-4">
                            <!-- Server Information Card -->
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-2 mb-4">
                                            <h6 class="mb-0 fw-bold">Información del servidor</h6>
                                        </div>

                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Versión PHP</small>
                                            <code class="d-block mt-2">{{ $server['php_version'] ?? 'Desconocido' }}</code>
                                        </div>
                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Límite de Memoria</small>
                                            <code class="d-block mt-2">{{ $server['memory_limit'] ?? 'Desconocido' }}</code>
                                        </div>
                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Tiempo Máximo de Ejecución</small>
                                            <code class="d-block mt-2">{{ $server['max_execution_time'] ?? 'Desconocido' }}
                                                s</code>
                                        </div>
                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Software del Servidor</small>
                                            <code class="d-block mt-2">{{ $server['server_software'] ?? 'Desconocido' }}</code>
                                        </div>
                                        <div class="system-info-item">
                                            <small class="text-muted d-block">Sistema Operativo</small>
                                            <code class="d-block mt-2">{{ $server['operating_system'] ?? 'Desconocido' }}</code>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuration Card -->
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-2 mb-4">
                                            <h6 class="mb-0 fw-bold">Configuración</h6>
                                        </div>

                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Base de datos</small>
                                            <div class="mt-2">
                                                <span class="badge bg-light-secondary text-light">{{ $server['database_driver'] ?? 'Desconocido' }}</span>
                                            </div>
                                        </div>
                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Driver de cache</small>
                                            <div class="mt-2">
                                                <span class="badge bg-light-secondary text-light">{{ $server['cache_driver'] ?? 'Desconocido' }}</span>
                                            </div>
                                        </div>
                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Driver de sesión</small>
                                            <div class="mt-2">
                                                <span class="badge bg-light-secondary text-light">{{ $server['session_driver'] ?? 'Desconocido' }}</span>
                                            </div>
                                        </div>
                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">Conexión de colas</small>
                                            <div class="mt-2">
                                                <span class="badge bg-light-secondary text-light">{{ $server['queue_connection'] ?? 'Desconocido' }}</span>
                                            </div>
                                        </div>
                                        <div class="system-info-item mb-3">
                                            <small class="text-muted d-block">SSL instalado</small>
                                            <div class="mt-2">
                                                @if($server['ssl_installed'])
                                                    <span class="badge bg-success text-success">
                                                    <i class="fa fa-symbols"></i> Sí
                                                </span>
                                                @else
                                                    <span class="badge bg-danger text-danger">
                                                    <i class="fa fa-circle"></i> No
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="system-info-item">
                                            <small class="text-muted d-block">URL fopen</small>
                                            <div class="mt-2">
                                                @if($server['url_fopen_enabled'])
                                                    <span class="badge bg-success-subtle text-success">
                                                    <span class="material-symbols-rounded">check_circle</span> Habilitado
                                                </span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">
                                                    <span class="material-symbols-rounded">cancel</span> Deshabilitado
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Extensions Tab -->
                    <div role="tabpanel" class="tab-pane fade" id="extensions">
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="fa fa-puzzle-piece text-success"></i>
                                <h6 class="mb-0 fw-bold">Estado de extensiones php</h6>
                            </div>
                            <p class="text-muted small mb-3">{{ count($extensions) }} extensiones detectadas</p>
                            <p class="text-muted small mb-0">Muestra el estado (habilitadas o deshabilitadas) de todas
                                las extensiones PHP instaladas en tu servidor. Estas extensiones son módulos que amplían
                                la funcionalidad de PHP y son necesarias para que Alsernet funcione correctamente.</p>
                        </div>

                        <div class="row g-3">
                            @foreach($extensions as $name => $enabled)
                                <div class="col-md-6 col-lg-4">
                                    <div class="card mb-1">
                                        <div class="card-body d-flex align-items-center justify-content-between p-3">
                                            <div>
                                                <h6 class="mb-0 fw-500">{{ $name }}</h6>
                                            </div>
                                            <div>
                                                @if($enabled)
                                                    <span class="badge bg-success-subtle text-success p-2">
                                                    <i class="fa fa-check"></i>
                                                </span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger p-2">
                                                    <i class="fa fa-times"></i>
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Packages Tab -->
                    <div role="tabpanel" class="tab-pane fade" id="packages">
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="fa fa-box text-info"></i>
                                <h6 class="mb-0 fw-bold">Paquetes instalados y versiones</h6>
                            </div>
                            <p class="text-muted small mb-0">Lista completa de todos los paquetes de Composer instalados
                                en tu proyecto, incluyendo sus versiones actuales y descripciones. Estos paquetes son
                                librerías PHP reutilizables que proporcionan funcionalidades adicionales a Alsernet.</p>
                        </div>

                        @if(empty($packages))
                            <div class="alert alert-info border-0 bg-info-subtle text-info">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-info-circle"></i>
                                    <span>No se encontraron paquetes de composer</span>
                                </div>
                            </div>
                        @else
                            <div class="search-box mb-4">
                                <div class="input-group">
                                <span class="input-group-text bg-light-secondary border-0">
                                    <i class="fa fa-search"></i>
                                </span>
                                    <input type="text" class="form-control border-start-0" id="packageSearch"
                                           placeholder="Buscar...">
                                </div>
                            </div>

                            <div class="card border-0 bg-light-subtle">
                                <div class="card-body p-0">
                                    <table class="table table-hover table-sm mb-0 packages-table" id="packagesTable">
                                        <thead>
                                        <tr>
                                            <th class="border-bottom-1 text-muted px-3 py-2">Paquete</th>
                                            <th class="border-bottom-1 text-muted px-3 py-2" style="width: 120px;">
                                                Versión
                                            </th>
                                            <th class="border-bottom-1 text-muted px-3 py-2">Descripción</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($packages as $vendor => $vendorPackages)
                                            @foreach($vendorPackages as $packageName => $package)
                                                <tr class="package-row">
                                                    <td class="px-3 py-2 align-middle">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fa fa-folder text-info"></i>
                                                            <span class="text-break">{{ $vendor }}/{{ $packageName }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-2 align-middle">
                                                        <code class="bg-light-secondary text-light px-2 py-1 package-version">{{ $package['version'] }}</code>
                                                    </td>
                                                    <td class="px-3 py-2 align-middle">
                                                        <small class="text-muted package-description"
                                                               title="{{ $package['description'] ?: '-' }}">
                                                            {{ \Illuminate\Support\Str::limit($package['description'] ?: '-', 60) }}
                                                        </small>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-3 text-muted">
                                <small>
                                    Mostrando {{ count(array_merge(...array_values($packages))) }} paquetes
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function copyToClipboard(elementId) {
                const text = document.getElementById(elementId).textContent;
                navigator.clipboard.writeText(text).then(() => {
                    // Show a brief notification
                    const btn = event.target.closest('button');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fa fa-check"></i>';
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                    }, 2000);
                });
            }

            // Package search functionality
            const packageSearch = document.getElementById('packageSearch');
            if (packageSearch) {
                packageSearch.addEventListener('keyup', function (e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const rows = document.querySelectorAll('.package-row');

                    rows.forEach(row => {
                        const packageName = row.textContent.toLowerCase();
                        row.style.display = packageName.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
        </script>
    @endpush

@endsection
