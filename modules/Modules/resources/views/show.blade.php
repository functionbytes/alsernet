@extends('layouts.theme')

@section('title', 'Módulo: ' . $module['name'])

@section('content')

    @include('core::components.card', ['title' => 'Detalle del módulo'])

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Columna izquierda: contenido principal --}}
        <div class="col-lg-8">

            <div class="card">

                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Información general</h6>
                    <p class="text-muted mb-3">Datos técnicos del módulo registrado en el sistema</p>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" class="form-control" value="{{ $module['name'] }}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Alias</label>
                            <input type="text" class="form-control font-monospace" value="{{ $module['alias'] }}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Versión</label>
                            <input type="text" class="form-control" value="{{ $module['version'] }}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Prioridad</label>
                            <input type="text" class="form-control" value="{{ $module['priority'] }}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Namespace</label>
                            <input type="text" class="form-control font-monospace" value="{{ $module['namespace'] }}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Estado</label>
                            <input type="text" class="form-control" value="{{ $module['enabled'] ? 'Activo' : 'Inactivo' }}" disabled>
                        </div>
                        @if($module['description'])
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción</label>
                                <textarea class="form-control" rows="2" disabled>{{ $module['description'] }}</textarea>
                            </div>
                        @endif
                    </div>
                </div>

                @if(!empty($module['providers']))
                    <hr class="my-0">

                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Service providers</h6>
                        <p class="text-muted mb-3">Clases registradas como proveedores de servicios del módulo</p>

                        <div class="d-flex flex-column gap-2">
                            @foreach($module['providers'] as $provider)
                                <input type="text" class="form-control font-monospace" value="{{ $provider }}" disabled>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($module['keywords']))
                    <hr class="my-0">

                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Keywords</h6>
                        <p class="text-muted mb-3">Etiquetas asociadas al módulo para clasificación y búsqueda</p>

                        <div class="d-flex flex-wrap gap-2">
                            @foreach($module['keywords'] as $keyword)
                                <span class="badge bg-primary-subtle text-primary border border-primary">{{ $keyword }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

        </div>

        {{-- Columna derecha: sidebar --}}
        <div class="col-lg-4">

            {{-- Resumen --}}
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Resumen</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Estado</span>
                        @if($module['enabled'])
                            <span class="badge bg-success-subtle text-success border border-success">
                                <i class="fas fa-circle fa-2xs me-1"></i>Activo
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary">
                                <i class="fas fa-circle fa-2xs me-1"></i>Inactivo
                            </span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Versión</span>
                        <span class="fw-bold">v{{ $module['version'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Prioridad</span>
                        <span class="fw-bold">{{ $module['priority'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Providers</span>
                        <span class="fw-bold">{{ count($module['providers'] ?? []) }}</span>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Acciones</h6>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('settings.modules.edit', $module['alias']) }}" class="btn btn-outline-secondary w-100">
                        Editar configuración
                    </a>

                    @if(!in_array($module['name'], ['Role', 'Modules']))
                        @if($module['enabled'])
                            <button type="button" class="btn btn-warning w-100"
                                    data-bs-toggle="modal" data-bs-target="#disableModuleModal"
                                    data-action="{{ route('settings.modules.disable', $module['alias']) }}">
                                Deshabilitar módulo
                            </button>
                        @else
                            <form action="{{ route('settings.modules.enable', $module['alias']) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">
                                    Habilitar módulo
                                </button>
                            </form>
                        @endif

                        <button type="button" class="btn btn-outline-secondary w-100"
                                data-bs-toggle="modal" data-bs-target="#uninstallModuleModal"
                                data-action="{{ route('settings.modules.uninstall', $module['alias']) }}">
                            Desinstalar módulo
                        </button>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-shield-alt me-1"></i>
                            Este módulo está protegido y no puede ser deshabilitado ni desinstalado.
                        </div>
                    @endif

                    <a href="{{ route('settings.modules.index') }}" class="btn btn-secondary w-100">
                        Volver a módulos
                    </a>
                </div>
            </div>

        </div>

    </div>

    <!-- Modal para deshabilitar módulo -->
    <div class="modal fade" id="disableModuleModal" tabindex="-1" aria-labelledby="disableModuleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="disableModuleModalLabel">Deshabilitar módulo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Estás seguro de que deseas deshabilitar el módulo <strong>{{ $module['name'] }}</strong>?</p>
                    <p class="text-muted mb-0 mt-2">El módulo dejará de estar disponible hasta que lo vuelvas a habilitar.</p>
                </div>
                <div class="modal-footer border-top">
                    <form id="disable-module-form" method="POST" class="w-100">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 mb-2">
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
                    <p class="mb-2">¿Estás seguro de que deseas desinstalar el módulo <strong>{{ $module['name'] }}</strong>?</p>
                    <div class="alert alert-danger mb-0" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Esta acción es irreversible.</strong> Se eliminarán todos los datos y configuraciones asociadas al módulo.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <form id="uninstall-module-form" method="POST" class="w-100">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Sí, desinstalar
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#disableModuleModal').on('show.bs.modal', function (e) {
        const action = $(e.relatedTarget).data('action');
        $('#disable-module-form').attr('action', action);
    });

    $('#uninstallModuleModal').on('show.bs.modal', function (e) {
        const action = $(e.relatedTarget).data('action');
        $('#uninstall-module-form').attr('action', action);
    });
});
</script>
@endpush
