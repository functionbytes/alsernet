@extends('layouts.admin')

@section('title', 'Editar Cuenta Social')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.accounts.index') }}">Cuentas Sociales</a></li>
                <li class="breadcrumb-item active">Editar</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="{{ $account->network->icon() }}"
                               style="color: {{ $account->network->color() }}"></i>
                            <h5 class="mb-0">Editar Cuenta: {{ $account->name }}</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.social.accounts.update', $account) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Network (Read-only) -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Red Social</label>
                                <div class="form-control bg-light">
                                    <i class="{{ $account->network->icon() }} me-2"
                                       style="color: {{ $account->network->color() }}"></i>
                                    {{ $account->network->label() }}
                                </div>
                                <small class="text-muted">La red social no puede ser modificada</small>
                            </div>

                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">
                                    Usuario/Nombre de página <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('username') is-invalid @enderror"
                                       id="username"
                                       name="username"
                                       value="{{ old('username', $account->username) }}"
                                       required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Display Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre para mostrar <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $account->name) }}"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    @foreach(\Modules\Social\Enums\AccountStatus::cases() as $statusOption)
                                        <div class="col-md-6">
                                            <input type="radio"
                                                   class="btn-check"
                                                   name="status"
                                                   id="status_{{ $statusOption->value }}"
                                                   value="{{ $statusOption->value }}"
                                                   {{ old('status', $account->status->value) == $statusOption->value ? 'checked' : '' }}
                                                   required>
                                            <label class="btn btn-outline-{{ $statusOption->color() }} w-100"
                                                   for="status_{{ $statusOption->value }}">
                                                <i class="{{ $statusOption->icon() }} me-1"></i>
                                                {{ $statusOption->label() }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('status')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Account Info -->
                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">Información de la Cuenta</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Token expira</small>
                                            @if($account->token_expiry)
                                                <span class="fw-semibold {{ $account->isTokenExpired() ? 'text-danger' : 'text-success' }}">
                                                    {{ $account->token_expiry->format('d/m/Y H:i') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Última sincronización</small>
                                            @if($account->last_sync_at)
                                                <span class="fw-semibold">
                                                    {{ $account->last_sync_at->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-muted">Nunca</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($account->needsReconnection())
                                        <div class="alert alert-warning mt-3 mb-0" role="alert">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Esta cuenta necesita ser reconectada. El token ha expirado o hay un error de conexión.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-between">
                                <a href="{{ route('admin.social.accounts.index') }}" class="btn btn-light">
                                    <i class="fas fa-arrow-left me-1"></i> Volver
                                </a>
                                <div class="d-flex gap-2">
                                    @if($account->needsReconnection())
                                        <a href="{{ route('admin.social.accounts.reconnect', $account) }}"
                                           class="btn btn-warning">
                                            <i class="fas fa-link me-1"></i> Reconectar
                                        </a>
                                    @endif
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
