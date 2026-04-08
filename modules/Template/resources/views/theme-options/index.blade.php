@extends('layouts.theme')

@section('title', 'Opciones del tema')

@section('content')

    @include('core::components.card', ['title' => 'Opciones del tema'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- ── Columna principal ── --}}
            <div class="col-lg-8">
                <form action="{{ route('settings.theme.options.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card">

                        {{-- ===== GENERAL ===== --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">General</h6>
                            <p class="text-muted mb-3">Información de contacto y datos básicos del sitio.</p>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="preloader_enabled"
                                           name="preloader_enabled" value="yes"
                                           {{ $o('preloader_enabled') === 'yes' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="preloader_enabled">
                                        Mostrar precargador (preloader)
                                    </label>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Teléfono</label>
                                    <input type="text" name="phone" class="form-control"
                                           value="{{ $o('phone') }}" placeholder="+57 300 000 0000">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email de contacto</label>
                                    <input type="email" name="email" class="form-control"
                                           value="{{ $o('email') }}" placeholder="info@ejemplo.com">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Dirección</label>
                                    <input type="text" name="address" class="form-control"
                                           value="{{ $o('address') }}" placeholder="Calle Ejemplo 123, Ciudad">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Horario</label>
                                    <input type="text" name="opening_hours" class="form-control"
                                           value="{{ $o('opening_hours') }}" placeholder="Lun-Vie 9:00-18:00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Copyright</label>
                                    <input type="text" name="copyright" class="form-control"
                                           value="{{ $o('copyright') }}" placeholder="© {{ date('Y') }} Mi Empresa">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">URL de términos y condiciones</label>
                                    <input type="text" name="terms_url" class="form-control"
                                           value="{{ $o('terms_url') }}" placeholder="/terminos-y-condiciones">
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- ===== SEO ===== --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">SEO general</h6>
                            <p class="text-muted mb-3">Metadatos para motores de búsqueda y redes sociales.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Título del sitio</label>
                                    <input type="text" name="site_title" class="form-control"
                                           value="{{ $o('site_title') }}" placeholder="Nombre del sitio">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Título SEO</label>
                                    <input type="text" name="seo_title" class="form-control"
                                           value="{{ $o('seo_title') }}" placeholder="Título para buscadores">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Descripción SEO</label>
                                    <textarea name="seo_description" class="form-control" rows="2"
                                              placeholder="Descripción breve del sitio...">{{ $o('seo_description') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Imagen Open Graph (URL)</label>
                                    <input type="text" name="og_image" class="form-control"
                                           value="{{ $o('og_image') }}" placeholder="https://...">
                                    <small class="text-muted">Imagen que se muestra al compartir en redes sociales.</small>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="seo_index"
                                               name="seo_index" value="yes"
                                               {{ $o('seo_index', 'yes') === 'yes' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="seo_index">
                                            Permitir indexación en buscadores (robots index)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- ===== REDES SOCIALES ===== --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Redes sociales</h6>
                            <p class="text-muted mb-3">Perfiles sociales que se muestran en el sitio.</p>

                            <div id="social-links-container">
                                @foreach($socialLinks as $i => $link)
                                    <div class="repeatable-row border rounded p-3 mb-2">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-3">
                                                <label class="form-label text-muted small mb-1">Red social</label>
                                                <input type="text" name="social_links[{{ $i }}][name]"
                                                       class="form-control form-control-sm"
                                                       value="{{ $link['name'] ?? '' }}" placeholder="Facebook">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label text-muted small mb-1">Icono (Font Awesome)</label>
                                                <input type="text" name="social_links[{{ $i }}][icon]"
                                                       class="form-control form-control-sm"
                                                       value="{{ $link['icon'] ?? '' }}" placeholder="fab fa-facebook">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted small mb-1">URL</label>
                                                <input type="text" name="social_links[{{ $i }}][url]"
                                                       class="form-control form-control-sm"
                                                       value="{{ $link['url'] ?? '' }}" placeholder="https://...">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label text-muted small mb-1">Color</label>
                                                <input type="color" name="social_links[{{ $i }}][color]"
                                                       class="form-control form-control-color form-control-sm"
                                                       value="{{ $link['color'] ?? '#000000' }}">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end pb-1">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-row w-100">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="add-social-link">
                                <i class="fas fa-plus me-1"></i> Agregar red social
                            </button>
                            <template id="tpl-social-link">
                                <div class="repeatable-row border rounded p-3 mb-2">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label text-muted small mb-1">Red social</label>
                                            <input type="text" name="social_links[__IDX__][name]" class="form-control form-control-sm" placeholder="Facebook">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-muted small mb-1">Icono (Font Awesome)</label>
                                            <input type="text" name="social_links[__IDX__][icon]" class="form-control form-control-sm" placeholder="fab fa-facebook">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted small mb-1">URL</label>
                                            <input type="text" name="social_links[__IDX__][url]" class="form-control form-control-sm" placeholder="https://...">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label text-muted small mb-1">Color</label>
                                            <input type="color" name="social_links[__IDX__][color]" class="form-control form-control-color form-control-sm" value="#000000">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end pb-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row w-100">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <hr class="my-0">

                        {{-- ===== MENSAJES DE ENCABEZADO ===== --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Mensajes de encabezado</h6>
                            <p class="text-muted mb-3">Textos informativos en la barra superior del sitio.</p>

                            <div id="header-messages-container">
                                @foreach($headerMessages as $i => $msg)
                                    <div class="repeatable-row border rounded p-3 mb-2">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-2">
                                                <label class="form-label text-muted small mb-1">Icono</label>
                                                <input type="text" name="header_messages[{{ $i }}][icon]"
                                                       class="form-control form-control-sm"
                                                       value="{{ $msg['icon'] ?? '' }}" placeholder="fas fa-truck">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted small mb-1">Mensaje</label>
                                                <input type="text" name="header_messages[{{ $i }}][message]"
                                                       class="form-control form-control-sm"
                                                       value="{{ $msg['message'] ?? '' }}" placeholder="Mensaje...">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label text-muted small mb-1">Enlace</label>
                                                <input type="text" name="header_messages[{{ $i }}][link]"
                                                       class="form-control form-control-sm"
                                                       value="{{ $msg['link'] ?? '' }}" placeholder="/envios">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label text-muted small mb-1">Texto del enlace</label>
                                                <input type="text" name="header_messages[{{ $i }}][link_text]"
                                                       class="form-control form-control-sm"
                                                       value="{{ $msg['link_text'] ?? '' }}" placeholder="Ver más">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end pb-1">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-row w-100">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="add-header-message">
                                <i class="fas fa-plus me-1"></i> Agregar mensaje
                            </button>
                            <template id="tpl-header-message">
                                <div class="repeatable-row border rounded p-3 mb-2">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-2">
                                            <label class="form-label text-muted small mb-1">Icono</label>
                                            <input type="text" name="header_messages[__IDX__][icon]" class="form-control form-control-sm" placeholder="fas fa-truck">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted small mb-1">Mensaje</label>
                                            <input type="text" name="header_messages[__IDX__][message]" class="form-control form-control-sm" placeholder="Mensaje...">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-muted small mb-1">Enlace</label>
                                            <input type="text" name="header_messages[__IDX__][link]" class="form-control form-control-sm" placeholder="/envios">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-muted small mb-1">Texto del enlace</label>
                                            <input type="text" name="header_messages[__IDX__][link_text]" class="form-control form-control-sm" placeholder="Ver más">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end pb-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row w-100">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <hr class="my-0">

                        {{-- ===== IDENTIDAD VISUAL ===== --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Identidad visual</h6>
                            <p class="text-muted mb-3">URLs de los logotipos y el favicon del sitio.</p>

                            <div class="row g-3">
                                @foreach([
                                    ['logo',       'Logotipo principal', 'Logo en el encabezado (fondo claro). Ej: /storage/logo.png'],
                                    ['logo_light',  'Logotipo claro',     'Logo para fondos oscuros (versión blanca/clara).'],
                                    ['favicon',    'Favicon',            'Icono del sitio (16×16 o 32×32 px, .ico o .png).'],
                                ] as [$key, $label, $help])
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">{{ $label }}</label>

                                        {{-- Preview --}}
                                        <div id="logo-preview-{{ $key }}" class="mb-2{{ $o($key) ? '' : ' d-none' }}">
                                            <div class="p-2 border rounded d-inline-flex bg-light">
                                                <img src="{{ $o($key) }}" alt="{{ $label }}" id="logo-img-{{ $key }}"
                                                     style="max-height: 48px; max-width: 180px;">
                                            </div>
                                        </div>

                                        {{-- Input + botones --}}
                                        <div class="input-group">
                                            <input type="text" name="{{ $key }}" id="logo-input-{{ $key }}"
                                                   class="form-control"
                                                   value="{{ $o($key) }}"
                                                   placeholder="https://... o /storage/images/{{ $key }}.png">
                                            <button type="button" class="btn btn-outline-secondary logo-pick-btn"
                                                    data-key="{{ $key }}" data-label="{{ $label }}"
                                                    title="Elegir desde gestor de medios">
                                                <i class="fas fa-images"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger logo-clear-btn{{ $o($key) ? '' : ' d-none' }}"
                                                    data-key="{{ $key }}" title="Quitar imagen">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">{{ $help }}</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- ===== ESTILO VISUAL ===== --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Estilo visual</h6>
                            <p class="text-muted mb-3">Tipografía, diseño del encabezado y paleta de colores.</p>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Fuente principal</label>
                                    <select name="primary_font" class="form-select">
                                        @foreach(['Roboto','Open Sans','Lato','Montserrat','Poppins','Raleway','Nunito','Source Sans Pro','Ubuntu','Playfair Display'] as $font)
                                            <option value="{{ $font }}" {{ $o('primary_font') === $font ? 'selected' : '' }}>{{ $font }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Fuente secundaria</label>
                                    <select name="secondary_font" class="form-select">
                                        @foreach(['Roboto','Open Sans','Lato','Montserrat','Poppins','Raleway','Nunito','Source Sans Pro','Ubuntu','Playfair Display'] as $font)
                                            <option value="{{ $font }}" {{ $o('secondary_font') === $font ? 'selected' : '' }}>{{ $font }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Estilo de encabezado</label>
                                    <select name="header_style" class="form-select">
                                        <option value="style1" {{ $o('header_style') === 'style1' ? 'selected' : '' }}>Estilo 1 — logo izquierda</option>
                                        <option value="style2" {{ $o('header_style') === 'style2' ? 'selected' : '' }}>Estilo 2 — logo centro</option>
                                        <option value="style3" {{ $o('header_style') === 'style3' ? 'selected' : '' }}>Estilo 3 — minimalista</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="fw-semibold text-dark mb-2">Fuentes adicionales</h6>
                            <p class="text-muted mb-2">Agrega fuentes de Google Fonts u otras fuentes web para usarlas en el sitio.</p>

                            <div id="extra-fonts-container">
                                @foreach($extraFonts as $i => $font)
                                    <div class="repeatable-row border rounded p-3 mb-2">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-3">
                                                <label class="form-label text-muted small mb-1">Nombre de la fuente</label>
                                                <input type="text" name="extra_fonts[{{ $i }}][name]"
                                                       class="form-control form-control-sm"
                                                       value="{{ $font['name'] ?? '' }}" placeholder="Inter">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label text-muted small mb-1">URL de la fuente</label>
                                                <input type="text" name="extra_fonts[{{ $i }}][url]"
                                                       class="form-control form-control-sm"
                                                       value="{{ $font['url'] ?? '' }}" placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end pb-1">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-row w-100">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="add-extra-font">
                                <i class="fas fa-plus me-1"></i> Agregar fuente
                            </button>
                            <template id="tpl-extra-font">
                                <div class="repeatable-row border rounded p-3 mb-2">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label text-muted small mb-1">Nombre de la fuente</label>
                                            <input type="text" name="extra_fonts[__IDX__][name]" class="form-control form-control-sm" placeholder="Inter">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label text-muted small mb-1">URL de la fuente</label>
                                            <input type="text" name="extra_fonts[__IDX__][url]" class="form-control form-control-sm" placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end pb-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row w-100">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>

                    </div>

                </form>
            </div>

            {{-- ── Columna lateral ── --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Vista previa</h6>
                        <p class="text-muted mb-3">Verifica los cambios en el sitio público antes de guardar.</p>
                        <a href="/" target="_blank" class="btn btn-outline-secondary w-100">
                            Ver sitio web
                        </a>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">CSS personalizado</h6>
                        <p class="text-muted mb-3">Agrega estilos adicionales al tema sin modificar los archivos fuente.</p>
                        <a href="{{ route('settings.theme.custom-html') }}" class="btn btn-outline-secondary w-100">
                            Editar CSS / HTML
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Notas</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted ps-3 mb-0">
                            <li class="mb-2">Los logotipos deben ser URLs accesibles desde el navegador.</li>
                            <li class="mb-2">El favicon debe ser un archivo <code>.ico</code> o <code>.png</code> de 32×32 px.</li>
                            <li>Las fuentes adicionales se cargan en el <code>&lt;head&gt;</code> del sitio.</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // ── Eliminar filas repetibles ──
    $(document).on('click', '.remove-row', function () {
        $(this).closest('.repeatable-row').remove();
        reindexRows('#social-links-container', 'social_links');
        reindexRows('#header-messages-container', 'header_messages');
        reindexRows('#extra-fonts-container', 'extra_fonts');
    });

    function reindexRows(container, prefix) {
        $(container + ' .repeatable-row').each(function (i) {
            $(this).find('[name]').each(function () {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace(new RegExp(prefix + '\\[\\d+\\]'), prefix + '[' + i + ']'));
            });
        });
    }

    function addRow(containerId, templateId) {
        var container = $(containerId);
        var idx = container.children('.repeatable-row').length;
        var html = $(templateId).html().replace(/__IDX__/g, idx);
        container.append(html);
    }

    $('#add-social-link').on('click', function () {
        addRow('#social-links-container', '#tpl-social-link');
    });

    $('#add-header-message').on('click', function () {
        addRow('#header-messages-container', '#tpl-header-message');
    });

    $('#add-extra-font').on('click', function () {
        addRow('#extra-fonts-container', '#tpl-extra-font');
    });

    // ── Identidad visual — Media Picker ──
    $(document).on('click', '.logo-pick-btn', function () {
        var key   = $(this).data('key');
        var label = $(this).data('label');
        window.MediaPicker.open({
            urls: {
                list:   '{{ route("media.list") }}',
                upload: '{{ route("media.files.upload") }}',
                base:   '{{ url("media") }}',
            },
            filter: 'image',
            title: 'Elegir imagen — ' + label,
            onSelect: function (fullUrl) {
                $('#logo-input-' + key).val(fullUrl);
                $('#logo-img-' + key).attr('src', fullUrl);
                $('#logo-preview-' + key).removeClass('d-none');
                $('.logo-clear-btn[data-key="' + key + '"]').removeClass('d-none');
            }
        });
    });

    $(document).on('click', '.logo-clear-btn', function () {
        var key = $(this).data('key');
        $('#logo-input-' + key).val('');
        $('#logo-preview-' + key).addClass('d-none');
        $(this).addClass('d-none');
    });

    // Sync manual URL input → preview
    $(document).on('input', '[id^="logo-input-"]', function () {
        var key = this.id.replace('logo-input-', '');
        var val = $(this).val().trim();
        if (val) {
            $('#logo-img-' + key).attr('src', val);
            $('#logo-preview-' + key).removeClass('d-none');
            $('.logo-clear-btn[data-key="' + key + '"]').removeClass('d-none');
        } else {
            $('#logo-preview-' + key).addClass('d-none');
            $('.logo-clear-btn[data-key="' + key + '"]').addClass('d-none');
        }
    });

});
</script>
@endpush
