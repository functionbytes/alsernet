@extends('layouts.theme')

@section('title', 'Nueva categoria')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Nueva categoria'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.categories.store') }}" method="POST">
        @csrf
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion de la categoria</h5>
                        <small class="text-muted">Complete los datos para crear la categoria.</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="cat-name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   placeholder="ej: Electrónica"
                                   required>
                            @error('name')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Enlace permanente</label>
                            <div class="input-group">
                                <span class="input-group-text text-muted small">{{ rtrim(url('/'), '/') }}/categorias/</span>
                                <input type="text" id="cat-slug" name="slug"
                                       class="form-control @error('slug') is-invalid @enderror"
                                       value="{{ old('slug') }}"
                                       placeholder="nombre-de-categoria">
                            </div>
                            <div id="permalink-preview" class="form-text text-muted d-none">
                                Vista: <a id="permalink-link" href="#" target="_blank"></a>
                            </div>
                            @error('slug')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Padre</label>
                            <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                <option value="">Nada</option>
                                @foreach($parents as $id => $name)
                                    <option value="{{ $id }}" {{ old('parent_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="4"
                                      placeholder="Descripcion breve de la categoria">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 80px;">

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Publicar</h6>
                            <button type="submit" class="btn btn-primary w-100 mb-2">Guardar categoria</button>
                            <a href="{{ route('ecommerce.categories.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Estado</h6>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                            </select>
                            @error('status')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Ajustes</h6>
                            <div class="mb-3">
                                <label class="form-label">Orden</label>
                                <input type="number" name="order"
                                       class="form-control @error('order') is-invalid @enderror"
                                       value="{{ old('order', 0) }}"
                                       min="0">
                                @error('order')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                       id="is_featured" {{ old('is_featured') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">Destacado</label>
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
    const baseUrl = '{{ rtrim(url('/'), '/') }}/categorias/';

    function toSlug(text) {
        return text
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    function updatePermalink(slug) {
        if (!slug) {
            $('#permalink-preview').addClass('d-none');
            return;
        }
        const url = baseUrl + slug;
        $('#permalink-link').attr('href', url).text(url);
        $('#permalink-preview').removeClass('d-none');
    }

    $('#cat-name').on('input', function () {
        const slug = toSlug($(this).val());
        $('#cat-slug').val(slug);
        updatePermalink(slug);
    });

    $('#cat-slug').on('input', function () {
        updatePermalink($(this).val());
    });
});
</script>
@endpush
