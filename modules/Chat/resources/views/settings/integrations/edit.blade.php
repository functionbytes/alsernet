@extends('layouts.theme')

@section('title', 'Editar integración')

@section('content')

    @include('core::components.alerts')

    <div class="card w-100">

        <form id="formIntegration" method="POST" action="{{ route('settings.chat.integrations.update', $integration) }}">

            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar integración</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Modifica la configuración de la integración con la aplicación externa.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Identificador de la aplicación y tipo de comunicación.</p>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                ID de aplicación
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="app_id" class="form-control @error('app_id') is-invalid @enderror" value="{{ old('app_id', $integration->app_id) }}" required placeholder="slack, zapier, webhooks">
                            <small class="form-text text-muted">Identificador único para la aplicación</small>
                            @error('app_id')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tipo de hook
                                <span class="text-danger">*</span>
                            </label>
                            <select name="hook_type" class="form-control select2 @error('hook_type') is-invalid @enderror" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="0" {{ old('hook_type', $integration->hook_type) == '0' ? 'selected' : '' }}>Entrante</option>
                                <option value="1" {{ old('hook_type', $integration->hook_type) == '1' ? 'selected' : '' }}>Saliente</option>
                            </select>
                            <small class="form-text text-muted">Dirección del flujo de datos</small>
                            @error('hook_type')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Connection -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Conexión</h6>
                        <p class="text-muted small mb-3">Parámetros de conexión y autenticación con el servicio externo.</p>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Bandeja</label>
                            <select name="inbox_id" class="form-control select2 @error('inbox_id') is-invalid @enderror">
                                <option value="">Ninguna (nivel de cuenta)</option>
                                @foreach($inboxes as $inbox)
                                    <option value="{{ $inbox->id }}" {{ old('inbox_id', $integration->inbox_id) == $inbox->id ? 'selected' : '' }}>
                                        {{ $inbox->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Dejar vacío para integración a nivel de cuenta</small>
                            @error('inbox_id')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Estado
                                <span class="text-danger">*</span>
                            </label>
                            <select name="status" class="form-control select2 @error('status') is-invalid @enderror" required>
                                <option value="{{ $statusOptions['disabled'] }}" {{ old('status', $integration->status) == $statusOptions['disabled'] ? 'selected' : '' }}>
                                    Inactivo
                                </option>
                                <option value="{{ $statusOptions['enabled'] }}" {{ old('status', $integration->status) == $statusOptions['enabled'] ? 'selected' : '' }}>
                                    Activo
                                </option>
                            </select>
                            <small class="form-text text-muted">Estado de la integración</small>
                            @error('status')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">ID de referencia</label>
                            <input type="text" name="reference_id" class="form-control @error('reference_id') is-invalid @enderror" value="{{ old('reference_id', $integration->reference_id) }}" placeholder="ID externo o canal">
                            <small class="form-text text-muted">Referencia externa o ID de canal (opcional)</small>
                            @error('reference_id')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Token de acceso</label>
                            <input type="text" name="access_token" class="form-control @error('access_token') is-invalid @enderror" value="{{ old('access_token', $integration->access_token) }}" placeholder="Token de API o clave">
                            <small class="form-text text-muted">Token OAuth o clave API para autenticación</small>
                            @error('access_token')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Additional Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración adicional</h6>
                        <p class="text-muted small mb-3">Parámetros personalizados en formato JSON.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Configuración JSON</label>
                            <textarea name="settings_json" class="form-control font-monospace @error('settings') is-invalid @enderror" rows="5" placeholder='{"clave": "valor"}'>{{ old('settings_json', json_encode($integration->settings ?? [], JSON_PRETTY_PRINT)) }}</textarea>
                            <small class="form-text text-muted">Configuración adicional en formato JSON</small>
                            @error('settings')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.integrations.index') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#formIntegration').on('submit', function(e) {
            const settingsField = $('[name="settings_json"]');
            const value = settingsField.val().trim();

            if (value) {
                try {
                    const parsed = JSON.parse(value);
                    const settingsInput = $('<input>')
                        .attr('type', 'hidden')
                        .attr('name', 'settings')
                        .val(JSON.stringify(parsed));
                    $(this).append(settingsInput);
                } catch (error) {
                    e.preventDefault();
                    toastr.error('JSON inválido en el campo de configuración');
                    return false;
                }
            }
        });

        @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
        @endif

        @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
        @endif
    });
</script>
@endpush
