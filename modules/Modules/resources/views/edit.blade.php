@extends('layouts.theme')

@section('title', 'Editar: ' . $module['name'])

@section('content')

<div class="row">
    <div class="col-lg-8 offset-lg-2 d-flex align-items-stretch">
        <div class="card w-100">
            <div class="card-body">
                <div class="d-flex no-block align-items-center mb-4">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Editar {{ $module['name'] }}
                    </h5>
                    <a href="{{ route('settings.modules.show', $module['alias']) }}" class="btn btn-sm btn-outline-secondary ms-auto">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </a>
                </div>

                <p class="card-subtitle mb-4">
                    Actualiza la configuración del módulo {{ $module['name'] }}.
                </p>

                {{-- Success/Error Messages --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Error de validación:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Edit Form --}}
                <form action="{{ route('settings.modules.update', $module['alias']) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Module Information Section --}}
                    <div class="mb-4">
                        <h6 class="mb-3 fw-bold border-bottom pb-2">
                            Información del módulo
                        </h6>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre del módulo</label>
                                <input type="text" class="form-control bg-light" value="{{ $module['name'] }}" disabled>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>El nombre del módulo no puede ser modificado
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Alias</label>
                                <input type="text" class="form-control bg-light" value="{{ $module['alias'] }}" disabled>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>El alias no puede ser modificado
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Versión</label>
                                <input type="text" class="form-control bg-light" value="v{{ $module['version'] }}" disabled>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>Definido en module.json
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Descripción
                                    <span class="text-muted fw-normal">(opcional)</span>
                                </label>
                                <textarea class="form-control" name="description" rows="4" maxlength="500"
                                          placeholder="Describe brevemente la funcionalidad de este módulo...">{{ old('description', $module['description']) }}</textarea>
                                <div class="form-text">
                                    Máximo 500 caracteres. Esta descripción aparecerá en la lista de módulos.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Configuration Section --}}
                    <hr class="my-4">

                    <div class="mb-4">
                        <h6 class="mb-3 fw-bold border-bottom pb-2">
                            Configuración avanzada
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Prioridad de carga
                                    <span class="badge bg-primary-subtle text-primary ms-1">Importante</span>
                                </label>
                                <input type="number" class="form-control @error('priority') is-invalid @enderror"
                                       name="priority" value="{{ old('priority', $module['priority']) }}"
                                       min="0" max="999" required>
                                <div class="form-text">
                                    <i class="fas fa-arrow-up me-1"></i>Mayor número = se carga primero (Rango: 0-999)
                                </div>
                                @error('priority')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Estado actual</label>
                                <div class="p-3 bg-light rounded">
                                    @if($module['enabled'])
                                        <span class="badge bg-success-subtle text-success border border-success fs-6">
                                            <i class="fas fa-circle fa-2xs me-1"></i>Activo
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary fs-6">
                                            <i class="fas fa-circle fa-2xs me-1"></i>Inactivo
                                        </span>
                                    @endif
                                </div>
                                <div class="form-text">
                                    Para cambiar el estado, use los botones en la página de detalles
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Technical Information --}}
                    <hr class="my-4">

                    <div class="mb-4">
                        <h6 class="mb-3 fw-bold border-bottom pb-2">
                            Información técnica
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary ms-2">Solo lectura</span>
                        </h6>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-muted small">Namespace del módulo</label>
                                <div class="p-3 bg-light rounded">
                                    <code class="text-dark">{{ $module['namespace'] }}</code>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-muted small">Ruta en el sistema</label>
                                <div class="p-3 bg-light rounded text-break">
                                    <code class="text-dark">{{ $module['path'] }}</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-between align-items-center">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Guardar cambios
                            </button>
                            <a href="{{ route('settings.modules.show', $module['alias']) }}" class="btn btn-light border">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                        <a href="{{ route('settings.modules.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver a módulos
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
