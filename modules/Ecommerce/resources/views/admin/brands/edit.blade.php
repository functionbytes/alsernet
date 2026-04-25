@extends('layouts.theme')

@section('title', 'Editar marca')

@section('content')
    @include('core::components.card', ['title' => 'Editar marca'])
    <div class="widget-content searchable-container list">
        <form action="{{ route('ecommerce.brands.update', $brand) }}" method="POST">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $brand->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripcion</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $brand->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sitio web</label>
                        <input type="url" name="website" class="form-control" value="{{ old('website', $brand->website) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo (URL)</label>
                        <input type="text" name="logo" class="form-control" value="{{ old('logo', $brand->logo) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="published" {{ old('status', $brand->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                            <option value="draft" {{ old('status', $brand->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Orden</label>
                        <input type="number" name="order" class="form-control" value="{{ old('order', $brand->order) }}">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured', $brand->is_featured) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_featured">Destacado</label>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('ecommerce.brands.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </div>
        </form>
    </div>
@endsection
