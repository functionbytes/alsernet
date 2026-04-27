@extends('layouts.theme')

@section('title', 'Nueva marca')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Nueva marca'])
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.brands.store') }}" method="POST">
        @csrf

        <div class="row g-4 align-items-start">

            {{-- Columna principal --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Información de la marca</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="brand-name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Enlace permanente</label>
                            <div class="input-group">
                                <span class="input-group-text text-muted small">{{ rtrim(url('/'), '/') }}/marcas/</span>
                                <input type="text" name="slug" id="brand-slug"
                                    class="form-control @error('slug') is-invalid @enderror"
                                    value="{{ old('slug') }}"
                                    placeholder="nombre-de-la-marca">
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                rows="4">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sitio web</label>
                            <input type="url" name="website"
                                class="form-control @error('website') is-invalid @enderror"
                                value="{{ old('website') }}"
                                placeholder="https://ejemplo.com">
                            @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Logo (URL)</label>
                            <input type="text" name="logo"
                                class="form-control @error('logo') is-invalid @enderror"
                                value="{{ old('logo') }}"
                                placeholder="https://ejemplo.com/logo.png">
                            @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">

                    {{-- Publicar --}}
                    <div class="card mb-4">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Guardar marca</button>
                            <a href="{{ route('ecommerce.brands.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    {{-- Estado --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
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

                    {{-- Ajustes --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Ajustes</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Orden</label>
                                <input type="number" name="order"
                                    class="form-control @error('order') is-invalid @enderror"
                                    value="{{ old('order', 0) }}" min="0">
                                @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
    $('#brand-name').on('input', function () {
        var slug = $(this).val().toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/\s+/g, '-');
        $('#brand-slug').val(slug);
    });
});
</script>
@endpush
