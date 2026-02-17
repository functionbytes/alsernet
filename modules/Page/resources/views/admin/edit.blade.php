@extends('layouts.theme')

@section('page_title', 'Editar página')

@section('content')

    @include('core::components.card', ['title' => 'Editar página'])

    @include('core::components.alerts')

    <form action="{{ route('pages.update', $page->id) }}" method="POST" enctype="multipart/form-data" id="pageForm">
        @csrf
        @method('PUT')

        <div class="row">

            {{-- Sidebar izquierdo --}}
            <div class="col-lg-4">

                {{-- Información --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Información</h5>
                        <p class="small mb-0 text-muted">Datos del registro</p>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted d-block">Fecha de creación</small>
                            <strong>{{ $page->created_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block">Última modificación</small>
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
                        <div class="mb-0">
                            <small class="text-muted d-block mb-1">Estado</small>
                            @php
                                $badges = [
                                    'published' => 'bg-success-subtle text-success',
                                    'draft'     => 'bg-secondary-subtle text-secondary',
                                    'pending'   => 'bg-warning-subtle text-warning',
                                ];
                                $labels = ['published' => 'Publicado', 'draft' => 'Borrador', 'pending' => 'Pendiente'];
                            @endphp
                            <span class="badge {{ $badges[$page->status] ?? 'bg-secondary-subtle text-secondary' }}">
                                {{ $labels[$page->status] ?? $page->status }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Publicación --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Publicación</h5>
                        <p class="small mb-0 text-muted">Estado, plantilla y fechas programadas</p>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ old('status', $page->status) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="template" class="form-label fw-semibold">Plantilla</label>
                            <select class="form-select @error('template') is-invalid @enderror" id="template" name="template">
                                @foreach($templates as $key => $label)
                                    <option value="{{ $key }}" {{ old('template', $page->template) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="published_at" class="form-label fw-semibold">Fecha de publicación</label>
                            <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror"
                                   id="published_at" name="published_at"
                                   value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}">
                            <small class="form-text text-muted">Dejar vacío para usar la fecha actual al publicar</small>
                            @error('published_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="publish_at" class="form-label fw-semibold">Publicar el (programado)</label>
                            <input type="datetime-local" class="form-control @error('publish_at') is-invalid @enderror"
                                   id="publish_at" name="publish_at"
                                   value="{{ old('publish_at', $page->publish_at?->format('Y-m-d\TH:i')) }}">
                            <small class="form-text text-muted">Programar publicación automática en una fecha futura</small>
                            @error('publish_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="unpublish_at" class="form-label fw-semibold">Despublicar el (programado)</label>
                            <input type="datetime-local" class="form-control @error('unpublish_at') is-invalid @enderror"
                                   id="unpublish_at" name="unpublish_at"
                                   value="{{ old('unpublish_at', $page->unpublish_at?->format('Y-m-d\TH:i')) }}">
                            <small class="form-text text-muted">Programar despublicación automática en una fecha futura</small>
                            @error('unpublish_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($page->willBePublished() || $page->willBeUnpublished())
                            <div class="alert alert-info mt-3 mb-0 py-2">
                                <small>
                                    <i class="fas fa-clock me-1"></i><strong>Estado programado:</strong>
                                    @if($page->willBePublished())
                                        <br>Se publicará el {{ $page->publish_at->format('d/m/Y H:i') }}
                                    @endif
                                    @if($page->willBeUnpublished())
                                        <br>Se despublicará el {{ $page->unpublish_at->format('d/m/Y H:i') }}
                                    @endif
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Imagen destacada --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Imagen destacada</h5>
                        <p class="small mb-0 text-muted">Imagen principal de la página</p>
                    </div>
                    <div class="card-body">
                        <div class="mb-3" id="imagePreviewContainer">
                            @if($page->featured_image)
                                <img src="{{ $page->featured_image }}" alt="Imagen destacada"
                                     class="img-fluid rounded" style="max-height:200px; object-fit:cover; width:100%">
                            @else
                                <div class="text-center py-4 border border-2 border-dashed rounded bg-light">
                                    <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                    <p class="text-muted small mb-0">No hay imagen destacada</p>
                                </div>
                            @endif
                        </div>
                        <div class="mb-0">
                            <label for="featured_image" class="form-label fw-semibold">Subir imagen</label>
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

                {{-- Acciones principales --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" form="pageForm" class="btn btn-primary">
                                Guardar cambios
                            </button>
                            <a href="{{ $page->url }}" class="btn btn-outline-secondary" target="_blank">
                                Ver página
                            </a>
                            @if($page->hasVersions())
                                <a href="{{ route('pages.versions.index', $page->id) }}" class="btn btn-outline-secondary">
                                    Ver versiones ({{ $page->getTotalVersions() }})
                                </a>
                            @endif
                            <a href="{{ route('pages.index') }}" class="btn btn-outline-secondary">
                                Volver al listado
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Acciones adicionales --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Acciones adicionales</h5>
                        <p class="small mb-0 text-muted">Publicación, duplicado y eliminación</p>
                    </div>
                    <div class="card-body d-flex flex-column gap-2">

                        @if($page->isPublished())
                            <form action="{{ route('pages.unpublish', $page->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    Despublicar
                                </button>
                            </form>
                        @else
                            <form action="{{ route('pages.publish', $page->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    Publicar ahora
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('pages.duplicate', $page->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                Duplicar página
                            </button>
                        </form>

                        <button type="button" class="btn btn-outline-secondary w-100" id="generatePreviewBtn">
                            Generar preview
                        </button>

                        <button type="button" class="btn btn-outline-secondary w-100" id="revokePreviewBtn">
                            Revocar previews
                        </button>

                        <hr class="my-2">

                        <button type="button" class="btn btn-outline-secondary w-100" id="deleteBtn">
                            Eliminar página
                        </button>
                        <form action="{{ route('pages.destroy', $page->id) }}" method="POST" id="deleteForm">
                            @csrf
                            @method('DELETE')
                        </form>

                    </div>
                </div>

            </div>

            {{-- Contenido principal --}}
            <div class="col-lg-8">

                {{-- Información principal --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Información principal</h5>
                        <p class="small mb-0 text-muted">Título, URL y contenido de la página</p>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title', $page->title) }}"
                                   required maxlength="255">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @php
                            $prefix = setting('permalink-modules-page-models-page', '');
                        @endphp
                        <div class="mb-3">
                            <label for="slug" class="form-label fw-semibold">Slug (Permalink) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text text-muted">
                                    {{ $prefix ? url($prefix) . '/' : url('/') . '/' }}
                                </span>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                       id="slug" name="slug"
                                       value="{{ old('slug', $page->slug) }}"
                                       placeholder="slug-de-la-pagina" required>
                                <button type="button" class="btn btn-outline-secondary"
                                        title="Regenerar slug único"
                                        onclick="regenerateSlug(document.getElementById('title').value)">
                                    Regenerar
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                URL pública: <a href="#" id="slug-preview-link" target="_blank">
                                    <span id="slug-preview"></span>
                                </a>
                            </small>
                            @error('slug')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Descripción breve</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      maxlength="500">{{ old('description', $page->description) }}</textarea>
                            <small class="form-text text-muted" id="description-counter">0 / 500 caracteres</small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="content" class="form-label fw-semibold">Contenido</label>
                            <textarea class="form-control @error('content') is-invalid @enderror"
                                      id="content" name="content" rows="15">{{ old('content', $page->content) }}</textarea>
                            <small class="form-text text-muted">Puedes usar HTML para formatear el contenido.</small>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- SEO y metadatos --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">SEO y metadatos</h5>
                        <p class="small mb-0 text-muted">Optimización para buscadores</p>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="seo_title" class="form-label fw-semibold">Título SEO</label>
                            <input type="text" class="form-control @error('seo_title') is-invalid @enderror"
                                   id="seo_title" name="seo_title"
                                   value="{{ old('seo_title', $page->seo_title) }}" maxlength="255">
                            <small class="form-text text-muted" id="seo_title-counter">0 / 255 caracteres · Recomendado: 50-60</small>
                            @error('seo_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="seo_description" class="form-label fw-semibold">Descripción SEO</label>
                            <textarea class="form-control @error('seo_description') is-invalid @enderror"
                                      id="seo_description" name="seo_description" rows="3"
                                      maxlength="500">{{ old('seo_description', $page->seo_description) }}</textarea>
                            <small class="form-text text-muted" id="seo_description-counter">0 / 500 caracteres · Recomendado: 150-160</small>
                            @error('seo_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="seo_keywords" class="form-label fw-semibold">Palabras clave SEO</label>
                            <input type="text" class="form-control @error('seo_keywords') is-invalid @enderror"
                                   id="seo_keywords" name="seo_keywords"
                                   value="{{ old('seo_keywords', $page->seo_keywords) }}" maxlength="500">
                            <small class="form-text text-muted">Separadas por comas</small>
                            @error('seo_keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Vista previa --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Vista previa</h5>
                        <p class="small mb-0 text-muted">Enlace temporal para compartir antes de publicar</p>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Genera un enlace de preview para compartir esta página antes de publicarla.
                            El enlace expirará en 24 horas y puede ser revocado en cualquier momento.
                        </p>

                        <div id="previewUrlSection" style="display: none;" class="mb-3">
                            <div class="alert alert-success py-2 mb-0">
                                <strong class="d-block mb-2 small">Enlace de preview generado:</strong>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="previewUrl" readonly>
                                    <button class="btn btn-outline-secondary" type="button" id="copyPreviewBtn">
                                        Copiar
                                    </button>
                                    <a href="#" class="btn btn-outline-primary" id="openPreviewBtn" target="_blank">
                                        Abrir
                                    </a>
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

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Auto-generate slug from title (only if not manually edited)
    $('#title').on('input', function () {
        const $slug = $('#slug');
        if (!$slug.data('manual')) {
            $slug.val(generateSlugFromTitle($(this).val()));
            updateSlugPreview();
        }
    });

    $('#slug').on('input', function () {
        $(this).data('manual', true);
        updateSlugPreview();
    });

    // Character counters
    function setupCounter(id, max, recMin, recMax) {
        const $field = $('#' + id);
        const $counter = $('#' + id + '-counter');
        $field.on('input', function () {
            const len = $(this).val().length;
            let text = len + ' / ' + max + ' caracteres';
            let cls = 'text-muted';
            if (recMin && recMax) {
                if (len >= recMin && len <= recMax) { cls = 'text-success'; text += ' ✓ óptimo'; }
                else if (len > 0) { cls = 'text-warning'; text += ' · Recomendado: ' + recMin + '-' + recMax; }
                else { text += ' · Recomendado: ' + recMin + '-' + recMax; }
            }
            $counter.attr('class', 'form-text ' + cls).text(text);
        }).trigger('input');
    }

    setupCounter('description', 500);
    setupCounter('seo_title', 255, 50, 60);
    setupCounter('seo_description', 500, 150, 160);

    // Image preview on file select
    $('#featured_image').on('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            $('#imagePreviewContainer').html(
                '<img src="' + e.target.result + '" class="img-fluid rounded mb-3" style="max-height:200px; object-fit:cover; width:100%">'
            );
        };
        reader.readAsDataURL(file);
    });

    // Generate Preview Token
    $('#generatePreviewBtn').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Generando...');

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
                    toastr.success('Preview generado exitosamente');
                } else {
                    toastr.error('Error al generar preview');
                }
            },
            error: function () { toastr.error('Error al generar preview'); },
            complete: function () {
                $btn.prop('disabled', false).text('Generar preview');
            }
        });
    });

    // Copy preview URL
    $('#copyPreviewBtn').on('click', function () {
        const $btn = $(this);
        const url = $('#previewUrl').val();
        navigator.clipboard.writeText(url).then(function () {
            const orig = $btn.html();
            $btn.text('Copiado').removeClass('btn-outline-secondary').addClass('btn-success');
            setTimeout(() => $btn.text('Copiar').removeClass('btn-success').addClass('btn-outline-secondary'), 2000);
            toastr.success('URL copiada al portapapeles');
        });
    });

    // Revoke previews
    $('#revokePreviewBtn').on('click', function () {
        if (!confirm('¿Revocar todos los enlaces de preview activos?')) return;

        const $btn = $(this);
        $btn.prop('disabled', true).text('Revocando...');

        $.ajax({
            url: '{{ route("pages.preview.revoke", $page->id) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                if (data.success) {
                    $('#previewUrlSection').fadeOut();
                    toastr.success(data.message || 'Previews revocados');
                } else {
                    toastr.error('Error al revocar previews');
                }
            },
            error: function () { toastr.error('Error al revocar previews'); },
            complete: function () { $btn.prop('disabled', false).text('Revocar previews'); }
        });
    });

    // Delete
    $('#deleteBtn').on('click', function () {
        if (confirm('¿Estás seguro de eliminar esta página?\n\nEsta acción no se puede deshacer.')) {
            $('#deleteForm').submit();
        }
    });

    // Load existing preview token
    $.ajax({
        url: '{{ route("pages.preview.index", $page->id) }}',
        method: 'GET',
        success: function (data) {
            if (data.success && data.data.length > 0) {
                const active = data.data.find(t => t.is_active);
                if (active) {
                    $('#previewUrl').val(active.url);
                    $('#openPreviewBtn').attr('href', active.url);
                    $('#previewExpires').text(active.expires_in_human);
                    $('#previewViews').text(active.viewed_count);
                    $('#previewUrlSection').show();
                }
            }
        }
    });

    updateSlugPreview();

    @if(session('success'))
    toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
    toastr.error('{{ session('error') }}', 'Error');
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

function updateSlugPreview() {
    const slug = document.getElementById('slug').value;
    const prefix = '{{ $prefix }}';
    const path = prefix ? prefix + '/' + slug : slug;
    const url = '{{ url('/') }}/' + path;
    document.getElementById('slug-preview').textContent = url;
    document.getElementById('slug-preview-link').href = url;
}

function regenerateSlug(title) {
    if (!title) return;

    @php
        $slugAjaxUrl = \Illuminate\Support\Facades\Route::has('pages.ajax.slug')
            ? route('pages.ajax.slug')
            : null;
    @endphp

    @if(!$slugAjaxUrl)
        document.getElementById('slug').value = generateSlugFromTitle(title);
        $('#slug').data('manual', true);
        updateSlugPreview();
    @else
        fetch('{{ $slugAjaxUrl }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ title: title, ignoreId: {{ $page->id }} }),
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('slug').value = data.slug;
            $('#slug').data('manual', true);
            updateSlugPreview();
        });
    @endif
}
</script>
@endpush
