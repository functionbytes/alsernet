@extends('layouts.theme')

@section('title', 'Tokens API')

@section('page_header')
    @include('core::components.card', ['title' => 'Tokens API'])
@endsection

@section('content')

@include('core::components.alerts')

@if(session('plain_token'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Tu nuevo token (cópialo ahora):</strong>
        <code class="user-select-all">{{ session('plain_token') }}</code>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Tokens API personales</h5>
                <p class="small mb-0 text-muted">Tokens para autenticar peticiones a la API en tu nombre.</p>
            </div>
        </div>
    </div>

    <div class="card-body">
        <form action="{{ route('settings.helpdesk.profile.tokens.store') }}" method="POST" class="row g-2 mb-4">
            @csrf
            <div class="col-md-8">
                <input type="text" name="name" class="form-control" placeholder="Nombre del token (ej. Mobile app)" required maxlength="80">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold mb-1">Permisos del token</label>
                <div class="d-flex flex-wrap gap-3">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="abilities[]" value="helpdesk.read" checked>
                        <span class="form-check-label">Lectura</span>
                    </label>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="abilities[]" value="helpdesk.write">
                        <span class="form-check-label">Escritura</span>
                    </label>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="abilities[]" value="helpdesk.manage">
                        <span class="form-check-label">Administración</span>
                    </label>
                </div>
                <small class="text-muted">Usa el mínimo necesario. Lectura permite GET; escritura permite crear o modificar datos.</small>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    Generar token
                </button>
            </div>
        </form>

        @if($tokens->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="fas fa-key fa-2x mb-2"></i>
                <p class="mb-0">No tienes tokens activos.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Permisos</th>
                            <th scope="col">Último uso</th>
                            <th scope="col">Creado</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tokens as $token)
                            <tr>
                                <td class="fw-semibold">{{ $token->name }}</td>
                                <td>
                                    @foreach(($token->abilities ?? []) as $ability)
                                        <span class="badge bg-light text-dark border me-1">{{ $ability }}</span>
                                    @endforeach
                                </td>
                                <td><small class="text-muted">{{ $token->last_used_at?->diffForHumans() ?? 'Nunca' }}</small></td>
                                <td><small class="text-muted">{{ $token->created_at->diffForHumans() }}</small></td>
                                <td class="text-end">
                                    <form action="{{ route('settings.helpdesk.profile.tokens.destroy', $token->id) }}" method="POST" class="d-inline needs-confirm" data-confirm-msg="¿Revocar este token? Las peticiones que lo usen dejarán de funcionar.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Revocar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
