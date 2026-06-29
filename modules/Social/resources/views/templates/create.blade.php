@extends('layouts.theme')

@section('title', 'Nueva Plantilla')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.templates.index') }}">Plantillas</a></li>
                <li class="breadcrumb-item active">Nueva Plantilla</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-code me-2"></i>Nueva Plantilla de Contenido
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.social.templates.store') }}" method="POST">
                            @csrf

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: Promoción de Producto"
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
                                       placeholder="Ej: promociones, anuncios, noticias"
                                       list="categories">
                                <datalist id="categories">
                                    <option value="promociones">
                                    <option value="anuncios">
                                    <option value="noticias">
                                    <option value="eventos">
                                    <option value="productos">
                                </datalist>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Content -->
                            <div class="mb-3">
                                <label for="content" class="form-label fw-semibold">
                                    Contenido <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control @error('content') is-invalid @enderror"
                                          id="content"
                                          name="content"
                                          rows="6"
                                          placeholder="Usa variables como {nombre}, {precio}, {fecha} para contenido dinámico..."
                                          required>{{ old('content') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Usa variables entre llaves como <code>{nombre}</code>, <code>{precio}</code>, <code>{descripcion}</code>
                                </small>
                            </div>

                            <!-- Variables (Auto-detected) -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Variables Detectadas</label>
                                <div id="variables-preview" class="p-3 bg-light rounded">
                                    <small class="text-muted">Las variables se detectarán automáticamente al escribir</small>
                                </div>
                            </div>

                            <!-- Hashtags -->
                            <div class="mb-3">
                                <label for="hashtags" class="form-label fw-semibold">
                                    Hashtags
                                </label>
                                <input type="text"
                                       class="form-control @error('hashtags') is-invalid @enderror"
                                       id="hashtags"
                                       name="hashtags"
                                       value="{{ old('hashtags') }}"
                                       placeholder="#marketing #ventas #promocion">
                                @error('hashtags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Separa hashtags con espacios</small>
                            </div>

                            <!-- Networks -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Redes Sociales</label>
                                <div class="row g-2">
                                    @foreach(['facebook', 'instagram', 'twitter', 'linkedin', 'pinterest', 'youtube'] as $network)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="networks[]"
                                                       value="{{ $network }}"
                                                       id="network_{{ $network }}"
                                                       {{ is_array(old('networks')) && in_array($network, old('networks')) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="network_{{ $network }}">
                                                    {{ ucfirst($network) }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">Deja sin marcar para usar en todas las redes</small>
                            </div>

                            <!-- Info Alert -->
                            <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
                                <i class="fas fa-lightbulb me-2 mt-1"></i>
                                <div>
                                    <strong>Consejo:</strong> Las plantillas te permiten crear contenido reutilizable.
                                    Usa variables para personalizar cada publicación sin tener que escribir todo de nuevo.
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.social.templates.index') }}" class="btn btn-light">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Crear Plantilla
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
    // Auto-detect variables in content
    const contentTextarea = document.getElementById('content');
    const variablesPreview = document.getElementById('variables-preview');

    function detectVariables() {
        const content = contentTextarea.value;
        const regex = /\{([^}]+)\}/g;
        const matches = [...content.matchAll(regex)];
        const variables = [...new Set(matches.map(m => m[1]))];

        if (variables.length > 0) {
            variablesPreview.innerHTML = variables.map(v =>
                `<span class="badge bg-primary me-1 mb-1"><code>{${v}}</code></span>`
            ).join('');
        } else {
            variablesPreview.innerHTML = '<small class="text-muted">No se detectaron variables</small>';
        }
    }

    contentTextarea.addEventListener('input', detectVariables);
    detectVariables();
</script>
@endpush
