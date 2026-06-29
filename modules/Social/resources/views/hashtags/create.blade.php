@extends('layouts.theme')

@section('title', 'Nuevo Grupo de Hashtags')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.hashtags.index') }}">Grupos de Hashtags</a></li>
                <li class="breadcrumb-item active">Nuevo Grupo</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-hashtag me-2"></i>Nuevo Grupo de Hashtags
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.social.hashtags.store') }}" method="POST">
                            @csrf

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre del Grupo <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: Marketing Digital"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category" class="form-label fw-semibold">
                                    Categoría
                                </label>
                                <input type="text"
                                       class="form-control @error('category') is-invalid @enderror"
                                       id="category"
                                       name="category"
                                       value="{{ old('category') }}"
                                       placeholder="Ej: marketing, productos, eventos"
                                       list="categories">
                                <datalist id="categories">
                                    <option value="marketing">
                                    <option value="productos">
                                    <option value="eventos">
                                    <option value="noticias">
                                    <option value="promociones">
                                </datalist>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Hashtags -->
                            <div class="mb-3">
                                <label for="hashtags" class="form-label fw-semibold">
                                    Hashtags <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control @error('hashtags') is-invalid @enderror"
                                          id="hashtags"
                                          name="hashtags"
                                          rows="5"
                                          placeholder="#marketing #digitalmarketing #socialmedia #business #entrepreneur"
                                          required>{{ old('hashtags') }}</textarea>
                                @error('hashtags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Separa los hashtags con espacios o comas. El # es opcional, se agregará automáticamente.
                                </small>
                            </div>

                            <!-- Hashtags Preview -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Vista Previa</label>
                                <div id="hashtags-preview" class="p-3 bg-light rounded">
                                    <small class="text-muted">Escribe hashtags arriba para ver la vista previa</small>
                                </div>
                                <small class="text-muted">
                                    Total: <span id="hashtag-count">0</span> hashtags
                                </small>
                            </div>

                            <!-- Color -->
                            <div class="mb-4">
                                <label for="color" class="form-label fw-semibold">
                                    Color de Identificación
                                </label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color"
                                           class="form-control form-control-color @error('color') is-invalid @enderror"
                                           id="color"
                                           name="color"
                                           value="{{ old('color', '#6C757D') }}"
                                           style="width: 60px; height: 40px;">
                                    <input type="text"
                                           class="form-control"
                                           id="color-hex"
                                           value="{{ old('color', '#6C757D') }}"
                                           readonly
                                           style="max-width: 100px;">
                                </div>
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Color para identificar visualmente este grupo</small>
                            </div>

                            <!-- Info Alert -->
                            <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
                                <i class="fas fa-lightbulb me-2 mt-1"></i>
                                <div>
                                    <strong>Consejo:</strong> Organiza tus hashtags por tema o campaña.
                                    Los grupos de hashtags te permiten copiar rápidamente todos los tags relevantes
                                    sin tener que escribirlos cada vez.
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.social.hashtags.index') }}" class="btn btn-light">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Crear Grupo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Hashtags preview
    const hashtagsTextarea = document.getElementById('hashtags');
    const hashtagsPreview = document.getElementById('hashtags-preview');
    const hashtagCount = document.getElementById('hashtag-count');

    function updatePreview() {
        const text = hashtagsTextarea.value.trim();
        if (!text) {
            hashtagsPreview.innerHTML = '<small class="text-muted">Escribe hashtags arriba para ver la vista previa</small>';
            hashtagCount.textContent = '0';
            return;
        }

        // Split by spaces or commas and filter empty
        const tags = text.split(/[\s,]+/).filter(tag => tag.length > 0);

        // Add # if not present and create preview
        const formattedTags = tags.map(tag => {
            const cleanTag = tag.trim();
            return cleanTag.startsWith('#') ? cleanTag : '#' + cleanTag;
        });

        hashtagsPreview.innerHTML = formattedTags.map(tag =>
            `<span class="badge bg-primary me-1 mb-1">${tag}</span>`
        ).join('');

        hashtagCount.textContent = formattedTags.length;
    }

    hashtagsTextarea.addEventListener('input', updatePreview);
    updatePreview();

    // Color picker sync
    const colorInput = document.getElementById('color');
    const colorHex = document.getElementById('color-hex');

    colorInput.addEventListener('input', function() {
        colorHex.value = this.value.toUpperCase();
    });
</script>
@endpush
