@extends('layouts.theme')

@section('title', 'Nueva página legal')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Nueva página legal'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.legal-pages.store') }}" method="POST">
        @csrf

        <div class="row g-4 align-items-start">

            {{-- Columna principal --}}
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Información básica</h6>
                        <small class="text-muted">Título y contenido principal de la página</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="legal-title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug" id="legal-slug"
                                class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug') }}"
                                placeholder="ej: terms, privacy, contact">
                            <small class="text-muted">Identificador único de la URL: /legal/<strong id="slug-preview">{{ old('slug', '...') }}</strong></small>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Contenido <span class="text-danger">*</span></label>
                            <textarea name="content"
                                class="form-control wysiwyg-editor @error('content') is-invalid @enderror"
                                rows="15">{{ old('content') }}</textarea>
                            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">SEO</h6>
                        <small class="text-muted">Metadatos para motores de búsqueda (opcional)</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Meta título</label>
                            <input type="text" name="meta_title"
                                class="form-control @error('meta_title') is-invalid @enderror"
                                value="{{ old('meta_title') }}">
                            @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Meta descripción</label>
                            <textarea name="meta_description"
                                class="form-control @error('meta_description') is-invalid @enderror"
                                rows="3" maxlength="500">{{ old('meta_description') }}</textarea>
                            @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">
                    <div class="card mb-4">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Guardar página</button>
                            <a href="{{ route('ecommerce.legal-pages.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Visibilidad</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_published" value="1"
                                    id="is_published" {{ old('is_published', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_published">Publicado</label>
                            </div>
                            <small class="text-muted d-block mt-1">Si está desactivado, la página no será visible en la tienda.</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('scripts')
@include('ecommerce::partials._wysiwyg-script')
<script>
$(document).ready(function () {
    $('#legal-title').on('input', function () {
        var slug = $(this).val().toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/\s+/g, '-');
        $('#legal-slug').val(slug);
        $('#slug-preview').text(slug || '...');
    });

    $('#legal-slug').on('input', function () {
        $('#slug-preview').text($(this).val() || '...');
    });
});
</script>
@endpush
