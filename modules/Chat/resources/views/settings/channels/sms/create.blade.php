@extends('layouts.theme')

@section('content')
@include('core::components.alerts')

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">Crear canal SMS</h5>
            <a href="{{ route('settings.chat.channels.sms.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('settings.chat.channels.sms.store') }}">
    @csrf

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-info-subtle">
                    <h6 class="mb-0">Información del canal</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="inbox_name" class="control-label col-form-label">Nombre del inbox <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                                id="inbox_name" name="inbox_name" value="{{ old('inbox_name') }}" required>
                            @error('inbox_name')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone_number" class="control-label col-form-label">Número de teléfono <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone_number') is-invalid @enderror"
                                id="phone_number" name="phone_number" value="{{ old('phone_number') }}"
                                placeholder="+1234567890" required>
                            <small class="form-text text-muted">Formato E.164: +[código país][número]</small>
                            @error('phone_number')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="provider" class="control-label col-form-label">Proveedor <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('provider') is-invalid @enderror" id="provider" name="provider" required>
                                <option value="">Selecciona un proveedor...</option>
                                <option value="bandwidth" {{ old('provider') === 'bandwidth' ? 'selected' : '' }}>Bandwidth</option>
                                <option value="twilio" {{ old('provider') === 'twilio' ? 'selected' : '' }}>Twilio</option>
                                <option value="telnyx" {{ old('provider') === 'telnyx' ? 'selected' : '' }}>Telnyx</option>
                            </select>
                            @error('provider')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="api_key" class="control-label col-form-label">API Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('api_key') is-invalid @enderror"
                                id="api_key" name="api_key" value="{{ old('api_key') }}" required>
                            @error('api_key')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="api_secret" class="control-label col-form-label">API Secret <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('api_secret') is-invalid @enderror"
                                id="api_secret" name="api_secret" required>
                            @error('api_secret')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="from_number" class="control-label col-form-label">Número de origen</label>
                            <input type="text" class="form-control @error('from_number') is-invalid @enderror"
                                id="from_number" name="from_number" value="{{ old('from_number') }}"
                                placeholder="+1234567890">
                            <small class="form-text text-muted">Opcional. Número desde el cual se enviarán los mensajes SMS.</small>
                            @error('from_number')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12" id="application-id-wrapper" style="display: none;">
                            <label for="application_id" class="control-label col-form-label">Application ID</label>
                            <input type="text" class="form-control @error('application_id') is-invalid @enderror"
                                id="application_id" name="application_id" value="{{ old('application_id') }}">
                            <small class="form-text text-muted">Requerido para Bandwidth</small>
                            @error('application_id')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('settings.chat.channels.sms.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Crear canal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function toggleApplicationId() {
        if ($('#provider').val() === 'bandwidth') {
            $('#application-id-wrapper').slideDown();
        } else {
            $('#application-id-wrapper').slideUp();
        }
    }

    $('#provider').on('change', toggleApplicationId);
    toggleApplicationId();
});
</script>
@endpush
