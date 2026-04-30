@extends('layouts.theme')

@section('page_title', 'Editar página')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar página: ' . $page->title])
@endsection

@section('content')

    {{-- Página bloqueada por otro usuario --}}
    <div id="lockAlert" class="alert alert-warning alert-dismissible fade show" style="display:none;">
        <i class="fas fa-lock me-2"></i>
        <strong id="lockAlertText">Esta página está siendo editada por otro usuario</strong>
        <p class="mb-0 small mt-1">
            <span id="lockUserName"></span>
            <br>
            Bloqueado: <span id="lockTime"></span>
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>

    <form action="{{ route('pages.update', $page->id) }}" method="POST" enctype="multipart/form-data" id="pageForm">
        @csrf
        @method('PUT')

        <div class="row">


            @php
                $sidebarPrefix = setting('permalink-modules-page-models-page', '');
                $translationData = [];
                foreach ($locales as $localeObj) {
                    $code = $localeObj->code;
                    $t = $translations->get($code);
                    $translationData[$code] = [
                        'slug'         => $t?->slug ?? '',
                        'status'       => old('translations.'.$code.'.status', $t?->status ?? 'draft'),
                        'published_at' => old('translations.'.$code.'.published_at', $t?->published_at?->format('Y-m-d\TH:i') ?? ''),
                        'url'          => $t?->slug ? ($sidebarPrefix ? url($sidebarPrefix.'/'.$t->slug) : url($t->slug)) : '',
                    ];
                }
            @endphp


            {{-- COLUMNA PRINCIPAL --}}
            <div class="col-lg-8 order-lg-1">

                {{-- Tabs de idiomas --}}
                <div class="card mb-3">
                    <ul class="nav nav-tabs border-0 user-profile-tab" id="langTabs" role="tablist">
                        @foreach($locales as $localeObj)
                            @php $locale = $localeObj->code; $t = $translations->get($locale); @endphp
                            <li class="nav-item" role="presentation">
                                <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $loop->first ? 'active' : '' }}"
                                        id="tab-btn-{{ $locale }}"
                                        data-bs-toggle="tab"
                                        data-bs-target="#pane-{{ $locale }}"
                                        data-locale="{{ $locale }}"
                                        type="button" role="tab">
                                    <span class="d-none d-md-block">
                                        {{ $localeObj->name }}
                                        @php
                                            $hasTitle   = !empty($t?->title);
                                            $hasContent = !empty($t?->content);
                                            $isPublished = $t?->status === 'published';
                                            $isComplete  = $hasTitle && $hasContent;
                                            if ($t && $isPublished && $isComplete) {
                                                $badge = '<span class="badge bg-success ms-1" title="Publicado y completo">●</span>';
                                            } elseif ($t && $isComplete) {
                                                $badge = '<span class="badge bg-info ms-1" title="Completo, pendiente de publicar">●</span>';
                                            } elseif ($t && ($hasTitle || $hasContent)) {
                                                $badge = '<span class="badge bg-warning ms-1" title="Incompleto: falta título o contenido">◐</span>';
                                            } elseif ($t) {
                                                $badge = '<span class="badge bg-secondary ms-1" title="Borrador sin contenido">○</span>';
                                            } else {
                                                $badge = '<span class="badge bg-danger ms-1" title="Sin traducción">✗</span>';
                                            }
                                        @endphp
                                        {!! $badge !!}
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content" id="langTabsContent">
                        @foreach($locales as $localeObj)
                            @php $locale = $localeObj->code; $t = $translations->get($locale); @endphp
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }} p-4"
                                 id="pane-{{ $locale }}" role="tabpanel">

                                {{-- Nombre --}}
                                <div class="mb-3">
                                    <label for="title-{{ $locale }}" class="form-label fw-semibold">Nombre</label>
                                    <input type="text"
                                           class="form-control @error('translations.'.$locale.'.title') is-invalid @enderror"
                                           id="title-{{ $locale }}"
                                           name="translations[{{ $locale }}][title]"
                                           value="{{ old('translations.'.$locale.'.title', $t?->title) }}"
                                           maxlength="255">
                                    @error('translations.'.$locale.'.title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Enlace permanente --}}
                                @php $prefix = setting('permalink-modules-page-models-page', ''); @endphp
                                <div class="mb-3">
                                    <label for="slug-{{ $locale }}" class="form-label fw-semibold">Enlace permanente</label>
                                    <div class="input-group">
                                <span class="input-group-text text-muted">
                                    {{ $prefix ? url($prefix) . '/' : url('/') . '/' }}
                                </span>
                                        <input type="text"
                                               class="form-control @error('translations.'.$locale.'.slug') is-invalid @enderror"
                                               id="slug-{{ $locale }}"
                                               name="translations[{{ $locale }}][slug]"
                                               value="{{ old('translations.'.$locale.'.slug', $t?->slug) }}"
                                               placeholder="slug-de-la-pagina">
                                        <button type="button" class="btn btn-info"
                                                onclick="regenerateSlugForLocale('{{ $locale }}')">
                                                <i class="fas fa-wand-magic-sparkles"></i>
                                        </button>
                                    </div>
                                    @error('translations.'.$locale.'.slug')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @php $slugVal = old('translations.'.$locale.'.slug', $translations[$locale]?->slug ?? null); @endphp
                                    @if($slugVal)
                                        <div class="mt-1">
                                            <a href="{{ $prefix ? url($prefix.'/'.$slugVal) : url($slugVal) }}"
                                               target="_blank"
                                               class="text-decoration-none small text-muted"
                                               id="view-link-{{ $locale }}">
                                                {{ $prefix ? url($prefix.'/'.$slugVal) : url($slugVal) }}
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                {{-- Descripción --}}
                                <div class="mb-3">
                                    <label for="description-{{ $locale }}" class="form-label fw-semibold">Descripción</label>
                                    <textarea class="form-control @error('translations.'.$locale.'.description') is-invalid @enderror"
                                              id="description-{{ $locale }}"
                                              name="translations[{{ $locale }}][description]"
                                              rows="3" maxlength="500"
                                              placeholder="Descripción corta">{{ old('translations.'.$locale.'.description', $t?->description) }}</textarea>
                                    @error('translations.'.$locale.'.description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <hr class="my-3">

                                {{-- Contenido --}}
                                <div class="mb-3">
                                    <div class="d-flex align-items-start justify-content-between mb-2 gap-2">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Contenido</h6>
                                            <p class="text-muted mb-0" >Cuerpo principal de la página. Usa los botones para insertar imágenes o bloques.</p>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0 justify-content-center align-items-center">
                                            <button type="button"
                                                    class="btn btn-sm btn-light border"
                                                    onclick="toggleEditor('{{ $locale }}')"
                                                    title="Alternar editor visual / HTML">
                                                <i class="fas fa-code"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-light border add-media-btn"
                                                    data-locale="{{ $locale }}"
                                                    title="Insertar imagen desde la galería">
                                                <i class="fas fa-image"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-light border ui-blocks-btn"
                                                    data-locale="{{ $locale }}"
                                                    data-bs-toggle="modal" data-bs-target="#uiBlocksModal"
                                                    title="Insertar bloque de interfaz">
                                                <i class="fas fa-th-large"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="editorWrapper-{{ $locale }}" class="mt-3 d-none">
                                        <textarea id="content-{{ $locale }}"
                                          name="translations[{{ $locale }}][content]"
                                          class="@error('translations.'.$locale.'.content') is-invalid @enderror">{{ old('translations.'.$locale.'.content', $t?->content) }}</textarea>
                                    </div>
                                    @error('translations.'.$locale.'.content')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                        @endforeach
                    </div>

                    <hr class="my-0">

                    {{-- Configuración general (siempre visible) --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Configuración general</h6>
                        <p class="text-muted mb-3 small">Opciones que aplican a todos los idiomas.</p>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                                <select class="form-select" id="main-status-select">
                                    <option value="draft">Borrador</option>
                                    <option value="published">Publicado</option>
                                    <option value="pending">Pendiente</option>
                                </select>
                            </div>


                            {{-- Hidden inputs para todas las traducciones (valores reales del form) --}}
                            @foreach($locales as $localeObj)
                                @php $locale = $localeObj->code; $tData = $translationData[$locale]; @endphp
                                <input type="hidden" class="trans-status-hidden" id="trans-status-{{ $locale }}"
                                       name="translations[{{ $locale }}][status]" data-locale="{{ $locale }}"
                                       value="{{ $tData['status'] }}">
                                <input type="hidden" class="trans-published-at-hidden" id="trans-published-at-{{ $locale }}"
                                       name="translations[{{ $locale }}][published_at]" data-locale="{{ $locale }}"
                                       value="{{ $tData['published_at'] }}">
                            @endforeach

                            {{-- Estilo de encabezado --}}
                            <div class="col-md-12">
                                <label for="header_style" class="form-label fw-semibold">Header</label>
                                <select class="form-select @error('header_style') is-invalid @enderror select2"
                                        id="header_style" name="header_style">
                                    <option value="header-style-1" {{ old('header_style', $page->header_style) === 'header-style-1' ? 'selected' : '' }}>Por defecto</option>
                                    <option value="header-style-2" {{ old('header_style', $page->header_style) === 'header-style-2' ? 'selected' : '' }}>Estilo de encabezado 2</option>
                                    <option value="header-style-3" {{ old('header_style', $page->header_style) === 'header-style-3' ? 'selected' : '' }}>Estilo de encabezado 3</option>
                                    <option value="header-style-4" {{ old('header_style', $page->header_style) === 'header-style-4' ? 'selected' : '' }}>Estilo de encabezado 4</option>
                                </select>
                                @error('header_style')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Plantilla --}}
                            <div class="col-md-12">
                                <label for="template" class="form-label fw-semibold">Plantilla <span class="text-danger">*</span></label>
                                <select class="form-select @error('template') is-invalid @enderror select2" id="template" name="template">
                                    @foreach($templates as $key => $label)
                                        <option value="{{ $key }}" {{ old('template', $page->template) === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>


                            <div class="col-md-12" id="main-published-at-wrap" style="display:none;">
                                <label class="form-label fw-semibold">Fecha de publicación</label>
                                <input type="datetime-local" class="form-control" id="main-published-at">
                                <small class="text-muted d-block mt-1">Dejar vacío para usar la fecha actual</small>
                            </div>

                            {{-- Publicar el (programado) --}}
                            <div class="col-md-12">
                                <label for="publish_at" class="form-label fw-semibold">Publicar el (programado)</label>
                                <input type="datetime-local" class="form-control @error('publish_at') is-invalid @enderror"
                                       id="publish_at" name="publish_at"
                                       value="{{ old('publish_at', $page->publish_at?->format('Y-m-d\TH:i')) }}">
                                @error('publish_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Despublicar el --}}
                            <div class="col-md-12">
                                <label for="unpublish_at" class="form-label fw-semibold">Despublicar el</label>
                                <input type="datetime-local" class="form-control @error('unpublish_at') is-invalid @enderror"
                                       id="unpublish_at" name="unpublish_at"
                                       value="{{ old('unpublish_at', $page->unpublish_at?->format('Y-m-d\TH:i')) }}">
                                @error('unpublish_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if($page->willBePublished() || $page->willBeUnpublished())
                            <div class="col-12">
                                <div class="alert alert-info mb-0 py-2">
                                    <small>
                                        @if($page->willBePublished())
                                            <i class="fas fa-clock me-1"></i> Se publica el {{ $page->publish_at->format('d/m/Y H:i') }}<br>
                                        @endif
                                        @if($page->willBeUnpublished())
                                            <i class="fas fa-clock me-1"></i> Se despublica el {{ $page->unpublish_at->format('d/m/Y H:i') }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                            @endif

                            {{-- Página padre --}}
                            @php $selectedCategories = old('categories', $page->categories->pluck('id')->toArray()); @endphp
                            <input type="hidden" name="status" value="{{ $page->status?->value ?? $page->status }}">
                            <div class="col-md-12">
                                <label for="parent_id" class="form-label fw-semibold">Página padre</label>
                                <select class="form-select select2 @error('parent_id') is-invalid @enderror"
                                        id="parent_id" name="parent_id">
                                    <option value="">Sin padre (página raíz)</option>
                                    @foreach(\Modules\Page\Models\Page::root()->where('id', '!=', $page->id)->orderBy('title')->get(['id', 'title']) as $parentPage)
                                        <option value="{{ $parentPage->id }}"
                                                {{ old('parent_id', $page->parent_id) == $parentPage->id ? 'selected' : '' }}>
                                            {{ $parentPage->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                                {{ in_array($category->id, $selectedCategories) ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('categories')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Tags --}}
                            <div class="col-md-12">
                                <label for="tags-input-edit" class="form-label fw-semibold">Tags</label>
                                <input type="text" class="form-control @error('tags_input') is-invalid @enderror"
                                       name="tags_input" id="tags-input-edit"
                                       placeholder="Agregar tags..."
                                       value="{{ old('tags_input', $page->tags->pluck('name')->join(', ')) }}">
                                <small class="form-text text-muted">Separa los tags con comas</small>
                                @error('tags_input')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Imagen destacada --}}
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Imagen destacada</label>
                                <input type="hidden" id="featured_image_url" name="featured_image_url"
                                       value="{{ old('featured_image_url', $page->featured_image_url ?? $page->featured_image) }}">
                                <div id="imagePreviewContainer" class="mb-2">
                                    @if($page->featured_image)
                                        <div class="position-relative">
                                            <img src="{{ $page->featured_image }}" alt="Imagen destacada" id="featuredImagePreview"
                                                 class="img-fluid rounded" style="max-height:180px;object-fit:cover;width:100%;">
                                            <button type="button" id="btn-remove-featured-image"
                                                    class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1"
                                                    title="Quitar imagen">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @else
                                        <div id="featuredImageEmpty"
                                             class="d-flex flex-column align-items-center justify-content-center rounded-3 bg-light border border-dashed cursor-pointer"
                                             style="min-height:140px;"
                                             onclick="document.getElementById('btn-featured-image-picker').click()">
                                            <i class="fas fa-image fa-3x mb-2 text-muted"></i>
                                            <p class="mb-0 small text-muted">Haz clic para seleccionar una imagen</p>
                                        </div>
                                    @endif
                                </div>
                                <button type="button" id="btn-featured-image-picker" class="d-none"></button>
                            </div>

                        </div>
                    </div>

                </div>{{-- /card tabs idiomas --}}

                {{-- Analytics de vistas --}}
                <div class="card mb-3 d-none" id="analytics-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title fw-semibold mb-0">Analytics de vistas</h4>
                                <p class="card-subtitle mt-1">Tendencia diaria de visitas</p>
                            </div>
                            <select class="form-select w-auto" id="analytics-days">
                                <option value="7">Últimos 7 días</option>
                                <option value="30" selected>Últimos 30 días</option>
                                <option value="90">Últimos 90 días</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">

                            <div class="col-4">
                                <div class="card w-100">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-8">
                                                <h5 class="card-title fw-semibold mb-3">Vistas totales</h5>
                                                <h4 class="fw-semibold mb-2" id="stat-total-views">
                                                    <span class="spinner-border spinner-border-sm text-muted"></span>
                                                </h4>
                                                <div id="cmp-total-views"></div>
                                            </div>
                                            <div class="col-4">
                                                <div class="d-flex justify-content-center">
                                                    <div id="spark-total-views"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="card w-100">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-8">
                                                <h5 class="card-title fw-semibold mb-3">Visitantes únicos</h5>
                                                <h4 class="fw-semibold mb-2" id="stat-unique-visitors">
                                                    <span class="spinner-border spinner-border-sm text-muted"></span>
                                                </h4>
                                                <div id="cmp-unique-visitors"></div>
                                            </div>
                                            <div class="col-4">
                                                <div class="d-flex justify-content-center">
                                                    <div id="spark-unique-visitors"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="card w-100">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-8">
                                                <h5 class="card-title fw-semibold mb-3">Promedio diario</h5>
                                                <h4 class="fw-semibold mb-2" id="stat-avg-daily">
                                                    <span class="spinner-border spinner-border-sm text-muted"></span>
                                                </h4>
                                                <div id="cmp-avg-daily"></div>
                                            </div>
                                            <div class="col-4">
                                                <div class="d-flex justify-content-center">
                                                    <div id="spark-avg-daily"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div id="chart-views-by-day" style="height:295px;"></div>
                    </div>
                </div>

            </div>

            
            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-2">

                {{-- Publicar --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Publicar</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <button type="submit" form="pageForm" class="btn btn-info">
                            Guardar
                        </button>
                        <a href="{{ route('pages.visual-editor', $page) }}?locale={{ $locales->first()?->code }}" class="btn btn-primary" id="btn-editor-visual">
                            Editor visual
                        </a>
                        <a href="{{ route('pages.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                        <a href="#" class="btn btn-outline-secondary" id="btn-ver-pagina" target="_blank">
                            Ver página
                        </a>
                        @if($page->hasVersions())
                            <a href="{{ route('pages.versions.index', $page->id) }}" class="btn btn-outline-secondary">
                                Ver versiones ({{ $page->getTotalVersions() }})
                            </a>
                        @endif
                        @php $firstLocaleCode = $locales->first()?->code ?? ''; @endphp
                        <div id="form-unpublish-action" style="{{ ($translationData[$firstLocaleCode]['status'] ?? '') !== 'published' ? 'display:none;' : '' }}">
                            <form action="{{ route('pages.unpublish', $page->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">Despublicar</button>
                            </form>
                        </div>
                        <div id="form-publish-action" style="{{ ($translationData[$firstLocaleCode]['status'] ?? '') === 'published' ? 'display:none;' : '' }}">
                            <form action="{{ route('pages.publish', $page->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">Publicar ahora</button>
                            </form>
                        </div>
                        <form action="{{ route('pages.duplicate', $page->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">Duplicar página</button>
                        </form>
                        <button type="button"
                                class="btn btn-info w-100 delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#delete-modal"
                                data-url="{{ route('pages.destroy', $page->id) }}">
                            Eliminar página
                        </button>
                    </div>
                </div>



                {{-- Información --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Información</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="text-muted d-block">Creado</span>
                            <strong>{{ $page->created_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted d-block">Modificado</span>
                            <strong>{{ $page->updated_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        @if($page->user)
                            <div class="mb-2">
                                <span class="text-muted d-block">Autor</span>
                                <strong>{{ $page->user->full_name ?? $page->user->name }}</strong>
                            </div>
                        @endif
                        @if($page->hasVersions())
                            <div class="mb-2">
                                <span class="text-muted d-block">Versiones</span>
                                <strong>{{ $page->getTotalVersions() }}</strong>
                            </div>
                        @endif
                        @php
                            $badges = ['published'=>'bg-success-subtle text-success','draft'=>'bg-secondary-subtle text-secondary','pending'=>'bg-warning-subtle text-warning'];
                            $labels = ['published'=>'Publicado','draft'=>'Borrador','pending'=>'Pendiente'];
                        @endphp
                        <div>
                            <span class="text-muted d-block mb-1">Estado</span>
                            @php $statusVal = $page->status instanceof \Modules\Page\Enums\PageStatus ? $page->status->value : $page->status; @endphp
                            <span class="badge {{ $badges[$statusVal] ?? 'bg-secondary-subtle text-secondary' }}">
                                {{ $labels[$statusVal] ?? $statusVal }}
                            </span>
                        </div>
                    </div>
                </div>


                {{-- SEO --}}
                <div class="card mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-semibold mb-1">SEO</h5>
                        <p class="card-subtitle mb-4">Optimización para buscadores</p>

                        @if($seoMeta)
                            @php
                                $seoScore = $seoMeta->seo_score;
                                $seoGrade = $seoMeta->seo_grade;
                                if ($seoScore >= 80) {
                                    $sBg = 'bg-success-subtle'; $sTxt = 'text-success'; $sIbg = 'text-bg-success';
                                    $sLabel = 'Excelente';
                                } elseif ($seoScore >= 60) {
                                    $sBg = 'bg-primary-subtle'; $sTxt = 'text-primary'; $sIbg = 'text-bg-primary';
                                    $sLabel = 'Bueno';
                                } elseif ($seoScore >= 40) {
                                    $sBg = 'bg-warning-subtle'; $sTxt = 'text-warning'; $sIbg = 'text-bg-warning';
                                    $sLabel = 'Mejorable';
                                } else {
                                    $sBg = 'bg-danger-subtle'; $sTxt = 'text-danger'; $sIbg = 'text-bg-danger';
                                    $sLabel = 'Bajo';
                                }
                            @endphp

                            @if($seoScore)
                                <div class="rounded-3 p-3 mb-3 d-flex align-items-center justify-content-between {{ $sBg }}">
                                    <div>
                                        <div class="text-muted small mb-1">Score SEO</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold fs-4 {{ $sTxt }}">{{ $seoScore }}<span class="fs-6 fw-normal text-muted">/100</span></span>
                                            <span class="badge {{ $sIbg }}">{{ $sLabel }}</span>
                                        </div>
                                    </div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 {{ $sTxt }}"
                                         style="width:52px;height:52px;border:3px solid currentColor;opacity:.85;">
                                        {{ $seoGrade }}
                                    </div>
                                </div>
                            @endif

                            <div class="card shadow-none mb-0">
                                <div class="card-body p-0">
                                    <dl class="mb-3 small">
                                        <dt class="text-muted fw-normal mb-1">Título SEO</dt>
                                        <dd class="fw-semibold mb-3 text-truncate">{{ $seoMeta->title ?: '—' }}</dd>
                                        <dt class="text-muted fw-normal mb-1">Descripción</dt>
                                        <dd class="text-muted mb-0" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $seoMeta->description ?: '—' }}</dd>
                                    </dl>
                                    <a href="{{ route('settings.seo.metas.edit', $seoMeta) }}" class="btn btn-info w-100">
                                        Editar SEO
                                    </a>
                                </div>
                            </div>
                        @else
                            <p class="text-muted small mb-2">Esta página no tiene configuración SEO todavía.</p>
                            <small class="text-muted">Guarda la página para que se cree automáticamente.</small>
                        @endif
                    </div>
                </div>



                {{-- DeepL Traducción automática --}}
                @if(count($locales) > 1)
                    <div class="card mb-3">
                        <div class="card-header p-3 bg-white border-bottom">
                            <h5 class="mb-0 fw-bold">
                                Traducción automática
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class=" text-muted mb-2">Traduce el contenido de la página al idioma seleccionado usando DeepL.</p>
                            <div class="mb-2">
                                <label for="page-deepl-target-lang" class="form-label ">Idioma destino</label>
                                <select id="page-deepl-target-lang" class="form-select select2">
                                    @foreach($locales as $localeObj)
                                        <option value="{{ $localeObj->code }}">{{ $localeObj->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <div class="card-footer"> <div class="d-grid gap-2">
                                <button type="button" id="btn-page-deepl-translate" class="btn btn-info">
                                    Traducir idioma seleccionado
                                    <span id="page-deepl-spinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                                </button>
                                <button type="button" id="btn-page-deepl-auto-translate" class="btn btn-outline-secondary">
                                    Traducir todos los idiomas
                                    <span id="page-deepl-auto-spinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Rendimiento PageSpeed --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Rendimiento</h5>
                    </div>
                    <div class="card-body" id="performance-results">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-tachometer-alt fa-2x mb-2 d-block"></i>
                            <span>Haz clic en "Analizar" para obtener métricas de Google PageSpeed</span>
                        </div>
                    </div>
                    <div class="card-footer" >
                            <a href="{{ route('pages.analytics.view', $page->id) }}" class="btn btn-info w-100 mb-1">
                                Analytics
                            </a>
                            <button type="button" class="btn btn-outline-secondary w-100" id="btn-scan-performance">
                                Analizar
                            </button>
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

    {{-- MODAL: Configuración de bloque seleccionado --}}
    <div class="modal fade" id="blockConfigModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="blockConfigTitle">Configurar bloque</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="blockConfigBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="insertBlockBtn">Insertar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de vista previa responsive --}}
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">

                <div class="modal-header py-2 bg-dark text-white border-0">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        <span class="fw-semibold">Vista previa</span>
                        <small class="text-secondary ms-1" id="preview-resolution">1920 × 1080</small>
                    </div>

                    <div class="btn-group mx-auto" role="group" aria-label="Selector de dispositivo" id="device-selector">
                        <button type="button" class="btn btn-sm btn-outline-light active"
                                data-device="desktop" data-width="100%" title="Escritorio">
                            <i class="fas fa-desktop"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light"
                                data-device="tablet" data-width="768px" title="Tablet">
                            <i class="fas fa-tablet-alt"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light"
                                data-device="mobile" data-width="375px" title="Móvil">
                            <i class="fas fa-mobile-alt"></i>
                        </button>
                    </div>

                    <a href="#" target="_blank" class="btn btn-sm btn-outline-light" id="preview-open-tab">
                        <i class="fas fa-external-link-alt me-1"></i>Nueva pestaña
                    </a>
                </div>

                <div class="modal-body p-0 bg-secondary d-flex justify-content-center align-items-start overflow-auto">
                    <div id="preview-frame-wrapper" class="bg-white" style="width:100%; height:100vh;">
                        <iframe id="preview-iframe"
                                src="about:blank"
                                style="width:100%; height:100%; border:none; display:block;"
                                title="Vista previa de la página">
                        </iframe>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts-head')
    <script src="{{ asset('core/tinymce/tinymce.min.js') }}"></script>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
$(document).ready(function () {

    // =========================================================
    // TINYMCE - multi-locale
    // =========================================================
    var mediaUploadUrl = '{{ route('media.files.upload') }}';
    var mediaBaseUrl = '{{ url('media') }}';
    var initializedEditors = {};
    var activeLocale = '{{ $locales->first()?->code ?? '' }}';
    var translationData = @json($translationData);
    var visualEditorBaseUrl = '{{ route("pages.visual-editor", $page) }}';

    function mediaUpload(formData, onSuccess, onError) {
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        $.ajax({
            url: mediaUploadUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    onSuccess(mediaBaseUrl + '/' + res.file.url.replace(/^media\//, ''));
                } else {
                    onError('Error al subir la imagen');
                }
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
            menubar: false,
            branding: false,
            promotion: false,
            resize: true,
            automatic_uploads: true,
            images_upload_handler: function (blobInfo, progress) {
                return new Promise(function (resolve, reject) {
                    var fd = new FormData();
                    fd.append('file', blobInfo.blob(), blobInfo.filename());
                    mediaUpload(fd, resolve, reject);
                });
            },
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
            setup: function (editor) {
                editor.on('change input keyup', function () {
                    editor.save();
                    clearTimeout(autoSaveTimeout);
                    autoSaveTimeout = setTimeout(performAutoSave, 2000);
                });
            }
        };
    }

    function initEditorForLocale(locale) {
        if (initializedEditors[locale]) return;
        initializedEditors[locale] = true;
        tinymce.init(tinyMCEConfig(locale));
    }

    // Inicializar primer locale al cargar
    @if($locales->isNotEmpty())
    initEditorForLocale('{{ $locales->first()->code }}');
    @endif

    // Inicializar al cambiar de tab
    $('#langTabs button').on('shown.bs.tab', function (e) {
        var locale = $(e.target).data('locale');
        activeLocale = locale;
        initEditorForLocale(locale);
        updateSidebarForLocale(locale);
    });

    // =========================================================
    // SIDEBAR REACTIVO POR LOCALE
    // =========================================================
    function updateSidebarForLocale(locale) {
        var d = translationData[locale] || {};
        var status = $('#trans-status-' + locale).val() || d.status || 'draft';
        var pubAt = $('#trans-published-at-' + locale).val() || d.published_at || '';

        // Ver página
        var $verBtn = $('#btn-ver-pagina');
        if (d.url) {
            $verBtn.attr('href', d.url).removeClass('disabled');
        } else {
            $verBtn.attr('href', '#').addClass('disabled');
        }

        // Editor visual
        $('#btn-editor-visual').attr('href', visualEditorBaseUrl + '?locale=' + locale);

        // Status select
        $('#main-status-select').val(status);

        // Published at
        $('#main-published-at').val(pubAt);
        $('#main-published-at-wrap').toggle(status === 'published');

        // Publish/unpublish buttons
        if (status === 'published') {
            $('#form-unpublish-action').show();
            $('#form-publish-action').hide();
        } else {
            $('#form-unpublish-action').hide();
            $('#form-publish-action').show();
        }
    }

    $('#main-status-select').on('change', function () {
        var status = $(this).val();
        $('#trans-status-' + activeLocale).val(status);
        $('#main-published-at-wrap').toggle(status === 'published');
        if (status === 'published') {
            $('#form-unpublish-action').show();
            $('#form-publish-action').hide();
        } else {
            $('#form-unpublish-action').hide();
            $('#form-publish-action').show();
        }
    });

    $('#main-published-at').on('change', function () {
        $('#trans-published-at-' + activeLocale).val($(this).val());
    });

    // Botón Agregar media - abre el Gestor de medios vía MediaPicker
    $(document).on('click', '.add-media-btn', function () {
        var locale = $(this).data('locale');
        window.MediaPicker.open({
            urls: {
                list:   '{{ route("media.list") }}',
                upload: '{{ route("media.files.upload") }}',
                base:   '{{ url("media") }}',
            },
            title: 'Gestor de medios',
            onSelect: function (fullUrl, file) {
                var tag = file && file.type === 'image'
                    ? '<img src="' + fullUrl + '" alt="' + (file.name || '') + '" style="max-width:100%">'
                    : '<a href="' + fullUrl + '" target="_blank">' + (file ? file.name : fullUrl) + '</a>';
                var editor = tinymce.get('content-' + locale);
                if (editor) {
                    editor.insertContent(tag);
                } else {
                    var ta = document.getElementById('content-' + locale);
                    if (ta) {
                        var pos = ta.selectionStart || ta.value.length;
                        ta.value = ta.value.substring(0, pos) + tag + ta.value.substring(pos);
                    }
                }
            }
        });
    });

    // Botón UI Blocks - rastrear locale activo al abrir modal
    $(document).on('click', '.ui-blocks-btn', function () {
        activeLocale = $(this).data('locale');
    });

    // Botón insertar shortcode desde panel de ayuda
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

        // Durante búsqueda: mostrar pane __all__ con filtro
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
        setTimeout(function () {
            $('#blockConfigModal').modal('show');
        }, 300);
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
    // IMAGEN DESTACADA — Media Picker
    // =========================================================
    function setFeaturedImage(url) {
        $('#featured_image_url').val(url);
        $('#imagePreviewContainer').html(
            '<div class="position-relative">' +
                '<img src="' + url + '" alt="Imagen destacada" id="featuredImagePreview"' +
                     ' class="img-fluid rounded" style="max-height:180px;object-fit:cover;width:100%;">' +
                '<button type="button" id="btn-remove-featured-image"' +
                        ' class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" title="Quitar imagen">' +
                    '<i class="fas fa-times"></i>' +
                '</button>' +
            '</div>'
        );
    }

    function clearFeaturedImage() {
        $('#featured_image_url').val('');
        $('#imagePreviewContainer').html(
            '<div id="featuredImageEmpty" class="text-center py-3 border border-2 border-dashed rounded bg-light">' +
                '<i class="fas fa-image fa-2x text-muted mb-1"></i>' +
                '<p class="text-muted mb-0 small">Sin imagen</p>' +
            '</div>'
        );
    }

    $('#btn-featured-image-picker').on('click', function () {
        window.MediaPicker.open({
            urls: {
                list:   '{{ route("media.list") }}',
                upload: '{{ route("media.files.upload") }}',
                base:   '{{ url("media") }}',
            },
            title: 'Seleccionar imagen destacada',
            onSelect: function (fullUrl) {
                setFeaturedImage(fullUrl);
            }
        });
    });

    $(document).on('click', '#btn-remove-featured-image', function () {
        clearFeaturedImage();
    });

    // =========================================================
    // PREVIEW TOKEN
    // =========================================================
    $('#generatePreviewBtn').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Generando...');
        $.ajax({
            url: '{{ route("pages.preview.generate", $page->id) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                if (data.success) {
                    $('#previewUrl').val(data.data.url);
                    $('#openPreviewBtn').attr('href', data.data.url);
                    $('#previewExpires').text(data.data.expires_in_human);
                    $('#previewUrlSection').fadeIn();
                    toastr.success('Preview generado');
                }
            },
            error: function () { toastr.error('Error al generar preview'); },
            complete: function () { $btn.prop('disabled', false).text('Generar preview'); }
        });
    });
    $('#copyPreviewBtn').on('click', function () {
        var $btn = $(this);
        navigator.clipboard.writeText($('#previewUrl').val()).then(function () {
            var orig = $btn.text();
            $btn.text('Copiado').removeClass('btn-outline-secondary').addClass('btn-success');
            setTimeout(function () { $btn.text(orig).removeClass('btn-success').addClass('btn-outline-secondary'); }, 2000);
        });
    });
    $('#revokePreviewBtn').on('click', function () {
        if (!confirm('¿Revocar todos los previews activos?')) return;
        var $btn = $(this).prop('disabled', true).text('Revocando...');
        $.ajax({
            url: '{{ route("pages.preview.revoke", $page->id) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                if (data.success) { $('#previewUrlSection').fadeOut(); toastr.success('Previews revocados'); }
            },
            error: function () { toastr.error('Error al revocar'); },
            complete: function () { $btn.prop('disabled', false).text('Revocar previews'); }
        });
    });

    // Cargar preview activo
    $.get('{{ route("pages.preview.index", $page->id) }}', function (data) {
        if (data.success && data.data.length > 0) {
            var active = data.data.find(function (t) { return t.is_active; });
            if (active) {
                $('#previewUrl').val(active.url);
                $('#openPreviewBtn').attr('href', active.url);
                $('#previewExpires').text(active.expires_in_human);
                $('#previewViews').text(active.viewed_count);
                $('#previewUrlSection').show();
            }
        }
    });

    // =========================================================
    // DELETE
    // =========================================================
    $('.delete-btn').on('click', function () {
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Toastr flash
    @if(session('success'))
    toastr.success(@json(session('success')), 'Éxito');
    @endif
    @if(session('error'))
    toastr.error(@json(session('error')), 'Error');
    @endif

    // =========================================================
    // AUTO-SAVE
    // =========================================================
    var pageId = {{ $page->id }};
    var autoSaveTimeout;
    var autoSaveUrl = '{{ route('api.pages.auto-save', $page->id) }}';
    var lastSavedData = {};

    function collectAutoSaveData() {
        var data = {
            status: $('#status').val(),
            template: $('#template').val(),
            header_style: $('#header_style').val(),
            translations: {}
        };
        @foreach($locales as $localeObj)
        @php $locale = $localeObj->code; @endphp
        data.translations['{{ $locale }}'] = {
            title: $('#title-{{ $locale }}').val(),
            slug: $('#slug-{{ $locale }}').val(),
            description: $('#description-{{ $locale }}').val(),
            content: tinymce.get('content-{{ $locale }}') ? tinymce.get('content-{{ $locale }}').getContent() : $('#content-{{ $locale }}').val(),
        };
        @endforeach
        return data;
    }

    function performAutoSave() {
        var currentData = collectAutoSaveData();

        if (JSON.stringify(currentData) === JSON.stringify(lastSavedData)) {
            return;
        }

        $.ajax({
            url: autoSaveUrl,
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            data: JSON.stringify(currentData),
            success: function (response) {
                if (response.success) {
                    lastSavedData = currentData;
                    toastr.success('Guardado ' + (new Date().toLocaleTimeString()), 'Auto-guardado', { timeOut: 2000, positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                console.error('Error en auto-save:', xhr);
                toastr.error('No se pudo auto-guardar', 'Error', { timeOut: 4000, positionClass: 'toast-bottom-right' });
            }
        });
    }

    // Detectar cambios en campos del sidebar
    $('#status, #template, #header_style').on('input change', function () {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(performAutoSave, 2000);
    });

    // Detectar cambios en campos traducibles (delegado)
    $(document).on('input change', '[name^="translations"]', function () {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(performAutoSave, 2000);
    });

    // Inicializar lastSavedData
    lastSavedData = collectAutoSaveData();

    // =========================================================
    // PAGE LOCKS
    // =========================================================
    var pageId = {{ $page->id }};
    var lockCheckUrl = '{{ route('api.pages.lock.check', $page->id) }}';
    var lockAcquireUrl = '{{ route('api.pages.lock.acquire', $page->id) }}';
    var lockReleaseUrl = '{{ route('api.pages.lock.release', $page->id) }}';
    var lockRenewUrl = '{{ route('api.pages.lock.renew', $page->id) }}';
    var lockRenewInterval;
    var currentLock = null;

    // Función para verificar/adquirir lock al cargar
    function checkAndAcquireLock() {
        $.ajax({
            url: lockCheckUrl,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.data && !response.data.is_owned_by_me) {
                    // Página bloqueada por otro usuario
                    showLockAlert(response.data);
                    disableEditForm();
                    return;
                }

                // Intentar adquirir lock
                acquireLock();
            },
            error: function(xhr) {
                if (xhr.status === 423) {
                    // Página bloqueada
                    var lockData = xhr.responseJSON?.data;
                    if (lockData) {
                        showLockAlert(lockData);
                        disableEditForm();
                    }
                }
            }
        });
    }

    function acquireLock() {
        $.ajax({
            url: lockAcquireUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    currentLock = response.data;
                    console.log('✓ Lock adquirido para página ' + pageId);

                    // Renovar lock cada 3 minutos
                    clearInterval(lockRenewInterval);
                    lockRenewInterval = setInterval(renewLock, 180000); // 3 minutos

                    // Liberar lock al abandonar la página
                    $(window).on('beforeunload', releaseLock);
                }
            },
            error: function(xhr) {
                if (xhr.status === 423) {
                    // Otra persona está editando
                    var lockData = xhr.responseJSON?.data;
                    if (lockData) {
                        showLockAlert(lockData);
                        disableEditForm();
                    }
                }
            }
        });
    }

    function renewLock() {
        if (!currentLock) return;

        $.ajax({
            url: lockRenewUrl,
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    currentLock = response.data;
                    console.log('✓ Lock renovado');
                }
            }
        });
    }

    function releaseLock() {
        if (!currentLock) return;

        // Usar navigator.sendBeacon para asegurar que se envíe
        var token = $('meta[name="csrf-token"]').attr('content');
        var data = new FormData();
        data.append('_token', token);
        data.append('_method', 'DELETE');

        // Intentar con AJAX pero sin esperar respuesta
        $.ajax({
            url: lockReleaseUrl,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token,
            },
            async: false // Esperar respuesta antes de cerrar
        });

        currentLock = null;
        console.log('✓ Lock liberado');
    }

    function showLockAlert(lockData) {
        var $alert = $('#lockAlert');
        var userName = lockData.locked_by_user?.name || 'Otro usuario';
        var lockedTime = lockData.locked_at || 'hace poco';

        $('#lockUserName').html('<strong>' + userName + '</strong>');
        $('#lockTime').text(lockedTime);
        $alert.show();

        // Auto-dismiss después de 10 segundos
        setTimeout(function() {
            $alert.fadeOut();
        }, 10000);
    }

    function disableEditForm() {
        // Deshabilitar botón de guardar
        $('#pageForm button[type="submit"]').prop('disabled', true).addClass('disabled');

        // Deshabilitar todos los inputs
        $('#pageForm input, #pageForm textarea, #pageForm select').prop('disabled', true);

        // Mostrar mensaje
        toastr.warning('Esta página está siendo editada por otro usuario. No puedes hacer cambios.');
    }

    // Inicializar sidebar para el locale activo
    updateSidebarForLocale(activeLocale || '{{ $locales->first()?->code ?? '' }}');

    // Verificar lock al cargar la página
    checkAndAcquireLock();

    // =========================================================
    // WEBSOCKET - BROADCASTING CON ECHO
    // =========================================================
    if (typeof Reverb !== 'undefined' && typeof Echo !== 'undefined') {
        // Escuchar eventos en tiempo real para esta página
        var channel = Echo.channel('page.' + pageId);

        // Cuando otro usuario adquiere lock
        channel.listen('lock-acquired', function(data) {
            console.log('👤 ' + data.locked_by.name + ' comenzó a editar');

            // Si no es nuestro lock, mostrar alerta
            if (data.locked_by.id !== {{ auth()->id() }}) {
                showLockAlert({
                    is_locked: true,
                    is_owned_by_me: false,
                    locked_by_user: data.locked_by,
                    locked_at: moment(data.locked_at).fromNow(),
                    expires_in_human: moment(data.expires_at).fromNow()
                });
                disableEditForm();
            }
        });

        // Cuando otro usuario libera lock
        channel.listen('lock-released', function(data) {
            console.log('✓ Lock liberado');

            // Si fue liberado por otro usuario, esconder alerta
            if (data.released_by_user_id !== {{ auth()->id() }}) {
                $('#lockAlert').fadeOut();
                // Recargar página para adquirir nuevo lock
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        });

        // Cuando otro usuario hace auto-save
        channel.listen('auto-saved', function(data) {
            // Solo mostrar si es otro usuario
            if (data.saved_by.id !== {{ auth()->id() }}) {
                console.log('💾 ' + data.saved_by.name + ' guardó cambios en: ' + data.fields_changed.join(', '));

                // Mostrar notificación (opcional)
                // toastr.info(data.saved_by.name + ' guardó cambios', 'Sincronización', { timeOut: 3000 });
            }
        });
    }

});

function updateViewLink(locale) {
    var baseUrl = '{{ $prefix ? url($prefix) . "/" : url("/") . "/" }}';
    var slug = $('#slug-' + locale).val();
    var $link = $('#view-link-' + locale);
    if (slug) {
        var fullUrl = baseUrl + slug;
        $link.attr('href', fullUrl).text('').html('<i class="fas fa-external-link-alt me-1"></i>' + fullUrl).closest('div').show();
    } else {
        $link.closest('div').hide();
    }
}

// Actualizar links al cambiar slug
$(document).on('input', '[id^="slug-"]', function() {
    var locale = this.id.replace('slug-', '');
    updateViewLink(locale);
});

function regenerateSlugForLocale(locale) {
    var title = $('#title-' + locale).val();
    if (!title) return;

    @php
        $slugAjaxUrl = \Illuminate\Support\Facades\Route::has('pages.ajax.slug') ? route('pages.ajax.slug') : null;
    @endphp
    @if($slugAjaxUrl)
    $.post('{{ $slugAjaxUrl }}', {
        title: title,
        ignoreId: {{ $page->id }},
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function (data) {
        $('#slug-' + locale).val(data.slug);
    });
    @else
    var slug = title.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
    $('#slug-' + locale).val(slug);
    @endif
}

function toggleEditor(locale) {
    var $wrapper = $('#editorWrapper-' + locale);
    var $btn = $('[onclick="toggleEditor(\'' + locale + '\')"]');
    $wrapper.toggleClass('d-none');
    $btn.toggleClass('active btn-secondary btn-light');
}

// =========================================================
// MODAL DE VISTA PREVIA RESPONSIVE
// =========================================================
var previewResolutions = {
    desktop: '1920 × 1080',
    tablet:  '768 × 1024',
    mobile:  '375 × 812'
};

function openPreviewModal(url) {
    var iframe = document.getElementById('preview-iframe');
    if (iframe.src !== url) {
        iframe.src = url;
    }
    $('#preview-open-tab').attr('href', url);
    var modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

$('#btn-preview').on('click', function () {
    var existingUrl = $('#previewUrl').val();
    if (existingUrl) {
        var previewUrl = existingUrl + (existingUrl.indexOf('?') === -1 ? '?' : '&') + 'locale=' + activeLocale;
        openPreviewModal(previewUrl);
        return;
    }

    var $btn = $(this).prop('disabled', true);
    $.ajax({
        url: '{{ route("pages.preview.generate", $page->id) }}',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (data) {
            if (data.success) {
                var url = data.data.url;
                $('#previewUrl').val(url);
                $('#openPreviewBtn').attr('href', url);
                $('#previewExpires').text(data.data.expires_in_human);
                $('#previewUrlSection').fadeIn();
                openPreviewModal(url + (url.indexOf('?') === -1 ? '?' : '&') + 'locale=' + activeLocale);
            } else {
                toastr.error('No se pudo generar la vista previa');
            }
        },
        error: function () { toastr.error('Error al generar la vista previa'); },
        complete: function () { $btn.prop('disabled', false); }
    });
});

// Selector de dispositivo
$('#device-selector .btn').on('click', function () {
    var $btn = $(this);
    var device = $btn.data('device');
    var width  = $btn.data('width');

    $('#device-selector .btn').removeClass('active');
    $btn.addClass('active');
    $('#preview-resolution').text(previewResolutions[device]);

    var $wrapper = $('#preview-frame-wrapper');
    var $iframe  = $('#preview-iframe');

    if (device === 'desktop') {
        $wrapper.css({ width: '100%', 'max-width': '', 'box-shadow': 'none', margin: '0', height: '100vh' });
        $iframe.css({ width: '100%', height: '100%', transform: 'none', 'transform-origin': '' });
        return;
    }

    var targetWidth  = parseInt(width);
    var targetHeight = device === 'mobile' ? 812 : 1024;
    var containerWidth = document.getElementById('previewModal').offsetWidth - 40;
    var scale = Math.min(1, containerWidth / targetWidth);

    $wrapper.css({
        width:       targetWidth + 'px',
        'max-width': targetWidth + 'px',
        height:      (targetHeight * scale) + 'px',
        'box-shadow': '0 0 30px rgba(0,0,0,0.4)',
        margin:      '20px auto'
    });

    if (scale < 1) {
        $iframe.css({
            width:              targetWidth + 'px',
            height:             targetHeight + 'px',
            transform:          'scale(' + scale + ')',
            'transform-origin': 'top left'
        });
    } else {
        $iframe.css({ width: '100%', height: targetHeight + 'px', transform: 'none', 'transform-origin': '' });
    }
});

// Restablecer dispositivo al cerrar el modal
$('#previewModal').on('hidden.bs.modal', function () {
    $('#device-selector .btn').removeClass('active').first().addClass('active');
    $('#preview-resolution').text(previewResolutions.desktop);
    var $wrapper = $('#preview-frame-wrapper');
    var $iframe  = $('#preview-iframe');
    $wrapper.css({ width: '100%', 'max-width': '', 'box-shadow': 'none', margin: '0', height: '100vh' });
    $iframe.css({ width: '100%', height: '100%', transform: 'none', 'transform-origin': '' });
});

// =========================================================
// PAGESPEED PERFORMANCE
// =========================================================
var perfScanUrl  = '{{ route("pages.performance.scan", $page->id) }}';
var perfShowUrl  = '{{ route("pages.performance.show", $page->id) }}';
var perfPollInterval = null;
var perfPollAttempts = 0;

function scoreCircle(score, label, color) {
    if (score === null || score === undefined) {
        return '<div class="text-center"><div class="display-6 fw-bold text-muted">—</div><small class="text-muted">' + label + '</small></div>';
    }
    return '<div class="text-center">'
        + '<div class="display-6 fw-bold text-' + color + '">' + Math.round(score) + '</div>'
        + '<small class="text-muted">' + label + '</small>'
        + '</div>';
}

function metricBadge(value, unit) {
    if (value === null || value === undefined) return '<span class="text-muted">—</span>';
    return '<strong>' + value + '</strong><small class="text-muted ms-1">' + unit + '</small>';
}

function renderPerformanceResults(data) {
    var strategies = [
        { key: 'mobile',   label: 'Móvil',    icon: 'fa-mobile-alt' },
        { key: 'desktop',  label: 'Escritorio', icon: 'fa-desktop' }
    ];

    var html = '<div class="row g-3">';

    strategies.forEach(function(s) {
        var m = data[s.key];
        html += '<div class="col-12"><h6 class="fw-semibold mb-2"><i class="fas ' + s.icon + ' me-1 text-muted"></i>' + s.label + '</h6>';

        if (!m) {
            html += '<p class="text-muted">Sin datos aún.</p></div>';
            return;
        }

        var perfColor = m.performance_color || 'secondary';
        html += '<div class="row g-2 mb-2">'
            + '<div class="col-3">' + scoreCircle(m.performance_score, 'Rendimiento', perfColor) + '</div>'
            + '<div class="col-3">' + scoreCircle(m.accessibility_score, 'Accesibilidad', 'info') + '</div>'
            + '<div class="col-3">' + scoreCircle(m.seo_score, 'SEO', 'primary') + '</div>'
            + '<div class="col-3">' + scoreCircle(m.best_practices_score, 'Prácticas', 'secondary') + '</div>'
            + '</div>';

        html += '<ul class="list-unstyled small mb-0 ps-1">'
            + '<li><span class="text-muted">LCP:</span> ' + metricBadge(m.lcp, 'ms') + '</li>'
            + '<li><span class="text-muted">FCP:</span> ' + metricBadge(m.fcp, 'ms') + '</li>'
            + '<li><span class="text-muted">TBT:</span> ' + metricBadge(m.tbt, 'ms') + '</li>'
            + '<li><span class="text-muted">TTFB:</span> ' + metricBadge(m.ttfb, 'ms') + '</li>'
            + '</ul>';

        if (m.opportunities && m.opportunities.length) {
            html += '<div class="mt-2"><small class="text-muted fw-semibold">Oportunidades de mejora:</small><ul class="list-unstyled small mt-1">';
            m.opportunities.forEach(function(o) {
                html += '<li class="text-truncate"><i class="fas fa-lightbulb text-warning me-1"></i>' + o.title + '</li>';
            });
            html += '</ul></div>';
        }

        html += '<small class="text-muted d-block mt-1"><i class="fas fa-clock me-1"></i>' + (m.created_at || '') + '</small>';
        html += '</div>';
    });

    html += '</div>';
    $('#performance-results').html(html);
}

$('#btn-scan-performance').on('click', function () {
    var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Analizando...');

    clearInterval(perfPollInterval);
    perfPollAttempts = 0;

    $.post(perfScanUrl, { _token: $('meta[name="csrf-token"]').attr('content') })
        .done(function (resp) {
            toastr.success(resp.message);
            $('#performance-results').html(
                '<div class="text-center py-3">'
                + '<span class="spinner-border spinner-border-sm text-primary me-2"></span>'
                + '<small class="text-muted">Procesando análisis...</small>'
                + '</div>'
            );

            perfPollInterval = setInterval(function () {
                perfPollAttempts++;
                $.get(perfShowUrl)
                    .done(function (d) {
                        if (d.mobile || d.desktop) {
                            clearInterval(perfPollInterval);
                            renderPerformanceResults(d);
                        }
                    });
                if (perfPollAttempts >= 12) {
                    clearInterval(perfPollInterval);
                }
            }, 5000);
        })
        .fail(function () {
            toastr.error('Error al iniciar el análisis');
        })
        .always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i>Analizar');
        });
});

// Cargar métricas existentes al abrir la página
$.get(perfShowUrl).done(function (d) {
    if (d.mobile || d.desktop) {
        renderPerformanceResults(d);
    }
});

// =========================================================
// ANALYTICS DE VISTAS
// =========================================================
var analyticsUrl = '{{ route("pages.analytics.show", $page->id) }}';
var analyticsChart = null;
var pageSparkCharts = {};

function fmtNum(n) { return parseInt(n || 0).toLocaleString('es-ES'); }

function cmpBadge(current, previous) {
    if (!previous || previous === 0) return '';
    var pct  = ((current - previous) / previous * 100).toFixed(1);
    var up   = parseFloat(pct) > 0;
    var icon = up ? 'fa-arrow-up' : 'fa-arrow-down';
    var bg   = up ? 'bg-success-subtle' : 'bg-danger-subtle';
    var txt  = up ? 'text-success'      : 'text-danger';
    var sign = parseFloat(pct) > 0 ? '+' : '';
    return '<div class="d-flex align-items-center">' +
        '<span class="me-1 rounded-circle ' + bg + ' d-flex align-items-center justify-content-center" style="width:20px;height:20px;">' +
            '<i class="fas ' + icon + ' ' + txt + '" style="font-size:0.6rem;"></i>' +
        '</span>' +
        '<p class="text-dark me-1 fs-3 mb-0">' + sign + Math.abs(pct) + '%</p>' +
        '<p class="fs-3 mb-0 text-muted">vs anterior</p>' +
        '</div>';
}

function pageSparkCfg(data, color, type) {
    return {
        series: [{ data: data }],
        chart: { type: type, height: 70, width: 70, sparkline: { enabled: true }, animations: { enabled: false }, fontFamily: 'inherit' },
        colors: [color],
        stroke: { curve: 'smooth', width: 2 },
        fill: type === 'area'
            ? { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } }
            : { type: 'solid' },
        tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: function() { return ''; } } } },
        plotOptions: { bar: { borderRadius: 2, columnWidth: '60%' } },
    };
}

function renderPageSparklines(viewsData) {
    var sparks = {
        '#spark-total-views':      { data: viewsData, color: '#b10100', type: 'bar'  },
        '#spark-unique-visitors':  { data: viewsData, color: '#333333', type: 'bar'  },
        '#spark-avg-daily':        { data: viewsData, color: '#7b0000', type: 'area' },
    };
    $.each(sparks, function(sel, cfg) {
        var el = document.querySelector(sel);
        if (!el) return;
        if (pageSparkCharts[sel]) { pageSparkCharts[sel].destroy(); }
        pageSparkCharts[sel] = new ApexCharts(el, pageSparkCfg(cfg.data, cfg.color, cfg.type));
        pageSparkCharts[sel].render();
    });
}

function loadAnalytics(days) {
    $('#stat-total-views, #stat-unique-visitors, #stat-avg-daily')
        .html('<span class="spinner-border spinner-border-sm text-muted"></span>');
    $('#cmp-total-views, #cmp-unique-visitors, #cmp-avg-daily').html('');

    var months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    var fmtDate = function(d) {
        if (!d || d.length < 8) return d;
        return months[parseInt(d.slice(5,7)) - 1] + ' ' + parseInt(d.slice(8,10));
    };

    $.get(analyticsUrl, { days: days })
        .done(function (data) {
            var s = data.summary;
            var p = data.previous_summary;

            if (!s.total_views) {
                $('#analytics-card').addClass('d-none');
                return;
            }
            $('#analytics-card').removeClass('d-none');

            $('#stat-total-views').text(fmtNum(s.total_views));
            $('#stat-unique-visitors').text(fmtNum(s.unique_visitors));
            $('#stat-avg-daily').text(parseFloat(s.avg_daily).toFixed(1));

            if (p) {
                $('#cmp-total-views').html(cmpBadge(s.total_views, p.total_views));
                $('#cmp-unique-visitors').html(cmpBadge(s.unique_visitors, p.unique_visitors));
                $('#cmp-avg-daily').html(cmpBadge(s.avg_daily, p.avg_daily));
            }

            var viewsData = $.map(data.views_by_day, function(r) { return r.views; });
            renderPageSparklines(viewsData);

            var dates = $.map(data.views_by_day, function(r) { return fmtDate(r.date); });

            if (analyticsChart) { analyticsChart.destroy(); analyticsChart = null; }
            $('#chart-views-by-day').html('');

            analyticsChart = new ApexCharts(document.querySelector('#chart-views-by-day'), {
                series: [{ name: 'Vistas', data: viewsData }],
                chart: { type: 'area', height: 295, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
                colors: ['#b10100'],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] } },
                xaxis: { categories: dates, labels: { style: { fontSize: '11px', colors: '#adb5bd' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: function(v) { return Math.round(v); } } },
                grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
                tooltip: { theme: 'light', shared: true, intersect: false },
                legend: { show: false },
                markers: { size: 0 },
                dataLabels: { enabled: false }
            });
            analyticsChart.render();
        })
        .fail(function () {
            $('#analytics-card').addClass('d-none');
        });
}

$('#analytics-days').on('change', function () {
    loadAnalytics($(this).val());
});

loadAnalytics(30);

// =========================================================
// DEEPL TRANSLATION
// =========================================================
var pageDeeplTranslateUrl     = '{{ route("pages.translate", $page->id) }}';
var pageDeeplAutoTranslateUrl = '{{ route("pages.auto-translate", $page->id) }}';

$('#btn-page-deepl-translate').on('click', function () {
    var targetLocale = $('#page-deepl-target-lang').val();
    if (!targetLocale) { toastr.warning('Selecciona un idioma de destino'); return; }

    var $btn = $(this);
    $btn.prop('disabled', true);
    $('#page-deepl-spinner').removeClass('d-none');

    $.ajax({
        url: pageDeeplTranslateUrl,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: { target_locale: targetLocale },
        success: function (res) {
            toastr.success('Traducción al ' + targetLocale.toUpperCase() + ' completada. Recarga para ver los cambios.');
        },
        error: function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error
                : 'Error al traducir con DeepL';
            toastr.error(msg);
        },
        complete: function () {
            $btn.prop('disabled', false);
            $('#page-deepl-spinner').addClass('d-none');
        }
    });
});

$('#btn-page-deepl-auto-translate').on('click', function () {
    if (!confirm('¿Traducir automáticamente a todos los idiomas soportados?')) return;

    var $btn = $(this);
    $btn.prop('disabled', true);
    $('#page-deepl-auto-spinner').removeClass('d-none');

    $.ajax({
        url: pageDeeplAutoTranslateUrl,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: {},
        success: function (res) {
            if (res.success) {
                toastr.success(res.message + ' Recarga para ver los cambios.');
            } else {
                toastr.warning(res.message);
            }
        },
        error: function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error
                : 'Error al traducir con DeepL';
            toastr.error(msg);
        },
        complete: function () {
            $btn.prop('disabled', false);
            $('#page-deepl-auto-spinner').addClass('d-none');
        }
    });
});
</script>
@endpush
