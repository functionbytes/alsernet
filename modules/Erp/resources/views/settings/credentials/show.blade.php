@extends('layouts.theme')

@section('title', $credential->name)

@section('content')

    @include('core::components.card', ['title' => $credential->name])

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            {{-- Basic Information --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Información básica</h5>
                    <p class="small mb-0 text-muted">Detalles generales de la credencial</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" class="form-control" value="{{ $credential->name }}" disabled>
                        </div>

                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Tipo de autenticación</label>
                            <div>
                                @php
                                    $authTypes = [
                                        'none' => ['label' => 'Sin autenticación', 'class' => 'secondary'],
                                        'basic' => ['label' => 'Basic Auth', 'class' => 'primary'],
                                        'bearer' => ['label' => 'Bearer Token', 'class' => 'info'],
                                        'api_key' => ['label' => 'API Key', 'class' => 'warning'],
                                        'custom' => ['label' => 'Custom Headers', 'class' => 'dark'],
                                    ];
                                    $authType = $authTypes[$credential->auth_type] ?? ['label' => $credential->auth_type, 'class' => 'secondary'];
                                @endphp
                                <span class="badge bg-{{ $authType['class'] }}-subtle text-{{ $authType['class'] }}">{{ $authType['label'] }}</span>
                            </div>
                        </div>

                        @if($credential->description)
                            <div class="col-sm-12">
                                <label class="form-label fw-semibold">Descripción</label>
                                <textarea class="form-control" rows="2" disabled>{{ $credential->description }}</textarea>
                            </div>
                        @endif

                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Estado</label>
                            <div>
                                @if($credential->is_active)
                                    @if($credential->isExpired())
                                        <span class="badge bg-danger-subtle text-danger">Expirada</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">Activa</span>
                                    @endif
                                @else
                                    <span class="badge bg-info-subtle text-info">Inactiva</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Creada</label>
                            <input type="text" class="form-control" value="{{ $credential->created_at->format('d/m/Y H:i') }}" disabled>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Authentication Details --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Detalles de autenticación</h5>
                    <p class="small mb-0 text-muted">Configuración específica del método de autenticación</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($credential->auth_type === 'basic')
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold">Usuario</label>
                                <input type="text" class="form-control" value="{{ $credential->username }}" disabled>
                            </div>

                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold">Contraseña</label>
                                <input type="text" class="form-control" value="••••••••" disabled>
                                <small class="form-text text-muted">(encriptada)</small>
                            </div>
                        @endif

                        @if($credential->auth_type === 'bearer')
                            <div class="col-sm-12">
                                <label class="form-label fw-semibold">Bearer Token</label>
                                <input type="text" class="form-control" value="••••••••" disabled>
                                <small class="form-text text-muted">(encriptado)</small>
                            </div>
                        @endif

                        @if($credential->auth_type === 'api_key')
                            <div class="col-sm-12 col-md-8">
                                <label class="form-label fw-semibold">API Key</label>
                                <input type="text" class="form-control" value="••••••••" disabled>
                                <small class="form-text text-muted">(encriptada)</small>
                            </div>

                            <div class="col-sm-12 col-md-4">
                                <label class="form-label fw-semibold">Nombre del header</label>
                                <input type="text" class="form-control" value="{{ $credential->api_key_header }}" disabled>
                            </div>
                        @endif

                        @if($credential->auth_type === 'custom' && $credential->custom_headers)
                            <div class="col-sm-12">
                                <label class="form-label fw-semibold">Custom Headers</label>
                                <textarea class="form-control" rows="4" disabled>{{ json_encode($credential->custom_headers, JSON_PRETTY_PRINT) }}</textarea>
                            </div>
                        @endif

                        @if($credential->auth_type === 'none')
                            <div class="col-sm-12">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Esta credencial no requiere autenticación. Los endpoints que la utilicen no enviarán headers de autenticación.
                                </div>
                            </div>
                        @endif

                        @if($credential->expires_at)
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold">Fecha de expiración</label>
                                <input type="text" class="form-control"
                                       value="{{ $credential->expires_at->format('d/m/Y H:i') }}"
                                       disabled>
                                @if($credential->isExpired())
                                    <small class="form-text text-danger">(expirada)</small>
                                @endif
                            </div>
                        @endif

                        @if($credential->last_used_at)
                            <div class="col-sm-12 col-md-6">
                                <label class="form-label fw-semibold">Último uso</label>
                                <input type="text" class="form-control" value="{{ $credential->last_used_at->diffForHumans() }}" disabled>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Endpoints using this credential --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Endpoints que usan esta credencial</h5>
                    <p class="small mb-0 text-muted">Lista de endpoints configurados con esta credencial</p>
                </div>
                <div class="card-body p-0">
                    @if($credential->endpoints && $credential->endpoints->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Nombre</th>
                                        <th>URL</th>
                                        <th class="text-center">Método</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($credential->endpoints as $endpoint)
                                        <tr>
                                            <td class="ps-4">
                                                <a href="{{ route('settings.erp.endpoints.show', $endpoint) }}" class="text-dark text-decoration-none fw-bold">
                                                    {{ $endpoint->name }}
                                                </a>
                                            </td>
                                            <td>
                                                <code class="small">{{ Str::limit($endpoint->url, 50) }}</code>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info-subtle text-info">{{ $endpoint->method }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($endpoint->is_active)
                                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                                @else
                                                    <span class="badge bg-info-subtle text-info">Inactivo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('settings.erp.endpoints.show', $endpoint) }}" class="btn btn-sm btn-light">
                                                    Ver detalles
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa fa-link-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay endpoints usando esta credencial</h5>
                            <p class="text-muted">Asigna esta credencial a un endpoint desde la configuración del endpoint</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Actions Card --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Acciones</h5>
                    <p class="small mb-0 text-muted">Operaciones disponibles</p>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <form action="{{ route('settings.erp.credentials.test', $credential) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                Probar credencial
                            </button>
                        </form>

                        <a href="{{ route('settings.erp.credentials.edit', $credential) }}" class="btn btn-primary w-100">
                            Editar
                        </a>

                        <form action="{{ route('settings.erp.credentials.toggle', $credential) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-{{ $credential->is_active ? 'warning' : 'success' }} w-100">
                                {{ $credential->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>

                        <hr class="my-2">

                        <form action="{{ route('settings.erp.credentials.destroy', $credential) }}" method="POST"
                              onsubmit="return confirm('¿Estás seguro de eliminar esta credencial? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100"
                                    @if($credential->endpoints && $credential->endpoints->count() > 0) disabled title="No se puede eliminar porque hay endpoints usando esta credencial" @endif>
                                Eliminar
                            </button>
                        </form>

                        @if($credential->endpoints && $credential->endpoints->count() > 0)
                            <div class="alert alert-warning mb-0 small mt-2">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                No se puede eliminar porque {{ $credential->endpoints->count() }} endpoint(s) la están usando
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Information Card --}}
            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-1 fw-bold">Información adicional</h5>
                    <p class="small mb-0 text-muted">Metadatos de la credencial</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ID</label>
                        <input type="text" class="form-control" value="#{{ $credential->id }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Última actualización</label>
                        <input type="text" class="form-control" value="{{ $credential->updated_at->format('d/m/Y H:i') }}" disabled>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Endpoints activos</label>
                        <input type="text" class="form-control"
                               value="{{ $credential->endpoints ? $credential->endpoints->where('is_active', true)->count() : 0 }} de {{ $credential->endpoints ? $credential->endpoints->count() : 0 }}"
                               disabled>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
