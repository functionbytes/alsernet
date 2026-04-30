@extends('layouts.theme')

@section('title', 'Editar: ' . $module['name'])

@section('page_header')
    @include('core::components.card', ['title' => 'Editar módulo'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">
                <form action="{{ route('settings.modules.update', $module['alias']) }}" method="POST" id="moduleForm">
                    @csrf
                    @method('PUT')

                    <div class="card">

                        {{-- Información del módulo --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Información del módulo</h6>
                            <p class="text-muted mb-3">Datos principales del módulo. El nombre y alias no pueden ser modificados.</p>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Nombre del módulo</label>
                                    <input type="text" class="form-control" value="{{ $module['name'] }}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Alias</label>
                                    <input type="text" class="form-control font-monospace" value="{{ $module['alias'] }}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Versión</label>
                                    <input type="text" class="form-control" value="v{{ $module['version'] }}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Estado</label>
                                    <input type="text" class="form-control" value="{{ $module['enabled'] ? 'Activo' : 'Inactivo' }}" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">
                                        Descripción
                                        <span class="text-muted fw-normal">(opcional)</span>
                                    </label>
                                    <textarea class="form-control" name="description" rows="3" maxlength="500"
                                              placeholder="Describe brevemente la funcionalidad de este módulo...">{{ old('description', $module['description']) }}</textarea>
                                    <small class="text-muted d-block mt-1">Máximo 500 caracteres.</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Configuración avanzada --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Configuración avanzada</h6>
                            <p class="text-muted mb-3">Controla el orden de carga del módulo en el sistema.</p>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">
                                        Prioridad de carga
                                        <span class="badge bg-primary-subtle text-primary ms-1">Importante</span>
                                    </label>
                                    <input type="number" class="form-control @error('priority') is-invalid @enderror"
                                           name="priority" value="{{ old('priority', $module['priority']) }}"
                                           min="0" max="999" required>
                                    <small class="text-muted d-block mt-1">Mayor número = se carga primero (rango: 0-999)</small>
                                    @error('priority')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Información técnica --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Información técnica</h6>
                            <p class="text-muted mb-3">Datos de solo lectura definidos en <code>module.json</code>.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Namespace</label>
                                    <input type="text" class="form-control font-monospace" value="{{ $module['namespace'] }}" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Ruta en el sistema</label>
                                    <input type="text" class="form-control font-monospace" value="{{ $module['path'] }}" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar cambios
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Ver detalles</h6>
                        <p class="text-muted mb-3">Consulta la información completa del módulo, incluyendo providers y keywords.</p>
                        <a href="{{ route('settings.modules.show', $module['alias']) }}" class="btn btn-outline-secondary w-100">
                            Ver módulo
                        </a>
                    </div>
                </div>

                @if(!in_array($module['name'], ['Role', 'Modules']))
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Estado del módulo</h6>
                            <p class="text-muted mb-3">
                                @if($module['enabled'])
                                    El módulo está activo. Puedes deshabilitarlo temporalmente sin perder la configuración.
                                @else
                                    El módulo está inactivo. Habilítalo para que funcione en el sistema.
                                @endif
                            </p>
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
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Desinstalar módulo</h6>
                            <p class="text-muted mb-3">Elimina el módulo permanentemente del sistema. Esta acción es irreversible.</p>
                            <button type="button" class="btn btn-outline-secondary w-100"
                                    data-bs-toggle="modal" data-bs-target="#uninstallModuleModal"
                                    data-action="{{ route('settings.modules.uninstall', $module['alias']) }}">
                                Desinstalar módulo
                            </button>
                        </div>
                    </div>
                @else
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-shield-alt me-1"></i>
                                Este módulo está protegido y no puede ser deshabilitado ni desinstalado.
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Volver a módulos</h6>
                        <p class="text-muted mb-3">Regresa al listado completo de módulos del sistema.</p>
                        <a href="{{ route('settings.modules.index') }}" class="btn btn-secondary w-100">
                            Ir a módulos
                        </a>
                    </div>
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
