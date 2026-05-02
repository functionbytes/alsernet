@extends('layouts.admin')

@section('title', 'Editar Grupo de Hashtags')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.hashtags.index') }}">Grupos de Hashtags</a></li>
                <li class="breadcrumb-item active">Editar Grupo</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-hashtag me-2"></i>Editar Grupo: {{ $hashtagGroup->name }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.social.hashtags.update', $hashtagGroup) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre del Grupo <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $hashtagGroup->name) }}"
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
                                       value="{{ old('category', $hashtagGroup->category) }}"
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
                                          required>{{ old('hashtags', $hashtagGroup->hashtags) }}</textarea>
                                @error('hashtags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Separa los hashtags con espacios o comas
                                </small>
                            </div>

                            <!-- Hashtags Preview -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Vista Previa</label>
                                <div id="hashtags-preview" class="p-3 bg-light rounded">
                                    <small class="text-muted">Cargando...</small>
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
                                           value="{{ old('color', $hashtagGroup->color) }}"
                                           style="width: 60px; height: 40px;">
                                    <input type="text"
                                           class="form-control"
                                           id="color-hex"
                                           value="{{ old('color', $hashtagGroup->color) }}"
                                           readonly
                                           style="max-width: 100px;">
                                </div>
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Usage Stats -->
                            <div class="alert alert-light border" role="alert">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fas fa-chart-bar fs-4 text-primary"></i>
                                    <div>
                                        <div class="fw-semibold">Estadísticas de uso</div>
                                        <small class="text-muted">
                                            Usado <strong>{{ $hashtagGroup->usage_count }}</strong> veces
                                            @if($hashtagGroup->last_used_at)
                                                - Última vez: {{ $hashtagGroup->last_used_at->diffForHumans() }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.social.hashtags.index') }}" class="btn btn-light">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Guardar Cambios
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
            hashtagsPreview.innerHTML = '<small class="text-muted">Escribe hashtags arriba</small>';
            hashtagCount.textContent = '0';
            return;
        }

        const tags = text.split(/[\s,]+/).filter(tag => tag.length > 0);
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
