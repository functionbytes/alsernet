@extends('layouts.theme')

@section('title', 'Opciones del tema')

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('settings.theme.options.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card">
            <div class="card-header p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Opciones del tema</h5>
                    <p class="small mb-0 text-muted">Configura la apariencia y comportamiento general del sitio</p>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar cambios
                </button>
            </div>

            <div class="card-body p-0">
                <div class="row g-0">
                    <!-- Sidebar de tabs vertical -->
                    <div class="col-md-3 border-end">
                        <div class="nav flex-column nav-pills py-3 px-2" id="themeOptionsTabs" role="tablist" aria-orientation="vertical">
                            <button class="nav-link text-start active mb-1" data-bs-toggle="pill" data-bs-target="#tab-general" type="button">
                                <i class="fas fa-gear me-2 text-muted"></i> General
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-social" type="button">
                                <i class="fas fa-share-nodes me-2 text-muted"></i> Redes sociales
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-header-messages" type="button">
                                <i class="fas fa-message me-2 text-muted"></i> Mensajes de encabezado
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-contact-boxes" type="button">
                                <i class="fas fa-address-card me-2 text-muted"></i> Cuadros de contacto
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-style" type="button">
                                <i class="fas fa-palette me-2 text-muted"></i> Estilo
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-logo" type="button">
                                <i class="fas fa-image me-2 text-muted"></i> Logo
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-page" type="button">
                                <i class="fas fa-file me-2 text-muted"></i> Página
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-breadcrumb" type="button">
                                <i class="fas fa-chevron-right me-2 text-muted"></i> Migaja de pan
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-facebook" type="button">
                                <i class="fab fa-facebook me-2 text-muted"></i> Facebook
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-blog" type="button">
                                <i class="fas fa-blog me-2 text-muted"></i> Blog
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-commerce" type="button">
                                <i class="fas fa-cart-shopping me-2 text-muted"></i> Comercio
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-ecommerce-urls" type="button">
                                <i class="fas fa-link me-2 text-muted"></i> URLs de comercio
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-ecommerce-seo" type="button">
                                <i class="fas fa-magnifying-glass me-2 text-muted"></i> SEO de comercio
                            </button>
                            <button class="nav-link text-start mb-1" data-bs-toggle="pill" data-bs-target="#tab-newsletter" type="button">
                                <i class="fas fa-envelope me-2 text-muted"></i> Boletín emergente
                            </button>
                            <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-cookies" type="button">
                                <i class="fas fa-cookie me-2 text-muted"></i> Cookies
                            </button>
                        </div>
                    </div>

                    <!-- Contenido de tabs -->
                    <div class="col-md-9">
                        <div class="tab-content p-4" id="themeOptionsTabsContent">

                            {{-- ===== TAB: GENERAL ===== --}}
                            <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                                <h6 class="fw-bold mb-1">General</h6>
                                <p class="text-muted small mb-3">Configuración básica del sitio</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Precargador (preloader)</label>
                                    <div class="col-md-8">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="preloader_enabled" value="yes"
                                                   {{ $o('preloader_enabled') === 'yes' ? 'checked' : '' }}>
                                            <label class="form-check-label">Mostrar animación de carga</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Teléfono</label>
                                    <div class="col-md-8">
                                        <input type="text" name="phone" class="form-control" value="{{ $o('phone') }}" placeholder="+34 900 000 000">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Email de contacto</label>
                                    <div class="col-md-8">
                                        <input type="email" name="email" class="form-control" value="{{ $o('email') }}" placeholder="info@ejemplo.com">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Dirección</label>
                                    <div class="col-md-8">
                                        <input type="text" name="address" class="form-control" value="{{ $o('address') }}" placeholder="Calle Ejemplo 123, Ciudad">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Horario</label>
                                    <div class="col-md-8">
                                        <input type="text" name="opening_hours" class="form-control" value="{{ $o('opening_hours') }}" placeholder="Lun-Vie 9:00-18:00">
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="fw-bold mb-1">SEO general</h6>
                                <p class="text-muted small mb-3">Metadatos para motores de búsqueda</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Título del sitio</label>
                                    <div class="col-md-8">
                                        <input type="text" name="site_title" class="form-control" value="{{ $o('site_title') }}" placeholder="Mi Tienda Online">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Título SEO</label>
                                    <div class="col-md-8">
                                        <input type="text" name="seo_title" class="form-control" value="{{ $o('seo_title') }}" placeholder="Título para buscadores">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Descripción SEO</label>
                                    <div class="col-md-8">
                                        <textarea name="seo_description" class="form-control" rows="3" placeholder="Descripción breve del sitio...">{{ $o('seo_description') }}</textarea>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Indexar en buscadores</label>
                                    <div class="col-md-8">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="seo_index" value="yes"
                                                   {{ $o('seo_index', 'yes') === 'yes' ? 'checked' : '' }}>
                                            <label class="form-check-label">Permitir indexación (robots index)</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Imagen OG (Open Graph)</label>
                                    <div class="col-md-8">
                                        <input type="text" name="og_image" class="form-control" value="{{ $o('og_image') }}" placeholder="https://...">
                                        <small class="text-muted">URL de la imagen que se muestra al compartir en redes sociales</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">URL de términos y condiciones</label>
                                    <div class="col-md-8">
                                        <input type="text" name="terms_url" class="form-control" value="{{ $o('terms_url') }}" placeholder="/terminos-y-condiciones">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Copyright</label>
                                    <div class="col-md-8">
                                        <input type="text" name="copyright" class="form-control" value="{{ $o('copyright') }}" placeholder="© {{ date('Y') }} Mi Empresa">
                                    </div>
                                </div>
                            </div>

                            {{-- ===== TAB: REDES SOCIALES ===== --}}
                            <div class="tab-pane fade" id="tab-social" role="tabpanel">
                                <h6 class="fw-bold mb-1">Vínculos sociales</h6>
                                <p class="text-muted small mb-3">Agrega los perfiles de redes sociales del sitio</p>
                                <hr class="mb-4">

                                <div id="social-links-container">
                                    @foreach($socialLinks as $i => $link)
                                        <div class="repeatable-row border rounded p-3 mb-3">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="form-label small text-muted">Red social</label>
                                                    <input type="text" name="social_links[{{ $i }}][name]" class="form-control form-control-sm"
                                                           value="{{ $link['name'] ?? '' }}" placeholder="Facebook">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small text-muted">Icono (Font Awesome)</label>
                                                    <input type="text" name="social_links[{{ $i }}][icon]" class="form-control form-control-sm"
                                                           value="{{ $link['icon'] ?? '' }}" placeholder="fab fa-facebook">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted">URL</label>
                                                    <input type="text" name="social_links[{{ $i }}][url]" class="form-control form-control-sm"
                                                           value="{{ $link['url'] ?? '' }}" placeholder="https://facebook.com/mipagina">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small text-muted">Color</label>
                                                    <input type="color" name="social_links[{{ $i }}][color]" class="form-control form-control-color form-control-sm"
                                                           value="{{ $link['color'] ?? '#000000' }}">
                                                </div>
                                                <div class="col-md-1 text-end">
                                                    <label class="form-label small text-muted d-block">&nbsp;</label>
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-social-link">
                                    <i class="fas fa-plus me-1"></i> Agregar red social
                                </button>
                                <template id="tpl-social-link">
                                    <div class="repeatable-row border rounded p-3 mb-3">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted">Red social</label>
                                                <input type="text" name="social_links[__IDX__][name]" class="form-control form-control-sm" placeholder="Facebook">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted">Icono (Font Awesome)</label>
                                                <input type="text" name="social_links[__IDX__][icon]" class="form-control form-control-sm" placeholder="fab fa-facebook">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-muted">URL</label>
                                                <input type="text" name="social_links[__IDX__][url]" class="form-control form-control-sm" placeholder="https://...">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small text-muted">Color</label>
                                                <input type="color" name="social_links[__IDX__][color]" class="form-control form-control-color form-control-sm" value="#000000">
                                            </div>
                                            <div class="col-md-1 text-end">
                                                <label class="form-label small text-muted d-block">&nbsp;</label>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- ===== TAB: MENSAJES DE ENCABEZADO ===== --}}
                            <div class="tab-pane fade" id="tab-header-messages" role="tabpanel">
                                <h6 class="fw-bold mb-1">Mensajes de encabezado</h6>
                                <p class="text-muted small mb-3">Textos informativos que se muestran en la barra superior del sitio</p>
                                <hr class="mb-4">

                                <div id="header-messages-container">
                                    @foreach($headerMessages as $i => $msg)
                                        <div class="repeatable-row border rounded p-3 mb-3">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-md-2">
                                                    <label class="form-label small text-muted">Icono</label>
                                                    <input type="text" name="header_messages[{{ $i }}][icon]" class="form-control form-control-sm"
                                                           value="{{ $msg['icon'] ?? '' }}" placeholder="fas fa-truck">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted">Mensaje</label>
                                                    <input type="text" name="header_messages[{{ $i }}][message]" class="form-control form-control-sm"
                                                           value="{{ $msg['message'] ?? '' }}" placeholder="Envío gratis en pedidos +50€">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small text-muted">Enlace</label>
                                                    <input type="text" name="header_messages[{{ $i }}][link]" class="form-control form-control-sm"
                                                           value="{{ $msg['link'] ?? '' }}" placeholder="/envios">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small text-muted">Texto del enlace</label>
                                                    <input type="text" name="header_messages[{{ $i }}][link_text]" class="form-control form-control-sm"
                                                           value="{{ $msg['link_text'] ?? '' }}" placeholder="Ver más">
                                                </div>
                                                <div class="col-md-1 text-end">
                                                    <label class="form-label small text-muted d-block">&nbsp;</label>
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-header-message">
                                    <i class="fas fa-plus me-1"></i> Agregar mensaje
                                </button>
                                <template id="tpl-header-message">
                                    <div class="repeatable-row border rounded p-3 mb-3">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-2">
                                                <label class="form-label small text-muted">Icono</label>
                                                <input type="text" name="header_messages[__IDX__][icon]" class="form-control form-control-sm" placeholder="fas fa-truck">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-muted">Mensaje</label>
                                                <input type="text" name="header_messages[__IDX__][message]" class="form-control form-control-sm" placeholder="Envío gratis en pedidos +50€">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted">Enlace</label>
                                                <input type="text" name="header_messages[__IDX__][link]" class="form-control form-control-sm" placeholder="/envios">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small text-muted">Texto del enlace</label>
                                                <input type="text" name="header_messages[__IDX__][link_text]" class="form-control form-control-sm" placeholder="Ver más">
                                            </div>
                                            <div class="col-md-1 text-end">
                                                <label class="form-label small text-muted d-block">&nbsp;</label>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- ===== TAB: CUADROS DE CONTACTO ===== --}}
                            <div class="tab-pane fade" id="tab-contact-boxes" role="tabpanel">
                                <h6 class="fw-bold mb-1">Cuadros de información de contacto</h6>
                                <p class="text-muted small mb-3">Bloques informativos de contacto para el footer o páginas de contacto</p>
                                <hr class="mb-4">

                                <div id="contact-boxes-container">
                                    @foreach($contactBoxes as $i => $box)
                                        <div class="repeatable-row border rounded p-3 mb-3">
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label small text-muted">Nombre / Titulo</label>
                                                    <input type="text" name="contact_boxes[{{ $i }}][name]" class="form-control form-control-sm"
                                                           value="{{ $box['name'] ?? '' }}" placeholder="Oficina central">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small text-muted">Dirección</label>
                                                    <input type="text" name="contact_boxes[{{ $i }}][address]" class="form-control form-control-sm"
                                                           value="{{ $box['address'] ?? '' }}" placeholder="Calle Ejemplo 123">
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label small text-muted">Teléfono</label>
                                                    <input type="text" name="contact_boxes[{{ $i }}][phone]" class="form-control form-control-sm"
                                                           value="{{ $box['phone'] ?? '' }}" placeholder="+34 900 000 000">
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label small text-muted">Email</label>
                                                    <input type="email" name="contact_boxes[{{ $i }}][email]" class="form-control form-control-sm"
                                                           value="{{ $box['email'] ?? '' }}" placeholder="contacto@ejemplo.com">
                                                </div>
                                                <div class="col-md-2 text-end d-flex align-items-end justify-content-end">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-contact-box">
                                    <i class="fas fa-plus me-1"></i> Agregar cuadro
                                </button>
                                <template id="tpl-contact-box">
                                    <div class="repeatable-row border rounded p-3 mb-3">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted">Nombre / Titulo</label>
                                                <input type="text" name="contact_boxes[__IDX__][name]" class="form-control form-control-sm" placeholder="Oficina central">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted">Dirección</label>
                                                <input type="text" name="contact_boxes[__IDX__][address]" class="form-control form-control-sm" placeholder="Calle Ejemplo 123">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small text-muted">Teléfono</label>
                                                <input type="text" name="contact_boxes[__IDX__][phone]" class="form-control form-control-sm" placeholder="+34 900 000 000">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small text-muted">Email</label>
                                                <input type="email" name="contact_boxes[__IDX__][email]" class="form-control form-control-sm" placeholder="contacto@ejemplo.com">
                                            </div>
                                            <div class="col-md-2 text-end d-flex align-items-end justify-content-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- ===== TAB: ESTILO ===== --}}
                            <div class="tab-pane fade" id="tab-style" role="tabpanel">
                                <h6 class="fw-bold mb-1">Estilo visual</h6>
                                <p class="text-muted small mb-3">Tipografía y colores del tema</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Fuente principal</label>
                                    <div class="col-md-8">
                                        <select name="primary_font" class="form-select">
                                            @foreach(['Roboto','Open Sans','Lato','Montserrat','Poppins','Raleway','Nunito','Source Sans Pro','Ubuntu','Playfair Display'] as $font)
                                                <option value="{{ $font }}" {{ $o('primary_font') === $font ? 'selected' : '' }}>{{ $font }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Fuente secundaria</label>
                                    <div class="col-md-8">
                                        <select name="secondary_font" class="form-select">
                                            @foreach(['Roboto','Open Sans','Lato','Montserrat','Poppins','Raleway','Nunito','Source Sans Pro','Ubuntu','Playfair Display'] as $font)
                                                <option value="{{ $font }}" {{ $o('secondary_font') === $font ? 'selected' : '' }}>{{ $font }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Estilo de encabezado</label>
                                    <div class="col-md-8">
                                        <select name="header_style" class="form-select">
                                            <option value="style1" {{ $o('header_style') === 'style1' ? 'selected' : '' }}>Estilo 1 (logo izquierda)</option>
                                            <option value="style2" {{ $o('header_style') === 'style2' ? 'selected' : '' }}>Estilo 2 (logo centro)</option>
                                            <option value="style3" {{ $o('header_style') === 'style3' ? 'selected' : '' }}>Estilo 3 (minimalista)</option>
                                        </select>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="fw-bold mb-1">Colores</h6>
                                <p class="text-muted small mb-3">Paleta de colores del tema</p>
                                <hr class="mb-4">

                                @foreach([
                                    ['primary_color', 'Color primario', '#90bb13'],
                                    ['secondary_color', 'Color secundario', '#6c757d'],
                                    ['header_bg_color', 'Fondo del encabezado', '#ffffff'],
                                    ['footer_bg_color', 'Fondo del footer', '#1a1a2e'],
                                    ['footer_text_color', 'Texto del footer', '#ffffff'],
                                ] as [$key, $label, $default])
                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label control-label">{{ $label }}</label>
                                        <div class="col-md-8 d-flex align-items-center gap-2">
                                            <input type="color" name="{{ $key }}" class="form-control form-control-color"
                                                   value="{{ $o($key, $default) }}" style="width: 60px;">
                                            <input type="text" class="form-control color-text-sync" style="max-width:120px;"
                                                   value="{{ $o($key, $default) }}" placeholder="{{ $default }}">
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- ===== TAB: LOGO ===== --}}
                            <div class="tab-pane fade" id="tab-logo" role="tabpanel">
                                <h6 class="fw-bold mb-1">Identidad visual</h6>
                                <p class="text-muted small mb-3">Logotipos e icono del sitio</p>
                                <hr class="mb-4">

                                @foreach([
                                    ['logo', 'Logotipo principal', 'Logo que se muestra en el encabezado (oscuro)'],
                                    ['logo_light', 'Logotipo claro', 'Logo para fondos oscuros (versión blanca/clara)'],
                                    ['favicon', 'Favicon', 'Icono del sitio (16x16 o 32x32 px, formato .ico, .png)'],
                                ] as [$key, $label, $help])
                                    <div class="row mb-4">
                                        <label class="col-md-4 col-form-label control-label">{{ $label }}</label>
                                        <div class="col-md-8">
                                            @if($o($key))
                                                <div class="mb-2 p-2 border rounded d-inline-block bg-light">
                                                    <img src="{{ $o($key) }}" alt="{{ $label }}" style="max-height: 60px; max-width: 200px;">
                                                </div>
                                                <br>
                                            @endif
                                            <input type="text" name="{{ $key }}" class="form-control" value="{{ $o($key) }}"
                                                   placeholder="https://... o /storage/images/logo.png">
                                            <small class="text-muted">{{ $help }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- ===== TAB: PÁGINA ===== --}}
                            <div class="tab-pane fade" id="tab-page" role="tabpanel">
                                <h6 class="fw-bold mb-1">Configuración de páginas</h6>
                                <p class="text-muted small mb-3">Asigna las páginas principales del sitio</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">ID de página de inicio</label>
                                    <div class="col-md-8">
                                        <input type="number" name="homepage_id" class="form-control" value="{{ $o('homepage_id') }}"
                                               placeholder="ID de la página principal">
                                        <small class="text-muted">ID de la página del módulo Page que se usa como portada</small>
                                    </div>
                                </div>
                            </div>

                            {{-- ===== TAB: MIGAJA DE PAN ===== --}}
                            <div class="tab-pane fade" id="tab-breadcrumb" role="tabpanel">
                                <h6 class="fw-bold mb-1">Migaja de pan (breadcrumb)</h6>
                                <p class="text-muted small mb-3">Navegación de ruta que se muestra en las páginas interiores</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Mostrar breadcrumb</label>
                                    <div class="col-md-8">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="breadcrumb_enabled" value="yes"
                                                   {{ $o('breadcrumb_enabled', 'yes') === 'yes' ? 'checked' : '' }}>
                                            <label class="form-check-label">Activar la navegación de migajas de pan</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Imagen de fondo</label>
                                    <div class="col-md-8">
                                        <input type="text" name="breadcrumb_bg_image" class="form-control" value="{{ $o('breadcrumb_bg_image') }}"
                                               placeholder="https://...">
                                        <small class="text-muted">Imagen de fondo para la barra de breadcrumb (opcional)</small>
                                    </div>
                                </div>
                            </div>

                            {{-- ===== TAB: FACEBOOK ===== --}}
                            <div class="tab-pane fade" id="tab-facebook" role="tabpanel">
                                <h6 class="fw-bold mb-1">Integración de Facebook</h6>
                                <p class="text-muted small mb-3">Conecta tu sitio con la plataforma de Facebook</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">ID de página de Facebook</label>
                                    <div class="col-md-8">
                                        <input type="text" name="fb_page_id" class="form-control" value="{{ $o('fb_page_id') }}" placeholder="1234567890">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">ID de aplicación de Facebook</label>
                                    <div class="col-md-8">
                                        <input type="text" name="fb_app_id" class="form-control" value="{{ $o('fb_app_id') }}" placeholder="1234567890">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Administradores de FB</label>
                                    <div class="col-md-8">
                                        <input type="text" name="fb_admins" class="form-control" value="{{ $o('fb_admins') }}" placeholder="id1,id2">
                                        <small class="text-muted">IDs de administradores separados por coma</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Comentarios de Facebook</label>
                                    <div class="col-md-8">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="fb_comments_enabled" value="yes"
                                                   {{ $o('fb_comments_enabled') === 'yes' ? 'checked' : '' }}>
                                            <label class="form-check-label">Mostrar sección de comentarios de Facebook</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Chat de Facebook Messenger</label>
                                    <div class="col-md-8">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="fb_chat_enabled" value="yes"
                                                   {{ $o('fb_chat_enabled') === 'yes' ? 'checked' : '' }}>
                                            <label class="form-check-label">Mostrar widget de chat de Facebook</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ===== TAB: BLOG ===== --}}
                            <div class="tab-pane fade" id="tab-blog" role="tabpanel">
                                <h6 class="fw-bold mb-1">Blog</h6>
                                <p class="text-muted small mb-3">Configuración del módulo de blog</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Diseño del listado</label>
                                    <div class="col-md-8">
                                        <select name="blog_layout" class="form-select">
                                            <option value="grid" {{ $o('blog_layout') === 'grid' ? 'selected' : '' }}>Cuadrícula</option>
                                            <option value="list" {{ $o('blog_layout') === 'list' ? 'selected' : '' }}>Lista</option>
                                            <option value="masonry" {{ $o('blog_layout') === 'masonry' ? 'selected' : '' }}>Masonry</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">ID de página del blog</label>
                                    <div class="col-md-8">
                                        <input type="number" name="blog_page_id" class="form-control" value="{{ $o('blog_page_id') }}"
                                               placeholder="ID de la página del blog">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Artículos por página</label>
                                    <div class="col-md-8">
                                        <input type="number" name="blog_posts_per_page" class="form-control" value="{{ $o('blog_posts_per_page', '9') }}"
                                               min="1" max="100">
                                    </div>
                                </div>
                            </div>

                            {{-- ===== TAB: COMERCIO ===== --}}
                            <div class="tab-pane fade" id="tab-commerce" role="tabpanel">
                                <h6 class="fw-bold mb-1">Comercio electrónico</h6>
                                <p class="text-muted small mb-3">Configuración de la tienda online</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Diseño de lista de productos</label>
                                    <div class="col-md-8">
                                        <select name="product_list_layout" class="form-select">
                                            <option value="grid" {{ $o('product_list_layout', 'grid') === 'grid' ? 'selected' : '' }}>Cuadrícula</option>
                                            <option value="list" {{ $o('product_list_layout') === 'list' ? 'selected' : '' }}>Lista</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Productos por página</label>
                                    <div class="col-md-8">
                                        <input type="number" name="products_per_page" class="form-control" value="{{ $o('products_per_page', '12') }}" min="1" max="200">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Productos relacionados</label>
                                    <div class="col-md-8">
                                        <input type="number" name="related_products_count" class="form-control" value="{{ $o('related_products_count', '4') }}" min="0" max="20">
                                        <small class="text-muted">Cantidad de productos relacionados en la ficha de producto</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Precio máximo del filtro</label>
                                    <div class="col-md-8">
                                        <input type="number" name="max_price_filter" class="form-control" value="{{ $o('max_price_filter', '1000') }}" min="0">
                                        <small class="text-muted">Valor máximo del rango de precio en el filtro lateral</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Imagen de métodos de pago</label>
                                    <div class="col-md-8">
                                        <input type="text" name="payment_methods_image" class="form-control" value="{{ $o('payment_methods_image') }}"
                                               placeholder="https://...">
                                        <small class="text-muted">Imagen con los logos de métodos de pago aceptados</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Logo del checkout</label>
                                    <div class="col-md-8">
                                        <input type="text" name="checkout_logo" class="form-control" value="{{ $o('checkout_logo') }}" placeholder="https://...">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Imagen de fondo login</label>
                                    <div class="col-md-8">
                                        <input type="text" name="login_bg_image" class="form-control" value="{{ $o('login_bg_image') }}" placeholder="https://...">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Imagen de fondo registro</label>
                                    <div class="col-md-8">
                                        <input type="text" name="register_bg_image" class="form-control" value="{{ $o('register_bg_image') }}" placeholder="https://...">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">URL de política de devoluciones</label>
                                    <div class="col-md-8">
                                        <input type="text" name="return_policy_url" class="form-control" value="{{ $o('return_policy_url') }}" placeholder="/politica-de-devoluciones">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Días para devoluciones</label>
                                    <div class="col-md-8">
                                        <input type="number" name="return_policy_days" class="form-control" value="{{ $o('return_policy_days', '30') }}" min="0">
                                    </div>
                                </div>
                            </div>

                            {{-- ===== TAB: URLs DE COMERCIO ===== --}}
                            <div class="tab-pane fade" id="tab-ecommerce-urls" role="tabpanel">
                                <h6 class="fw-bold mb-1">URLs de comercio electrónico</h6>
                                <p class="text-muted small mb-3">Slugs de las páginas principales de la tienda</p>
                                <hr class="mb-4">

                                @foreach([
                                    ['url_login', 'Página de inicio de sesión', '/cuenta/login'],
                                    ['url_register', 'Página de registro', '/cuenta/registro'],
                                    ['url_reset_password', 'Restablecer contraseña', '/cuenta/recuperar'],
                                    ['url_products', 'Catálogo de productos', '/tienda'],
                                    ['url_cart', 'Carrito de compras', '/carrito'],
                                    ['url_checkout', 'Proceso de pago', '/pago'],
                                    ['url_order_tracking', 'Seguimiento de pedido', '/seguimiento'],
                                    ['url_wishlist', 'Lista de deseos', '/favoritos'],
                                    ['url_compare', 'Comparador de productos', '/comparar'],
                                    ['url_customer_account', 'Mi cuenta', '/mi-cuenta'],
                                ] as [$key, $label, $placeholder])
                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label control-label">{{ $label }}</label>
                                        <div class="col-md-8">
                                            <input type="text" name="{{ $key }}" class="form-control" value="{{ $o($key) }}" placeholder="{{ $placeholder }}">
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- ===== TAB: SEO DE COMERCIO ===== --}}
                            <div class="tab-pane fade" id="tab-ecommerce-seo" role="tabpanel">
                                <h6 class="fw-bold mb-1">SEO del comercio electrónico</h6>
                                <p class="text-muted small mb-3">Títulos y descripciones para las páginas de la tienda</p>
                                <hr class="mb-4">

                                @foreach([
                                    ['seo_products', 'Catálogo de productos'],
                                    ['seo_order_tracking', 'Seguimiento de pedidos'],
                                    ['seo_cart', 'Carrito de compras'],
                                    ['seo_wishlist', 'Lista de deseos'],
                                ] as [$prefix, $pageLabel])
                                    <h6 class="text-muted small fw-semibold mb-2 mt-3">{{ $pageLabel }}</h6>
                                    <div class="row mb-2">
                                        <label class="col-md-4 col-form-label control-label">Título SEO</label>
                                        <div class="col-md-8">
                                            <input type="text" name="{{ $prefix }}_title" class="form-control" value="{{ $o($prefix.'_title') }}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label control-label">Descripción SEO</label>
                                        <div class="col-md-8">
                                            <textarea name="{{ $prefix }}_description" class="form-control" rows="2">{{ $o($prefix.'_description') }}</textarea>
                                        </div>
                                    </div>
                                    @if(!$loop->last)<hr>@endif
                                @endforeach
                            </div>

                            {{-- ===== TAB: NEWSLETTER POPUP ===== --}}
                            <div class="tab-pane fade" id="tab-newsletter" role="tabpanel">
                                <h6 class="fw-bold mb-1">Ventana emergente del boletín</h6>
                                <p class="text-muted small mb-3">Configura el popup de suscripción al newsletter</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Activar popup</label>
                                    <div class="col-md-8">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="newsletter_popup_enabled" value="yes"
                                                   {{ $o('newsletter_popup_enabled') === 'yes' ? 'checked' : '' }}>
                                            <label class="form-check-label">Mostrar popup de suscripción</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Imagen</label>
                                    <div class="col-md-8">
                                        <input type="text" name="newsletter_popup_image" class="form-control" value="{{ $o('newsletter_popup_image') }}" placeholder="https://...">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Título</label>
                                    <div class="col-md-8">
                                        <input type="text" name="newsletter_popup_title" class="form-control" value="{{ $o('newsletter_popup_title') }}"
                                               placeholder="¡Suscríbete a nuestro boletín!">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Subtítulo</label>
                                    <div class="col-md-8">
                                        <input type="text" name="newsletter_popup_subtitle" class="form-control" value="{{ $o('newsletter_popup_subtitle') }}"
                                               placeholder="Ofertas exclusivas directamente en tu email">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Descripción</label>
                                    <div class="col-md-8">
                                        <textarea name="newsletter_popup_description" class="form-control" rows="3"
                                                  placeholder="Descripción adicional...">{{ $o('newsletter_popup_description') }}</textarea>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Retraso de aparición (segundos)</label>
                                    <div class="col-md-8">
                                        <input type="number" name="newsletter_popup_delay" class="form-control" value="{{ $o('newsletter_popup_delay', '5') }}" min="0" max="60">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Mostrar en</label>
                                    <div class="col-md-8">
                                        <select name="newsletter_popup_show_on" class="form-select">
                                            <option value="all" {{ $o('newsletter_popup_show_on', 'all') === 'all' ? 'selected' : '' }}>Todas las páginas</option>
                                            <option value="home" {{ $o('newsletter_popup_show_on') === 'home' ? 'selected' : '' }}>Solo en la portada</option>
                                            <option value="blog" {{ $o('newsletter_popup_show_on') === 'blog' ? 'selected' : '' }}>Solo en el blog</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- ===== TAB: COOKIES ===== --}}
                            <div class="tab-pane fade" id="tab-cookies" role="tabpanel">
                                <h6 class="fw-bold mb-1">Consentimiento de cookies</h6>
                                <p class="text-muted small mb-3">Configura el aviso de cookies conforme al RGPD</p>
                                <hr class="mb-4">

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Activar aviso de cookies</label>
                                    <div class="col-md-8">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="cookie_consent_enabled" value="yes"
                                                   {{ $o('cookie_consent_enabled') === 'yes' ? 'checked' : '' }}>
                                            <label class="form-check-label">Mostrar banner de consentimiento</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Estilo del aviso</label>
                                    <div class="col-md-8">
                                        <select name="cookie_consent_style" class="form-select">
                                            <option value="bar" {{ $o('cookie_consent_style', 'bar') === 'bar' ? 'selected' : '' }}>Barra inferior</option>
                                            <option value="popup" {{ $o('cookie_consent_style') === 'popup' ? 'selected' : '' }}>Ventana emergente</option>
                                            <option value="floating" {{ $o('cookie_consent_style') === 'floating' ? 'selected' : '' }}>Flotante</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Mensaje</label>
                                    <div class="col-md-8">
                                        <textarea name="cookie_consent_message" class="form-control" rows="3"
                                                  placeholder="Este sitio usa cookies para mejorar tu experiencia...">{{ $o('cookie_consent_message') }}</textarea>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Texto del botón aceptar</label>
                                    <div class="col-md-8">
                                        <input type="text" name="cookie_consent_btn_text" class="form-control" value="{{ $o('cookie_consent_btn_text', 'Aceptar') }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Texto de "más información"</label>
                                    <div class="col-md-8">
                                        <input type="text" name="cookie_consent_more_info_text" class="form-control" value="{{ $o('cookie_consent_more_info_text', 'Más información') }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">URL de política de cookies</label>
                                    <div class="col-md-8">
                                        <input type="text" name="cookie_consent_more_info_url" class="form-control" value="{{ $o('cookie_consent_more_info_url') }}"
                                               placeholder="/politica-de-cookies">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Botón de rechazo</label>
                                    <div class="col-md-8">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="cookie_consent_reject_btn" value="yes"
                                                   {{ $o('cookie_consent_reject_btn') === 'yes' ? 'checked' : '' }}>
                                            <label class="form-check-label">Mostrar botón "Rechazar todo"</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Botón de preferencias</label>
                                    <div class="col-md-8">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="cookie_consent_preferences_btn" value="yes"
                                                   {{ $o('cookie_consent_preferences_btn') === 'yes' ? 'checked' : '' }}>
                                            <label class="form-check-label">Mostrar botón "Gestionar preferencias"</label>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="fw-bold mb-1">Colores del aviso</h6>
                                <p class="text-muted small mb-3"></p>
                                <hr class="mb-4">

                                @foreach([
                                    ['cookie_consent_bg_color', 'Color de fondo', '#1a1a2e'],
                                    ['cookie_consent_text_color', 'Color del texto', '#ffffff'],
                                    ['cookie_consent_btn_color', 'Color del botón aceptar', '#90bb13'],
                                ] as [$key, $label, $default])
                                    <div class="row mb-3">
                                        <label class="col-md-4 col-form-label control-label">{{ $label }}</label>
                                        <div class="col-md-8 d-flex align-items-center gap-2">
                                            <input type="color" name="{{ $key }}" class="form-control form-control-color"
                                                   value="{{ $o($key, $default) }}" style="width: 60px;">
                                            <input type="text" class="form-control color-text-sync" style="max-width:120px;"
                                                   value="{{ $o($key, $default) }}" placeholder="{{ $default }}">
                                        </div>
                                    </div>
                                @endforeach

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label control-label">Ancho máximo del aviso</label>
                                    <div class="col-md-8">
                                        <input type="text" name="cookie_consent_max_width" class="form-control" value="{{ $o('cookie_consent_max_width', '100%') }}"
                                               placeholder="100%, 800px, etc.">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save me-1"></i> Guardar todos los cambios
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // ── Repeatable rows: remove ──
    $(document).on('click', '.remove-row', function () {
        $(this).closest('.repeatable-row').remove();
        reindexRows('#social-links-container', 'social_links');
        reindexRows('#header-messages-container', 'header_messages');
        reindexRows('#contact-boxes-container', 'contact_boxes');
    });

    function reindexRows(container, prefix) {
        $(container + ' .repeatable-row').each(function (i) {
            $(this).find('[name]').each(function () {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace(new RegExp(prefix + '\\[\\d+\\]'), prefix + '[' + i + ']'));
            });
        });
    }

    function addRow(containerId, templateId, prefix) {
        var container = $(containerId);
        var idx = container.children('.repeatable-row').length;
        var html = $(templateId).html().replace(/__IDX__/g, idx);
        container.append(html);
    }

    $('#add-social-link').on('click', function () {
        addRow('#social-links-container', '#tpl-social-link', 'social_links');
    });

    $('#add-header-message').on('click', function () {
        addRow('#header-messages-container', '#tpl-header-message', 'header_messages');
    });

    $('#add-contact-box').on('click', function () {
        addRow('#contact-boxes-container', '#tpl-contact-box', 'contact_boxes');
    });

    // ── Color picker sync (color input ↔ text input) ──
    $(document).on('input', 'input[type="color"]', function () {
        $(this).siblings('.color-text-sync').val(this.value);
    });

    $(document).on('input', '.color-text-sync', function () {
        var hex = this.value;
        if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
            $(this).siblings('input[type="color"]').val(hex);
        }
    });

    // ── Persist active tab in URL hash ──
    var hash = window.location.hash;
    if (hash) {
        var targetBtn = $('[data-bs-target="' + hash + '"]');
        if (targetBtn.length) {
            targetBtn.tab('show');
        }
    }

    $('[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        window.location.hash = $(e.target).data('bs-target');
    });
});
</script>
@endpush
