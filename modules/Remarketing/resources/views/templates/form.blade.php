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
                            @if(isset($template))
                                <li class="nav-item">
                                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-translations" id="tab-translations-btn">Traducciones</button>
                                </li>
                                <li class="nav-item">
                                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-versions" id="tab-versions-btn">Versiones</button>
                                </li>
                            @endif
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
                                    <label class="form-label">Tienda</label>
                                    <select name="store_id" class="form-select">
                                        <option value="">Sin tienda específica</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ old('store_id', $template->store_id ?? '') == $store->id ? 'selected' : '' }}>
                                                {{ $store->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Visibilidad <span class="text-danger">*</span></label>
                                    <select name="visibility" class="form-select @error('visibility') is-invalid @enderror" required>
                                        <option value="store"  {{ old('visibility', $template->visibility ?? 'store') === 'store'  ? 'selected' : '' }}>Privada (esta tienda)</option>
                                        <option value="user"   {{ old('visibility', $template->visibility ?? '') === 'user'   ? 'selected' : '' }}>Compartida (mis tiendas)</option>
                                        <option value="global" {{ old('visibility', $template->visibility ?? '') === 'global' ? 'selected' : '' }}>Global (todos los usuarios)</option>
                                    </select>
                                    @error('visibility') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Quién puede ver y usar esta plantilla.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Layout de Mailer</label>
                                    <select name="layout_id" class="form-select @error('layout_id') is-invalid @enderror">
                                        <option value="">Sin layout (contenido puro)</option>
                                        @foreach($layouts as $layout)
                                            <option value="{{ $layout->id }}" {{ old('layout_id', $template->layout_id ?? '') == $layout->id ? 'selected' : '' }}>
                                                {{ $layout->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('layout_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Wrapper HTML alrededor del contenido.</div>
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
                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                <select id="previewLangSelect" class="form-select form-select-sm" style="width:auto">
                                    <option value="">Idioma por defecto</option>
                                    @foreach($langs ?? [] as $lang)
                                        <option value="{{ $lang->id }}">{{ $lang->title }}</option>
                                    @endforeach
                                </select>
                                <div class="d-flex gap-2">
                                    <button type="button" id="btn-toggle-dark" class="btn btn-sm btn-outline-secondary" title="Alternar modo oscuro">
                                        <i class="fas fa-moon me-1"></i> Modo oscuro
                                    </button>
                                    <button type="button" id="btn-refresh-preview" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-sync me-1"></i> Actualizar
                                    </button>
                                </div>
                            </div>
                            <div class="border rounded preview-frame-wrapper" id="preview-wrapper">
                                <iframe id="html-preview-frame" class="w-100 h-100 border-0"
                                        srcdoc="<p style='font-family:sans-serif;color:#888;text-align:center;padding:40px'>Escribe HTML en la pestaña anterior y pulsa Actualizar</p>"
                                        style="display:block;height:500px">
                                </iframe>
                            </div>
                        </div>

                        @if(isset($template))
                            {{-- Tab: translations --}}
                            <div class="tab-pane fade p-4" id="tab-translations">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">Traducciones</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-translation">
                                        <i class="fas fa-plus me-1"></i> Agregar traducción
                                    </button>
                                </div>

                                <div id="translations-container">
                                    @forelse($templateTranslations ?? [] as $t)
                                        <div class="translation-row border rounded p-3 mb-3" data-index="{{ $loop->index }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <select name="translations[{{ $loop->index }}][lang_id]" class="form-select form-select-sm lang-select" style="width:auto" required>
                                                    <option value="">Seleccionar idioma...</option>
                                                    @foreach($langs as $lang)
                                                        <option value="{{ $lang->id }}" {{ $t->lang_id == $lang->id ? 'selected' : '' }}>{{ $lang->title }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-translation">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <div class="mb-2">
                                                <input type="text" name="translations[{{ $loop->index }}][subject]" class="form-control form-control-sm"
                                                       placeholder="Asunto" value="{{ $t->subject }}">
                                            </div>
                                            <div class="mb-2">
                                                <input type="text" name="translations[{{ $loop->index }}][preheader]" class="form-control form-control-sm"
                                                       placeholder="Preheader" value="{{ $t->preheader }}">
                                            </div>
                                            <textarea name="translations[{{ $loop->index }}][content]" rows="6" class="form-control font-monospace form-control-sm"
                                                      placeholder="Contenido HTML...">{{ $t->content }}</textarea>
                                        </div>
                                    @empty
                                        <p class="text-muted" id="no-translations-msg">No hay traducciones adicionales.</p>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Tab: versions --}}
                            <div class="tab-pane fade p-4" id="tab-versions">
                                <h6 class="fw-bold mb-3">Historial de versiones</h6>
                                @if(!empty($templateVersions) && $templateVersions->count())
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Idioma</th>
                                                    <th>Asunto</th>
                                                    <th>Fecha</th>
                                                    <th>Nota</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($templateVersions as $version)
                                                    <tr>
                                                        <td>{{ $version->lang?->title ?? '—' }}</td>
                                                        <td>{{ Str::limit($version->subject, 60) }}</td>
                                                        <td>{{ $version->created_at?->format('d/m/Y H:i') }}</td>
                                                        <td>{{ $version->change_note }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted">Aún no hay versiones guardadas.</p>
                                @endif
                            </div>
                        @endif

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
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Variables legacy</h6>
                        <p class="small text-muted mb-3">Compatibles con plantillas antiguas.</p>
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

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Variables Mailer</h6>
                        <p class="small text-muted mb-3">Usa <code>{VARIABLE}</code> en plantillas sincronizadas con Mailer.</p>
                        <table class="table table-sm table-borderless small">
                            <thead class="table-light">
                                <tr>
                                    <th>Variable</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mailerVariables as $var)
                                    <tr>
                                        <td><code>{&#123; $var->name &#125;}</code></td>
                                        <td>{{ $var->description }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-muted">No hay variables configuradas.</td></tr>
                                @endforelse
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

    var previewUrl = @json(isset($template) ? route('remarketing.templates.preview', $template) : null);
    var translationIndex = {{ count($templateTranslations ?? []) }};
    var availableLangs = @json($langs ?? []);
    var isDarkMode = false;

    var darkCss = [
        'html,body{background:#1e1e2e!important;color:#cdd6f4!important}',
        'table,td,th{background:#1e1e2e!important;color:#cdd6f4!important;border-color:#45475a!important}',
        'a{color:#89b4fa!important}',
        'h1,h2,h3,h4,h5,h6{color:#cba6f7!important}',
        'img{opacity:.9}',
    ].join('');

    // Preview tab
    $('#tab-preview-btn').on('click', function () {
        refreshPreview();
    });

    $('#btn-refresh-preview').on('click', function () {
        refreshPreview();
    });

    $('#previewLangSelect').on('change', function () {
        refreshPreview();
    });

    $('#btn-toggle-dark').on('click', function () {
        isDarkMode = !isDarkMode;
        if (isDarkMode) {
            $(this).removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#preview-wrapper').css('background', '#1e1e2e');
        } else {
            $(this).removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#preview-wrapper').css('background', '');
        }
        refreshPreview();
    });

    function injectDarkMode(html) {
        if (!isDarkMode) { return html; }
        var style = '<style id="__dark_override">' + darkCss + '</style>';
        if (html.indexOf('</head>') !== -1) {
            return html.replace('</head>', style + '</head>');
        }
        return style + html;
    }

    function refreshPreview() {
        var langId = $('#previewLangSelect').val();

        if (previewUrl) {
            $.get(previewUrl, { lang_id: langId }, function (html) {
                $('#html-preview-frame').attr('srcdoc', injectDarkMode(html));
            }).fail(function () {
                fallbackPreview();
            });
        } else {
            fallbackPreview();
        }
    }

    function fallbackPreview() {
        var html = $('#htmlContent').val();
        html = html
            .replace(/\{\{firstName\}\}/g, 'Juan')
            .replace(/\{\{lastName\}\}/g, 'García')
            .replace(/\{\{email\}\}/g, 'juan@ejemplo.com')
            .replace(/\{\{storeName\}\}/g, 'Mi tienda')
            .replace(/\{\{unsubscribeUrl\}\}/g, '#')
            .replace(/\{\{preferencesUrl\}\}/g, '#')
            .replace(/\{\{trackingPixel\}\}/g, '');

        var fallback = '<p style="font-family:sans-serif;color:#888;text-align:center;padding:40px">El HTML está vacío</p>';
        $('#html-preview-frame').attr('srcdoc', injectDarkMode(html || fallback));
    }

    // Translations
    $('#btn-add-translation').on('click', function () {
        $('#no-translations-msg').hide();

        var options = '<option value="">Seleccionar idioma...</option>';
        availableLangs.forEach(function (lang) {
            options += '<option value="' + lang.id + '">' + lang.title + '</option>';
        });

        var html = '<div class="translation-row border rounded p-3 mb-3" data-index="' + translationIndex + '">' +
            '<div class="d-flex justify-content-between align-items-center mb-2">' +
            '<select name="translations[' + translationIndex + '][lang_id]" class="form-select form-select-sm lang-select" style="width:auto" required>' + options + '</select>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-translation"><i class="fas fa-trash"></i></button>' +
            '</div>' +
            '<div class="mb-2"><input type="text" name="translations[' + translationIndex + '][subject]" class="form-control form-control-sm" placeholder="Asunto"></div>' +
            '<div class="mb-2"><input type="text" name="translations[' + translationIndex + '][preheader]" class="form-control form-control-sm" placeholder="Preheader"></div>' +
            '<textarea name="translations[' + translationIndex + '][content]" rows="6" class="form-control font-monospace form-control-sm" placeholder="Contenido HTML..."></textarea>' +
            '</div>';

        $('#translations-container').append(html);
        translationIndex++;
    });

    $(document).on('click', '.btn-remove-translation', function () {
        $(this).closest('.translation-row').remove();
        if ($('.translation-row').length === 0) {
            $('#no-translations-msg').show();
        }
    });

});
</script>
@endpush
