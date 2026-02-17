<div class="row">
    <!-- Main Content -->
    <div class="col-lg-12">
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="fw-bold mb-0">Información principal</h6>
            </div>
            <div class="card-body">
                <!-- Title -->
                <div class="mb-3">
                    <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                           id="title" name="title" value="{{ old('title', $page->title ?? '') }}"
                           required maxlength="255">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Permalink -->
                @php
                    $prefix = setting('permalink-modules-page-models-page', '');
                @endphp
                <div class="mb-3">
                    <label for="slug" class="form-label">Slug (Permalink)</label>
                    <div class="input-group">
                        <span class="input-group-text text-muted" id="slug-prefix">
                            {{ $prefix ? url($prefix) . '/' : url('/') . '/' }}
                        </span>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror"
                               id="slug" name="slug"
                               value="{{ old('slug', $page->slug ?? '') }}"
                               placeholder="se-generara-automaticamente" required>
                        <button type="button" class="btn btn-outline-secondary"
                                id="btn-regenerate-slug"
                                title="Regenerar slug único"
                                onclick="regenerateSlug(document.getElementById('title').value)">
                            <i class="fas fa-wand-magic-sparkles"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">
                        URL pública: <a href="#" id="slug-preview-link" target="_blank">
                            <span id="slug-preview">{{ isset($page->id) ? $page->url : '' }}</span>
                        </a>
                    </small>
                    @error('slug')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción Breve</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description" name="description" rows="3"
                              maxlength="500">{{ old('description', $page->description ?? '') }}</textarea>
                    <small class="form-text text-muted">Máximo 500 caracteres</small>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Content -->
                <div class="mb-3">
                    <label for="content" class="form-label">Contenido</label>
                    <textarea class="form-control @error('content') is-invalid @enderror"
                              id="content" name="content" rows="15">{{ old('content', $page->content ?? '') }}</textarea>
                    <small class="form-text text-muted">
                        Puedes usar HTML para formatear el contenido.
                    </small>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- SEO Section -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="fw-bold mb-0">SEO y metadatos</h6>
            </div>
            <div class="card-body">
                <!-- SEO Title -->
                <div class="mb-3">
                    <label for="seo_title" class="form-label">Título SEO</label>
                    <input type="text" class="form-control @error('seo_title') is-invalid @enderror"
                           id="seo_title" name="seo_title"
                           value="{{ old('seo_title', $page->seo_title ?? '') }}"
                           maxlength="255">
                    <small class="form-text text-muted">Recomendado: 50-60 caracteres</small>
                    @error('seo_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- SEO Description -->
                <div class="mb-3">
                    <label for="seo_description" class="form-label">Descripción SEO</label>
                    <textarea class="form-control @error('seo_description') is-invalid @enderror"
                              id="seo_description" name="seo_description" rows="3"
                              maxlength="500">{{ old('seo_description', $page->seo_description ?? '') }}</textarea>
                    <small class="form-text text-muted">Recomendado: 150-160 caracteres</small>
                    @error('seo_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- SEO Keywords -->
                <div class="mb-3">
                    <label for="seo_keywords" class="form-label">Palabras Clave SEO</label>
                    <input type="text" class="form-control @error('seo_keywords') is-invalid @enderror"
                           id="seo_keywords" name="seo_keywords"
                           value="{{ old('seo_keywords', $page->seo_keywords ?? '') }}"
                           maxlength="500">
                    <small class="form-text text-muted">Separadas por comas</small>
                    @error('seo_keywords')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4 d-none">
        <!-- Status & Publishing -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="fw-bold mb-0">Publicación</h6>
            </div>
            <div class="card-body">
                <!-- Status -->
                <div class="mb-3">
                    <label for="status" class="form-label">Estado <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror"
                            id="status" name="status" required>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}"
                                {{ old('status', $page->status ?? 'draft') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Published At -->
                <div class="mb-3">
                    <label for="published_at" class="form-label">Fecha de Publicación</label>
                    <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror"
                           id="published_at" name="published_at"
                           value="{{ old('published_at', $page->published_at ? $page->published_at->format('Y-m-d\TH:i') : '') }}">
                    <small class="form-text text-muted">Dejar vacío para usar la fecha actual al publicar</small>
                    @error('published_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Publish At (Scheduled Publishing) -->
                <div class="mb-3">
                    <label for="publish_at" class="form-label">Publicar el (Programado)</label>
                    <input type="datetime-local" class="form-control @error('publish_at') is-invalid @enderror"
                           id="publish_at" name="publish_at"
                           value="{{ old('publish_at', $page->publish_at ? $page->publish_at->format('Y-m-d\TH:i') : '') }}">
                    <small class="form-text text-muted">
                        Programar la publicación automática de esta página en una fecha futura
                    </small>
                    @error('publish_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Unpublish At (Scheduled Unpublishing) -->
                <div class="mb-3">
                    <label for="unpublish_at" class="form-label">Despublicar el (Programado)</label>
                    <input type="datetime-local" class="form-control @error('unpublish_at') is-invalid @enderror"
                           id="unpublish_at" name="unpublish_at"
                           value="{{ old('unpublish_at', $page->unpublish_at ? $page->unpublish_at->format('Y-m-d\TH:i') : '') }}">
                    <small class="form-text text-muted">
                        Programar la despublicación automática de esta página en una fecha futura
                    </small>
                    @error('unpublish_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if(isset($page) && ($page->willBePublished() || $page->willBeUnpublished()))
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-clock"></i> <strong>Estado Programado:</strong>
                        @if($page->willBePublished())
                            <br>Esta página se publicará automáticamente el {{ $page->publish_at->format('d/m/Y H:i') }}
                        @endif
                        @if($page->willBeUnpublished())
                            <br>Esta página se despublicará automáticamente el {{ $page->unpublish_at->format('d/m/Y H:i') }}
                        @endif
                    </div>
                @endif

                <!-- Template -->
                <div class="mb-3">
                    <label for="template" class="form-label">Plantilla</label>
                    <select class="form-select @error('template') is-invalid @enderror"
                            id="template" name="template">
                        @foreach($templates as $key => $label)
                            <option value="{{ $key }}"
                                {{ old('template', $page->template ?? 'default') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('template')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Featured Image -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="fw-bold mb-0">Imagen destacada</h6>
            </div>
            <div class="card-body">
                @if(isset($page) && $page->featured_image)
                    <div class="mb-3">
                        <img src="{{ $page->featured_image }}" alt="Featured Image"
                             class="img-fluid rounded">
                    </div>
                @endif

                <div class="mb-3">
                    <label for="featured_image" class="form-label">Subir Imagen</label>
                    <input type="file" class="form-control @error('featured_image') is-invalid @enderror"
                           id="featured_image" name="featured_image"
                           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                    <small class="form-text text-muted">Máximo 2MB. Formatos: JPG, PNG, GIF, WebP</small>
                    @error('featured_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="card border-0 shadow-sm d-none">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg" style="background-color: #90bb13; border-color: #90bb13;">
                        <i class="fas fa-save"></i>
                        {{ isset($page->id) ? 'Actualizar página' : 'Crear página' }}
                    </button>
                    <a href="{{ route('pages.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Nueva sección: Estado y publicación en el contenido principal -->
    <div class="col-lg-12">
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="fw-bold mb-0">Publicación y multimedia</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Estado y fechas de publicación -->
                    <div class="col-md-6">
                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Estado <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror"
                                    id="status" name="status" required>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('status', $page->status ?? 'draft') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Published At -->
                        <div class="mb-3">
                            <label for="published_at" class="form-label">Fecha de publicación</label>
                            <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror"
                                   id="published_at" name="published_at"
                                   value="{{ old('published_at', $page->published_at ?? null ? $page->published_at->format('Y-m-d\TH:i') : '') }}">
                            <small class="form-text text-muted">Dejar vacío para usar la fecha actual al publicar</small>
                            @error('published_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Publish At (Scheduled Publishing) -->
                        <div class="mb-3">
                            <label for="publish_at" class="form-label">Publicar el (programado)</label>
                            <input type="datetime-local" class="form-control @error('publish_at') is-invalid @enderror"
                                   id="publish_at" name="publish_at"
                                   value="{{ old('publish_at', $page->publish_at ?? null ? $page->publish_at->format('Y-m-d\TH:i') : '') }}">
                            <small class="form-text text-muted">
                                Programar la publicación automática de esta página en una fecha futura
                            </small>
                            @error('publish_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Unpublish At (Scheduled Unpublishing) -->
                        <div class="mb-3">
                            <label for="unpublish_at" class="form-label">Despublicar el (programado)</label>
                            <input type="datetime-local" class="form-control @error('unpublish_at') is-invalid @enderror"
                                   id="unpublish_at" name="unpublish_at"
                                   value="{{ old('unpublish_at', $page->unpublish_at ?? null ? $page->unpublish_at->format('Y-m-d\TH:i') : '') }}">
                            <small class="form-text text-muted">
                                Programar la despublicación automática de esta página en una fecha futura
                            </small>
                            @error('unpublish_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(isset($page) && ($page->willBePublished() || $page->willBeUnpublished()))
                            <div class="alert alert-info">
                                <i class="fas fa-clock"></i> <strong>Estado programado:</strong>
                                @if($page->willBePublished())
                                    <br>Esta página se publicará automáticamente el {{ $page->publish_at->format('d/m/Y H:i') }}
                                @endif
                                @if($page->willBeUnpublished())
                                    <br>Esta página se despublicará automáticamente el {{ $page->unpublish_at->format('d/m/Y H:i') }}
                                @endif
                            </div>
                        @endif

                        <!-- Template -->
                        <div class="mb-3">
                            <label for="template" class="form-label">Plantilla</label>
                            <select class="form-select @error('template') is-invalid @enderror"
                                    id="template" name="template">
                                @foreach($templates as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('template', $page->template ?? 'default') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Imagen destacada -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Imagen destacada</label>

                            @if(isset($page) && $page->featured_image)
                                <div class="mb-3 position-relative">
                                    <img src="{{ $page->featured_image }}" alt="Featured Image"
                                         class="img-fluid rounded border" style="max-height: 300px; object-fit: cover; width: 100%;">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Imagen actual
                                        </span>
                                    </div>
                                </div>
                            @else
                                <div class="mb-3 text-center py-5 border border-dashed rounded bg-light">
                                    <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No hay imagen destacada</p>
                                </div>
                            @endif

                            <label for="featured_image" class="form-label">Subir imagen</label>
                            <input type="file" class="form-control @error('featured_image') is-invalid @enderror"
                                   id="featured_image" name="featured_image"
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            <small class="form-text text-muted">Máximo 2MB. Formatos: JPG, PNG, GIF, WebP</small>
                            @error('featured_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-generate slug from title
    document.getElementById('title').addEventListener('input', function (e) {
        const slug = document.getElementById('slug');
        if (!slug.dataset.manual) {
            slug.value = generateSlugFromTitle(e.target.value);
            updateSlugPreview();
        }
    });

    // Mark slug as manually edited
    document.getElementById('slug').addEventListener('input', function () {
        this.dataset.manual = 'true';
        updateSlugPreview();
    });

    function generateSlugFromTitle(title) {
        return title
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }

    function updateSlugPreview() {
        const slug = document.getElementById('slug').value;
        const prefix = '{{ $prefix }}';
        const previewLink = document.getElementById('slug-preview-link');
        const previewText = document.getElementById('slug-preview');
        const path = prefix ? `${prefix}/${slug}` : slug;
        const url = `{{ url('/') }}/${path}`;
        previewText.textContent = url;
        previewLink.href = url;
    }

    function regenerateSlug(title) {
        if (!title) return;

        @php
            $slugAjaxUrl = \Illuminate\Support\Facades\Route::has('pages.ajax.slug')
                ? route('pages.ajax.slug')
                : null;
        @endphp
        @if(!$slugAjaxUrl)
        // Route pages.ajax.slug not defined — auto-generate without server validation
        document.getElementById('slug').value = generateSlugFromTitle(title);
        document.getElementById('slug').dataset.manual = 'true';
        updateSlugPreview();
        @else
        fetch('{{ $slugAjaxUrl }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                title: title,
                ignoreId: {{ $page->id ?? 0 }},
            }),
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('slug').value = data.slug;
            document.getElementById('slug').dataset.manual = 'true';
            updateSlugPreview();
        })
        .catch(err => console.error('Error generando slug:', err));
        @endif
    }

    // Inicializar preview al cargar
    updateSlugPreview();
</script>
@endpush
