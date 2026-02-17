@extends('layouts.theme')

@section('title', 'Nueva campaign')

@section('content')
<div class="widget-content">

    @include('core::components.alerts')

    {{-- Breadcrumb --}}
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('managers.mailrelay.campaigns.index') }}">Campaigns</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nueva campaign</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('managers.mailrelay.campaigns.store') }}" method="POST">
        @csrf

        <div class="row">
            {{-- Main Form --}}
            <div class="col-lg-8">

                {{-- Información básica --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-info-circle me-2"></i>Información básica
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">Nombre de la campaign <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}"
                                   placeholder="Ej: Newsletter Mensual - Febrero 2026" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Nombre interno para identificar esta campaign</small>
                        </div>

                        <div class="mb-4">
                            <label for="subject" class="form-label fw-semibold">Asunto del email <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                   id="subject" name="subject" value="{{ old('subject') }}"
                                   placeholder="Ej: 📰 Novedades de Febrero - Ofertas Exclusivas" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Este texto aparecerá en la bandeja de entrada</small>
                        </div>
                    </div>
                </div>

                {{-- Plantilla y contenido --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-palette me-2"></i>Plantilla y contenido
                        </h6>
                    </div>
                    <div class="card-body p-4">

                        {{-- Tipo de contenido --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tipo de contenido</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check card p-3 border {{ old('content_type', 'template') === 'template' ? 'border-primary' : '' }}">
                                        <input class="form-check-input" type="radio" name="content_type"
                                               id="use_template" value="template"
                                               {{ old('content_type', 'template') === 'template' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="use_template">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-palette fs-4 text-primary me-3"></i>
                                                <div>
                                                    <div class="fw-semibold">Usar plantilla Mailer</div>
                                                    <small class="text-muted">Selecciona una plantilla existente</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check card p-3 border {{ old('content_type') === 'html' ? 'border-primary' : '' }}">
                                        <input class="form-check-input" type="radio" name="content_type"
                                               id="use_html" value="html"
                                               {{ old('content_type') === 'html' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="use_html">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-code fs-4 text-secondary me-3"></i>
                                                <div>
                                                    <div class="fw-semibold">HTML personalizado</div>
                                                    <small class="text-muted">Escribe HTML directo</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Selector de plantilla Mailer --}}
                        <div id="template-section" class="mb-4" style="display: {{ old('content_type', 'template') === 'template' ? 'block' : 'none' }}">
                            <label for="mailer_template_id" class="form-label fw-semibold">Plantilla Mailer <span class="text-danger">*</span></label>
                            <select class="form-select @error('mailer_template_id') is-invalid @enderror"
                                    id="mailer_template_id" name="mailer_template_id">
                                <option value="">Selecciona una plantilla...</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" {{ old('mailer_template_id') == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }}
                                        @if($template->module)
                                            <span class="text-muted">({{ $template->module }})</span>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('mailer_template_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Las plantillas incluyen layouts, componentes y estilos predefinidos</small>
                        </div>

                        {{-- Editor HTML personalizado --}}
                        <div id="html-section" class="mb-4" style="display: {{ old('content_type') === 'html' ? 'block' : 'none' }}">
                            <label for="html_content" class="form-label fw-semibold">Contenido HTML <span class="text-danger">*</span></label>
                            <textarea class="form-control font-monospace @error('html_content') is-invalid @enderror"
                                      id="html_content" name="html_content" rows="15"
                                      placeholder="<html>...</html>">{{ old('html_content') }}</textarea>
                            @error('html_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">HTML completo del email. Puedes usar variables como {SUBSCRIBER_FIRSTNAME}</small>
                        </div>

                        {{-- Idioma --}}
                        <div class="mb-4">
                            <label for="lang_id" class="form-label fw-semibold">Idioma</label>
                            <select class="form-select @error('lang_id') is-invalid @enderror"
                                    id="lang_id" name="lang_id">
                                <option value="">Idioma por defecto</option>
                                @foreach($languages as $language)
                                    <option value="{{ $language->id }}" {{ old('lang_id') == $language->id ? 'selected' : '' }}>
                                        {{ $language->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lang_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Variables de plantilla --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Variables personalizadas (opcional)</label>
                            <div id="template-variables-container">
                                <div class="variable-row mb-2">
                                    <div class="row">
                                        <div class="col-5">
                                            <input type="text" class="form-control" name="variable_keys[]" placeholder="NOMBRE_VARIABLE">
                                        </div>
                                        <div class="col-6">
                                            <input type="text" class="form-control" name="variable_values[]" placeholder="Valor">
                                        </div>
                                        <div class="col-1">
                                            <button type="button" class="btn btn-sm btn-light" onclick="addVariableRow()">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Ejemplo: PROMO_CODE = DESCUENTO50. Úsala en la plantilla como {PROMO_CODE}</small>
                        </div>
                    </div>
                </div>

                {{-- Provider de envío --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-server me-2"></i>Provider de envío
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label for="mail_provider_id" class="form-label fw-semibold">Provider <span class="text-danger">*</span></label>
                            <select class="form-select @error('mail_provider_id') is-invalid @enderror"
                                    id="mail_provider_id" name="mail_provider_id" required>
                                <option value="">Selecciona un provider...</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}"
                                            {{ old('mail_provider_id', $provider->is_default ? $provider->id : '') == $provider->id ? 'selected' : '' }}
                                            {{ !$provider->is_active ? 'disabled' : '' }}>
                                        {{ $provider->name }}
                                        @if($provider->is_default)
                                            (Predeterminado)
                                        @endif
                                        @if(!$provider->is_active)
                                            [Inactivo]
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('mail_provider_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Servicio que enviará los emails (Mailrelay, Mailtrap, etc.)</small>
                        </div>
                    </div>
                </div>

                {{-- Tracking --}}
                <div class="card">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-chart-line me-2"></i>Seguimiento
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="track_opens"
                                   id="track_opens" value="1" {{ old('track_opens', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="track_opens">
                                <strong>Rastrear aperturas</strong>
                                <br><small class="text-muted">Insertar pixel de tracking para saber quién abrió el email</small>
                            </label>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="track_clicks"
                                   id="track_clicks" value="1" {{ old('track_clicks', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="track_clicks">
                                <strong>Rastrear clicks</strong>
                                <br><small class="text-muted">Reescribir URLs para saber qué enlaces se clickean</small>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Ayuda --}}
                <div class="card mb-3">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fas fa-lightbulb text-warning me-2"></i>Variables disponibles
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <small class="fw-semibold d-block mb-2">Globales:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <code class="small">{SITE_NAME}</code>
                                <code class="small">{SITE_URL}</code>
                                <code class="small">{CURRENT_YEAR}</code>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="fw-semibold d-block mb-2">Suscriptor:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <code class="small">{SUBSCRIBER_EMAIL}</code>
                                <code class="small">{SUBSCRIBER_FIRSTNAME}</code>
                                <code class="small">{SUBSCRIBER_LASTNAME}</code>
                            </div>
                        </div>
                        <div class="mb-0">
                            <small class="fw-semibold d-block mb-2">Campaign:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <code class="small">{CAMPAIGN_NAME}</code>
                                <code class="small">{UNSUBSCRIBE_URL}</code>
                                <code class="small">{WEB_VERSION_URL}</code>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="card-body p-3">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-2"></i>Guardar campaign
                        </button>
                        <a href="{{ route('managers.mailrelay.campaigns.index') }}" class="btn btn-light w-100">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="alert alert-light border mt-3">
                    <h6 class="fw-semibold mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>Consejos
                    </h6>
                    <ul class="small mb-0 ps-3">
                        <li class="mb-1">Usa asuntos claros y atractivos</li>
                        <li class="mb-1">Personaliza con variables del suscriptor</li>
                        <li class="mb-1">Siempre incluye link de desuscripción</li>
                        <li class="mb-1">Prueba antes de enviar</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Toggle content type
document.querySelectorAll('input[name="content_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'template') {
            document.getElementById('template-section').style.display = 'block';
            document.getElementById('html-section').style.display = 'none';
            document.getElementById('mailer_template_id').required = true;
            document.getElementById('html_content').required = false;
        } else {
            document.getElementById('template-section').style.display = 'none';
            document.getElementById('html-section').style.display = 'block';
            document.getElementById('mailer_template_id').required = false;
            document.getElementById('html_content').required = true;
        }
    });
});

// Add variable row
function addVariableRow() {
    const container = document.getElementById('template-variables-container');
    const newRow = document.createElement('div');
    newRow.className = 'variable-row mb-2';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-5">
                <input type="text" class="form-control" name="variable_keys[]" placeholder="NOMBRE_VARIABLE">
            </div>
            <div class="col-6">
                <input type="text" class="form-control" name="variable_values[]" placeholder="Valor">
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-sm btn-light" onclick="this.closest('.variable-row').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
}
</script>
@endpush
@endsection
