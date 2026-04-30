@extends('layouts.theme')

@section('title', 'Configuración general')

@section('content')

    <div class="card w-100">

        <form id="formGlobalConfig" method="POST" action="{{ route('settings.chat.configurations.update') }}">

            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Configuración general</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Configura los ajustes generales del sistema de chat, incluyendo zona horaria, idioma y políticas de retención de datos.
                </p>

                <div class="row">

                    <!-- Sistema -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información del sistema</h6>
                        <p class="text-muted small mb-3">Define el nombre del sistema que se mostrará en toda la aplicación.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre del sistema
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="system_name" class="form-control" value="{{ old('system_name', $settings['system_name']) }}" required minlength="3" maxlength="100" placeholder="Ej: Helpdesk Alsernet">
                            <small class="form-text text-muted">Nombre que se mostrará en el sistema</small>
                            @error('system_name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Localización -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Localización</h6>
                        <p class="text-muted small mb-3">Configura la zona horaria y el idioma predeterminado del sistema.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Zona horaria
                                <span class="text-danger">*</span>
                            </label>
                            <select name="timezone" class="form-control select2" required>
                                @foreach(timezone_identifiers_list() as $tz)
                                    <option value="{{ $tz }}" {{ old('timezone', $settings['timezone']) === $tz ? 'selected' : '' }}>
                                        {{ $tz }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Zona horaria para fechas y horas del sistema</small>
                            @error('timezone')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Idioma
                                <span class="text-danger">*</span>
                            </label>
                            <select name="language" class="form-control select2" required>
                                <option value="es" {{ old('language', $settings['language']) === 'es' ? 'selected' : '' }}>Español</option>
                                <option value="en" {{ old('language', $settings['language']) === 'en' ? 'selected' : '' }}>English</option>
                                <option value="pt" {{ old('language', $settings['language']) === 'pt' ? 'selected' : '' }}>Português</option>
                                <option value="fr" {{ old('language', $settings['language']) === 'fr' ? 'selected' : '' }}>Français</option>
                            </select>
                            <small class="form-text text-muted">Idioma predeterminado de la interfaz</small>
                            @error('language')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Retención de datos -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Retención de datos</h6>
                        <p class="text-muted small mb-3">Define cuánto tiempo se conservarán los logs y datos analíticos del sistema.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Días de retención
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="retention_days" class="form-control" value="{{ old('retention_days', $settings['retention_days']) }}" min="1" max="365" required placeholder="Ej: 90">
                            <small class="form-text text-muted">Número de días para conservar logs y datos de analítica</small>
                            @error('retention_days')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Funcionalidades -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Funcionalidades</h6>
                        <p class="text-muted small mb-3">Activa o desactiva características específicas del sistema.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="enable_logging" value="0">
                                <input type="checkbox" name="enable_logging" class="form-check-input" id="enableLogging" value="1" {{ old('enable_logging', $settings['enable_logging']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enableLogging">
                                    Activar registro de actividad
                                </label>
                            </div>
                            <small class="form-text text-muted">Registra eventos y acciones del sistema</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="enable_analytics" value="0">
                                <input type="checkbox" name="enable_analytics" class="form-check-input" id="enableAnalytics" value="1" {{ old('enable_analytics', $settings['enable_analytics']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enableAnalytics">
                                    Activar analítica
                                </label>
                            </div>
                            <small class="form-text text-muted">Habilita la recopilación de datos analíticos</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
        @endif

        @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
        @endif
    });
</script>
@endpush
