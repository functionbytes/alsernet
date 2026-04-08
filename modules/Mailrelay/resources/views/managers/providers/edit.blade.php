@extends('layouts.theme')

@section('title', 'Editar provider')

@section('content')
<div class="widget-content">

    @include('core::components.alerts')

    {{-- Breadcrumb --}}
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('managers.mailrelay.providers.index') }}">Providers</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar: {{ $provider->name }}</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('managers.mailrelay.providers.update', $provider->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- Main Form --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-4 border-bottom">
                        <h5 class="mb-0 fw-bold">Información del provider</h5>
                    </div>
                    <div class="card-body p-4">

                        {{-- Nombre --}}
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">Nombre del provider <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $provider->name) }}"
                                   placeholder="Ej: Mailrelay Producción" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Driver (Read-only) --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tipo de provider</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $driverInfo['name'] }}" readonly>
                                <span class="input-group-text">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                            </div>
                            <small class="text-muted">El tipo de provider no puede modificarse después de crearlo</small>
                        </div>

                        {{-- Credentials Section --}}
                        <h6 class="fw-bold mb-3 border-bottom pb-2">
                            <i class="fas fa-key me-2"></i>Credenciales
                        </h6>

                        {{-- Mailrelay Credentials --}}
                        @if($provider->driver === 'mailrelay')
                            <div class="mb-3">
                                <label class="form-label">API URL</label>
                                <input type="text" class="form-control" name="credentials[api_url]"
                                       value="{{ old('credentials.api_url', $provider->credentials['api_url'] ?? '') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API Key <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="credentials[api_key]"
                                       value="{{ old('credentials.api_key', $provider->credentials['api_key'] ?? '') }}">
                            </div>
                        @endif

                        {{-- Mailtrap Credentials --}}
                        @if($provider->driver === 'mailtrap')
                            <div class="mb-3">
                                <label class="form-label">API Token <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="credentials[api_token]"
                                       value="{{ old('credentials.api_token', $provider->credentials['api_token'] ?? '') }}">
                            </div>
                        @endif

                        {{-- SendGrid Credentials --}}
                        @if($provider->driver === 'sendgrid')
                            <div class="mb-3">
                                <label class="form-label">API Key <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="credentials[api_key]"
                                       value="{{ old('credentials.api_key', $provider->credentials['api_key'] ?? '') }}">
                            </div>
                        @endif

                        {{-- Common Email Fields --}}
                        <div class="mt-4">
                            <h6 class="fw-semibold mb-3">Configuración de remitente</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email remitente <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="credentials[from_email]"
                                           value="{{ old('credentials.from_email', $provider->credentials['from_email'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre remitente <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="credentials[from_name]"
                                           value="{{ old('credentials.from_name', $provider->credentials['from_name'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email de respuesta</label>
                                    <input type="email" class="form-control" name="credentials[reply_to]"
                                           value="{{ old('credentials.reply_to', $provider->credentials['reply_to'] ?? '') }}">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Connection Status --}}
                @if($provider->last_tested_at)
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Estado de la conexión</h6>
                        <div class="d-flex align-items-center">
                            @if($provider->connection_ok)
                                <i class="fas fa-check-circle text-success fs-4 me-3"></i>
                                <div>
                                    <div class="fw-semibold">Conexión exitosa</div>
                                    <small class="text-muted">Última prueba: {{ $provider->last_tested_at->diffForHumans() }}</small>
                                </div>
                            @else
                                <i class="fas fa-times-circle text-danger fs-4 me-3"></i>
                                <div>
                                    <div class="fw-semibold">Error de conexión</div>
                                    <small class="text-muted">Última prueba: {{ $provider->last_tested_at->diffForHumans() }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Configuración --}}
                <div class="card mb-3">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-semibold">Configuración</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label">Prioridad</label>
                            <input type="number" class="form-control" name="priority"
                                   min="0" max="100" value="{{ old('priority', $provider->priority) }}">
                            <small class="text-muted">Mayor prioridad = mayor probabilidad de uso (0-100)</small>
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="is_active" value="1" {{ old('is_active', $provider->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Provider activo
                            </label>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_default"
                                   id="is_default" value="1" {{ old('is_default', $provider->is_default) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_default">
                                Provider predeterminado
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                @if($provider->metadata)
                <div class="card mb-3">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-semibold">Metadata</h6>
                    </div>
                    <div class="card-body p-3">
                        @if(isset($provider->metadata['description']))
                            <p class="small mb-0">{{ $provider->metadata['description'] }}</p>
                        @endif
                        @if(isset($provider->metadata['use_cases']))
                            <div class="mt-2">
                                <small class="text-muted d-block mb-1">Casos de uso:</small>
                                @foreach($provider->metadata['use_cases'] as $useCase)
                                    <span class="badge bg-light text-dark me-1 mb-1">{{ $useCase }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="card">
                    <div class="card-body p-3">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            <i class="fas fa-save me-2"></i>Actualizar provider
                        </button>
                        <a href="{{ route('managers.mailrelay.providers.index') }}" class="btn btn-light w-100">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>

                {{-- Info --}}
                <div class="alert alert-light border mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        Las credenciales están encriptadas en la base de datos.
                    </small>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
