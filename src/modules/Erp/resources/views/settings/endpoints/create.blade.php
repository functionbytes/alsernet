@extends('layouts.theme')

@section('title', 'Crear Endpoint ERP')

@section('content')

    <div class="row">
        <div class="col-lg-8">
            <div class="card w-100">

        <form method="POST" action="{{ route('settings.erp.endpoints.store') }}">

            @csrf

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear nuevo endpoint ERP</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Configura un nuevo endpoint de integración con servicios externos.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y la descripción del endpoint.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required placeholder="Ej: API Gestión Central">
                            <small class="form-text text-muted">Nombre identificativo del endpoint</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Slug</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug') }}" placeholder="api-gestion-central">
                            <small class="form-text text-muted">Se genera automáticamente si se deja vacío</small>
                            @error('slug')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                      rows="2" placeholder="Descripción opcional del endpoint">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este endpoint</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Connection Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de conexión</h6>
                        <p class="text-muted small mb-3">Especifica la URL y el método HTTP para las peticiones.</p>
                    </div>

                    <div class="col-12 col-md-8">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                URL
                                <span class="text-danger">*</span>
                            </label>
                            <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                                   value="{{ old('url') }}" required placeholder="https://api.example.com/endpoint">
                            <small class="form-text text-muted">URL completa del endpoint (incluye https:// o http://)</small>
                            @error('url')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Método HTTP
                                <span class="text-danger">*</span>
                            </label>
                            <select name="method" class="form-control select2 @error('method') is-invalid @enderror" required>
                                <option value="GET" {{ old('method') === 'GET' ? 'selected' : '' }}>GET</option>
                                <option value="POST" {{ old('method', 'POST') === 'POST' ? 'selected' : '' }}>POST</option>
                                <option value="PUT" {{ old('method') === 'PUT' ? 'selected' : '' }}>PUT</option>
                                <option value="PATCH" {{ old('method') === 'PATCH' ? 'selected' : '' }}>PATCH</option>
                                <option value="DELETE" {{ old('method') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                            </select>
                            @error('method')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Content-Type</label>
                            <input type="text" name="content_type" class="form-control @error('content_type') is-invalid @enderror"
                                   value="{{ old('content_type', 'application/json') }}" placeholder="application/json">
                            <small class="form-text text-muted">Tipo de contenido de las peticiones (ej: application/json, application/xml)</small>
                            @error('content_type')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Authentication -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Autenticación</h6>
                        <p class="text-muted small mb-3">Selecciona una credencial del pool global o configura autenticación manual.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Credencial</label>
                            <select name="credential_id" class="form-control select2 @error('credential_id') is-invalid @enderror">
                                <option value="">Sin credencial (autenticación manual)</option>
                                @foreach($credentials as $credential)
                                    <option value="{{ $credential->id }}"
                                            {{ old('credential_id') == $credential->id ? 'selected' : '' }}>
                                        {{ $credential->name }}
                                        @if($credential->description)
                                            - {{ Str::limit($credential->description, 40) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Selecciona una credencial para autenticación automática.
                                <a href="{{ route('settings.erp.credentials.create') }}" target="_blank">Crear nueva credencial</a>
                            </small>
                            @error('credential_id')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración avanzada</h6>
                        <p class="text-muted small mb-3">Ajusta los tiempos de espera y reintentos para las peticiones.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Timeout (segundos)</label>
                            <input type="number" name="timeout" class="form-control @error('timeout') is-invalid @enderror"
                                   value="{{ old('timeout', 30) }}" min="1" max="300" placeholder="30">
                            <small class="form-text text-muted">Tiempo máximo de espera para la respuesta (1-300 segundos)</small>
                            @error('timeout')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Reintentos</label>
                            <input type="number" name="retry_attempts" class="form-control @error('retry_attempts') is-invalid @enderror"
                                   value="{{ old('retry_attempts', 3) }}" min="0" max="10" placeholder="3">
                            <small class="form-text text-muted">Número de reintentos en caso de error (0-10)</small>
                            @error('retry_attempts')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Crear
                </button>
                <a href="{{ route('settings.erp.endpoints.index') }}" class="btn btn-secondary w-100">
                    Cancelar
                </a>
            </div>

        </form>

            </div>
        </div>

        <div class="col-lg-4">
            {{-- Instructions Card --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="fas fa-circle-question me-2"></i>
                        Cómo usar
                    </h5>

                    <ol class="mb-3 ps-3">
                        <li class="mb-2">Define el <strong>nombre</strong> y <strong>URL</strong> del endpoint externo</li>
                        <li class="mb-2">Selecciona el <strong>método HTTP</strong> (GET, POST, etc.)</li>
                        <li class="mb-2">Configura la <strong>autenticación</strong> asignando una credencial</li>
                        <li class="mb-2">Ajusta <strong>timeout</strong> y <strong>reintentos</strong> según sea necesario</li>
                        <li class="mb-2">Guarda y prueba la conexión desde la página de detalle</li>
                    </ol>

                    <div class="alert alert-info mb-0">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-lightbulb fs-5 me-2"></i>
                            </div>
                            <div>
                                <strong>Consejo:</strong> Asegúrate de crear primero una credencial en la sección
                                <a href="{{ route('settings.erp.credentials.index') }}" class="alert-link">Credenciales ERP</a>
                                antes de asignarla al endpoint. Esto te permitirá reutilizar las mismas credenciales
                                en múltiples endpoints.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
