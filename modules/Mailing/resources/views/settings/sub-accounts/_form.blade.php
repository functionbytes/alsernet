<form method="POST"
      action="{{ isset($subAccount) ? route('settings.mailing.sub-accounts.update', $subAccount->uid) : route('settings.mailing.sub-accounts.store') }}"
      id="formSubAccount">
    @method(isset($subAccount) ? 'PATCH' : 'POST')
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
                               value="{{ old('name', $subAccount->name ?? '') }}"
                               placeholder="Ej: Sub-cuenta ventas"
                               required>
                        @error('name')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Sending Server --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="sending_server_id" class="form-label">
                            Servidor de envío <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('sending_server_id') is-invalid @enderror"
                                id="sending_server_id"
                                name="sending_server_id"
                                required>
                            <option value="">Selecciona un servidor</option>
                            @foreach($sendingServers as $server)
                                <option value="{{ $server->id }}" {{ old('sending_server_id', $subAccount->sending_server_id ?? '') == $server->id ? 'selected' : '' }}>
                                    {{ $server->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('sending_server_id')
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
                            <option value="active" {{ old('status', $subAccount->status ?? 'active') === 'active' ? 'selected' : '' }}>Activa</option>
                            <option value="suspended" {{ old('status', $subAccount->status ?? '') === 'suspended' ? 'selected' : '' }}>Suspendida</option>
                            <option value="inactive" {{ old('status', $subAccount->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactiva</option>
                        </select>
                        @error('status')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Email --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email
                        </label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email', $subAccount->email ?? '') }}"
                               placeholder="subcuenta@example.com">
                        @error('email')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Username --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            Usuario
                        </label>
                        <input type="text"
                               class="form-control @error('username') is-invalid @enderror"
                               id="username"
                               name="username"
                               value="{{ old('username', $subAccount->username ?? '') }}"
                               placeholder="usuario">
                        @error('username')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            Descripción
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  placeholder="Descripción de la sub-cuenta">{{ old('description', $subAccount->description ?? '') }}</textarea>
                        @error('description')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Credentials Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Credenciales</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- API Key --}}
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="api_key" class="form-label">
                            API Key
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
                        @if(isset($subAccount))
                            <small class="text-muted">Deja en blanco para mantener la API Key actual</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Permissions Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Permisos</h6>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="can_create_campaigns"
                               name="can_create_campaigns"
                               value="1"
                               {{ old('can_create_campaigns', $subAccount->can_create_campaigns ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="can_create_campaigns">
                            Puede crear campañas
                        </label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="can_manage_subscribers"
                               name="can_manage_subscribers"
                               value="1"
                               {{ old('can_manage_subscribers', $subAccount->can_manage_subscribers ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="can_manage_subscribers">
                            Puede gestionar suscriptores
                        </label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="can_view_reports"
                               name="can_view_reports"
                               value="1"
                               {{ old('can_view_reports', $subAccount->can_view_reports ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="can_view_reports">
                            Puede ver reportes
                        </label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="can_create_sending_servers"
                               name="can_create_sending_servers"
                               value="1"
                               {{ old('can_create_sending_servers', $subAccount->can_create_sending_servers ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="can_create_sending_servers">
                            Puede crear servidores de envío
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quotas Card --}}
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Cuotas</h6>
        </div>

        <div class="card-body">
            <div class="row">
                {{-- Email Quota Per Day --}}
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="email_quota_per_day" class="form-label">
                            Cuota de correos por día
                        </label>
                        <input type="number"
                               class="form-control @error('email_quota_per_day') is-invalid @enderror"
                               id="email_quota_per_day"
                               name="email_quota_per_day"
                               value="{{ old('email_quota_per_day', $subAccount->email_quota_per_day ?? '') }}"
                               placeholder="Ilimitado"
                               min="1">
                        @error('email_quota_per_day')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                        <small class="text-muted">Dejar vacío para ilimitado</small>
                    </div>
                </div>

                {{-- Email Quota Per Month --}}
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="email_quota_per_month" class="form-label">
                            Cuota de correos por mes
                        </label>
                        <input type="number"
                               class="form-control @error('email_quota_per_month') is-invalid @enderror"
                               id="email_quota_per_month"
                               name="email_quota_per_month"
                               value="{{ old('email_quota_per_month', $subAccount->email_quota_per_month ?? '') }}"
                               placeholder="Ilimitado"
                               min="1">
                        @error('email_quota_per_month')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                        <small class="text-muted">Dejar vacío para ilimitado</small>
                    </div>
                </div>

                {{-- Subscriber Quota --}}
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="subscriber_quota" class="form-label">
                            Cuota de suscriptores
                        </label>
                        <input type="number"
                               class="form-control @error('subscriber_quota') is-invalid @enderror"
                               id="subscriber_quota"
                               name="subscriber_quota"
                               value="{{ old('subscriber_quota', $subAccount->subscriber_quota ?? '') }}"
                               placeholder="Ilimitado"
                               min="1">
                        @error('subscriber_quota')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                        <small class="text-muted">Dejar vacío para ilimitado</small>
                    </div>
                </div>

                {{-- Campaign Quota --}}
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="campaign_quota" class="form-label">
                            Cuota de campañas
                        </label>
                        <input type="number"
                               class="form-control @error('campaign_quota') is-invalid @enderror"
                               id="campaign_quota"
                               name="campaign_quota"
                               value="{{ old('campaign_quota', $subAccount->campaign_quota ?? '') }}"
                               placeholder="Ilimitado"
                               min="1">
                        @error('campaign_quota')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                        @enderror
                        <small class="text-muted">Dejar vacío para ilimitado</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="card">
        <div class="card-body d-flex gap-2 justify-content-end">
            <a href="{{ route('settings.mailing.sub-accounts.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-times me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-2"></i>{{ isset($subAccount) ? 'Actualizar' : 'Crear' }} sub-cuenta
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
