@extends('layouts.theme')

@section('page_title', 'Credenciales ERP')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Credenciales ERP',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'ERP', 'url' => route('settings.erp.index')],
            ['label' => 'Credenciales', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Credenciales ERP</h5>
                        <p class="small mb-0 text-muted">Gestiona las credenciales de autenticación para endpoints</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.erp.index') }}" class="btn btn-secondary">
                            Volver a configuración
                        </a>
                        <a href="{{ route('settings.erp.credentials.create') }}" class="btn btn-primary">
                            Nueva credencial
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">¿Qué son las credenciales?</h6>
                            <p class="mb-0">
                                Las credenciales almacenan información de autenticación (Basic Auth, Bearer Token, API Key, etc.)
                                que pueden ser reutilizadas por múltiples endpoints. Esto centraliza la gestión de accesos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters Section --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.erp.credentials.index') }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="auth_type" class="form-control select2">
                                <option value="">Todos los tipos</option>
                                <option value="none" {{ request('auth_type') === 'none' ? 'selected' : '' }}>Sin autenticación</option>
                                <option value="basic" {{ request('auth_type') === 'basic' ? 'selected' : '' }}>Basic Auth</option>
                                <option value="bearer" {{ request('auth_type') === 'bearer' ? 'selected' : '' }}>Bearer Token</option>
                                <option value="api_key" {{ request('auth_type') === 'api_key' ? 'selected' : '' }}>API Key</option>
                                <option value="custom" {{ request('auth_type') === 'custom' ? 'selected' : '' }}>Custom Headers</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="is_active" class="form-control select2">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activas</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Credentials List --}}
            <div class="card-body p-0">
                @if($credentials->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nombre</th>
                                    <th>Tipo de autenticación</th>
                                    <th class="text-center">Endpoints</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Último uso</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($credentials as $credential)
                                    <tr>
                                        <td class="ps-4">
                                            <div>
                                                <a href="{{ route('settings.erp.credentials.show', $credential) }}" class="fw-bold text-dark text-decoration-none">
                                                    {{ $credential->name }}
                                                </a>
                                                @if($credential->description)
                                                    <div class="small text-muted">{{ Str::limit($credential->description, 60) }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
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
                                            <span class="badge bg-{{ $authType['class'] }}">{{ $authType['label'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $credential->endpoints_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($credential->is_active)
                                                @if($credential->isExpired())
                                                    <span class="badge bg-danger">Expirada</span>
                                                @else
                                                    <span class="badge bg-success">Activa</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-muted small">
                                            @if($credential->last_used_at)
                                                {{ $credential->last_used_at->diffForHumans() }}
                                            @else
                                                <span class="text-muted">Nunca</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                                                    Acciones
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="{{ route('settings.erp.credentials.show', $credential) }}" class="dropdown-item">
                                                            <i class="fa fa-eye me-2"></i> Ver detalles
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('settings.erp.credentials.edit', $credential) }}" class="dropdown-item">
                                                            <i class="fa fa-edit me-2"></i> Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('settings.erp.credentials.test', $credential) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="fa fa-plug me-2"></i> Probar credencial
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('settings.erp.credentials.toggle', $credential) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="fa fa-power-off me-2"></i>
                                                                {{ $credential->is_active ? 'Desactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('settings.erp.credentials.destroy', $credential) }}" method="POST" class="d-inline"
                                                              onsubmit="return confirm('¿Estás seguro de eliminar esta credencial?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fa fa-trash me-2"></i> Eliminar
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="card-footer border-top">
                        {{ $credentials->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fa fa-key fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay credenciales registradas</h5>
                        <p class="text-muted">Crea tu primera credencial para empezar</p>
                        <a href="{{ route('settings.erp.credentials.create') }}" class="btn btn-primary">
                            Nueva credencial
                        </a>
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection
