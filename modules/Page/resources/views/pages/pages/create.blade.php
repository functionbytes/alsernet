@extends('layouts.theme')

@section('page_title', 'Crear página')

@section('content')

    @include('core::components.card', ['title' => 'Crear página'])

    @include('core::components.alerts')

    <form action="{{ route('pages.store') }}" method="POST" enctype="multipart/form-data" id="pageForm">
        @csrf

        <div class="row">

            {{-- COLUMNA PRINCIPAL --}}
            <div class="col-lg-8 order-lg-2">

                {{-- Información principal --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Información principal</h5>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title') }}"
                                   required maxlength="255" autofocus>
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @php $prefix = setting('permalink-modules-page-models-page', ''); @endphp
                        <div class="mb-3">
                            <label for="slug" class="form-label fw-semibold">Enlace permanente <span
                                        class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text text-muted small">
                                    {{ $prefix ? url($prefix) . 'create.blade.php/' : url('/') . '/' }}
                                </span>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                       id="slug" name="slug"
                                       value="{{ old('slug') }}"
                                       placeholder="se-generara-automaticamente" required>
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
                            @error('slug')
                            <div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label for="description" class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      maxlength="500"
                                      placeholder="Descripción corta">{{ old('description') }}</textarea>
                            <small class="form-text text-muted" id="description-counter">0 / 500 caracteres</small>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <textarea id="content" name="content"
                                      class="@error('content') is-invalid @enderror">{{ old('content') }}</textarea>
                        </div>
                        @error('content')
                        <div class="invalid-feedback d-block">{{ $message }}</div>@enderror

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
                                    Título de la página
                                </div>
                                <div style="color:#006621; font-size:13px;">
                                    {{ url('/') }}/...
                                </div>
                                <div style="color:#545454; font-size:13px; margin-top:2px;">
                                    <span id="seoPreviewDesc">La descripción de la página aparecerá aquí.</span>
                                </div>
                            </div>
                        </div>

                        {{-- Campos SEO editables (ocultos por defecto) --}}
                        <div id="seoEditSection" style="display:none;">
                            <hr class="my-3">

                            <div class="mb-3">
                                <label for="seo_title" class="form-label fw-semibold">Título SEO</label>
                                <input type="text" class="form-control @error('seo_title') is-invalid @enderror"
                                       id="seo_title" name="seo_title"
                                       value="{{ old('seo_title') }}" maxlength="70">
                                <small class="form-text text-muted" id="seo_title-counter">0 / 70 · Recomendado:
                                    50-60</small>
                                @error('seo_title')
                                <div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="seo_description" class="form-label fw-semibold">Descripción SEO</label>
                                <textarea class="form-control @error('seo_description') is-invalid @enderror"
                                          id="seo_description" name="seo_description" rows="3"
                                          maxlength="160">{{ old('seo_description') }}</textarea>
                                <small class="form-text text-muted" id="seo_description-counter">0 / 160 · Recomendado:
                                    150-160</small>
                                @error('seo_description')
                                <div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="seo_keywords" class="form-label fw-semibold">Palabras clave</label>
                                <input type="text" class="form-control @error('seo_keywords') is-invalid @enderror"
                                       id="seo_keywords" name="seo_keywords"
                                       value="{{ old('seo_keywords') }}" maxlength="500"
                                       placeholder="palabra1, palabra2, ...">
                                <small class="form-text text-warning"><i class="fas fa-info-circle me-1"></i>Google ya
                                    no usa meta keywords, pero pueden ser útiles para búsqueda interna.</small>
                                @error('seo_keywords')
                                <div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Imagen SEO (Open Graph)</label>
                                <div id="seoImagePreviewContainer" class="mb-2" style="cursor:pointer">
                                    @if(old('seo_image_url'))
                                        <img src="{{ old('seo_image_url') }}"
                                             class="img-fluid rounded" style="max-height:120px; object-fit:cover; width:100%">
                                    @else
                                        <div class="text-center py-3 border border-dashed rounded bg-light">
                                            <i class="fas fa-image fa-2x text-muted mb-1"></i>
                                            <p class="text-muted small mb-0">Haz clic para elegir</p>
                                        </div>
                                    @endif
                                </div>
                                <input type="hidden" id="seo_image_url" name="seo_image_url"
                                       value="{{ old('seo_image_url') }}">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1"
                                            id="seoPickerBtn">
                                        <i class="fas fa-images me-1"></i> Elegir imagen
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="seoImageClearBtn"
                                            {{ old('seo_image_url') ? '' : 'style="display:none"' }}>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">Recomendado: 1200×630px. Máx. 2MB.</small>
                                @error('seo_image_url')
                                <div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-semibold">Índice</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="seo_noindex" id="seo_index"
                                               value="0" {{ old('seo_noindex', '0') == '0' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="seo_index">Índice</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="seo_noindex"
                                               id="seo_noindex_radio"
                                               value="1" {{ old('seo_noindex') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="seo_noindex_radio">Sin índice</label>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            {{-- SIDEBAR --}}
            <div class="col-lg-4 order-lg-1">

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
                                <option value="header-style-1" {{ old('header_style', 'header-style-1') === 'header-style-1' ? 'selected' : '' }}>
                                    Por defecto
                                </option>
                                <option value="header-style-2" {{ old('header_style') === 'header-style-2' ? 'selected' : '' }}>
                                    Estilo de encabezado 2
                                </option>
                                <option value="header-style-3" {{ old('header_style') === 'header-style-3' ? 'selected' : '' }}>
                                    Estilo de encabezado 3
                                </option>
                                <option value="header-style-4" {{ old('header_style') === 'header-style-4' ? 'selected' : '' }}>
                                    Estilo de encabezado 4
                                </option>
                            </select>
                            @error('header_style')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Estatus <span
                                        class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status"
                                    required>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ old('status', 'draft') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label for="template" class="form-label fw-semibold">Plantilla</label>
                            <select class="form-select @error('template') is-invalid @enderror" id="template"
                                    name="template">
                                @foreach($templates as $key => $label)
                                    <option value="{{ $key }}" {{ old('template', 'default') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('template')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <label for="published_at" class="form-label fw-semibold small">Fecha de publicación</label>
                            <input type="datetime-local"
                                   class="form-control form-control-sm @error('published_at') is-invalid @enderror"
                                   id="published_at" name="published_at" value="{{ old('published_at') }}">
                            <small class="form-text text-muted">Dejar vacío para usar la fecha actual</small>
                            @error('published_at')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="publish_at" class="form-label fw-semibold small">Publicar el (programado)</label>
                            <input type="datetime-local"
                                   class="form-control form-control-sm @error('publish_at') is-invalid @enderror"
                                   id="publish_at" name="publish_at" value="{{ old('publish_at') }}">
                            @error('publish_at')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label for="unpublish_at" class="form-label fw-semibold small">Despublicar el</label>
                            <input type="datetime-local"
                                   class="form-control form-control-sm @error('unpublish_at') is-invalid @enderror"
                                   id="unpublish_at" name="unpublish_at" value="{{ old('unpublish_at') }}">
                            @error('unpublish_at')
                            <div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>

                {{-- Imagen destacada --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">Imagen</h5>
                    </div>
                    <div class="card-body">
                        <div id="imagePreviewContainer" class="mb-3">
                            <div class="text-center py-3 border border-2 border-dashed rounded bg-light">
                                <i class="fas fa-image fa-2x text-muted mb-1"></i>
                                <p class="text-muted small mb-0">Sin imagen</p>
                            </div>
                        </div>
                        <input type="file"
                               class="form-control form-control-sm @error('featured_image') is-invalid @enderror"
                               id="featured_image" name="featured_image"
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                        <small class="form-text text-muted">Máx. 2MB. JPG, PNG, GIF, WebP</small>
                        @error('featured_image')
                        <div class="invalid-feedback">{{ $message }}</div>@enderror
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

                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="uiBlocksSearch" placeholder="Buscar...">
                            <button class="btn btn-outline-secondary" type="button" id="uiBlocksClearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row" id="uiBlocksGrid">
                        @php $uiBlocks = \Modules\Template\Models\Shortcode::active()->get(); @endphp

                        @forelse($uiBlocks as $block)
                            <div class="col-xl-3 col-lg-4 col-sm-6 mb-3 ui-block-item"
                                 data-name="{{ strtolower($block->name) }}">
                                <div class="card h-100 ui-block-card border" style="cursor:pointer;"
                                     data-block-key="{{ $block->key }}"
                                     data-block-name="{{ $block->name }}">
                                    <div class="card-body text-center py-3">
                                        <div class="mb-2" style="font-size:2rem; color:#adb5bd;">
                                            <i class="{{ $block->icon }}"></i>
                                        </div>
                                        <h6 class="card-title mb-1 fw-semibold">{{ $block->name }}</h6>
                                        <p class="card-text small text-muted mb-2">{{ $block->description }}</p>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-use-block"
                                                data-block-key="{{ $block->key }}"
                                                data-block-name="{{ $block->name }}">
                                            Usar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-4 text-muted">
                                <i class="fas fa-puzzle-piece fa-2x mb-2 d-block"></i>
                                No hay bloques definidos.
                                <a href="{{ route('settings.shortcodes.create') }}">Crear uno</a>
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

            // =========================================================
            // TINYMCE
            // =========================================================
            var editorActive = true;
            var editorId = 'content';

            var mediaUploadUrl = '{{ route('media.upload') }}';
            var mediaBaseUrl = '{{ url('media') }}';
            var mediaListUrl = '{{ url('/media/list') }}';
            var mediaPickerCallback = null;

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
                    error: function () {
                        onError('Error al subir la imagen');
                    }
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

            initTinyMCE();

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
            var uiBlocksData = @json($uiBlocks->toArray());
            var selectedBlockKey = null;
            var selectedBlockName = null;

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

            $(document).on('click', '.ui-block-card', function () {
                $('.ui-block-card').removeClass('border-primary');
                $(this).addClass('border-primary');
                selectedBlockKey = $(this).data('block-key');
                selectedBlockName = $(this).data('block-name');
                $('#useSelectedBlock').prop('disabled', false);
            });

            $(document).on('dblclick', '.ui-block-card', function () {
                selectedBlockKey = $(this).data('block-key');
                selectedBlockName = $(this).data('block-name');
                openBlockConfig(selectedBlockKey, selectedBlockName);
            });

            $(document).on('click', '.btn-use-block', function (e) {
                e.stopPropagation();
                selectedBlockKey = $(this).data('block-key');
                selectedBlockName = $(this).data('block-name');
                openBlockConfig(selectedBlockKey, selectedBlockName);
            });

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
                if (!block || !block.shortcode_template) {
                    return '[' + key + '][/' + key + ']';
                }
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
                if (!$('#seo_title').val()) {
                    $('#seoPreviewTitle').text(val || 'Título de la página');
                }
                if (!$('#slug').data('manual')) {
                    $('#slug').val(generateSlugFromTitle(val));
                    updateSlugPreview();
                }
            });
            $('#seo_title').on('input', function () {
                var val = $(this).val();
                $('#seoPreviewTitle').text(val || $('#title').val() || 'Título de la página');
            });
            $('#seo_description').on('input', function () {
                var val = $(this).val();
                $('#seoPreviewDesc').text(val || 'La descripción de la página aparecerá aquí.');
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
                        if (len >= recMin && len <= recMax) {
                            cls = 'text-success';
                            text += ' ✓ óptimo';
                        } else if (len > 0) {
                            cls = 'text-warning';
                            text += ' · Recomendado: ' + recMin + '-' + recMax;
                        } else {
                            text += ' · Recomendado: ' + recMin + '-' + recMax;
                        }
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

            // =========================================================
            // MEDIA PICKER - compartido por Agregar + Imagen SEO
            // =========================================================
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

            // Toastr flash
            @if(session('success'))
            toastr.success(@json(session('success')), 'Éxito');
            @endif
            @if(session('error'))
            toastr.error(@json(session('error')), 'Error');
            @endif

        });

        function generateSlugFromTitle(title) {
            return title.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
        }

        function regenerateSlug(title) {
            if (!title) return;
            @php
                $slugAjaxUrl = \Illuminate\Support\Facades\Route::has('pages.ajax.slug') ? route('pages.ajax.slug') : null;
            @endphp
            @if($slugAjaxUrl)
            $.post('{{ $slugAjaxUrl }}', {
                title: title,
                ignoreId: 0,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function (data) {
                $('#slug').val(data.slug).data('manual', true);
                updateSlugPreview();
            });
            @else
            $('#slug').val(generateSlugFromTitle(title)).data('manual', true);
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
