@extends('layouts.theme')

@section('page_title', 'Crear página')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear página'])
@endsection

@section('content')

    @include('core::components.alerts')

    @php
        $prefix = setting('permalink-modules-page-models-page', '');
    @endphp

    <form action="{{ route('pages.store') }}" method="POST" enctype="multipart/form-data" id="pageForm">
        @csrf

        {{-- Hidden status sent with the form (synced from #main-status-select via JS) --}}
        <input type="hidden" name="status" id="status-hidden" value="{{ old('status', 'draft') }}">

        <div class="row">

            {{-- COLUMNA PRINCIPAL --}}
            <div class="col-lg-8 order-lg-1">

                <div class="card mb-3">

                    {{-- Tabs de idioma --}}
                    <div class="card-header border-bottom px-4 pt-3 pb-0">
                        <ul class="nav nav-tabs card-header-tabs" id="langTabs" role="tablist">
                            @foreach($locales as $localeObj)
                                @php $locale = $localeObj->code; @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                            id="tab-btn-{{ $locale }}"
                                            data-bs-toggle="tab"
                                            data-bs-target="#pane-{{ $locale }}"
                                            data-locale="{{ $locale }}"
                                            type="button" role="tab">
                                        {{ $localeObj->name }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="tab-content" id="langTabsContent">
                        @foreach($locales as $localeObj)
                            @php $locale = $localeObj->code; @endphp
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }} p-3"
                                 id="pane-{{ $locale }}" role="tabpanel">

                                {{-- Nombre --}}
                                <div class="mb-3">
                                    <label for="title-{{ $locale }}" class="form-label fw-semibold">
                                        Nombre @if($loop->first)<span class="text-danger">*</span>@endif
                                    </label>
                                    <input type="text"
                                           class="form-control @error('translations.'.$locale.'.title') is-invalid @enderror"
                                           id="title-{{ $locale }}"
                                           name="translations[{{ $locale }}][title]"
                                           value="{{ old('translations.'.$locale.'.title') }}"
                                           maxlength="255"
                                           {{ $loop->first ? 'autofocus' : '' }}>
                                    @error('translations.'.$locale.'.title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Enlace permanente --}}
                                <div class="mb-3">
                                    <label for="slug-{{ $locale }}" class="form-label fw-semibold">Enlace permanente</label>
                                    <div class="input-group">
                                        <span class="input-group-text text-muted">
                                            {{ $prefix ? url($prefix).'/' : url('/').'/' }}
                                        </span>
                                        <input type="text"
                                               class="form-control @error('translations.'.$locale.'.slug') is-invalid @enderror"
                                               id="slug-{{ $locale }}"
                                               name="translations[{{ $locale }}][slug]"
                                               value="{{ old('translations.'.$locale.'.slug') }}"
                                               placeholder="slug-de-la-pagina">
                                        <button type="button" class="btn btn-outline-secondary"
                                                onclick="regenerateSlugForLocale('{{ $locale }}')">
                                            Regenerar
                                        </button>
                                    </div>
                                    @error('translations.'.$locale.'.slug')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Descripción --}}
                                <div class="mb-3">
                                    <label for="description-{{ $locale }}" class="form-label fw-semibold">Descripción</label>
                                    <textarea class="form-control @error('translations.'.$locale.'.description') is-invalid @enderror"
                                              id="description-{{ $locale }}"
                                              name="translations[{{ $locale }}][description]"
                                              rows="3" maxlength="500"
                                              placeholder="Descripción corta">{{ old('translations.'.$locale.'.description') }}</textarea>
                                    @error('translations.'.$locale.'.description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <hr class="my-3">

                                {{-- Contenido --}}
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Contenido</label>
                                    <div class="d-grid gap-2 mb-2">
                                        <button type="button" class="btn btn-outline-secondary"
                                                onclick="toggleEditor('{{ $locale }}')">
                                            Mostrar/Ocultar Editor
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary add-media-btn"
                                                data-locale="{{ $locale }}">
                                            <i class="fas fa-image me-1"></i> Agregar
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary ui-blocks-btn"
                                                data-locale="{{ $locale }}"
                                                data-bs-toggle="modal" data-bs-target="#uiBlocksModal">
                                            <i class="fas fa-th-large me-1"></i> Bloques de interfaz de usuario
                                        </button>
                                    </div>
                                    @include('page::pages.partials.shortcode-help', ['locale' => $locale])
                                    <div id="editorWrapper-{{ $locale }}">
                                        <textarea id="content-{{ $locale }}"
                                                  name="translations[{{ $locale }}][content]"
                                                  class="@error('translations.'.$locale.'.content') is-invalid @enderror">{{ old('translations.'.$locale.'.content') }}</textarea>
                                    </div>
                                    @error('translations.'.$locale.'.content')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <hr class="my-3">

                                {{-- SEO por locale --}}
                                <div class="card mb-3">
                                    <div class="card-header p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 fw-bold">SEO</h5>
                                        <a href="#" class="text-primary small fw-semibold seo-toggle" data-locale="{{ $locale }}">Editar</a>
                                    </div>
                                    <div class="card-body">

                                        {{-- Preview SEO --}}
                                        <div class="seo-preview" id="seoPreview-{{ $locale }}">
                                            <div class="p-3 border rounded bg-white">
                                                <div class="text-primary fw-semibold">
                                                    {{ old('translations.'.$locale.'.seo_title') ?: old('translations.'.$locale.'.title') ?: 'Título de la página' }}
                                                </div>
                                                <div class="text-success small">{{ url('/') }}/...</div>
                                                <div class="text-muted small mt-1">
                                                    {{ old('translations.'.$locale.'.seo_description') ?: 'La descripción de la página aparecerá aquí.' }}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Campos SEO editables --}}
                                        <div class="seo-edit-section" id="seoEditSection-{{ $locale }}" style="display:none;">
                                            <hr class="my-3">

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Título SEO</label>
                                                <input type="text"
                                                       class="form-control seo-title-input @error('translations.'.$locale.'.seo_title') is-invalid @enderror"
                                                       id="seo_title-{{ $locale }}"
                                                       name="translations[{{ $locale }}][seo_title]"
                                                       value="{{ old('translations.'.$locale.'.seo_title') }}"
                                                       maxlength="70"
                                                       data-locale="{{ $locale }}">
                                                @error('translations.'.$locale.'.seo_title')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Descripción SEO</label>
                                                <textarea class="form-control seo-desc-input @error('translations.'.$locale.'.seo_description') is-invalid @enderror"
                                                          id="seo_description-{{ $locale }}"
                                                          name="translations[{{ $locale }}][seo_description]"
                                                          rows="3" maxlength="160"
                                                          data-locale="{{ $locale }}">{{ old('translations.'.$locale.'.seo_description') }}</textarea>
                                                @error('translations.'.$locale.'.seo_description')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            {{-- SEO Live Preview --}}
                                            <div class="mt-3 p-3 border rounded bg-light" id="seo-preview-{{ $locale }}">
                                                <small class="text-muted d-block mb-1">Vista previa en buscadores:</small>
                                                <div class="seo-serp-preview">
                                                    <div class="text-primary fw-semibold text-truncate"
                                                         id="seo-preview-title-{{ $locale }}">
                                                        {{ old('translations.'.$locale.'.seo_title') ?: 'Título de la página' }}
                                                    </div>
                                                    <div class="text-success small">
                                                        {{ url('/') }}/{{ old('translations.'.$locale.'.slug') ?: 'slug-pagina' }}
                                                    </div>
                                                    <div class="text-secondary small mt-1"
                                                         id="seo-preview-desc-{{ $locale }}">
                                                        {{ old('translations.'.$locale.'.seo_description') ?: 'Descripción de la página.' }}
                                                    </div>
                                                </div>
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        Título: <span id="seo-title-count-{{ $locale }}">0</span>/60 chars &nbsp;|&nbsp;
                                                        Desc: <span id="seo-desc-count-{{ $locale }}">0</span>/160 chars
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="mb-3 mt-3">
                                                <label class="form-label fw-semibold">Palabras clave</label>
                                                <input type="text"
                                                       class="form-control @error('translations.'.$locale.'.seo_keywords') is-invalid @enderror"
                                                       name="translations[{{ $locale }}][seo_keywords]"
                                                       value="{{ old('translations.'.$locale.'.seo_keywords') }}"
                                                       maxlength="500"
                                                       placeholder="palabra1, palabra2, ...">
                                                @error('translations.'.$locale.'.seo_keywords')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Imagen SEO (Open Graph)</label>
                                                <input type="hidden"
                                                       id="seo_image_url-{{ $locale }}"
                                                       name="translations[{{ $locale }}][seo_image_url]"
                                                       value="{{ old('translations.'.$locale.'.seo_image_url') }}">
                                                <div class="seo-image-preview mb-2" id="seoImagePreview-{{ $locale }}">
                                                    @if(old('translations.'.$locale.'.seo_image_url'))
                                                        <img src="{{ old('translations.'.$locale.'.seo_image_url') }}"
                                                             class="img-fluid rounded" style="max-height:120px; object-fit:cover; width:100%">
                                                    @else
                                                        <div class="text-center py-3 border rounded bg-light">
                                                            <i class="fas fa-image fa-2x text-muted mb-1"></i>
                                                            <p class="text-muted mb-0">Sin imagen SEO</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-outline-secondary flex-grow-1 seo-image-picker-btn"
                                                            data-locale="{{ $locale }}">
                                                        <i class="fas fa-images me-1"></i> Elegir imagen
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger seo-image-clear-btn {{ old('translations.'.$locale.'.seo_image_url') ? '' : 'd-none' }}"
                                                            data-locale="{{ $locale }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <small class="form-text text-muted">Recomendado: 1200×630px. Máx. 2MB.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Índice</label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                               name="translations[{{ $locale }}][seo_noindex]"
                                                               value="0"
                                                               {{ old('translations.'.$locale.'.seo_noindex', '0') == '0' ? 'checked' : '' }}>
                                                        <label class="form-check-label">Índice</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                               name="translations[{{ $locale }}][seo_noindex]"
                                                               value="1"
                                                               {{ old('translations.'.$locale.'.seo_noindex') == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label">Sin índice</label>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>

                    <hr class="my-0">

                    {{-- Configuración general (aplica a todos los idiomas) --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Configuración general</h6>
                        <p class="text-muted mb-3 small">Opciones que aplican a todos los idiomas.</p>

                        <div class="row g-3">

                            {{-- Estado --}}
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                                <select class="form-select" id="main-status-select">
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}" {{ old('status', 'draft') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Hidden inputs por locale para status --}}
                            @foreach($locales as $localeObj)
                                @php $locale = $localeObj->code; @endphp
                                <input type="hidden" class="trans-status-hidden"
                                       name="translations[{{ $locale }}][status]"
                                       id="trans-status-{{ $locale }}"
                                       value="{{ old('translations.'.$locale.'.status', 'draft') }}">
                            @endforeach

                            <div class="col-md-12"><hr class="my-2"></div>

                            {{-- Header style --}}
                            <div class="col-md-12">
                                <label for="header_style" class="form-label fw-semibold">Header</label>
                                <select class="form-select @error('header_style') is-invalid @enderror select2"
                                        id="header_style" name="header_style">
                                    <option value="header-style-1" {{ old('header_style', 'header-style-1') === 'header-style-1' ? 'selected' : '' }}>Por defecto</option>
                                    <option value="header-style-2" {{ old('header_style') === 'header-style-2' ? 'selected' : '' }}>Estilo de encabezado 2</option>
                                    <option value="header-style-3" {{ old('header_style') === 'header-style-3' ? 'selected' : '' }}>Estilo de encabezado 3</option>
                                    <option value="header-style-4" {{ old('header_style') === 'header-style-4' ? 'selected' : '' }}>Estilo de encabezado 4</option>
                                </select>
                                @error('header_style')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Plantilla --}}
                            <div class="col-md-12">
                                <label for="template" class="form-label fw-semibold">Plantilla</label>
                                <select class="form-select @error('template') is-invalid @enderror select2"
                                        id="template" name="template">
                                    @foreach($templates as $key => $label)
                                        <option value="{{ $key }}" {{ old('template', 'default') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Fecha de publicación (si se publica ahora) --}}
                            <div class="col-md-12" id="main-published-at-wrap" style="display:none;">
                                <label class="form-label fw-semibold">Fecha de publicación</label>
                                <input type="datetime-local" class="form-control" name="published_at" id="published_at"
                                       value="{{ old('published_at') }}">
                                <small class="text-muted d-block mt-1">Dejar vacío para usar la fecha actual</small>
                            </div>

                            {{-- Programar publicación --}}
                            <div class="col-md-12">
                                <label for="publish_at" class="form-label fw-semibold">Publicar el (programado)</label>
                                <input type="datetime-local"
                                       class="form-control @error('publish_at') is-invalid @enderror"
                                       id="publish_at" name="publish_at"
                                       value="{{ old('publish_at') }}">
                                @error('publish_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Despublicar el --}}
                            <div class="col-md-12">
                                <label for="unpublish_at" class="form-label fw-semibold">Despublicar el</label>
                                <input type="datetime-local"
                                       class="form-control @error('unpublish_at') is-invalid @enderror"
                                       id="unpublish_at" name="unpublish_at"
                                       value="{{ old('unpublish_at') }}">
                                @error('unpublish_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-12"><hr class="my-2"></div>

                            {{-- Página padre --}}
                            <div class="col-md-12">
                                <label for="parent_id" class="form-label fw-semibold">Página padre</label>
                                <select class="form-select select2 @error('parent_id') is-invalid @enderror"
                                        id="parent_id" name="parent_id">
                                    <option value="">Sin padre (página raíz)</option>
                                    @foreach(\Modules\Page\Models\Page::root()->orderBy('title')->get(['id', 'title']) as $parentPage)
                                        <option value="{{ $parentPage->id }}"
                                                {{ old('parent_id') == $parentPage->id ? 'selected' : '' }}>
                                            {{ $parentPage->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Si seleccionas una página padre, la URL será: /padre/esta-pagina</div>
                            </div>

                            {{-- Categorías --}}
                            <div class="col-md-12">
                                <label for="categories-select" class="form-label fw-semibold">Categorías</label>
                                <select id="categories-select" name="categories[]"
                                        class="form-select select2 @error('categories') is-invalid @enderror"
                                        multiple>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                                {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('categories')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Tags --}}
                            <div class="col-md-12">
                                <label for="tags-input-create" class="form-label fw-semibold">Tags</label>
                                <input type="text" class="form-control @error('tags_input') is-invalid @enderror"
                                       name="tags_input" id="tags-input-create"
                                       placeholder="Agregar tags..."
                                       value="{{ old('tags_input', '') }}">
                                <small class="form-text text-muted">Separa los tags con comas</small>
                                @error('tags_input')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>

                </div>{{-- /card tabs idiomas --}}

            </div>

            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-2">

                {{-- Publicar --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Publicar</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <button type="submit" form="pageForm" class="btn btn-primary">
                            Guardar página
                        </button>
                        <a href="{{ route('pages.index') }}" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </div>

                {{-- Imagen destacada --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Imagen</h5>
                    </div>
                    <div class="card-body">
                        <div id="imagePreviewContainer" class="mb-3">
                            <div class="d-flex flex-column align-items-center justify-content-center rounded-3 bg-light border border-dashed cursor-pointer"
                                 style="min-height:140px;"
                                 onclick="document.getElementById('featured_image').click()">
                                <i class="fas fa-image fa-3x mb-2 text-muted"></i>
                                <p class="mb-0 small text-muted">Haz clic para seleccionar una imagen</p>
                            </div>
                        </div>
                        <input type="file"
                               class="form-control @error('featured_image') is-invalid @enderror"
                               id="featured_image" name="featured_image"
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                        <small class="form-text text-muted">Máx. 2MB. JPG, PNG, GIF, WebP</small>
                        @error('featured_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

            </div>

        </div>
    </form>

    {{-- MODAL: Bloques de interfaz de usuario --}}
    @php
        $uiIconMap = [
            'button' => 'fa-hand-pointer', 'alert' => 'fa-exclamation-circle',
            'columns' => 'fa-table-columns', 'column' => 'fa-bars', 'youtube' => 'fa-youtube',
            'image' => 'fa-image', 'icon' => 'fa-icons', 'badge' => 'fa-tag',
            'card' => 'fa-id-card', 'accordion' => 'fa-layer-group',
            'accordion-item' => 'fa-list', 'quote' => 'fa-quote-left',
            'contact-form' => 'fa-envelope', 'form' => 'fa-file-alt',
            'our-offices' => 'fa-building', 'site-features' => 'fa-star',
            'reviews' => 'fa-star-half-alt', 'gallery' => 'fa-images',
            'spacer' => 'fa-arrows-alt-v', 'raw-html' => 'fa-code',
            'image-gallery' => 'fa-grip', 'video' => 'fa-video',
            'faq' => 'fa-question-circle', 'testimonials' => 'fa-comments',
            'cta' => 'fa-bullhorn', 'map' => 'fa-map-marker-alt',
            'ticker' => 'fa-scroll', 'slider' => 'fa-sliders',
        ];
        $uiCatIconMap = [
            'estructura'  => 'fa-table-columns',
            'contenido'   => 'fa-pen-nib',
            'media'       => 'fa-photo-film',
            'formularios' => 'fa-file-alt',
            'tema'        => 'fa-palette',
            'otros'       => 'fa-cubes',
        ];
        $uiCategoryLabels = $shortcodeCategories->pluck('label', 'slug');
        $uiCategorySlugs  = $shortcodeCategories->pluck('slug');
        $uiGrouped = collect($shortcodes)->groupBy(fn($sc) => $sc['category'] ?? 'otros');
        $orderedCats = $uiCategorySlugs->filter(fn($s) => $uiGrouped->has($s))
            ->concat($uiGrouped->keys()->diff($uiCategorySlugs)->values())
            ->values();
        $uiTotalBlocks = collect($shortcodes)->count();
    @endphp
    @include('theme::partials.ui-blocks-modal')

    {{-- MODAL: Configuración de bloque --}}
    <div class="modal fade" id="blockConfigModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="blockConfigTitle">Configurar bloque</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="blockConfigBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="insertBlockBtn">Insertar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Selector de imagen SEO --}}
    <div class="modal fade" id="seoImagePickerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-images me-2"></i>Elegir imagen SEO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="seoImageSearch" placeholder="Buscar imágenes...">
                    </div>
                    <div class="row g-2" id="seoImageGrid">
                        <div class="col-12 text-center py-4">
                            <div class="spinner-border text-secondary" role="status"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-2 mt-3" id="seoImagePagination"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts-head')
    <script src="{{ asset('core/tinymce/tinymce.min.js') }}"></script>
@endpush

@push('scripts')
<script>
$(document).ready(function () {

    var mediaUploadUrl    = '{{ route('media.files.upload') }}';
    var mediaBaseUrl      = '{{ url('media') }}';
    var mediaListUrl      = '{{ url('/media/list') }}';
    var mediaPickerCallback = null;
    var initializedEditors  = {};
    var activeLocale        = '{{ $locales[0]->code ?? 'es' }}';

    // =========================================================
    // STATUS — sync main select → hidden locale inputs + hidden top-level
    // =========================================================
    $('#main-status-select').on('change', function () {
        var val = $(this).val();
        $('#status-hidden').val(val);
        $('.trans-status-hidden').val(val);
        $('#main-published-at-wrap').toggle(val === 'published');
    }).trigger('change');

    // =========================================================
    // TINYMCE — multi-locale
    // =========================================================
    function mediaUpload(formData, onSuccess, onError) {
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        $.ajax({
            url: mediaUploadUrl, type: 'POST', data: formData,
            processData: false, contentType: false,
            success: function (res) {
                if (res.success) {
                    onSuccess(mediaBaseUrl + '/' + res.file.url.replace(/^media\//, ''));
                } else { onError('Error al subir la imagen'); }
            },
            error: function () { onError('Error al subir la imagen'); }
        });
    }

    function tinyMCEConfig(locale) {
        return {
            selector: '#content-' + locale,
            language: 'es',
            height: 500,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'wordcount', 'emoticons',
                'codesample', 'directionality', 'colorpicker', 'textcolor', 'paste'
            ],
            toolbar1: 'formatselect | fontselect fontsizeselect | bold italic underline strikethrough | forecolor backcolor | link | bullist numlist',
            toolbar2: 'alignleft aligncenter alignright alignjustify | ltr rtl | outdent indent | blockquote | image media table | codesample code | undo redo | searchreplace removeformat | fullscreen',
            menubar: false, branding: false, promotion: false, resize: true,
            automatic_uploads: true,
            images_upload_handler: function (blobInfo) {
                return new Promise(function (resolve, reject) {
                    var fd = new FormData();
                    fd.append('file', blobInfo.blob(), blobInfo.filename());
                    mediaUpload(fd, resolve, reject);
                });
            },
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
            setup: function (editor) {
                editor.on('change input keyup', function () { editor.save(); });
            }
        };
    }

    function initEditorForLocale(locale) {
        if (initializedEditors[locale]) return;
        initializedEditors[locale] = true;
        tinymce.init(tinyMCEConfig(locale));
    }

    @if(count($locales) > 0)
    initEditorForLocale('{{ $locales[0]->code ?? 'es' }}');
    @endif

    $('#langTabs button').on('shown.bs.tab', function (e) {
        activeLocale = $(e.target).data('locale');
        initEditorForLocale(activeLocale);
    });

    window.toggleEditor = function (locale) {
        var editor = tinymce.get('content-' + locale);
        if (editor) {
            editor.remove();
            initializedEditors[locale] = false;
            $('#content-' + locale).show();
        } else {
            initEditorForLocale(locale);
        }
    };

    // =========================================================
    // MEDIA — botón Agregar
    // =========================================================
    $(document).on('click', '.add-media-btn', function () {
        var locale = $(this).data('locale');
        mediaPickerCallback = function (url, fullUrl) {
            var tag = '<img src="' + fullUrl + '" alt="" style="max-width:100%">';
            var editor = tinymce.get('content-' + locale);
            if (editor) {
                editor.insertContent(tag);
            } else {
                var ta = document.getElementById('content-' + locale);
                var pos = ta.selectionStart || ta.value.length;
                ta.value = ta.value.substring(0, pos) + tag + ta.value.substring(pos);
            }
            $('#seoImagePickerModal').modal('hide');
        };
        $('#seoImagePickerModal').modal('show');
    });

    $(document).on('click', '.ui-blocks-btn', function () {
        activeLocale = $(this).data('locale');
    });

    $(document).on('click', '.shortcode-insert-btn', function () {
        var locale = $(this).data('locale');
        var text = $(this).data('shortcode');
        var editor = tinymce.get('content-' + locale);
        if (editor) {
            editor.insertContent(text);
        } else {
            var ta = document.getElementById('content-' + locale);
            var pos = ta.selectionStart || ta.value.length;
            ta.value = ta.value.substring(0, pos) + text + ta.value.substring(pos);
        }
    });

    // =========================================================
    // AUTO-SLUG por locale
    // =========================================================
    function generateSlugFromTitle(title) {
        return title.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }

    $('[id^="title-"]').on('input', function () {
        var locale = this.id.replace('title-', '');
        var $slug = $('#slug-' + locale);
        if (!$slug.data('manual')) { $slug.val(generateSlugFromTitle($(this).val())); }
    });

    $('[id^="slug-"]').on('input', function () { $(this).data('manual', true); });

    window.regenerateSlugForLocale = function (locale) {
        var title = $('#title-' + locale).val();
        if (!title) return;
        @php $slugAjaxUrl = \Illuminate\Support\Facades\Route::has('pages.ajax.slug') ? route('pages.ajax.slug') : null; @endphp
        @if($slugAjaxUrl)
        $.post('{{ $slugAjaxUrl }}', {
            title: title, ignoreId: 0,
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function (data) { $('#slug-' + locale).val(data.slug).data('manual', true); });
        @else
        $('#slug-' + locale).val(generateSlugFromTitle(title)).data('manual', true);
        @endif
    };

    // =========================================================
    // SEO — toggle + live preview por locale
    // =========================================================
    $(document).on('click', '.seo-toggle', function (e) {
        e.preventDefault();
        var locale = $(this).data('locale');
        var $section = $('#seoEditSection-' + locale);
        $section.toggle();
        $(this).text($section.is(':visible') ? 'Cerrar' : 'Editar');
    });

    $(document).on('input', '.seo-title-input', function () {
        var locale = $(this).data('locale');
        var val = $(this).val();
        $('#seo-preview-title-' + locale).text(val || 'Título de la página');
        $('#seo-title-count-' + locale).text(val.length);
    });

    $(document).on('input', '.seo-desc-input', function () {
        var locale = $(this).data('locale');
        var val = $(this).val();
        $('#seo-preview-desc-' + locale).text(val || 'Descripción de la página.');
        $('#seo-desc-count-' + locale).text(val.length);
    });

    // =========================================================
    // SEO IMAGE PICKER per locale
    // =========================================================
    var seoPickerLocale = null;

    $(document).on('click', '.seo-image-picker-btn', function () {
        seoPickerLocale = $(this).data('locale');
        mediaPickerCallback = function (url, fullUrl) {
            $('#seo_image_url-' + seoPickerLocale).val(fullUrl);
            $('#seoImagePreview-' + seoPickerLocale).html(
                '<img src="' + fullUrl + '" class="img-fluid rounded" style="max-height:120px; object-fit:cover; width:100%">'
            );
            $('[data-locale="' + seoPickerLocale + '"].seo-image-clear-btn').removeClass('d-none');
            $('#seoImagePickerModal').modal('hide');
        };
        $('#seoImagePickerModal').modal('show');
    });

    $(document).on('click', '.seo-image-clear-btn', function () {
        var locale = $(this).data('locale');
        $('#seo_image_url-' + locale).val('');
        $('#seoImagePreview-' + locale).html(
            '<div class="text-center py-3 border border-dashed rounded bg-light">' +
            '<i class="fas fa-image fa-2x text-muted mb-1"></i>' +
            '<p class="text-muted mb-0">Sin imagen SEO</p></div>'
        );
        $(this).addClass('d-none');
    });

    // =========================================================
    // UI BLOCKS MODAL
    // =========================================================

    // Categoría activa (sidebar buttons + mobile pills)
    $(document).on('click', '.ui-cat-btn, .ui-cat-pill', function () {
        var cat = $(this).data('cat');
        $('.ui-cat-btn, .ui-cat-pill').removeClass('active');
        $('[data-cat="' + cat + '"]').addClass('active');
        $('.ui-blocks-pane').addClass('d-none');
        $('.ui-blocks-pane[data-pane="' + cat + '"]').removeClass('d-none');
        $('#uiBlocksSearch').val('');
        $('#uiSearchClear').hide();
        $('#uiSearchMsg').hide();
        $('#uiEmptySearch').hide();
    });

    // Búsqueda
    $('#uiBlocksSearch').on('input', function () {
        var q = $(this).val().toLowerCase().trim();
        var $clear = $('#uiSearchClear');
        var $msg   = $('#uiSearchMsg');
        var $empty = $('#uiEmptySearch');

        $clear.toggle(q.length > 0);

        if (!q) {
            $msg.hide();
            $empty.hide();
            var activeCat = $('.ui-cat-btn.active, .ui-cat-pill.active').first().data('cat') || '__all__';
            $('.ui-blocks-pane').addClass('d-none');
            $('.ui-blocks-pane[data-pane="' + activeCat + '"]').removeClass('d-none');
            $('.ui-block-item').show();
            return;
        }

        $('.ui-cat-btn, .ui-cat-pill').removeClass('active');
        $('.ui-blocks-pane').addClass('d-none');
        var $allPane = $('.ui-blocks-pane[data-pane="__all__"]');
        $allPane.removeClass('d-none');

        var count = 0;
        $allPane.find('.ui-block-item').each(function () {
            var name = $(this).data('name') || '';
            var cat  = $(this).data('category') || '';
            var desc = $(this).find('.sc-desc').text().toLowerCase();
            var match = name.includes(q) || cat.includes(q) || desc.includes(q);
            $(this).toggle(match);
            if (match) { count++; }
        });

        if (count === 0) {
            $allPane.addClass('d-none');
            $empty.css('display', 'flex');
            $msg.hide();
        } else {
            $empty.hide();
            $msg.text(count + ' resultado' + (count !== 1 ? 's' : '') + ' para "' + q + '"').show();
        }
    });

    // Botón limpiar búsqueda
    $('#uiSearchClear').on('click', function () {
        $('#uiBlocksSearch').val('').trigger('input');
    });

    // Clic en card → insertar bloque
    $(document).on('click', '.ui-block-card', function () {
        openBlockConfig($(this).data('block-key'), $(this).data('block-name'));
    });

    // Limpiar búsqueda al abrir el modal
    $('#uiBlocksModal').on('show.bs.modal', function () {
        $('#uiBlocksSearch').val('');
        $('#uiSearchClear').hide();
        $('#uiSearchMsg').hide();
        $('#uiEmptySearch').hide();
        $('.ui-cat-btn, .ui-cat-pill').removeClass('active');
        $('[data-cat="__all__"]').addClass('active');
        $('.ui-blocks-pane').addClass('d-none');
        $('.ui-blocks-pane[data-pane="__all__"]').removeClass('d-none');
        $('.ui-block-item').show();
    });

    var uiBlocksData = @json($shortcodes);

    function openBlockConfig(key, name) {
        var block = uiBlocksData.find(function (b) { return b.name === key; });
        var hasAttrs = block && block.attributes && block.attributes.length;
        if (!hasAttrs) {
            insertBlock(key, block);
            return;
        }
        $('#uiBlocksModal').modal('hide');
        $('#blockConfigTitle').text('Configurar: ' + name);
        $('#blockConfigBody').html(getBlockConfigForm(block));
        $('#insertBlockBtn').off('click').on('click', function () {
            insertBlock(key, block);
            $('#blockConfigModal').modal('hide');
        });
        setTimeout(function () { $('#blockConfigModal').modal('show'); }, 300);
    }

    function getBlockConfigForm(block) {
        if (!block || !block.attributes || !block.attributes.length) {
            return '<p class="text-muted">Este bloque no requiere configuración.</p>';
        }
        var html = '';
        block.attributes.forEach(function (attr) {
            var id = 'attr-' + attr.name;
            html += '<div class="mb-3"><label class="form-label fw-semibold">' + (attr.label || attr.name) + '</label>';
            if (attr.type === 'select' && attr.options) {
                html += '<select class="form-select" id="' + id + '">';
                Object.entries(attr.options).forEach(function (entry) {
                    html += '<option value="' + entry[0] + '">' + entry[1] + '</option>';
                });
                html += '</select>';
            } else if (attr.type === 'textarea') {
                html += '<textarea class="form-control" id="' + id + '" rows="4" placeholder="' + (attr.placeholder || '') + '"></textarea>';
            } else {
                html += '<input type="' + (attr.type || 'text') + '" class="form-control" id="' + id + '" placeholder="' + (attr.placeholder || '') + '">';
            }
            html += '</div>';
        });
        return html;
    }

    function insertBlock(key, block) {
        var shortcode = buildShortcode(key, block);
        var editor = tinymce.get('content-' + activeLocale);
        if (editor) {
            editor.insertContent(shortcode + '\n');
        } else {
            var ta = document.getElementById('content-' + activeLocale);
            var pos = ta.selectionStart || ta.value.length;
            ta.value = ta.value.substring(0, pos) + shortcode + '\n' + ta.value.substring(pos);
        }
        $('#uiBlocksModal').modal('hide');
    }

    function buildShortcode(key, block) {
        if (!block || !block.attributes || !block.attributes.length) {
            return '[' + key + '][/' + key + ']';
        }
        var attrs = block.attributes.map(function (attr) {
            var val = ($('#attr-' + attr.name).val() || '').replace(/"/g, '&quot;');
            return val ? attr.name + '="' + val + '"' : '';
        }).filter(Boolean).join(' ');
        return attrs ? '[' + key + ' ' + attrs + '][/' + key + ']' : '[' + key + '][/' + key + ']';
    }

    // =========================================================
    // MEDIA IMAGE PICKER — carga de imágenes
    // =========================================================
    function loadSeoImages(page, search) {
        $('#seoImageGrid').html('<div class="col-12 text-center py-4"><div class="spinner-border text-secondary" role="status"></div></div>');
        $.get(mediaListUrl, { page: page, search: search, filter: 'image', per_page: 24 }, function (data) {
            var html = '';
            var files = (data.files || []).filter(function (f) { return f.type === 'image'; });
            if (files.length > 0) {
                $.each(files, function (i, file) {
                    var fullUrl = mediaBaseUrl + '/' + file.url.replace(/^media\//, '');
                    html += '<div class="col-xl-2 col-lg-3 col-md-4 col-6">' +
                        '<div class="card h-100 border media-picker-item" style="cursor:pointer" data-url="' + file.url + '" data-full-url="' + fullUrl + '">' +
                        '<img src="' + fullUrl + '" class="card-img-top" style="height:90px; object-fit:cover" loading="lazy">' +
                        '<div class="card-body p-1"><p class="card-text text-truncate text-muted mb-0" style="font-size:11px">' + file.name + '</p></div>' +
                        '</div></div>';
                });
            } else {
                html = '<div class="col-12 text-center py-4 text-muted"><i class="fas fa-image fa-2x mb-2 d-block"></i>No hay imágenes</div>';
            }
            $('#seoImageGrid').html(html);
            var p = data.pagination || {};
            var pager = '';
            if (p.last_page > 1) {
                if (page > 1) pager += '<button class="btn btn-sm btn-outline-secondary" onclick="loadSeoImages(' + (page - 1) + ',\'' + search + '\')">Anterior</button>';
                pager += '<span class="btn btn-sm disabled">' + page + ' / ' + p.last_page + '</span>';
                if (page < p.last_page) pager += '<button class="btn btn-sm btn-outline-secondary" onclick="loadSeoImages(' + (page + 1) + ',\'' + search + '\')">Siguiente</button>';
            }
            $('#seoImagePagination').html(pager);
        });
    }

    var seoSearchTimer;
    $('#seoImagePickerModal').on('show.bs.modal', function () { loadSeoImages(1, ''); });
    $('#seoImageSearch').on('input', function () {
        var q = $(this).val();
        clearTimeout(seoSearchTimer);
        seoSearchTimer = setTimeout(function () { loadSeoImages(1, q); }, 400);
    });
    $(document).on('click', '.media-picker-item', function () {
        if (mediaPickerCallback) {
            mediaPickerCallback($(this).data('url'), $(this).data('full-url'));
            mediaPickerCallback = null;
        }
    });

    // =========================================================
    // IMAGEN DESTACADA
    // =========================================================
    $('#featured_image').on('change', function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#imagePreviewContainer').html(
                '<img src="' + e.target.result + '" class="img-fluid rounded mb-2" style="max-height:180px; object-fit:cover; width:100%">'
            );
        };
        reader.readAsDataURL(file);
    });

    // Flash messages
    @if(session('success'))
    toastr.success(@json(session('success')), 'Éxito');
    @endif
    @if(session('error'))
    toastr.error(@json(session('error')), 'Error');
    @endif

});
</script>
@endpush
