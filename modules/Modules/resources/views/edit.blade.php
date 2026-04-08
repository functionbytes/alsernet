@extends('layouts.theme')

@section('title', 'Editar: ' . $module['name'])

@section('content')

    @include('core::components.card', ['title' => 'Editar módulo'])

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
                                <form action="{{ route('settings.modules.disable', $module['alias']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-info w-100"
                                            onclick="return confirm('¿Deshabilitar {{ $module['name'] }}?')">
                                        Deshabilitar módulo
                                    </button>
                                </form>
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
                            <form action="{{ route('settings.modules.uninstall', $module['alias']) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-info w-100"
                                        onclick="return confirm('¿Desinstalar {{ $module['name'] }}? Esta acción es irreversible.')">
                                    Desinstalar módulo
                                </button>
                            </form>
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

@endsection
