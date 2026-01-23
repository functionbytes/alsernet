@extends('layouts.theme')

@section('title', 'Administración de módulos')

@section('content')

    @include('core::components.card', ['title' => 'Administración de módulos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Módulos del sistema</h5>
                        <p class="small mb-0 text-muted">Administra, habilita, deshabilita e instala módulos del sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.modules.uploadForm') }}" class="btn btn-primary">
                            Instalar módulo
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $totalModules }}</h4>
                                        <small class="text-muted">Módulos instalados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Habilitados</h6>
                                        <h4 class="mb-1 fw-bold">{{ $enabledCount }}</h4>
                                        <small class="text-muted">Módulos activos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Deshabilitados</h6>
                                        <h4 class="mb-1 fw-bold">{{ $disabledCount }}</h4>
                                        <small class="text-muted">Módulos inactivos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.modules.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre, alias o descripción..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 200px;">
                            <select name="status" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="enabled" {{ request('status') == 'enabled' ? 'selected' : '' }}>Habilitados</option>
                                <option value="disabled" {{ request('status') == 'disabled' ? 'selected' : '' }}>Deshabilitados</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('settings.modules.index') }}"
                                   class="btn btn-outline-secondary"
                                   title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modules List -->
            <div class="card-body">
                @if(count($modules) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="25%">Módulo</th>
                                <th width="35%">Descripción</th>
                                <th width="10%">Versión</th>
                                <th width="15%" class="text-center">Estado</th>
                                <th width="15%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($modules as $module)
                                <tr>
                                    <td>
                                        <div>
                                            <a href="{{ route('settings.modules.show', $module['alias']) }}" class="text-decoration-none">
                                                <strong>{{ $module['name'] }}</strong>
                                            </a>
                                            <small class="d-block text-muted">{{ $module['alias'] }}</small>
                                            @if(in_array($module['name'], ['Role', 'Modules']))
                                                <small class="badge badge-sm bg-primary-subtle text-primary mt-0">Módulo protegido</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ Str::limit($module['description'] ?: 'Sin descripción', 80) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light-subtle text-dark">v{{ $module['version'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($module['enabled'])
                                            <span class="badge bg-success-subtle text-success">
                                                Activo
                                            </span>
                                        @else
                                            <span class="badge bg-light text-black">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.modules.show', $module['alias']) }}">
                                                        Ver detalles
                                                    </a>
                                                </li>
                                                @if(!in_array($module['name'], ['Role', 'Modules']))
                                                    <li><hr class="dropdown-divider"></li>
                                                    @if($module['enabled'])
                                                        <li>
                                                            <button type="button"
                                                                    class="dropdown-item text-warning disable-module-btn"
                                                                    data-module-name="{{ $module['name'] }}"
                                                                    data-module-alias="{{ $module['alias'] }}"
                                                                    data-action="{{ route('settings.modules.disable', $module['alias']) }}">
                                                                Deshabilitar
                                                            </button>
                                                        </li>
                                                    @else
                                                        <li>
                                                            <form action="{{ route('settings.modules.enable', $module['alias']) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    Habilitar
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item  uninstall-module-btn"
                                                                data-module-name="{{ $module['name'] }}"
                                                                data-module-alias="{{ $module['alias'] }}"
                                                                data-action="{{ route('settings.modules.uninstall', $module['alias']) }}">
                                                            Desinstalar
                                                        </button>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-cube fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('status'))
                                    No se encontraron módulos
                                @else
                                    No hay módulos instalados
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('status'))
                                    No se encontraron resultados con los criterios de búsqueda
                                @else
                                    Instala tu primer módulo para extender la funcionalidad del sistema
                                @endif
                            </p>
                            @if(!request('search') && !request('status'))
                                <a href="{{ route('settings.modules.uploadForm') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Instalar primer módulo
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal para deshabilitar módulo -->
    <div class="modal fade" id="disableModuleModal" tabindex="-1" aria-labelledby="disableModuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="disableModuleModalLabel">
                        Deshabilitar módulo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        ¿Estás seguro de que deseas deshabilitar el módulo <strong id="disable-module-name"></strong>?
                    </p>
                    <p class="text-muted small mb-0 mt-2">
                        El módulo dejará de estar disponible hasta que lo vuelvas a habilitar.
                    </p>
                </div>
                <div class="modal-footer border-top">

                    <form id="disable-module-form" method="POST" class="w-100">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Sí, deshabilitar
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para desinstalar módulo -->
    <div class="modal fade" id="uninstallModuleModal" tabindex="-1" aria-labelledby="uninstallModuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom bg-danger-subtle">
                    <h5 class="modal-title text-danger" id="uninstallModuleModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Desinstalar módulo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        ¿Estás seguro de que deseas desinstalar el módulo <strong id="uninstall-module-name"></strong>?
                    </p>
                    <div class="alert alert-danger mb-0" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Esta acción es irreversible.</strong> Se eliminarán todos los datos y configuraciones asociadas al módulo.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <form id="uninstall-module-form" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Sí, desinstalar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
            @endif

            @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
            @endif

            // Modal para deshabilitar módulo
            const disableModal = new bootstrap.Modal(document.getElementById('disableModuleModal'));

            $('.disable-module-btn').on('click', function() {
                const moduleName = $(this).data('module-name');
                const moduleAction = $(this).data('action');

                $('#disable-module-name').text(moduleName);
                $('#disable-module-form').attr('action', moduleAction);

                disableModal.show();
            });

            // Modal para desinstalar módulo
            const uninstallModal = new bootstrap.Modal(document.getElementById('uninstallModuleModal'));

            $('.uninstall-module-btn').on('click', function() {
                const moduleName = $(this).data('module-name');
                const moduleAction = $(this).data('action');

                $('#uninstall-module-name').text(moduleName);
                $('#uninstall-module-form').attr('action', moduleAction);

                uninstallModal.show();
            });
        });
    </script>
@endpush
