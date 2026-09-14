@extends('layouts.theme')

@section('title', 'Credenciales del Endpoint')

@section('page_header')
    @include('core::components.card', ['title' => 'Credenciales del Endpoint'])
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Credenciales: {{ $endpoint->name }}</h4>
                        <small class="text-muted">{{ $endpoint->url }}</small>
                    </div>
                    <div>
                        <a href="{{ route('settings.erp.endpoints.credentials.create', $endpoint) }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Credencial
                        </a>
                        <a href="{{ route('settings.erp.endpoints.show', $endpoint) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver al Endpoint
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if($credentials->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo de Autenticación</th>
                                        <th>Usuario</th>
                                        <th>Estado</th>
                                        <th>Expira</th>
                                        <th>Último Uso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($credentials as $credential)
                                        <tr class="{{ $credential->is_active ? 'table-success' : '' }}">
                                            <td>
                                                <span class="badge bg-{{ $credential->is_active ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($credential->auth_type) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($credential->username)
                                                    {{ $credential->username }}
                                                @elseif($credential->api_key_header)
                                                    <code>{{ $credential->api_key_header }}</code>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <form action="{{ route('settings.erp.endpoints.credentials.toggle', [$endpoint, $credential]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-{{ $credential->is_active ? 'success' : 'secondary' }}">
                                                        <i class="fas fa-{{ $credential->is_active ? 'check-circle' : 'times-circle' }}"></i>
                                                        {{ $credential->is_active ? 'Activa' : 'Inactiva' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                @if($credential->expires_at)
                                                    @if($credential->expires_at->isPast())
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-exclamation-circle"></i> Expirada
                                                        </span>
                                                    @else
                                                        <span class="text-muted">{{ $credential->expires_at->diffForHumans() }}</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">Sin expiración</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($credential->last_used_at)
                                                    {{ $credential->last_used_at->diffForHumans() }}
                                                @else
                                                    <span class="text-muted">Nunca</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('settings.erp.endpoints.credentials.edit', [$endpoint, $credential]) }}" class="btn btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('settings.erp.endpoints.credentials.rotate', [$endpoint, $credential]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning" title="Rotar credencial">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('settings.erp.endpoints.credentials.destroy', [$endpoint, $credential]) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('¿Está seguro de eliminar esta credencial?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay credenciales configuradas para este endpoint.
                            <a href="{{ route('settings.erp.endpoints.credentials.create', $endpoint) }}">Crear la primera</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
