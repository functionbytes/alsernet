@extends('layouts.theme')

@section('page_title', 'Editar página')

@section('content')

    @include('core::components.card', ['title' => 'Editar página: ' . $page->title])

    @include('core::components.alerts')

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


            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-2">

                {{-- Publicar --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Publicar</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <button type="submit" form="pageForm" class="btn btn-primary">
                            Guardar
                        </button>
                        <a href="{{ route('pages.index') }}" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <hr class="my-1">
                        <a href="{{ $page->url }}" class="btn btn-outline-secondary" target="_blank">
                            Ver página
                        </a>
                        @if($page->hasVersions())
                            <a href="{{ route('pages.versions.index', $page->id) }}" class="btn btn-outline-secondary">
                                Ver versiones ({{ $page->getTotalVersions() }})
                            </a>
                        @endif
                        @if($page->isPublished())
                            <form action="{{ route('pages.unpublish', $page->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">Despublicar</button>
                            </form>
                        @else
                            <form action="{{ route('pages.publish', $page->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">Publicar ahora</button>
                            </form>
                        @endif
                        <form action="{{ route('pages.duplicate', $page->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">Duplicar página</button>
                        </form>
                        <button type="button" class="btn btn-outline-secondary" id="generatePreviewBtn">Generar preview</button>
                        <button type="button" class="btn btn-outline-secondary" id="revokePreviewBtn">Revocar previews</button>
                        <hr class="my-1">
                        <button type="button" class="btn btn-outline-danger" id="deleteBtn">Eliminar página</button>
                        <form action="{{ route('pages.destroy', $page->id) }}" method="POST" id="deleteForm">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>

                {{-- Apariencia --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Apariencia</h5>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="header_style" class="form-label fw-semibold">Estilo de encabezado</label>
                            <select class="form-select @error('header_style') is-invalid @enderror"
                                    id="header_style" name="header_style">
                                <option value="header-style-1" {{ old('header_style', $page->header_style) === 'header-style-1' ? 'selected' : '' }}>Por defecto</option>
                                <option value="header-style-2" {{ old('header_style', $page->header_style) === 'header-style-2' ? 'selected' : '' }}>Estilo de encabezado 2</option>
                                <option value="header-style-3" {{ old('header_style', $page->header_style) === 'header-style-3' ? 'selected' : '' }}>Estilo de encabezado 3</option>
                                <option value="header-style-4" {{ old('header_style', $page->header_style) === 'header-style-4' ? 'selected' : '' }}>Estilo de encabezado 4</option>
                            </select>
                            @error('header_style')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ old('status', $page->status) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label for="template" class="form-label fw-semibold">Plantilla <span class="text-danger">*</span></label>
                            <select class="form-select @error('template') is-invalid @enderror" id="template" name="template">
                                @foreach($templates as $key => $label)
                                    <option value="{{ $key }}" {{ old('template', $page->template) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <label for="published_at" class="form-label fw-semibold small">Fecha de publicación</label>
                            <input type="datetime-local" class="form-control form-control-sm @error('published_at') is-invalid @enderror"
                                   id="published_at" name="published_at"
                                   value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}">
                            @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="publish_at" class="form-label fw-semibold small">Publicar el (programado)</label>
                            <input type="datetime-local" class="form-control form-control-sm @error('publish_at') is-invalid @enderror"
                                   id="publish_at" name="publish_at"
                                   value="{{ old('publish_at', $page->publish_at?->format('Y-m-d\TH:i')) }}">
                            @error('publish_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label for="unpublish_at" class="form-label fw-semibold small">Despublicar el</label>
                            <input type="datetime-local" class="form-control form-control-sm @error('unpublish_at') is-invalid @enderror"
                                   id="unpublish_at" name="unpublish_at"
                                   value="{{ old('unpublish_at', $page->unpublish_at?->format('Y-m-d\TH:i')) }}">
                            @error('unpublish_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if($page->willBePublished() || $page->willBeUnpublished())
                            <div class="alert alert-info mt-3 mb-0 py-2">
                                <small>
                                    @if($page->willBePublished())
                                        <i class="fas fa-clock me-1"></i> Se publica el {{ $page->publish_at->format('d/m/Y H:i') }}<br>
                                    @endif
                                    @if($page->willBeUnpublished())
                                        <i class="fas fa-clock me-1"></i> Se despublica el {{ $page->unpublish_at->format('d/m/Y H:i') }}
                                    @endif
                                </small>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- Imagen destacada --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Imagen</h5>
                    </div>
                    <div class="card-body">
                        <div id="imagePreviewContainer" class="mb-3">
                            @if($page->featured_image)
                                <img src="{{ $page->featured_image }}" alt="Imagen destacada"
                                     class="img-fluid rounded" style="max-height:180px; object-fit:cover; width:100%">
                            @else
                                <div class="text-center py-3 border border-2 border-dashed rounded bg-light">
                                    <i class="fas fa-image fa-2x text-muted mb-1"></i>
                                    <p class="text-muted small mb-0">Sin imagen</p>
                                </div>
                            @endif
                        </div>
                        <input type="file" class="form-control form-control-sm @error('featured_image') is-invalid @enderror"
                               id="featured_image" name="featured_image"
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                        <small class="form-text text-muted">Máx. 2MB. JPG, PNG, GIF, WebP</small>
                        @error('featured_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Información --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Información</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted d-block">Creado</small>
                            <strong>{{ $page->created_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block">Modificado</small>
                            <strong>{{ $page->updated_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        @if($page->user)
                            <div class="mb-2">
                                <small class="text-muted d-block">Autor</small>
                                <strong>{{ $page->user->full_name ?? $page->user->name }}</strong>
                            </div>
                        @endif
                        @if($page->hasVersions())
                            <div class="mb-2">
                                <small class="text-muted d-block">Versiones</small>
                                <strong>{{ $page->getTotalVersions() }}</strong>
                            </div>
                        @endif
                        @php
                            $badges = ['published'=>'bg-success-subtle text-success','draft'=>'bg-secondary-subtle text-secondary','pending'=>'bg-warning-subtle text-warning'];
                            $labels = ['published'=>'Publicado','draft'=>'Borrador','pending'=>'Pendiente'];
                        @endphp
                        <div>
                            <small class="text-muted d-block mb-1">Estado</small>
                            <span class="badge {{ $badges[$page->status] ?? 'bg-secondary-subtle text-secondary' }}">
                                {{ $labels[$page->status] ?? $page->status }}
                            </span>
                        </div>
                    </div>
                </div>


            </div>


            {{-- COLUMNA PRINCIPAL --}}
            <div class="col-lg-8 order-lg-1">

                {{-- Información principal --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Información principal</h5>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title', $page->title) }}"
                                   required maxlength="255">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @php $prefix = setting('permalink-modules-page-models-page', ''); @endphp
                        <div class="mb-3">
                            <label for="slug" class="form-label fw-semibold">Enlace permanente <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text text-muted small">
                                    {{ $prefix ? url($prefix) . '/' : url('/') . '/' }}
                                </span>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                       id="slug" name="slug"
                                       value="{{ old('slug', $page->slug) }}"
                                       placeholder="slug-de-la-pagina" required>
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="regenerateSlug(document.getElementById('title').value)">
                                    Regenerar
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                Vista: <a href="#" id="slug-preview-link" target="_blank" class="text-primary">
                                    <span id="slug-preview"></span>
                                </a>
                            </small>
                            @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label for="description" class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      maxlength="500" placeholder="Descripción corta">{{ old('description', $page->description) }}</textarea>
                            <small class="form-text text-muted" id="description-counter">0 / 500 caracteres</small>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>

                {{-- Editor de contenido --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Contenido</h5>
                    </div>
                    <div class="card-body pb-0">

                        <div class="d-grid gap-2 mb-2">
                            <button type="button" class="btn btn-outline-secondary" id="toggleEditorBtn">
                                Mostrar/Ocultar Editor
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="addMediaBtn">
                                <i class="fas fa-image me-1"></i> Agregar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="uiBlocksBtn"
                                    data-bs-toggle="modal" data-bs-target="#uiBlocksModal">
                                <i class="fas fa-th-large me-1"></i> Bloques de interfaz de usuario
                            </button>
                        </div>

                        <div id="editorWrapper">
                            <textarea id="content" name="content" class="@error('content') is-invalid @enderror">{{ old('content', $page->content) }}</textarea>
                        </div>
                        @error('content')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror


                    </div>
                </div>

                {{-- SEO --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Optimizar para motores de búsqueda (SEO)</h5>
                        <a href="#" id="seoEditToggle" class="text-primary small fw-semibold">Editar</a>
                    </div>
                    <div class="card-body">

                        {{-- Preview estilo Google (modo colapsado) --}}
                        <div id="seoPreview">
                            <div class="p-3 border rounded bg-white">
                                <div id="seoPreviewTitle"
                                     style="color:#1a0dab; font-size:18px; font-weight:400; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $page->seo_title ?: $page->title }}
                                </div>
                                <div style="color:#006621; font-size:13px;">
                                    {{ $page->url }}
                                </div>
                                <div style="color:#545454; font-size:13px; margin-top:2px;">
                                    <span>{{ $page->updated_at->format('M d, Y') }} — </span>
                                    <span id="seoPreviewDesc">{{ $page->seo_description ?: $page->getExcerpt(160) }}</span>
                                </div>
                            </div>
                            @if(old('seo_noindex', $page->seo_noindex))
                                <div class="mt-2 small text-warning">
                                    <i class="fas fa-search me-1"></i> Esta página está marcada como <strong>noindex</strong>
                                </div>
                            @endif
                        </div>

                        {{-- Campos SEO editables (ocultos por defecto) --}}
                        <div id="seoEditSection" style="display:none;">
                            <hr class="my-3">

                            <div class="mb-3">
                                <label for="seo_title" class="form-label fw-semibold">Título SEO</label>
                                <input type="text" class="form-control @error('seo_title') is-invalid @enderror"
                                       id="seo_title" name="seo_title"
                                       value="{{ old('seo_title', $page->seo_title) }}" maxlength="70">
                                <small class="form-text text-muted" id="seo_title-counter">0 / 70 · Recomendado: 50-60</small>
                                @error('seo_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="seo_description" class="form-label fw-semibold">Descripción SEO</label>
                                <textarea class="form-control @error('seo_description') is-invalid @enderror"
                                          id="seo_description" name="seo_description" rows="3"
                                          maxlength="160">{{ old('seo_description', $page->seo_description) }}</textarea>
                                <small class="form-text text-muted" id="seo_description-counter">0 / 160 · Recomendado: 150-160</small>
                                @error('seo_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="seo_keywords" class="form-label fw-semibold">Palabras clave</label>
                                <input type="text" class="form-control @error('seo_keywords') is-invalid @enderror"
                                       id="seo_keywords" name="seo_keywords"
                                       value="{{ old('seo_keywords', $page->seo_keywords) }}" maxlength="500"
                                       placeholder="palabra1, palabra2, ...">
                                <small class="form-text text-warning"><i class="fas fa-info-circle me-1"></i>Google ya no usa meta keywords, pero pueden ser útiles para búsqueda interna.</small>
                                @error('seo_keywords')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Imagen SEO (Open Graph)</label>
                                <div id="seoImagePreviewContainer" class="mb-2" style="cursor:pointer">
                                    @if(old('seo_image_url', $page->seo_image_url))
                                        <img src="{{ old('seo_image_url', $page->seo_image_url) }}"
                                             class="img-fluid rounded" style="max-height:120px; object-fit:cover; width:100%">
                                    @else
                                        <div class="text-center py-3 border border-dashed rounded bg-light">
                                            <i class="fas fa-image fa-2x text-muted mb-1"></i>
                                            <p class="text-muted small mb-0">Haz clic para elegir</p>
                                        </div>
                                    @endif
                                </div>
                                <input type="hidden" id="seo_image_url" name="seo_image_url"
                                       value="{{ old('seo_image_url', $page->seo_image_url) }}">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1"
                                            id="seoPickerBtn">
                                        <i class="fas fa-images me-1"></i> Elegir imagen
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="seoImageClearBtn"
                                            {{ old('seo_image_url', $page->seo_image_url) ? '' : 'style="display:none"' }}>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Recomendado: 1200×630px. Máx. 2MB.</small>
                                @error('seo_image_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-semibold">Índice</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="seo_noindex" id="seo_index"
                                               value="0" {{ old('seo_noindex', $page->seo_noindex ? '1' : '0') == '0' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="seo_index">Índice</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="seo_noindex" id="seo_noindex_radio"
                                               value="1" {{ old('seo_noindex', $page->seo_noindex ? '1' : '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="seo_noindex_radio">Sin índice</label>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Vista previa de preview token --}}
                <div class="card mb-3" id="previewTokenCard">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Vista previa</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Genera un enlace temporal para compartir antes de publicar. Expira en 24h.</p>
                        <div id="previewUrlSection" style="display:none;" class="mb-3">
                            <div class="alert alert-success py-2 mb-0">
                                <strong class="d-block mb-2 small">Enlace generado:</strong>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="previewUrl" readonly>
                                    <button class="btn btn-outline-secondary" type="button" id="copyPreviewBtn">Copiar</button>
                                    <a href="#" class="btn btn-outline-primary" id="openPreviewBtn" target="_blank">Abrir</a>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-clock"></i> Expira: <span id="previewExpires"></span>
                                    <span class="ms-2"><i class="fas fa-eye"></i> Vistas: <span id="previewViews">0</span></span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

    {{-- MODAL: Bloques de interfaz de usuario --}}
    <div class="modal fade" id="uiBlocksModal" tabindex="-1" aria-labelledby="uiBlocksModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="uiBlocksModalLabel">Bloques de interfaz de usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">

                    {{-- Búsqueda --}}
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="uiBlocksSearch" placeholder="Buscar...">
                            <button class="btn btn-outline-secondary" type="button" id="uiBlocksClearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Grid de bloques --}}
                    @php $uiBlocks = \Modules\Template\Models\Shortcode::active()->get(); @endphp
                    <div class="row" id="uiBlocksGrid">
                        @forelse($uiBlocks as $block)
                            <div class="col-xl-3 col-lg-4 col-sm-6 mb-3 ui-block-item" data-name="{{ strtolower($block->name) }}">
                                <div class="card h-100 ui-block-card border" style="cursor:pointer;"
                                     data-block-key="{{ $block->key }}"
                                     data-block-name="{{ $block->name }}">
                                    <div class="card-body text-center py-3">
                                        <div class="mb-2" style="font-size:2rem; color:#adb5bd;">
                                            <i class="{{ $block->icon }}"></i>
                                        </div>
                                        <h6 class="card-title mb-1 fw-semibold">{{ $block->name }}</h6>
                                        <p class="card-text  text-muted mb-2">{{ $block->description }}</p>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-use-block"
                                                data-block-key="{{ $block->key }}"
                                                data-block-name="{{ $block->name }}">
                                            Usar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted text-center py-4">No hay shortcodes configurados. <a href="{{ route('settings.shortcodes.create') }}">Crear uno</a>.</p>
                            </div>
                        @endforelse
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="useSelectedBlock" disabled>Usar</button>
                </div>
            </div>
        </div>
    </div>

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

    // =========================================================
    // TINYMCE
    // =========================================================
    var editorActive = true;
    var editorId = 'content';
    var mediaUploadUrl = '{{ route('media.upload') }}';
    var mediaBaseUrl = '{{ url('media') }}';

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

    function initTinyMCE() {
        tinymce.init({
            selector: '#' + editorId,
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
                });
            }
        });
        editorActive = true;
    }

    function destroyTinyMCE() {
        if (tinymce.get(editorId)) {
            tinymce.get(editorId).remove();
        }
        editorActive = false;
    }

    // Inicializar al cargar
    initTinyMCE();

    // Toggle editor
    $('#toggleEditorBtn').on('click', function () {
        if (editorActive) {
            destroyTinyMCE();
            $('#' + editorId).show();
        } else {
            initTinyMCE();
        }
    });

    $('#addMediaBtn').on('click', function () {
        mediaPickerCallback = function (url, fullUrl) {
            var tag = '<img src="' + fullUrl + '" alt="" style="max-width:100%">';
            var editor = tinymce.get(editorId);
            if (editor) {
                editor.insertContent(tag);
            } else {
                var ta = document.getElementById(editorId);
                var pos = ta.selectionStart || ta.value.length;
                ta.value = ta.value.substring(0, pos) + tag + ta.value.substring(pos);
            }
            $('#seoImagePickerModal').modal('hide');
        };
        $('#seoImagePickerModal').modal('show');
    });

    // =========================================================
    // UI BLOCKS MODAL
    // =========================================================
    var selectedBlockKey = null;
    var selectedBlockName = null;

    // Búsqueda
    $('#uiBlocksSearch').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('.ui-block-item').each(function () {
            $(this).toggle($(this).data('name').includes(q));
        });
    });
    $('#uiBlocksClearSearch').on('click', function () {
        $('#uiBlocksSearch').val('');
        $('.ui-block-item').show();
    });

    // Selección de bloque (clic en card)
    $(document).on('click', '.ui-block-card', function () {
        $('.ui-block-card').removeClass('border-primary');
        $(this).addClass('border-primary');
        selectedBlockKey = $(this).data('block-key');
        selectedBlockName = $(this).data('block-name');
        $('#useSelectedBlock').prop('disabled', false);
    });

    // Doble clic → usar directamente
    $(document).on('dblclick', '.ui-block-card', function () {
        selectedBlockKey = $(this).data('block-key');
        selectedBlockName = $(this).data('block-name');
        openBlockConfig(selectedBlockKey, selectedBlockName);
    });

    // Botón "Usar" en card
    $(document).on('click', '.btn-use-block', function (e) {
        e.stopPropagation();
        selectedBlockKey = $(this).data('block-key');
        selectedBlockName = $(this).data('block-name');
        openBlockConfig(selectedBlockKey, selectedBlockName);
    });

    // Botón "Usar" en footer del modal
    $('#useSelectedBlock').on('click', function () {
        if (selectedBlockKey) {
            openBlockConfig(selectedBlockKey, selectedBlockName);
        }
    });

    function openBlockConfig(key, name) {
        $('#uiBlocksModal').modal('hide');
        $('#blockConfigTitle').text('Configurar: ' + name);
        $('#blockConfigBody').html(getBlockConfigForm(key));
        $('#insertBlockBtn').off('click').on('click', function () {
            insertBlock(key);
        });
        setTimeout(function () {
            $('#blockConfigModal').modal('show');
        }, 300);
    }

    function getBlockConfigForm(key) {
        var block = uiBlocksData.find(function (b) { return b.key === key; });
        if (!block || !block.config_fields || !block.config_fields.length) {
            return '<p class="text-muted">Este bloque no requiere configuración.</p>';
        }
        var html = '';
        block.config_fields.forEach(function (field) {
            html += '<div class="mb-3"><label class="form-label fw-semibold">' + field.label + '</label>';
            if (field.type === 'select' && field.options) {
                html += '<select class="form-select" id="' + field.id + '">';
                Object.entries(field.options).forEach(function (entry) {
                    html += '<option value="' + entry[0] + '">' + entry[1] + '</option>';
                });
                html += '</select>';
            } else if (field.type === 'textarea') {
                html += '<textarea class="form-control" id="' + field.id + '" rows="' + (field.rows || 4) + '" placeholder="' + (field.placeholder || '') + '"></textarea>';
            } else {
                html += '<input type="' + (field.type || 'text') + '" class="form-control" id="' + field.id + '" placeholder="' + (field.placeholder || '') + '">';
            }
            html += '</div>';
        });
        return html;
    }

    function insertBlock(key) {
        var shortcode = buildShortcode(key);
        var editor = tinymce.get(editorId);
        if (editor) {
            editor.insertContent(shortcode + '\n');
        } else {
            var ta = document.getElementById(editorId);
            var pos = ta.selectionStart || ta.value.length;
            ta.value = ta.value.substring(0, pos) + shortcode + '\n' + ta.value.substring(pos);
        }
        $('#blockConfigModal').modal('hide');
    }

    function buildShortcode(key) {
        var block = uiBlocksData.find(function (b) { return b.key === key; });
        if (!block || !block.shortcode_template) { return '[' + key + '][/' + key + ']'; }
        var result = block.shortcode_template;
        (block.config_fields || []).forEach(function (field) {
            var val = ($('#' + field.id).val() || '').replace(/"/g, '&quot;');
            result = result.replace(new RegExp('\\{' + field.id + '\\}', 'g'), val);
        });
        return result;
    }

    // =========================================================
    // SEO TOGGLE
    // =========================================================
    $('#seoEditToggle').on('click', function (e) {
        e.preventDefault();
        var $section = $('#seoEditSection');
        $section.toggle();
        $(this).text($section.is(':visible') ? 'Cerrar' : 'Editar');
    });

    // Preview SEO en tiempo real
    $('#title').on('input', function () {
        var val = $(this).val();
        var seoTitle = $('#seo_title').val();
        if (!seoTitle) {
            $('#seoPreviewTitle').text(val || 'Título de la página');
        }
        var $slug = $('#slug');
        if (!$slug.data('manual')) {
            $slug.val(generateSlugFromTitle(val));
            updateSlugPreview();
        }
    });
    $('#seo_title').on('input', function () {
        var val = $(this).val();
        $('#seoPreviewTitle').text(val || $('#title').val() || 'Título de la página');
    });
    $('#seo_description').on('input', function () {
        var val = $(this).val();
        $('#seoPreviewDesc').text(val || $('#description').val() || '');
    });

    // Contadores
    function setupCounter(id, max, recMin, recMax) {
        var $field = $('#' + id), $counter = $('#' + id + '-counter');
        if (!$field.length) return;
        $field.on('input', function () {
            var len = $(this).val().length;
            var text = len + ' / ' + max;
            var cls = 'text-muted';
            if (recMin && recMax) {
                if (len >= recMin && len <= recMax) { cls = 'text-success'; text += ' ✓ óptimo'; }
                else if (len > 0) { cls = 'text-warning'; text += ' · Recomendado: ' + recMin + '-' + recMax; }
                else { text += ' · Recomendado: ' + recMin + '-' + recMax; }
            }
            $counter.attr('class', 'form-text ' + cls).text(text);
        }).trigger('input');
    }
    setupCounter('description', 500);
    setupCounter('seo_title', 70, 50, 60);
    setupCounter('seo_description', 160, 150, 160);

    // =========================================================
    // SLUG
    // =========================================================
    $('#slug').on('input', function () {
        $(this).data('manual', true);
        updateSlugPreview();
    });

    updateSlugPreview();

    function generateSlugFromTitle(title) {
        return title.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }

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

    // =========================================================
    // MEDIA PICKER - compartido por Agregar + Imagen SEO
    // =========================================================
    var mediaBaseUrl = '{{ url('media') }}';
    var mediaListUrl = '{{ url('/media/list') }}';
    var mediaPickerCallback = null;
    var uiBlocksData = @json($uiBlocks->toArray());

    function openSeoImagePicker() {
        mediaPickerCallback = function (url, fullUrl) {
            $('#seo_image_url').val(fullUrl);
            $('#seoImagePreviewContainer').html(
                '<img src="' + fullUrl + '" class="img-fluid rounded" style="max-height:120px; object-fit:cover; width:100%">'
            );
            $('#seoImageClearBtn').show();
            $('#seoImagePickerModal').modal('hide');
        };
        $('#seoImagePickerModal').modal('show');
    }

    $('#seoImagePreviewContainer').on('click', function () { openSeoImagePicker(); });
    $('#seoPickerBtn').on('click', function () { openSeoImagePicker(); });

    $('#seoImageClearBtn').on('click', function () {
        $('#seo_image_url').val('');
        $('#seoImagePreviewContainer').html(
            '<div class="text-center py-3 border border-dashed rounded bg-light">' +
            '<i class="fas fa-image fa-2x text-muted mb-1"></i>' +
            '<p class="text-muted small mb-0">Haz clic para elegir</p></div>'
        );
        $(this).hide();
    });

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
                        '<div class="card-body p-1"><p class="card-text x-small text-truncate text-muted mb-0" style="font-size:11px">' + file.name + '</p></div>' +
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
    $('#deleteBtn').on('click', function () {
        if (confirm('¿Eliminar esta página?\n\nEsta acción no se puede deshacer.')) {
            $('#deleteForm').submit();
        }
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
    var autoSaveIndicator = '<span id="autoSaveIndicator" class="ms-2 badge bg-info"><i class="fas fa-sync-alt fa-spin me-1"></i>Auto-guardando...</span>';
    var lastSavedData = {};

    // Crear indicador en la barra de guardar si no existe
    if ($('#autoSaveIndicator').length === 0) {
        $('#pageForm .btn-primary:first').after(autoSaveIndicator);
    }

    function hasChanges() {
        var currentData = {
            title: $('#title').val(),
            slug: $('#slug').val(),
            description: $('#description').val(),
            content: tinymce.get(editorId) ? tinymce.get(editorId).getContent() : $('#' + editorId).val(),
            status: $('#status').val(),
            template: $('#template').val(),
            seo_title: $('#seo_title').val(),
            seo_description: $('#seo_description').val(),
            seo_keywords: $('#seo_keywords').val(),
            header_style: $('#header_style').val(),
            seo_noindex: $('input[name="seo_noindex"]:checked').val(),
        };

        return JSON.stringify(currentData) !== JSON.stringify(lastSavedData);
    }

    function performAutoSave() {
        var currentData = {
            title: $('#title').val(),
            slug: $('#slug').val(),
            description: $('#description').val(),
            content: tinymce.get(editorId) ? tinymce.get(editorId).getContent() : $('#' + editorId).val(),
            status: $('#status').val(),
            template: $('#template').val(),
            seo_title: $('#seo_title').val(),
            seo_description: $('#seo_description').val(),
            seo_keywords: $('#seo_keywords').val(),
            header_style: $('#header_style').val(),
            seo_noindex: $('input[name="seo_noindex"]:checked').val(),
        };

        // No guardar si no hay cambios
        if (JSON.stringify(currentData) === JSON.stringify(lastSavedData)) {
            return;
        }

        var $indicator = $('#autoSaveIndicator');
        $indicator.html('<i class="fas fa-sync-alt fa-spin me-1"></i>Auto-guardando...').removeClass('bg-success').addClass('bg-info');

        $.ajax({
            url: autoSaveUrl,
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            data: JSON.stringify(currentData),
            success: function(response) {
                if (response.success) {
                    lastSavedData = currentData;
                    $indicator.html('<i class="fas fa-check me-1"></i>Guardado ' + (new Date().toLocaleTimeString())).removeClass('bg-info').addClass('bg-success');

                    // Desaparecer después de 3 segundos
                    setTimeout(function() {
                        $indicator.fadeOut(300, function() { $(this).html('').fadeIn(); });
                    }, 3000);
                }
            },
            error: function(xhr) {
                console.error('Error en auto-save:', xhr);
                $indicator.html('<i class="fas fa-exclamation-triangle me-1"></i>Error al guardar').removeClass('bg-info').addClass('bg-danger');

                // Mostrar el error por más tiempo
                setTimeout(function() {
                    $indicator.fadeOut(300, function() {
                        $(this).html('<i class="fas fa-sync-alt fa-spin me-1"></i>Auto-guardando...').removeClass('bg-danger').addClass('bg-info').fadeIn();
                    });
                }, 5000);
            }
        });
    }

    // Detectar cambios en campos
    $('#title, #slug, #description, #status, #template, #header_style, #seo_title, #seo_description, #seo_keywords').on('input change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(performAutoSave, 2000); // 2 segundos de debounce
    });

    // Detectar cambios en checkboxes de SEO
    $('input[name="seo_noindex"]').on('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(performAutoSave, 2000);
    });

    // Detectar cambios en TinyMCE
    if (tinymce.get(editorId)) {
        tinymce.get(editorId).on('change', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(performAutoSave, 2000);
        });

        tinymce.get(editorId).on('keyup', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(performAutoSave, 2000);
        });
    }

    // Inicializar lastSavedData con datos actuales
    lastSavedData = {
        title: $('#title').val(),
        slug: $('#slug').val(),
        description: $('#description').val(),
        content: tinymce.get(editorId) ? tinymce.get(editorId).getContent() : $('#' + editorId).val(),
        status: $('#status').val(),
        template: $('#template').val(),
        seo_title: $('#seo_title').val(),
        seo_description: $('#seo_description').val(),
        seo_keywords: $('#seo_keywords').val(),
        header_style: $('#header_style').val(),
        seo_noindex: $('input[name="seo_noindex"]:checked').val(),
    };

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

function regenerateSlug(title) {
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
        $('#slug').val(data.slug).data('manual', true);
        updateSlugPreview();
    });
    @else
    $('#slug').val(title.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^\w\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').trim());
    updateSlugPreview();
    @endif
}

function updateSlugPreview() {
    var slug = document.getElementById('slug').value;
    var prefix = '{{ $prefix }}';
    var path = prefix ? prefix + '/' + slug : slug;
    var url = '{{ url('/') }}/' + path;
    document.getElementById('slug-preview').textContent = url;
    document.getElementById('slug-preview-link').href = url;
}
</script>
@endpush
