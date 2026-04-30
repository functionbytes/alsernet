@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-info-subtle">
                    <h5 class="mb-0"><i class="fab fa-facebook"></i> Seleccionar página</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Selecciona la página de Facebook que deseas conectar a esta bandeja de entrada.
                    </p>

                    <form action="{{ route('settings.chat.channels.facebook-pages.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="inbox_name" class="control-label col-form-label">Nombre de bandeja <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                                   id="inbox_name" name="inbox_name" value="{{ old('inbox_name') }}"
                                   placeholder="ej., Soporte al cliente" required>
                            <div class="form-text">Elige un nombre para identificar esta bandeja en tu panel.</div>
                            @error('inbox_name')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="control-label col-form-label">Seleccionar página de Facebook <span class="text-danger">*</span></label>

                            @foreach($pages as $page)
                                <div class="card mb-2 border">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="page_id"
                                                   id="page_{{ $page['id'] }}" value="{{ $page['id'] }}" required>
                                            <label class="form-check-label w-100" for="page_{{ $page['id'] }}">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $page['name'] }}</strong><br>
                                                        <small class="text-muted">ID: {{ $page['id'] }}</small>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-primary-subtle text-primary">{{ $page['category'] ?? 'Página' }}</span>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('settings.chat.channels.facebook-pages.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Conectar página
                            </button>
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
                        <li>Elige un nombre para la bandeja</li>
                        <li>Selecciona la página de Facebook a conectar</li>
                        <li>Haz clic en "Conectar página"</li>
                        <li>Configura el webhook en Facebook Developer Console</li>
                        <li>¡Comienza a recibir mensajes!</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
