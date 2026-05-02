@extends('layouts.theme')

@section('title', isset($template) ? 'Editar plantilla' : 'Nueva plantilla')

@section('page_header')
    @include('core::components.card', ['title' => isset($template) ? 'Editar plantilla' : 'Nueva plantilla'])
@endsection

@section('content')

    @include('core::components.alerts')

    <form action="{{ isset($template) ? route('remarketing.templates.update', $template) : route('remarketing.templates.store') }}"
          method="POST" id="templateForm">
        @csrf
        @if(isset($template)) @method('PUT') @endif

        <div class="row g-3">

            {{-- Main editor column --}}
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-0">
                        <ul class="nav nav-tabs border-0 px-3" id="templateTabs">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-data">Datos</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-html" id="tab-html-btn">HTML</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-preview" id="tab-preview-btn">Preview</button>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content">

                        {{-- Tab: data --}}
                        <div class="tab-pane fade show active p-4" id="tab-data">
                            <div class="row g-3">

                                <div class="col-12">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $template->name ?? '') }}" required>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tipo</label>
                                    <select name="type" class="form-select">
                                        <option value="campaign"      {{ old('type', $template->type ?? 'campaign') === 'campaign'      ? 'selected' : '' }}>Campaña</option>
                                        <option value="automation"    {{ old('type', $template->type ?? '') === 'automation'    ? 'selected' : '' }}>Automatización</option>
                                        <option value="transactional" {{ old('type', $template->type ?? '') === 'transactional' ? 'selected' : '' }}>Transaccional</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tienda (vacío = global)</label>
                                    <select name="store_id" class="form-select">
                                        <option value="">Global (todas las tiendas)</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ old('store_id', $template->store_id ?? '') == $store->id ? 'selected' : '' }}>
                                                {{ $store->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Asunto por defecto</label>
                                    <input type="text" name="subject" class="form-control"
                                           value="{{ old('subject', $template->subject ?? '') }}"
                                           placeholder="Se puede sobreescribir en la campaña">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Preheader</label>
                                    <input type="text" name="preheader" class="form-control"
                                           value="{{ old('preheader', $template->preheader ?? '') }}">
                                </div>

                            </div>
                        </div>

                        {{-- Tab: HTML --}}
                        <div class="tab-pane fade p-4" id="tab-html">
                            <label class="form-label">Contenido HTML</label>
                            <textarea name="html_content" id="htmlContent" rows="20"
                                      class="form-control font-monospace @error('html_content') is-invalid @enderror"
                                      placeholder="Introduce el HTML del email...">{{ old('html_content', $template->html_content ?? '') }}</textarea>
                            @error('html_content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text mt-2">
                                Variables disponibles: <code>&#123;&#123;firstName&#125;&#125;</code>,
                                <code>&#123;&#123;lastName&#125;&#125;</code>,
                                <code>&#123;&#123;email&#125;&#125;</code>,
                                <code>&#123;&#123;unsubscribeUrl&#125;&#125;</code>,
                                <code>&#123;&#123;preferencesUrl&#125;&#125;</code>,
                                <code>&#123;&#123;storeName&#125;&#125;</code>
                            </div>
                        </div>

                        {{-- Tab: preview --}}
                        <div class="tab-pane fade p-3" id="tab-preview">
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" id="btn-refresh-preview" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-sync me-1"></i> Actualizar preview
                                </button>
                            </div>
                            <div class="border rounded" style="height:500px;overflow:hidden">
                                <iframe id="html-preview-frame" style="width:100%;height:100%;border:0"
                                        srcdoc="<p style='font-family:sans-serif;color:#888;text-align:center;padding:40px'>Escribe HTML en la pestaña anterior y pulsa Actualizar preview</p>">
                                </iframe>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            {{ isset($template) ? 'Guardar cambios' : 'Crear plantilla' }}
                        </button>
                        <a href="{{ route('remarketing.templates.index') }}" class="btn btn-light">Cancelar</a>
                    </div>
                </div>
            </div>

            {{-- Side: variable reference --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Variables de personalización</h6>
                        <p class="small text-muted mb-3">Usa estas variables en el HTML — se reemplazarán al enviar.</p>
                        <table class="table table-sm table-borderless small">
                            <thead class="table-light">
                                <tr>
                                    <th>Variable</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><code>&#123;&#123;firstName&#125;&#125;</code></td><td>Nombre del destinatario</td></tr>
                                <tr><td><code>&#123;&#123;lastName&#125;&#125;</code></td><td>Apellido</td></tr>
                                <tr><td><code>&#123;&#123;email&#125;&#125;</code></td><td>Email del destinatario</td></tr>
                                <tr><td><code>&#123;&#123;storeName&#125;&#125;</code></td><td>Nombre de la tienda</td></tr>
                                <tr><td><code>&#123;&#123;unsubscribeUrl&#125;&#125;</code></td><td>URL de baja (obligatorio)</td></tr>
                                <tr><td><code>&#123;&#123;preferencesUrl&#125;&#125;</code></td><td>URL de preferencias</td></tr>
                                <tr><td><code>&#123;&#123;trackingPixel&#125;&#125;</code></td><td>Pixel de apertura 1×1</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Update preview when switching to the preview tab
    $('#tab-preview-btn').on('click', function () {
        refreshPreview();
    });

    $('#btn-refresh-preview').on('click', function () {
        refreshPreview();
    });

    function refreshPreview() {
        var html = $('#htmlContent').val();
        // Replace template variables with placeholder values for preview
        html = html
            .replace(/\{\{firstName\}\}/g, 'Juan')
            .replace(/\{\{lastName\}\}/g, 'García')
            .replace(/\{\{email\}\}/g, 'juan@ejemplo.com')
            .replace(/\{\{storeName\}\}/g, 'Mi tienda')
            .replace(/\{\{unsubscribeUrl\}\}/g, '#')
            .replace(/\{\{preferencesUrl\}\}/g, '#')
            .replace(/\{\{trackingPixel\}\}/g, '');

        $('#html-preview-frame').attr('srcdoc', html || '<p style="font-family:sans-serif;color:#888;text-align:center;padding:40px">El HTML está vacío</p>');
    }

});
</script>
@endpush
