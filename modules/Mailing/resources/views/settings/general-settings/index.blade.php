@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración general'])

    @if ($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.mailing.settings.general.store') }}" id="formGeneralSettings">
        @csrf

        {{-- General Information Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">Información general</h6>
            </div>

            <div class="card-body">
                <div class="row">
                    {{-- App Name --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="app_name" class="form-label">
                                Nombre de la aplicación <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('app_name') is-invalid @enderror"
                                   id="app_name"
                                   name="app_name"
                                   value="{{ old('app_name', $settings['app_name'] ?? '') }}"
                                   placeholder="Ej: Mi Aplicación de Email"
                                   required>
                            @error('app_name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Nombre que aparecerá en la interfaz</small>
                        </div>
                    </div>

                    {{-- Support Email --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="support_email" class="form-label">
                                Email de soporte <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('support_email') is-invalid @enderror"
                                   id="support_email"
                                   name="support_email"
                                   value="{{ old('support_email', $settings['support_email'] ?? '') }}"
                                   placeholder="soporte@ejemplo.com"
                                   required>
                            @error('support_email')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Default From Name --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="default_from_name" class="form-label">
                                Nombre de remitente predeterminado <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('default_from_name') is-invalid @enderror"
                                   id="default_from_name"
                                   name="default_from_name"
                                   value="{{ old('default_from_name', $settings['default_from_name'] ?? '') }}"
                                   placeholder="Ej: Mi Empresa"
                                   required>
                            @error('default_from_name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Aparecerá como nombre del remitente en los emails</small>
                        </div>
                    </div>

                    {{-- Default From Email --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="default_from_email" class="form-label">
                                Email predeterminado <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('default_from_email') is-invalid @enderror"
                                   id="default_from_email"
                                   name="default_from_email"
                                   value="{{ old('default_from_email', $settings['default_from_email'] ?? '') }}"
                                   placeholder="noreply@ejemplo.com"
                                   required>
                            @error('default_from_email')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Behavior Settings Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">Comportamiento del módulo</h6>
            </div>

            <div class="card-body">
                <div class="row">
                    {{-- Enable Campaign Scheduling --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="enable_campaign_scheduling"
                                   name="enable_campaign_scheduling"
                                   value="1"
                                   {{ old('enable_campaign_scheduling', $settings['enable_campaign_scheduling'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_campaign_scheduling">
                                Permitir programación de campañas
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Habilita la capacidad de programar campañas para enviarlas en el futuro</small>
                    </div>

                    {{-- Enable A/B Testing --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="enable_ab_testing"
                                   name="enable_ab_testing"
                                   value="1"
                                   {{ old('enable_ab_testing', $settings['enable_ab_testing'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_ab_testing">
                                Permitir pruebas A/B
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Habilita pruebas A/B para optimizar campañas</small>
                    </div>

                    {{-- Enable Click Tracking --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="enable_click_tracking"
                                   name="enable_click_tracking"
                                   value="1"
                                   {{ old('enable_click_tracking', $settings['enable_click_tracking'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_click_tracking">
                                Habilitar seguimiento de clics
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Rastrear cuándo los usuarios hacen clic en enlaces</small>
                    </div>

                    {{-- Enable Open Tracking --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="enable_open_tracking"
                                   name="enable_open_tracking"
                                   value="1"
                                   {{ old('enable_open_tracking', $settings['enable_open_tracking'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_open_tracking">
                                Habilitar seguimiento de aperturas
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Rastrear cuándo los usuarios abren un email</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interface Settings Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">Configuración de la interfaz</h6>
            </div>

            <div class="card-body">
                <div class="row">
                    {{-- Default Timezone --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="default_timezone" class="form-label">
                                Zona horaria predeterminada
                            </label>
                            <select class="form-select @error('default_timezone') is-invalid @enderror"
                                    id="default_timezone"
                                    name="default_timezone">
                                <option value="">Seleccionar zona horaria</option>
                                <option value="UTC" {{ old('default_timezone', $settings['default_timezone'] ?? '') === 'UTC' ? 'selected' : '' }}>UTC</option>
                                <option value="America/New_York" {{ old('default_timezone', $settings['default_timezone'] ?? '') === 'America/New_York' ? 'selected' : '' }}>América/Nueva York</option>
                                <option value="Europe/London" {{ old('default_timezone', $settings['default_timezone'] ?? '') === 'Europe/London' ? 'selected' : '' }}>Europa/Londres</option>
                                <option value="Europe/Madrid" {{ old('default_timezone', $settings['default_timezone'] ?? '') === 'Europe/Madrid' ? 'selected' : '' }}>Europa/Madrid</option>
                                <option value="America/Mexico_City" {{ old('default_timezone', $settings['default_timezone'] ?? '') === 'America/Mexico_City' ? 'selected' : '' }}>América/Ciudad de México</option>
                            </select>
                            @error('default_timezone')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Items Per Page --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="items_per_page" class="form-label">
                                Elementos por página
                            </label>
                            <input type="number"
                                   class="form-control @error('items_per_page') is-invalid @enderror"
                                   id="items_per_page"
                                   name="items_per_page"
                                   value="{{ old('items_per_page', $settings['items_per_page'] ?? 25) }}"
                                   placeholder="25"
                                   min="10"
                                   max="100">
                            @error('items_per_page')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Número de elementos a mostrar en listas paginadas</small>
                        </div>
                    </div>

                    {{-- Enable Dark Mode --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="enable_dark_mode"
                                   name="enable_dark_mode"
                                   value="1"
                                   {{ old('enable_dark_mode', $settings['enable_dark_mode'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_dark_mode">
                                Habilitar modo oscuro
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Permitir que los usuarios cambien a modo oscuro</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="card">
            <div class="card-body d-flex gap-2 justify-content-end">
                <a href="{{ route('settings.mailing.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-times me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-2"></i>Guardar configuración
                </button>
            </div>
        </div>

    </form>

@endsection
