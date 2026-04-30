@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-info-subtle">
                    <h5 class="mb-0"><i class="fab fa-instagram"></i> Seleccionar cuenta</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Selecciona la cuenta de Instagram que deseas conectar.
                    </p>

                    <form action="{{ route('settings.chat.channels.instagrams.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="control-label col-form-label">Seleccionar cuenta <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                @foreach($accounts as $account)
                                    <div class="col-12">
                                        <div class="card border">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="instagram_id"
                                                           id="account_{{ $account['id'] }}" value="{{ $account['id'] }}" required>
                                                    <label class="form-check-label w-100" for="account_{{ $account['id'] }}">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong>{{ $account['username'] ?? 'Desconocido' }}</strong>
                                                                @if(isset($account['followers_count']))
                                                                    <br><small class="text-muted">{{ number_format($account['followers_count']) }} seguidores</small>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <span class="badge bg-info-subtle text-info">Instagram</span>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="inbox_name" class="control-label col-form-label">Nombre de bandeja <span class="text-danger">*</span></label>
                            <input type="text" name="inbox_name" id="inbox_name" class="form-control @error('inbox_name') is-invalid @enderror"
                                   placeholder="ej., Soporte Instagram" value="{{ old('inbox_name') }}" required>
                            <div class="form-text">Este nombre se mostrará en tu lista de bandejas de entrada</div>
                            @error('inbox_name')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Conectar cuenta
                            </button>
                            <a href="{{ route('settings.chat.channels.instagrams.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info-subtle">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Siguientes pasos</h6>
                </div>
                <div class="card-body">
                    <ol class="small mb-0">
                        <li>Selecciona la cuenta de Instagram</li>
                        <li>Elige un nombre para la bandeja</li>
                        <li>Haz clic en "Conectar cuenta"</li>
                        <li>¡Comienza a recibir mensajes!</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
