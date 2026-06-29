@extends('layouts.theme')

@section('title', 'Nueva coleccion')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Nueva coleccion'])
@endsection

@section('content')
    <form action="{{ route('ecommerce.collections.store') }}" method="POST">
        @csrf
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion de la coleccion</h5>
                        <small class="text-muted">Complete los campos requeridos para crear la coleccion.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="col-name" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Enlace permanente <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text text-muted small">{{ rtrim(url('/'), '/') }}/colecciones/</span>
                                <input type="text" id="col-slug" name="slug"
                                    class="form-control @error('slug') is-invalid @enderror"
                                    value="{{ old('slug') }}" required>
                            </div>
                            @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <small id="permalink-preview" class="form-text text-muted mt-1"></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" rows="4"
                                class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4" style="position: sticky; top: 80px;">
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Publicar</h6>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar coleccion</button>
                        <a href="{{ route('ecommerce.collections.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Estado</h6>
                    </div>
                    <div class="card-body">
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Publicado</option>
                            <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Borrador</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const baseUrl = '{{ rtrim(url("/"), "/") }}/colecciones/';

    function toSlug(value) {
        return value.toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-');
    }

    $('#col-name').on('input', function () {
        const slug = toSlug($(this).val());
        $('#col-slug').val(slug);
        $('#permalink-preview').text(slug ? baseUrl + slug : '');
    });

    $('#col-slug').on('input', function () {
        const slug = $(this).val();
        $('#permalink-preview').text(slug ? baseUrl + slug : '');
    });

    const initialSlug = $('#col-slug').val();
    if (initialSlug) {
        $('#permalink-preview').text(baseUrl + initialSlug);
    }
});
</script>
@endpush
