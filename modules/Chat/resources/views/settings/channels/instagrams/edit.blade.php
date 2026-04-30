@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-info-subtle">
                    <h5 class="mb-0"><i class="fab fa-instagram"></i> Editar cuenta de Instagram</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.chat.channels.instagrams.update', $instagram) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="inbox_name" class="control-label col-form-label">Nombre de bandeja <span class="text-danger">*</span></label>
                            <input type="text" name="inbox_name" id="inbox_name" class="form-control @error('inbox_name') is-invalid @enderror"
                                   value="{{ old('inbox_name', $instagram->inbox->name ?? '') }}" required>
                            @error('inbox_name')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="control-label col-form-label">Información de la cuenta conectada</label>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <strong>Instagram ID:</strong><br>
                                            <code>{{ $instagram->instagram_id }}</code>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Username:</strong><br>
                                            {{ $instagram->username ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-2">El Instagram ID no puede ser modificado</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar
                            </button>
                            <a href="{{ route('settings.chat.channels.instagrams.show', $instagram) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-info-subtle">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Estado del token</h6>
                </div>
                <div class="card-body">
                    @if($instagram->isTokenExpired())
                        <span class="badge bg-danger-subtle d-block mb-2">Expirado</span>
                        <p class="small text-muted mb-0">El token ha expirado. Por favor, reautoriza tu cuenta de Instagram.</p>
                    @elseif($instagram->needsTokenRefresh())
                        <span class="badge bg-warning-subtle text-warning d-block mb-2">Por expirar</span>
                        <p class="small text-muted mb-0">Expira {{ $instagram->token_expires_at->diffForHumans() }}</p>
                    @else
                        <span class="badge bg-success-subtle text-success d-block mb-2">Activo</span>
                        <p class="small text-muted mb-0">Expira {{ $instagram->token_expires_at?->diffForHumans() ?? 'Desconocido' }}</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-warning-subtle">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Reautorización</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Si tus credenciales de Instagram son inválidas o han expirado, puedes reautorizar tu cuenta.
                    </p>

                    <form action="{{ route('settings.chat.channels.instagrams.reauthorize', $instagram) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-sync-alt"></i> Reautorizar cuenta
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
