@extends('layouts.admin')

@section('title', 'Conectar Cuenta Social')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.accounts.index') }}">Cuentas Sociales</a></li>
                <li class="breadcrumb-item active">Conectar Cuenta</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plug me-2"></i>Conectar Cuenta Social
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Selecciona la red social que deseas conectar para publicar contenido
                        </p>

                        <form action="{{ route('admin.social.accounts.store') }}" method="POST">
                            @csrf

                            <!-- Network Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Red Social <span class="text-danger">*</span></label>
                                <div class="row g-3">
                                    @foreach($networks as $network)
                                        <div class="col-md-6">
                                            <input type="radio"
                                                   class="btn-check"
                                                   name="network"
                                                   id="network_{{ $network->value }}"
                                                   value="{{ $network->value }}"
                                                   {{ old('network') === $network->value ? 'checked' : '' }}
                                                   required>
                                            <label class="btn btn-outline-primary w-100 py-3"
                                                   for="network_{{ $network->value }}">
                                                <i class="{{ $network->icon() }} fs-2 d-block mb-2"
                                                   style="color: {{ $network->color() }}"></i>
                                                <span class="d-block fw-bold">{{ $network->label() }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('network')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
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
                                       value="{{ old('username') }}"
                                       placeholder="@miusuario o ID de página"
                                       required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">El nombre de usuario o ID de la página a conectar</small>
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
                                       value="{{ old('name') }}"
                                       placeholder="Mi Página de Facebook"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Nombre descriptivo para identificar esta cuenta</small>
                            </div>

                            <!-- Access Token (Optional - will use OAuth flow) -->
                            <div class="mb-4">
                                <label for="access_token" class="form-label fw-semibold">
                                    Token de Acceso (Opcional)
                                </label>
                                <textarea class="form-control @error('access_token') is-invalid @enderror"
                                          id="access_token"
                                          name="access_token"
                                          rows="3"
                                          placeholder="Pega aquí el token de acceso si lo tienes">{{ old('access_token') }}</textarea>
                                @error('access_token')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    Si dejas esto vacío, serás redirigido al flujo de OAuth para autorizar la conexión
                                </small>
                            </div>

                            <!-- Info Alert -->
                            <div class="alert alert-info d-flex align-items-start" role="alert">
                                <i class="fas fa-info-circle me-2 mt-1"></i>
                                <div>
                                    <strong>Nota:</strong> Para conectar cuentas de redes sociales necesitarás:
                                    <ul class="mb-0 mt-2">
                                        <li>Permisos de administrador en la página/cuenta</li>
                                        <li>Acceso a la configuración de desarrolladores de la plataforma</li>
                                        <li>Un token de acceso válido con los permisos necesarios</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.social.accounts.index') }}" class="btn btn-light">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plug me-1"></i> Conectar Cuenta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Auto-fill name based on username
    document.getElementById('username').addEventListener('blur', function() {
        const nameField = document.getElementById('name');
        if (!nameField.value && this.value) {
            const network = document.querySelector('input[name="network"]:checked');
            if (network) {
                const networkLabel = network.closest('label').querySelector('span').textContent.trim();
                nameField.value = `${this.value} - ${networkLabel}`;
            }
        }
    });
</script>
@endpush
