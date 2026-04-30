@extends('layouts.theme')

@section('title', 'Opciones del tema')

@section('page_header')
    @include('core::components.card', ['title' => 'Opciones del tema'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- ── Columna principal ── --}}
            <div class="col-lg-8">
                <form action="{{ route('settings.theme.options.update') }}" method="POST" enctype="multipart/form-data" id="theme-options-form">
                    @csrf

                    <div class="card">

                        {{-- ===== GENERAL ===== --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">General</h6>
                            <p class="text-muted mb-3">Información de contacto y datos básicos del sitio.</p>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Teléfono</label>
                                    <input type="text" name="phone" class="form-control"
                                           value="{{ $o('phone') }}" placeholder="+57 300 000 0000">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Email de contacto</label>
                                    <input type="email" name="email" class="form-control"
                                           value="{{ $o('email') }}" placeholder="info@ejemplo.com">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Dirección</label>
                                    <input type="text" name="address" class="form-control"
                                           value="{{ $o('address') }}" placeholder="Calle Ejemplo 123, Ciudad">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Horario</label>
                                    <input type="text" name="opening_hours" class="form-control"
                                           value="{{ $o('opening_hours') }}" placeholder="Lun-Vie 9:00-18:00">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Copyright</label>
                                    <input type="text" name="copyright" class="form-control"
                                           value="{{ $o('copyright') }}" placeholder="© {{ date('Y') }} Mi Empresa">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Precargador</label>
                                    <select name="preloader_enabled" class="form-select">
                                        <option value="" {{ $o('preloader_enabled') !== 'yes' ? 'selected' : '' }}>Oculto</option>
                                        <option value="yes" {{ $o('preloader_enabled') === 'yes' ? 'selected' : '' }}>Mostrar</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">CTA flotante (WhatsApp + Llamar)</label>
                                    <select name="cta_floating_enabled" class="form-select">
                                        <option value="1" {{ $o('cta_floating_enabled', '1') === '1' ? 'selected' : '' }}>Mostrar en todas las páginas</option>
                                        <option value="0" {{ $o('cta_floating_enabled', '1') !== '1' ? 'selected' : '' }}>Oculto</option>
                                    </select>
                                    <small class="text-muted">Usa el número del campo "Teléfono" arriba.</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- ===== SEO GLOBAL (fallbacks) ===== --}}
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="fw-bold text-dark mb-0">SEO global</h6>
                                <a href="{{ route('settings.seo.metas.index') }}" class="small text-decoration-none">
                                    Gestionar SEO por página <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-3">
                                Defaults globales del sitio. El título y descripción por página se gestionan desde el
                                <strong>módulo SEO</strong>.
                            </p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Título del sitio</label>
                                    <input type="text" name="site_title" class="form-control"
                                           value="{{ $o('site_title') }}" placeholder="Caixilharia Blanco">
                                    <small class="text-muted">Usado como sufijo en títulos y en <code>og:site_name</code>.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Indexación global</label>
                                    <select name="seo_index" class="form-select">
                                        <option value="yes" {{ $o('seo_index', 'yes') === 'yes' ? 'selected' : '' }}>Permitir indexación</option>
                                        <option value="" {{ $o('seo_index', 'yes') !== 'yes' ? 'selected' : '' }}>Bloquear todo el sitio (noindex)</option>
                                    </select>
                                    <small class="text-muted">Switch global. Úsalo solo en mantenimiento.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Imagen Open Graph default</label>
                                    <div class="logo-card logo-card--og {{ $o('og_image') ? 'has-image' : 'is-empty' }}"
                                         id="logo-card-og_image"
                                         data-key="og_image" data-label="Imagen Open Graph">
                                        <div class="logo-card__preview logo-card__preview--og">
                                            <img src="{{ $o('og_image') }}" alt="Imagen Open Graph" id="logo-img-og_image" class="{{ $o('og_image') ? '' : 'd-none' }}">
                                            <div class="logo-card__empty {{ $o('og_image') ? 'd-none' : '' }}">
                                                <i class="fas fa-cloud-arrow-up"></i>
                                                <span>Click para subir · Recomendado 1200×630 px</span>
                                            </div>
                                            <div class="logo-card__overlay">
                                                <span><i class="fas fa-image me-1"></i> Cambiar</span>
                                            </div>
                                        </div>
                                        <div class="logo-card__body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="logo-card__title">Imagen para compartir en redes</div>
                                                    <small class="text-muted">Fallback cuando una página no tiene imagen propia.</small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-link logo-clear-btn {{ $o('og_image') ? '' : 'd-none' }}"
                                                        data-key="og_image" title="Quitar imagen">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="og_image" id="logo-input-og_image" value="{{ $o('og_image') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- ===== REDES SOCIALES ===== --}}
                        <div class="card-body">
                            <div class="section-head">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Redes sociales</h6>
                                    <p class="text-muted mb-0">Perfiles sociales que aparecen en el sitio.</p>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm add-btn" id="add-social-link">
                                    <i class="fas fa-plus me-1"></i> Agregar
                                </button>
                            </div>

                            <div id="social-links-container" class="stack-list mt-3">
                                @foreach($socialLinks as $i => $link)
                                    <div class="social-row repeatable-row">
                                        <div class="social-row__body">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-md-4">
                                                    <select class="form-select form-select-sm social-preset">
                                                        <option value="">— Selecciona red —</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-8">
                                                    <input type="url" name="social_links[{{ $i }}][url]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $link['url'] ?? '' }}" placeholder="https://...">
                                                </div>
                                            </div>
                                            <input type="hidden" name="social_links[{{ $i }}][name]"  class="social-name"  value="{{ $link['name']  ?? '' }}">
                                            <input type="hidden" name="social_links[{{ $i }}][icon]"  class="social-icon"  value="{{ $link['icon']  ?? '' }}" data-icon-target>
                                            <input type="hidden" name="social_links[{{ $i }}][color]" class="social-color" value="{{ $link['color'] ?? '#6c757d' }}">
                                        </div>
                                        <button type="button" class="btn btn-sm btn-link text-muted remove-row" title="Quitar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            <div class="empty-hint{{ count($socialLinks) ? ' d-none' : '' }}" id="social-empty">
                                <i class="fas fa-share-nodes"></i>
                                <span>No hay redes sociales agregadas.</span>
                            </div>

                            <template id="tpl-social-link">
                                <div class="social-row repeatable-row">
                                    <div class="social-row__body">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-4">
                                                <select class="form-select form-select-sm social-preset">
                                                    <option value="">— Selecciona red —</option>
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="url" name="social_links[__IDX__][url]"
                                                       class="form-control form-control-sm" placeholder="https://...">
                                            </div>
                                        </div>
                                        <input type="hidden" name="social_links[__IDX__][name]"  class="social-name"  value="">
                                        <input type="hidden" name="social_links[__IDX__][icon]"  class="social-icon"  value="" data-icon-target>
                                        <input type="hidden" name="social_links[__IDX__][color]" class="social-color" value="#6c757d">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-muted remove-row" title="Quitar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <hr class="my-0">

                        {{-- ===== MENSAJES DE ENCABEZADO ===== --}}
                        <div class="card-body">
                            <div class="section-head">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Mensajes de encabezado</h6>
                                    <p class="text-muted mb-0">Textos informativos en la barra superior del sitio.</p>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm add-btn" id="add-header-message">
                                    <i class="fas fa-plus me-1"></i> Agregar
                                </button>
                            </div>

                            <div id="header-messages-container" class="stack-list mt-3">
                                @foreach($headerMessages as $i => $msg)
                                    <div class="hmsg-row repeatable-row">
                                        <button type="button" class="hmsg-row__chip hmsg-icon-btn" title="Elegir icono">
                                            <i class="{{ $msg['icon'] ?? 'fas fa-bullhorn' }}" data-icon-target></i>
                                        </button>
                                        <div class="hmsg-row__body">
                                            <input type="text" name="header_messages[{{ $i }}][message]"
                                                   class="form-control form-control-sm"
                                                   value="{{ $msg['message'] ?? '' }}" placeholder="Mensaje (ej. Envío gratuito desde $50)">
                                            <div class="row g-2 mt-1">
                                                <div class="col-7">
                                                    <input type="text" name="header_messages[{{ $i }}][link]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $msg['link'] ?? '' }}" placeholder="Enlace (/envios) — opcional">
                                                </div>
                                                <div class="col-5">
                                                    <input type="text" name="header_messages[{{ $i }}][link_text]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $msg['link_text'] ?? '' }}" placeholder="Texto del enlace">
                                                </div>
                                            </div>
                                            <input type="hidden" name="header_messages[{{ $i }}][icon]"
                                                   class="hmsg-icon" value="{{ $msg['icon'] ?? 'fas fa-bullhorn' }}">
                                        </div>
                                        <button type="button" class="btn btn-sm btn-link text-muted remove-row" title="Quitar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            <div class="empty-hint{{ count($headerMessages) ? ' d-none' : '' }}" id="hmsg-empty">
                                <i class="fas fa-bullhorn"></i>
                                <span>No hay mensajes de encabezado.</span>
                            </div>

                            <template id="tpl-header-message">
                                <div class="hmsg-row repeatable-row">
                                    <button type="button" class="hmsg-row__chip hmsg-icon-btn" title="Elegir icono">
                                        <i class="fas fa-bullhorn" data-icon-target></i>
                                    </button>
                                    <div class="hmsg-row__body">
                                        <input type="text" name="header_messages[__IDX__][message]"
                                               class="form-control form-control-sm" placeholder="Mensaje (ej. Envío gratuito desde $50)">
                                        <div class="row g-2 mt-1">
                                            <div class="col-7">
                                                <input type="text" name="header_messages[__IDX__][link]"
                                                       class="form-control form-control-sm" placeholder="Enlace (/envios) — opcional">
                                            </div>
                                            <div class="col-5">
                                                <input type="text" name="header_messages[__IDX__][link_text]"
                                                       class="form-control form-control-sm" placeholder="Texto del enlace">
                                            </div>
                                        </div>
                                        <input type="hidden" name="header_messages[__IDX__][icon]" class="hmsg-icon" value="fas fa-bullhorn">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-muted remove-row" title="Quitar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <hr class="my-0">

                        {{-- ===== IDENTIDAD VISUAL ===== --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Identidad visual</h6>
                            <p class="text-muted mb-3">Haz clic en cualquier tarjeta para elegir una imagen desde la biblioteca de medios.</p>

                            <div class="row g-3">
                                @php
                                    $assets = [
                                        ['logo',       'Logotipo principal', 'Sobre fondo claro.',          'logo-card--light'],
                                        ['logo_light', 'Logotipo claro',     'Para fondos oscuros.',        'logo-card--dark'],
                                        ['favicon',    'Favicon',            '.ico o .png 32×32 px.',       'logo-card--favicon'],
                                    ];
                                @endphp

                                @foreach($assets as [$key, $label, $help, $variant])
                                    <div class="col-md-4">
                                        <div class="logo-card {{ $variant }} {{ $o($key) ? 'has-image' : 'is-empty' }}"
                                             id="logo-card-{{ $key }}"
                                             data-key="{{ $key }}" data-label="{{ $label }}">
                                            <div class="logo-card__preview">
                                                <img src="{{ $o($key) }}" alt="{{ $label }}" id="logo-img-{{ $key }}" class="{{ $o($key) ? '' : 'd-none' }}">
                                                <div class="logo-card__empty {{ $o($key) ? 'd-none' : '' }}">
                                                    <i class="fas fa-cloud-arrow-up"></i>
                                                    <span>Click para subir</span>
                                                </div>
                                                <div class="logo-card__overlay">
                                                    <span><i class="fas fa-image me-1"></i> Cambiar</span>
                                                </div>
                                            </div>
                                            <div class="logo-card__body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <div class="logo-card__title">{{ $label }}</div>
                                                        <small class="text-muted">{{ $help }}</small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-link text-muted logo-clear-btn {{ $o($key) ? '' : 'd-none' }}"
                                                            data-key="{{ $key }}" title="Quitar imagen">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <input type="hidden" name="{{ $key }}" id="logo-input-{{ $key }}" value="{{ $o($key) }}">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- ===== ESTILO VISUAL ===== --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Estilo visual</h6>
                            <p class="text-muted mb-3">Tipografía y diseño del encabezado.</p>

                            @php
                                $fonts = ['Roboto','Open Sans','Lato','Montserrat','Poppins','Raleway','Nunito','Source Sans Pro','Ubuntu','Playfair Display'];
                                $headerStyles = [
                                    'style1' => ['Logo izquierda', 'Navegación horizontal con logo alineado a la izquierda.'],
                                    'style2' => ['Logo centrado',  'Menú dividido con logo en el centro.'],
                                    'style3' => ['Minimalista',    'Logo a la izquierda y menú tipo hamburguesa.'],
                                ];
                                $currentHeader = $o('header_style', 'style1') ?: 'style1';
                                [$currentHeaderTitle, $currentHeaderDesc] = $headerStyles[$currentHeader] ?? $headerStyles['style1'];
                            @endphp

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Fuente principal</label>
                                    <select name="primary_font" class="form-select font-select" data-preview="#font-preview-primary">
                                        @foreach($fonts as $font)
                                            <option value="{{ $font }}" {{ $o('primary_font') === $font ? 'selected' : '' }}>{{ $font }}</option>
                                        @endforeach
                                    </select>
                                    <div class="font-preview mt-2" id="font-preview-primary">
                                        <span class="font-preview__aa">Aa</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Fuente secundaria</label>
                                    <select name="secondary_font" class="form-select font-select" data-preview="#font-preview-secondary">
                                        @foreach($fonts as $font)
                                            <option value="{{ $font }}" {{ $o('secondary_font') === $font ? 'selected' : '' }}>{{ $font }}</option>
                                        @endforeach
                                    </select>
                                    <div class="font-preview mt-2" id="font-preview-secondary">
                                        <span class="font-preview__aa">Aa</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Estilo de encabezado</label>
                                    <div class="header-style-summary" data-bs-toggle="modal" data-bs-target="#headerStyleModal">
                                        <div class="header-style-summary__mockup header-style-card__mockup--{{ $currentHeader }}" id="header-style-summary-mockup">
                                            <span class="mock-logo"></span>
                                            <span class="mock-menu"></span>
                                            <span class="mock-menu"></span>
                                            <span class="mock-menu"></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold text-dark" id="header-style-title">{{ $currentHeaderTitle }}</div>
                                            <div class="text-muted small" id="header-style-desc">{{ $currentHeaderDesc }}</div>
                                        </div>
                                        <button type="button" class="header-style-summary__edit" title="Cambiar estilo" aria-label="Cambiar estilo de encabezado">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="header_style" id="header_style" value="{{ $currentHeader }}">
                                </div>
                            </div>

                            <hr class="my-4">

                            {{-- Fuentes adicionales --}}
                            <div class="section-head">
                                <div>
                                    <h6 class="fw-semibold text-dark mb-1">Fuentes adicionales</h6>
                                    <p class="text-muted mb-0 small">Carga fuentes de Google Fonts u otras fuentes web.</p>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm add-btn" id="add-extra-font">
                                    <i class="fas fa-plus me-1"></i> Agregar
                                </button>
                            </div>

                            <div id="extra-fonts-container" class="stack-list mt-3">
                                @foreach($extraFonts as $i => $font)
                                    <div class="font-row repeatable-row">
                                        <div class="font-row__chip">
                                            <i class="fas fa-font"></i>
                                        </div>
                                        <div class="font-row__body">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <input type="text" name="extra_fonts[{{ $i }}][name]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $font['name'] ?? '' }}" placeholder="Nombre (ej. Inter)">
                                                </div>
                                                <div class="col-md-8">
                                                    <input type="url" name="extra_fonts[{{ $i }}][url]"
                                                           class="form-control form-control-sm"
                                                           value="{{ $font['url'] ?? '' }}" placeholder="URL de la fuente (Google Fonts, etc.)">
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-link text-muted remove-row" title="Quitar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            <div class="empty-hint{{ count($extraFonts) ? ' d-none' : '' }}" id="fonts-empty">
                                <i class="fas fa-font"></i>
                                <span>No hay fuentes adicionales.</span>
                            </div>

                            <template id="tpl-extra-font">
                                <div class="font-row repeatable-row">
                                    <div class="font-row__chip">
                                        <i class="fas fa-font"></i>
                                    </div>
                                    <div class="font-row__body">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <input type="text" name="extra_fonts[__IDX__][name]" class="form-control form-control-sm" placeholder="Nombre (ej. Inter)">
                                            </div>
                                            <div class="col-md-8">
                                                <input type="url" name="extra_fonts[__IDX__][url]" class="form-control form-control-sm" placeholder="URL de la fuente (Google Fonts, etc.)">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-muted remove-row" title="Quitar">
                                        <i class="fas fa-times"></i>
                                    </button>
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

    {{-- ===== MODAL: Estilo de encabezado ===== --}}
    <div class="modal fade" id="headerStyleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Estilo de encabezado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Selecciona cómo se verá la barra superior del sitio.</p>
                    <div class="header-style-grid">
                        @foreach($headerStyles as $val => [$title, $desc])
                            <label class="header-style-card {{ $currentHeader === $val ? 'is-active' : '' }}" data-value="{{ $val }}" data-title="{{ $title }}" data-desc="{{ $desc }}">
                                <input type="radio" name="header_style_choice" value="{{ $val }}"
                                       {{ $currentHeader === $val ? 'checked' : '' }}>
                                <div class="header-style-card__mockup header-style-card__mockup--{{ $val }}">
                                    <span class="mock-logo"></span>
                                    <span class="mock-menu"></span>
                                    <span class="mock-menu"></span>
                                    <span class="mock-menu"></span>
                                </div>
                                <div class="header-style-card__title">{{ $title }}</div>
                                <div class="header-style-card__desc">{{ $desc }}</div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="header-style-apply" data-bs-dismiss="modal">Aplicar selección</button>
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MODAL: Elegir icono ===== --}}
    <div class="modal fade" id="iconPickerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Elegir icono</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="icon-grid">
                        @foreach(['fas fa-truck','fas fa-bullhorn','fas fa-gift','fas fa-percent','fas fa-tag','fas fa-bolt','fas fa-phone','fas fa-envelope','fas fa-clock','fas fa-star','fas fa-headset','fas fa-shield-halved','fas fa-credit-card','fas fa-rotate-left','fas fa-check','fas fa-circle-info','fas fa-fire','fas fa-heart'] as $ic)
                            <button type="button" class="icon-grid__btn" data-icon="{{ $ic }}" title="{{ $ic }}">
                                <i class="{{ $ic }}"></i>
                            </button>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <label class="form-label small text-muted mb-1">O escribe una clase Font Awesome personalizada</label>
                        <input type="text" class="form-control form-control-sm" id="icon-custom-input" placeholder="ej. fas fa-user">
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="icon-apply" data-bs-dismiss="modal">Aplicar</button>
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto&family=Open+Sans&family=Lato&family=Montserrat&family=Poppins&family=Raleway&family=Nunito&family=Source+Sans+Pro&family=Ubuntu&family=Playfair+Display&display=swap">
<style>
.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.section-head .add-btn { flex-shrink: 0; }

.stack-list { display: flex; flex-direction: column; gap: .5rem; }

.repeatable-row {
    display: flex;
    gap: .75rem;
    align-items: center;
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    padding: .625rem .75rem;
    background: #fff;
    transition: border-color .15s ease;
}
.repeatable-row:hover { border-color: #cbd5e1; }
.repeatable-row .remove-row {
    flex-shrink: 0;
    padding: .25rem .5rem;
    color: #b10100 !important;
}
.repeatable-row .remove-row:hover {
    color: #7f0100 !important;
}

.empty-hint {
    padding: 1.5rem;
    text-align: center;
    color: #9ca3af;
    border: 1px dashed #e5e7eb;
    border-radius: .5rem;
    background: #fafafa;
    margin-top: .75rem;
}
.empty-hint i { display: block; font-size: 1.5rem; margin-bottom: .25rem; opacity: .6; }

.social-row__body { flex: 1 1 auto; min-width: 0; }
.social-row {
    padding: .5rem .75rem;
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    background: #fff;
    transition: border-color .15s ease;
}
.social-row:hover { border-color: #d1d5db; }

.header-style-summary__edit {
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .95rem;
    transition: all .15s ease;
    cursor: pointer;
}
.header-style-summary__edit:hover {
    background: #e5e7eb;
    color: #374151;
    border-color: #d1d5db;
}

.hmsg-row__chip {
    flex: 0 0 44px;
    width: 44px;
    height: 44px;
    border-radius: .5rem;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    padding: 0;
    cursor: pointer;
    transition: all .15s ease;
}
.hmsg-row__chip:hover { background: #fee2e2; color: #b10100; border-color: #b10100; }
.hmsg-row__body { flex: 1 1 auto; min-width: 0; }

.font-row__chip {
    flex: 0 0 44px;
    width: 44px;
    height: 44px;
    border-radius: .5rem;
    background: #fee2e2;
    color: #b10100;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
}
.font-row__body { flex: 1 1 auto; min-width: 0; }

.logo-card {
    position: relative;
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #fff;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.logo-card:hover { border-color: #b10100; box-shadow: 0 2px 8px rgba(177,1,0,.12); }
.logo-card__preview {
    position: relative;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    cursor: pointer;
    border-bottom: 1px solid #e5e7eb;
}
.logo-card__preview img { max-height: 100%; max-width: 100%; object-fit: contain; }
.logo-card--light .logo-card__preview {
    background: #ffffff;
    background-image: linear-gradient(45deg, #f5f5f5 25%, transparent 25%, transparent 75%, #f5f5f5 75%), linear-gradient(45deg, #f5f5f5 25%, transparent 25%, transparent 75%, #f5f5f5 75%);
    background-size: 16px 16px;
    background-position: 0 0, 8px 8px;
}
.logo-card--dark .logo-card__preview { background: #1f2937; }
.logo-card--favicon .logo-card__preview { background: #fafafa; }
.logo-card--favicon .logo-card__preview img { max-height: 64px; max-width: 64px; }
.logo-card--og .logo-card__preview {
    height: 200px;
    background: #f8fafc;
    background-image:
        linear-gradient(45deg, #eef2f6 25%, transparent 25%, transparent 75%, #eef2f6 75%),
        linear-gradient(45deg, #eef2f6 25%, transparent 25%, transparent 75%, #eef2f6 75%);
    background-size: 20px 20px;
    background-position: 0 0, 10px 10px;
}
.logo-card--og .logo-card__preview img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}
.logo-card__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #9ca3af;
    font-size: .85rem;
    pointer-events: none;
}
.logo-card--dark .logo-card__empty { color: #9ca3af; }
.logo-card__empty i { font-size: 1.75rem; margin-bottom: .35rem; }
.logo-card__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, .65);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity .15s ease;
    font-weight: 600;
    pointer-events: none;
}
.logo-card.has-image .logo-card__preview:hover .logo-card__overlay { opacity: 1; }
.logo-card__body { padding: .625rem .75rem; background: #fff; }
.logo-card__title { font-weight: 600; font-size: .875rem; color: #111827; }

.font-preview {
    border: 1px dashed #e5e7eb;
    border-radius: .5rem;
    padding: .4rem .75rem;
    background: #fafafa;
    display: flex;
    align-items: center;
}
.font-preview__aa {
    font-size: 1.35rem;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.header-style-summary {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem;
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    background: #fff;
    cursor: pointer;
    transition: all .15s ease;
}
.header-style-summary:hover {
    border-color: #b10100;
    box-shadow: 0 2px 8px rgba(177,1,0,.12);
}
.header-style-summary__mockup {
    flex: 0 0 120px;
    height: 44px;
}

.header-style-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .75rem;
}
.header-style-card {
    position: relative;
    display: flex;
    flex-direction: column;
    padding: .75rem;
    border: 2px solid #e5e7eb;
    border-radius: .5rem;
    background: #fff;
    cursor: pointer;
    transition: all .15s ease;
    margin: 0;
}
.header-style-card:hover { border-color: #cbd5e1; }
.header-style-card.is-active {
    border-color: #b10100;
    box-shadow: 0 0 0 3px rgba(177, 1, 0, .15);
}
.header-style-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.header-style-card__mockup,
.header-style-summary__mockup {
    background: #f8fafc;
    border-radius: .35rem;
    padding: 0 .5rem;
    display: flex;
    align-items: center;
    gap: .35rem;
}
.header-style-card__mockup { height: 48px; margin-bottom: .5rem; }
.header-style-card__mockup .mock-logo,
.header-style-summary__mockup .mock-logo {
    width: 32px; height: 12px; background: #b10100; border-radius: 2px;
}
.header-style-card__mockup .mock-menu,
.header-style-summary__mockup .mock-menu {
    width: 16px; height: 4px; background: #cbd5e1; border-radius: 2px;
}
.header-style-card__mockup--style1 { justify-content: flex-start; }
.header-style-card__mockup--style1 .mock-menu:first-of-type { margin-left: auto; }
.header-style-card__mockup--style2 { justify-content: center; }
.header-style-card__mockup--style2 .mock-logo { order: 2; margin: 0 .5rem; }
.header-style-card__mockup--style2 .mock-menu:nth-of-type(1) { order: 1; }
.header-style-card__mockup--style2 .mock-menu:nth-of-type(2) { order: 3; }
.header-style-card__mockup--style2 .mock-menu:nth-of-type(3) { order: 4; }
.header-style-card__mockup--style3 { justify-content: space-between; }
.header-style-card__mockup--style3 .mock-menu:nth-of-type(1),
.header-style-card__mockup--style3 .mock-menu:nth-of-type(2) { display: none; }
.header-style-card__mockup--style3 .mock-menu:last-of-type {
    width: 16px; height: 12px; border-radius: 2px;
    background: repeating-linear-gradient(to bottom, #cbd5e1 0 2px, transparent 2px 5px);
}
.header-style-card__title { font-weight: 600; font-size: .875rem; color: #111827; }
.header-style-card__desc { color: #6b7280; font-size: .75rem; line-height: 1.3; margin-top: .15rem; }

.icon-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: .4rem;
}
.icon-grid__btn {
    aspect-ratio: 1;
    border: 1px solid #e5e7eb;
    border-radius: .35rem;
    background: #fff;
    color: #475569;
    font-size: 1.1rem;
    transition: all .15s ease;
}
.icon-grid__btn:hover { border-color: #b10100; color: #b10100; }
.icon-grid__btn.is-active {
    background: #b10100;
    color: #fff;
    border-color: #b10100;
}

@media (max-width: 767.98px) {
    .header-style-grid { grid-template-columns: 1fr; }
    .header-style-summary { flex-wrap: wrap; }
    .header-style-summary__mockup { flex-basis: 100%; }
    .icon-grid { grid-template-columns: repeat(5, 1fr); }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {

    var SOCIAL_PRESETS = [
        { key: 'facebook',  name: 'Facebook',    icon: 'fab fa-facebook-f',  color: '#1877F2' },
        { key: 'instagram', name: 'Instagram',   icon: 'fab fa-instagram',   color: '#E4405F' },
        { key: 'twitter',   name: 'Twitter / X', icon: 'fab fa-x-twitter',   color: '#000000' },
        { key: 'youtube',   name: 'YouTube',     icon: 'fab fa-youtube',     color: '#FF0000' },
        { key: 'tiktok',    name: 'TikTok',      icon: 'fab fa-tiktok',      color: '#000000' },
        { key: 'linkedin',  name: 'LinkedIn',    icon: 'fab fa-linkedin-in', color: '#0A66C2' },
        { key: 'whatsapp',  name: 'WhatsApp',    icon: 'fab fa-whatsapp',    color: '#25D366' },
        { key: 'telegram',  name: 'Telegram',    icon: 'fab fa-telegram',    color: '#229ED9' },
        { key: 'pinterest', name: 'Pinterest',   icon: 'fab fa-pinterest',   color: '#BD081C' },
        { key: 'github',    name: 'GitHub',      icon: 'fab fa-github',      color: '#181717' },
        { key: 'spotify',   name: 'Spotify',     icon: 'fab fa-spotify',     color: '#1DB954' }
    ];

    function populatePresetSelect($select) {
        if ($select.find('option').length > 1) return;
        SOCIAL_PRESETS.forEach(function (p) {
            $select.append('<option value="' + p.key + '">' + p.name + '</option>');
        });
    }

    function detectPreset($row) {
        var name = ($row.find('.social-name').val() || '').toLowerCase();
        return (SOCIAL_PRESETS.find(function (p) { return p.name.toLowerCase() === name || p.key === name; }) || {}).key || '';
    }

    function applyPreset($row, key) {
        var p = SOCIAL_PRESETS.find(function (x) { return x.key === key; });
        if (!p) return;
        $row.find('.social-name').val(p.name);
        $row.find('.social-icon').val(p.icon);
        $row.find('.social-color').val(p.color);
        paintSocialChip($row, p);
    }

    function paintSocialChip($row, preset) {
        var color = preset ? preset.color : ($row.find('.social-color').val() || '#6c757d');
        var icon  = preset ? preset.icon  : ($row.find('.social-icon').val()  || 'fas fa-share-alt');
        $row.find('.social-row__chip').css('background', color);
        $row.find('.social-row__chip [data-icon-target]').attr('class', icon);
    }

    $('#social-links-container .social-row').each(function () {
        var $row = $(this);
        var $sel = $row.find('.social-preset');
        populatePresetSelect($sel);
        var k = detectPreset($row);
        if (k) $sel.val(k);
        paintSocialChip($row);
    });

    $(document).on('change', '.social-preset', function () {
        applyPreset($(this).closest('.social-row'), $(this).val());
    });

    $(document).on('click', '.remove-row', function () {
        $(this).closest('.repeatable-row').remove();
        reindexRows('#social-links-container', 'social_links');
        reindexRows('#header-messages-container', 'header_messages');
        reindexRows('#extra-fonts-container', 'extra_fonts');
        toggleEmptyHints();
    });

    function reindexRows(container, prefix) {
        $(container + ' .repeatable-row').each(function (i) {
            $(this).find('[name]').each(function () {
                var name = $(this).attr('name');
                var re = new RegExp(prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\[\\d+\\]');
                $(this).attr('name', name.replace(re, prefix + '[' + i + ']'));
            });
        });
    }

    function toggleEmptyHints() {
        $('#social-empty').toggleClass('d-none', $('#social-links-container .social-row').length > 0);
        $('#hmsg-empty').toggleClass('d-none', $('#header-messages-container .hmsg-row').length > 0);
        $('#fonts-empty').toggleClass('d-none', $('#extra-fonts-container .font-row').length > 0);
    }

    function addRow(containerId, templateId) {
        var container = $(containerId);
        var idx = container.children('.repeatable-row').length;
        var html = $(templateId).html().replace(/__IDX__/g, idx);
        var $new = $(html);
        container.append($new);
        return $new;
    }

    $('#add-social-link').on('click', function () {
        var $row = addRow('#social-links-container', '#tpl-social-link');
        populatePresetSelect($row.find('.social-preset'));
        paintSocialChip($row);
        toggleEmptyHints();
    });

    $('#add-header-message').on('click', function () {
        addRow('#header-messages-container', '#tpl-header-message');
        toggleEmptyHints();
    });

    $('#add-extra-font').on('click', function () {
        addRow('#extra-fonts-container', '#tpl-extra-font');
        toggleEmptyHints();
    });

    var activeIconRow = null;
    $(document).on('click', '.hmsg-icon-btn', function () {
        activeIconRow = $(this).closest('.hmsg-row');
        var current = activeIconRow.find('.hmsg-icon').val() || '';
        $('#iconPickerModal .icon-grid__btn').removeClass('is-active');
        $('#iconPickerModal .icon-grid__btn[data-icon="' + current + '"]').addClass('is-active');
        $('#icon-custom-input').val(current);
        new bootstrap.Modal(document.getElementById('iconPickerModal')).show();
    });

    $(document).on('click', '.icon-grid__btn', function () {
        $('.icon-grid__btn').removeClass('is-active');
        $(this).addClass('is-active');
        $('#icon-custom-input').val($(this).data('icon'));
    });

    $('#icon-apply').on('click', function () {
        if (!activeIconRow) return;
        var icon = ($('#icon-custom-input').val() || '').trim() || 'fas fa-bullhorn';
        activeIconRow.find('.hmsg-icon').val(icon);
        activeIconRow.find('[data-icon-target]').attr('class', icon);
        activeIconRow = null;
    });

    function applyFontPreview($select) {
        $($select.data('preview')).css('font-family', '"' + $select.val() + '", sans-serif');
    }
    $('.font-select').each(function () { applyFontPreview($(this)); });
    $(document).on('change', '.font-select', function () { applyFontPreview($(this)); });

    $(document).on('change', '#headerStyleModal input[name="header_style_choice"]', function () {
        $('#headerStyleModal .header-style-card').removeClass('is-active');
        $(this).closest('.header-style-card').addClass('is-active');
    });

    $('#header-style-apply').on('click', function () {
        var $selected = $('#headerStyleModal input[name="header_style_choice"]:checked').closest('.header-style-card');
        if (!$selected.length) return;
        $('#header_style').val($selected.data('value'));
        $('#header-style-title').text($selected.data('title'));
        $('#header-style-desc').text($selected.data('desc'));
        $('#header-style-summary-mockup').attr('class', 'header-style-summary__mockup header-style-card__mockup--' + $selected.data('value'));
    });

    $(document).on('click', '.logo-card__preview', function () {
        var $card = $(this).closest('.logo-card');
        openLogoPicker($card.data('key'), $card.data('label'));
    });

    $(document).on('click', '.logo-clear-btn', function (e) {
        e.stopPropagation();
        setLogoValue($(this).data('key'), '');
    });

    function openLogoPicker(key, label) {
        window.MediaPicker.open({
            urls: {
                list:   '{{ route("media.list") }}',
                upload: '{{ route("media.files.upload") }}',
                base:   '{{ url("media") }}',
            },
            filter: 'image',
            title: 'Elegir imagen — ' + label,
            onSelect: function (fullUrl) {
                setLogoValue(key, fullUrl);
            }
        });
    }

    function setLogoValue(key, val) {
        $('#logo-input-' + key).val(val);
        var $img = $('#logo-img-' + key);
        var $card = $('#logo-card-' + key);
        var $empty = $card.find('.logo-card__empty');
        var $clear = $card.find('.logo-clear-btn');

        if (val) {
            $img.attr('src', val).removeClass('d-none');
            $empty.addClass('d-none');
            $card.addClass('has-image').removeClass('is-empty');
            $clear.removeClass('d-none');
        } else {
            $img.attr('src', '').addClass('d-none');
            $empty.removeClass('d-none');
            $card.removeClass('has-image').addClass('is-empty');
            $clear.addClass('d-none');
        }
    }

    toggleEmptyHints();
});
</script>
@endpush
