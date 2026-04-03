@extends('layouts.theme')

@section('title', 'Configuración del blog')

@section('content')
    @include('core::components.card', ['title' => 'Configuración del blog'])

    @include('core::components.alerts')

    <form method="POST" action="{{ route('settings.blog.update') }}">
        @csrf
        @method('PUT')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <h5 class="mb-1 fw-bold">Configuración del blog</h5>
                <p class="small mb-0 text-muted">Controla el comportamiento general del módulo de blog</p>
            </div>

            {{-- Identidad --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">Identidad</h6>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="blog_title" class="form-label">Título del blog</label>
                        <input type="text" id="blog_title" name="blog_title"
                               class="form-control @error('blog_title') is-invalid @enderror"
                               maxlength="255"
                               value="{{ old('blog_title', $settings['blog_title'] ?? '') }}">
                        @error('blog_title')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="text-muted d-block mt-1">Nombre que aparece como encabezado del blog en el sitio público.</small>
                    </div>

                    <div class="col-12">
                        <label for="blog_description" class="form-label">Descripción del blog</label>
                        <textarea id="blog_description" name="blog_description" rows="3"
                                  class="form-control @error('blog_description') is-invalid @enderror"
                                  maxlength="1000">{{ old('blog_description', $settings['blog_description'] ?? '') }}</textarea>
                        @error('blog_description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="text-muted d-block mt-1">Descripción corta que se muestra bajo el título y puede usarse como meta descripción.</small>
                    </div>
                </div>
            </div>

            {{-- General --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">General</h6>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="posts_per_page" class="form-label">Posts por página</label>
                        <input type="number" id="posts_per_page" name="posts_per_page"
                               class="form-control @error('posts_per_page') is-invalid @enderror"
                               min="1" max="100"
                               value="{{ old('posts_per_page', $settings['posts_per_page'] ?? 10) }}">
                        @error('posts_per_page')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="text-muted d-block mt-1">Número de posts que se muestran en el listado público.</small>
                    </div>

                    <div class="col-12">
                        <label for="default_status" class="form-label">Estado predeterminado al crear</label>
                        <select id="default_status" name="default_status"
                                class="form-select select2 @error('default_status') is-invalid @enderror">
                            <option value="draft"     {{ ($settings['default_status'] ?? 'draft') === 'draft'     ? 'selected' : '' }}>Borrador</option>
                            <option value="published" {{ ($settings['default_status'] ?? '') === 'published' ? 'selected' : '' }}>Publicado</option>
                            <option value="pending"   {{ ($settings['default_status'] ?? '') === 'pending'   ? 'selected' : '' }}>Pendiente</option>
                        </select>
                        @error('default_status')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="text-muted d-block mt-1">Estado asignado automáticamente a los nuevos posts.</small>
                    </div>
                </div>
            </div>

            {{-- Comentarios --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-bold mb-3">Comentarios</h6>

                <div class="mb-0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="allow_comments"
                               name="allow_comments" value="1"
                               {{ ($settings['allow_comments'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="allow_comments">
                            Permitir comentarios en los posts
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Habilita la sección de comentarios en los posts publicados. Puede sobreescribirse por post individual.
                    </small>
                </div>
            </div>

            {{-- Footer --}}
            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-2">Guardar configuración </button>
                <a href="javascript:history.back()" class="btn btn-secondary w-100">Cancelar</a>

            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
