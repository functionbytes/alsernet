@extends('layouts.theme')

@section('page_title', 'Crear página')

@section('content')

    @include('core::components.card', ['title' => 'Crear página'])

    @include('core::components.alerts')

    <form action="{{ route('pages.store') }}" method="POST" enctype="multipart/form-data" id="pageForm">
        @csrf

        <div class="row">

            {{-- Sidebar izquierdo --}}
            <div class="col-lg-4">

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
                                    <option value="{{ $key }}" {{ old('status', 'draft') === $key ? 'selected' : '' }}>
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
                                    <option value="{{ $key }}" {{ old('template', 'default') === $key ? 'selected' : '' }}>
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
                                   id="published_at" name="published_at" value="{{ old('published_at') }}">
                            <small class="form-text text-muted">Dejar vacío para usar la fecha actual al publicar</small>
                            @error('published_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="publish_at" class="form-label fw-semibold">Publicar el (programado)</label>
                            <input type="datetime-local" class="form-control @error('publish_at') is-invalid @enderror"
                                   id="publish_at" name="publish_at" value="{{ old('publish_at') }}">
                            <small class="form-text text-muted">Programar publicación automática en una fecha futura</small>
                            @error('publish_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="unpublish_at" class="form-label fw-semibold">Despublicar el (programado)</label>
                            <input type="datetime-local" class="form-control @error('unpublish_at') is-invalid @enderror"
                                   id="unpublish_at" name="unpublish_at" value="{{ old('unpublish_at') }}">
                            <small class="form-text text-muted">Programar despublicación automática en una fecha futura</small>
                            @error('unpublish_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Imagen destacada --}}
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <h5 class="mb-1 fw-bold">Imagen destacada</h5>
                        <p class="small mb-0 text-muted">Imagen principal de la página</p>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 text-center py-4 border border-2 border-dashed rounded bg-light" id="imagePreviewContainer">
                            <i class="fas fa-image fa-3x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">No hay imagen seleccionada</p>
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

                {{-- Acciones --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" form="pageForm" class="btn btn-primary">
                                Guardar página
                            </button>
                            <a href="{{ route('pages.index') }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
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
                                   id="title" name="title" value="{{ old('title') }}"
                                   required maxlength="255" autofocus>
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
                                <span class="input-group-text text-muted" id="slug-prefix">
                                    {{ $prefix ? url($prefix) . '/' : url('/') . '/' }}
                                </span>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                       id="slug" name="slug"
                                       value="{{ old('slug') }}"
                                       placeholder="se-generara-automaticamente" required>
                                <button type="button" class="btn btn-outline-secondary"
                                        id="btn-regenerate-slug"
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
                                      maxlength="500">{{ old('description') }}</textarea>
                            <small class="form-text text-muted" id="description-counter">0 / 500 caracteres</small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="content" class="form-label fw-semibold">Contenido</label>
                            <textarea class="form-control @error('content') is-invalid @enderror"
                                      id="content" name="content" rows="15">{{ old('content') }}</textarea>
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
                                   id="seo_title" name="seo_title" value="{{ old('seo_title') }}" maxlength="255">
                            <small class="form-text text-muted" id="seo_title-counter">0 / 255 caracteres · Recomendado: 50-60</small>
                            @error('seo_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="seo_description" class="form-label fw-semibold">Descripción SEO</label>
                            <textarea class="form-control @error('seo_description') is-invalid @enderror"
                                      id="seo_description" name="seo_description" rows="3"
                                      maxlength="500">{{ old('seo_description') }}</textarea>
                            <small class="form-text text-muted" id="seo_description-counter">0 / 500 caracteres · Recomendado: 150-160</small>
                            @error('seo_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label for="seo_keywords" class="form-label fw-semibold">Palabras clave SEO</label>
                            <input type="text" class="form-control @error('seo_keywords') is-invalid @enderror"
                                   id="seo_keywords" name="seo_keywords" value="{{ old('seo_keywords') }}" maxlength="500">
                            <small class="form-text text-muted">Separadas por comas</small>
                            @error('seo_keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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

    // Auto-generate slug from title
    $('#title').on('input', function () {
        const slug = $('#slug');
        if (!slug.data('manual')) {
            slug.val(generateSlugFromTitle($(this).val()));
            updateSlugPreview();
        }
    });

    // Mark slug as manually edited
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

    // Image preview
    $('#featured_image').on('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            $('#imagePreviewContainer').html(
                '<img src="' + e.target.result + '" class="img-fluid rounded" style="max-height:200px; object-fit:cover; width:100%">'
            );
        };
        reader.readAsDataURL(file);
    });

    // Initialize slug preview
    updateSlugPreview();

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
            body: JSON.stringify({ title: title, ignoreId: 0 }),
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
