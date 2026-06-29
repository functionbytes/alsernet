@extends('layouts.theme')

@section('title', 'Cuentas Sociales')

@section('content')

    <div class="widget-content searchable-container list">

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fab fa-facebook me-2"></i>Cuentas Sociales
                        </h5>
                        <p class="small mb-0 text-muted">Gestiona las cuentas conectadas para publicación en redes sociales</p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-plus me-1"></i> Conectar Cuenta
                            </button>
                            <ul class="dropdown-menu">
                                <li><h6 class="dropdown-header">Conectar con OAuth</h6></li>
                                <li><a class="dropdown-item" href="{{ route('admin.social.oauth.redirect', 'facebook') }}">
                                    <i class="fab fa-facebook me-2" style="color: #1877F2"></i> Facebook
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.social.oauth.redirect', 'instagram') }}">
                                    <i class="fab fa-instagram me-2" style="color: #E4405F"></i> Instagram
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.social.oauth.redirect', 'twitter') }}">
                                    <i class="fab fa-twitter me-2" style="color: #1DA1F2"></i> Twitter
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.social.oauth.redirect', 'linkedin') }}">
                                    <i class="fab fa-linkedin me-2" style="color: #0A66C2"></i> LinkedIn
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.social.accounts.create') }}">
                                    <i class="fas fa-pencil me-2"></i> Agregar Manualmente
                                </a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Accounts Grid -->
            <div class="card-body">
                @if($accounts->count() > 0)
                    <div class="row g-4">
                        @foreach($accounts as $account)
                            <div class="col-md-6 col-lg-4">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <!-- Network Badge -->
                                        <div class="d-flex align-items-start justify-content-between mb-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle p-2" style="background-color: {{ $account->network->color() }}15;">
                                                    <i class="{{ $account->network->icon() }} fs-5" style="color: {{ $account->network->color() }}"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $account->network->label() }}</h6>
                                                    <small class="text-muted">{{ '@' . $account->username }}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-{{ $account->status->color() }}">
                                                <i class="{{ $account->status->icon() }} me-1"></i>
                                                {{ $account->status->label() }}
                                            </span>
                                        </div>

                                        <!-- Account Info -->
                                        <div class="mb-3">
                                            <p class="mb-1 fw-semibold">{{ $account->name }}</p>
                                            @if($account->last_sync_at)
                                                <small class="text-muted">
                                                    <i class="fas fa-sync-alt me-1"></i>
                                                    Sincronizado {{ $account->last_sync_at->diffForHumans() }}
                                                </small>
                                            @endif
                                        </div>

                                        <!-- Actions -->
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('admin.social.accounts.edit', $account) }}"
                                               class="btn btn-sm btn-light flex-fill">
                                                <i class="fas fa-edit me-1"></i> Editar
                                            </a>

                                            @if($account->needsReconnection())
                                                <a href="{{ route('admin.social.accounts.reconnect', $account) }}"
                                                   class="btn btn-sm btn-warning flex-fill">
                                                    <i class="fas fa-link me-1"></i> Reconectar
                                                </a>
                                            @else
                                                <form action="{{ route('admin.social.accounts.sync', $account) }}"
                                                      method="POST" class="flex-fill">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-secondary w-100">
                                                        <i class="fas fa-sync-alt me-1"></i> Sincronizar
                                                    </button>
                                                </form>
                                            @endif

                                            <form action="{{ route('admin.social.accounts.destroy', $account) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar esta cuenta?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                        @if($account->last_error)
                                            <div class="mt-3">
                                                <div class="alert alert-danger py-2 px-3 mb-0" role="alert">
                                                    <small>
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        {{ $account->last_error }}
                                                    </small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fab fa-facebook fs-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay cuentas conectadas</h5>
                        <p class="text-muted mb-4">Conecta tu primera cuenta social para comenzar a publicar contenido</p>
                        <a href="{{ route('admin.social.accounts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Conectar Primera Cuenta
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
