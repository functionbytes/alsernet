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

                        <form action="{{ route('settings.modules.uninstall', $module['alias']) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-info w-100"
                                    onclick="return confirm('¿Desinstalar {{ $module['name'] }}? Esta acción es irreversible.')">
                                Desinstalar módulo
                            </button>
                        </form>
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

@endsection
