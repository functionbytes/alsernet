@extends('layouts.theme')

@section('title', 'Editar campaign')

@section('content')
<div class="widget-content">

    @include('core::components.alerts')

    {{-- Breadcrumb --}}
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('managers.mailrelay.campaigns.index') }}">Campaigns</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar: {{ $campaign->name }}</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('managers.mailrelay.campaigns.update', $campaign->id) }}" method="POST">
        @csrf
        @method('PUT')

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
                                   id="name" name="name" value="{{ old('name', $campaign->name) }}"
                                   placeholder="Ej: Newsletter Mensual - Febrero 2026" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="subject" class="form-label fw-semibold">Asunto del email <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                   id="subject" name="subject" value="{{ old('subject', $campaign->subject) }}"
                                   placeholder="Ej: 📰 Novedades de Febrero - Ofertas Exclusivas" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                    <div class="form-check card p-3 border {{ $campaign->mailer_template_id ? 'border-primary' : '' }}">
                                        <input class="form-check-input" type="radio" name="content_type"
                                               id="use_template" value="template"
                                               {{ old('content_type', $campaign->mailer_template_id ? 'template' : 'html') === 'template' ? 'checked' : '' }}>
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
                                    <div class="form-check card p-3 border {{ !$campaign->mailer_template_id ? 'border-primary' : '' }}">
                                        <input class="form-check-input" type="radio" name="content_type"
                                               id="use_html" value="html"
                                               {{ old('content_type', !$campaign->mailer_template_id ? 'html' : 'template') === 'html' ? 'checked' : '' }}>
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
                        <div id="template-section" class="mb-4" style="display: {{ old('content_type', $campaign->mailer_template_id ? 'template' : 'html') === 'template' ? 'block' : 'none' }}">
                            <label for="mailer_template_id" class="form-label fw-semibold">Plantilla Mailer</label>
                            <select class="form-select @error('mailer_template_id') is-invalid @enderror select2"
                                    id="mailer_template_id" name="mailer_template_id">
                                <option value="">Selecciona una plantilla...</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}"
                                            {{ old('mailer_template_id', $campaign->mailer_template_id) == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }}
                                        @if($template->module)
                                            ({{ $template->module }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('mailer_template_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Editor HTML personalizado --}}
                        <div id="html-section" class="mb-4" style="display: {{ old('content_type', !$campaign->mailer_template_id ? 'html' : 'template') === 'html' ? 'block' : 'none' }}">
                            <label for="html_content" class="form-label fw-semibold">Contenido HTML</label>
                            <textarea class="form-control font-monospace @error('html_content') is-invalid @enderror"
                                      id="html_content" name="html_content" rows="15"
                                      placeholder="<html>...</html>">{{ old('html_content', $campaign->html_content) }}</textarea>
                            @error('html_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Idioma --}}
                        <div class="mb-4">
                            <label for="lang_id" class="form-label fw-semibold">Idioma</label>
                            <select class="form-select @error('lang_id') is-invalid @enderror select2"
                                    id="lang_id" name="lang_id">
                                <option value="">Idioma por defecto</option>
                                @foreach($languages as $language)
                                    <option value="{{ $language->id }}"
                                            {{ old('lang_id', $campaign->lang_id) == $language->id ? 'selected' : '' }}>
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
                            <label class="form-label fw-semibold">Variables personalizadas</label>
                            <div id="template-variables-container">
                                @php
                                    $variables = old('variable_keys') ? array_combine(old('variable_keys'), old('variable_values')) : ($campaign->template_variables ?? []);
                                @endphp

                                @if(empty($variables))
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
                                @else
                                    @foreach($variables as $key => $value)
                                        <div class="variable-row mb-2">
                                            <div class="row">
                                                <div class="col-5">
                                                    <input type="text" class="form-control" name="variable_keys[]" value="{{ $key }}" placeholder="NOMBRE_VARIABLE">
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" class="form-control" name="variable_values[]" value="{{ $value }}" placeholder="Valor">
                                                </div>
                                                <div class="col-1">
                                                    @if($loop->first)
                                                        <button type="button" class="btn btn-sm btn-light" onclick="addVariableRow()">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-light" onclick="this.closest('.variable-row').remove()">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <small class="text-muted">Ejemplo: PROMO_CODE = DESCUENTO50</small>
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
                            <select class="form-select @error('mail_provider_id') is-invalid @enderror select2"
                                    id="mail_provider_id" name="mail_provider_id" required>
                                <option value="">Selecciona un provider...</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}"
                                            {{ old('mail_provider_id', $campaign->mail_provider_id) == $provider->id ? 'selected' : '' }}
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
                        <div class="mb-3">
                            <label class="form-label" for="track_opens">Rastrear aperturas</label>
                            <select class="form-select" name="track_opens" id="track_opens">
                                <option value="1" {{ old('track_opens', $campaign->track_opens) ? 'selected' : '' }}>Activado</option>
                                <option value="0" {{ !old('track_opens', $campaign->track_opens) ? 'selected' : '' }}>Desactivado</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label" for="track_clicks">Rastrear clicks</label>
                            <select class="form-select" name="track_clicks" id="track_clicks">
                                <option value="1" {{ old('track_clicks', $campaign->track_clicks) ? 'selected' : '' }}>Activado</option>
                                <option value="0" {{ !old('track_clicks', $campaign->track_clicks) ? 'selected' : '' }}>Desactivado</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Estado --}}
                <div class="card mb-3">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-semibold">Estado</h6>
                    </div>
                    <div class="card-body p-3">
                        @switch($campaign->status->value)
                            @case('draft')
                                <div class="alert alert-secondary mb-0">
                                    <i class="fas fa-pencil me-2"></i>Borrador
                                </div>
                                @break
                            @case('scheduled')
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-clock me-2"></i>Programada
                                </div>
                                @break
                            @case('sent')
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-check-circle me-2"></i>Enviada
                                </div>
                                @break
                            @case('failed')
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-times-circle me-2"></i>Fallida
                                </div>
                                @break
                        @endswitch
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="card-body p-3">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            <i class="fas fa-save me-2"></i>Actualizar campaign
                        </button>
                        <a href="{{ route('managers.mailrelay.campaigns.show', $campaign->id) }}" class="btn btn-light w-100">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
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
        } else {
            document.getElementById('template-section').style.display = 'none';
            document.getElementById('html-section').style.display = 'block';
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
