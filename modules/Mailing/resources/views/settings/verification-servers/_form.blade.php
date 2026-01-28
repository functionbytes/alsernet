<form method="POST"
      action="{{ isset($server) ? route('settings.mailing.verification-servers.update', $server->uid) : route('settings.mailing.verification-servers.store') }}"
      id="formVerificationServer">
    @method(isset($server) ? 'PATCH' : 'POST')
    @csrf

    {{-- General Information Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Información general</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Name --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $server->name ?? '') }}"
                               placeholder="Ej: ZeroBounce verificación"
                               required>
                        @error('name')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Type --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="type" class="form-label">
                            Tipo <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('type') is-invalid @enderror"
                                id="type"
                                name="type"
                                required>
                            <option value="">Selecciona un tipo</option>
                            <option value="zerobounce" {{ old('type', $server->type ?? '') === 'zerobounce' ? 'selected' : '' }}>ZeroBounce</option>
                            <option value="neverbounce" {{ old('type', $server->type ?? '') === 'neverbounce' ? 'selected' : '' }}>NeverBounce</option>
                            <option value="kickbox" {{ old('type', $server->type ?? '') === 'kickbox' ? 'selected' : '' }}>Kickbox</option>
                            <option value="clearout" {{ old('type', $server->type ?? '') === 'clearout' ? 'selected' : '' }}>Clearout</option>
                            <option value="debounce" {{ old('type', $server->type ?? '') === 'debounce' ? 'selected' : '' }}>Debounce</option>
                            <option value="custom" {{ old('type', $server->type ?? '') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                        </select>
                        @error('type')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Status --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">
                            Estado <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('status') is-invalid @enderror"
                                id="status"
                                name="status"
                                required>
                            <option value="active" {{ old('status', $server->status ?? 'inactive') === 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ old('status', $server->status ?? 'inactive') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                            <option value="error" {{ old('status', $server->status ?? '') === 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                        @error('status')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- API Configuration Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Configuración API</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- API Key --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="api_key" class="form-label">
                            API Key <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('api_key') is-invalid @enderror"
                                   id="api_key"
                                   name="api_key"
                                   placeholder="API Key">
                            <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                <i class="fa fa-eye"></i>
                            </button>
                            @error('api_key')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                        @if(isset($server))
                            <small class="text-muted">Deja en blanco para mantener la API Key actual</small>
                        @endif
                    </div>
                </div>

                {{-- API URL --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="api_url" class="form-label">
                            URL de API
                        </label>
                        <input type="url"
                               class="form-control @error('api_url') is-invalid @enderror"
                               id="api_url"
                               name="api_url"
                               value="{{ old('api_url', $server->api_url ?? '') }}"
                               placeholder="https://api.zerobounce.net">
                        @error('api_url')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Verification Settings Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Configuración de verificación</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Check Disposable --}}
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="check_disposable"
                               name="check_disposable"
                               value="1"
                               {{ old('check_disposable', $server->check_disposable ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_disposable">
                            Verificar email desechable
                        </label>
                    </div>
                </div>

                {{-- Check Role Based --}}
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="check_role_based"
                               name="check_role_based"
                               value="1"
                               {{ old('check_role_based', $server->check_role_based ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_role_based">
                            Verificar email basado en rol
                        </label>
                    </div>
                </div>

                {{-- Check SMTP --}}
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="check_smtp"
                               name="check_smtp"
                               value="1"
                               {{ old('check_smtp', $server->check_smtp ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_smtp">
                            Verificación SMTP
                        </label>
                    </div>
                </div>

                {{-- Check Catch All --}}
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="check_catch_all"
                               name="check_catch_all"
                               value="1"
                               {{ old('check_catch_all', $server->check_catch_all ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="check_catch_all">
                            Detectar Catch-All
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rate Limiting Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Límites de solicitud</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Requests Per Minute --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="requests_per_minute" class="form-label">
                            Solicitudes por minuto
                        </label>
                        <input type="number"
                               class="form-control @error('requests_per_minute') is-invalid @enderror"
                               id="requests_per_minute"
                               name="requests_per_minute"
                               value="{{ old('requests_per_minute', $server->requests_per_minute ?? 10) }}"
                               placeholder="10"
                               min="1">
                        @error('requests_per_minute')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Requests Per Day --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="requests_per_day" class="form-label">
                            Solicitudes por día
                        </label>
                        <input type="number"
                               class="form-control @error('requests_per_day') is-invalid @enderror"
                               id="requests_per_day"
                               name="requests_per_day"
                               value="{{ old('requests_per_day', $server->requests_per_day ?? 1000) }}"
                               placeholder="1000"
                               min="1">
                        @error('requests_per_day')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Credit Threshold --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="credit_threshold" class="form-label">
                            Umbral de alerta de créditos (%)
                        </label>
                        <input type="number"
                               class="form-control @error('credit_threshold') is-invalid @enderror"
                               id="credit_threshold"
                               name="credit_threshold"
                               value="{{ old('credit_threshold', $server->credit_threshold ?? 10) }}"
                               placeholder="10"
                               min="1"
                               max="100">
                        @error('credit_threshold')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="card">
        <div class="card-body d-flex gap-2 justify-content-end">
            <a href="{{ route('settings.mailing.verification-servers.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-times me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-2"></i>{{ isset($server) ? 'Actualizar' : 'Crear' }} servidor
            </button>
        </div>
    </div>

</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiKeyToggle = document.getElementById('toggleApiKey');

    // Toggle API Key visibility
    if (apiKeyToggle) {
        apiKeyToggle.addEventListener('click', function() {
            const apiKeyInput = document.getElementById('api_key');
            const icon = this.querySelector('i');
            if (apiKeyInput.type === 'password') {
                apiKeyInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                apiKeyInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
});
</script>
@endpush
