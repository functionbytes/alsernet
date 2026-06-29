@extends('layouts.theme')

@section('title', 'Nueva lista de correo')

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form method="post" action="{{ route('manager.maillists.store') }}">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva lista de correo</h5>
                        <small class="text-muted">Complete la información para crear una nueva lista de suscriptores.</small>
                    </div>
                    <div class="card-body">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required autofocus
                                   placeholder="ej: Newsletter mensual">
                            <small class="form-text text-muted">Nombre interno para identificar la lista</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea id="description" name="description"
                                      class="form-control" rows="2"
                                      placeholder="Descripción opcional de la lista">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Máximo 500 caracteres</small>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="from_email" class="form-label">From email</label>
                                <input type="email" id="from_email" name="from_email"
                                       class="form-control @error('from_email') is-invalid @enderror"
                                       value="{{ old('from_email') }}"
                                       placeholder="noreply@empresa.com">
                                <small class="form-text text-muted">Remitente por defecto</small>
                                @error('from_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="from_name" class="form-label">From name</label>
                                <input type="text" id="from_name" name="from_name"
                                       class="form-control @error('from_name') is-invalid @enderror"
                                       value="{{ old('from_name') }}"
                                       placeholder="Mi empresa">
                                <small class="form-text text-muted">Nombre que verá el destinatario</small>
                                @error('from_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="default_subject" class="form-label">Asunto por defecto</label>
                            <input type="text" id="default_subject" name="default_subject"
                                   class="form-control"
                                   value="{{ old('default_subject') }}"
                                   placeholder="Asunto del email">
                            <small class="form-text text-muted">Asunto predefinido para nuevas campañas con esta lista</small>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-3">Política de suscripción</h6>

                        <div class="form-check mb-2">
                            <input type="checkbox" name="subscribe_confirmation" value="1"
                                   id="dco" class="form-check-input" checked>
                            <label for="dco" class="form-check-label">
                                Doble opt-in (email de confirmación)
                            </label>
                            <div class="form-text text-muted">El suscriptor debe confirmar su email antes de quedar activo</div>
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" name="send_welcome_email" value="1"
                                   id="we" class="form-check-input">
                            <label for="we" class="form-check-label">
                                Enviar email de bienvenida
                            </label>
                            <div class="form-text text-muted">Se envía automáticamente cuando el suscriptor se confirma</div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Crear lista
                        </button>
                        <a href="{{ route('manager.maillists.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">¿Qué es una lista de correo?</h6>
                    <p class="card-text text-muted">
                        Una lista agrupa suscriptores a los que puedes enviar campañas. Cada lista tiene su propia configuración de remitente y política de suscripción.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">From email y from name</h6>
                    <p class="card-text text-muted mb-0">
                        Son los datos del remitente que verán tus suscriptores. Si los dejas en blanco, se usará la configuración global del servidor de envío.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Doble opt-in</h6>
                    <p class="card-text text-muted mb-0">
                        Recomendado. El suscriptor recibe un email de confirmación y debe hacer clic para quedar activo, garantizando la calidad de la lista.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
