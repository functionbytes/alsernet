@extends('layouts.theme')

@section('title', 'Editar conjunto de atributos')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar conjunto de atributos'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <form id="edit-form" action="{{ route('ecommerce.product-attribute-sets.update', $productAttributeSet) }}" method="POST">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Titulo <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $productAttributeSet->title) }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $productAttributeSet->slug) }}" placeholder="Se genera automaticamente">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Layout de visualizacion</label>
                            <select name="display_layout" class="form-select @error('display_layout') is-invalid @enderror">
                                <option value="swatch_dropdown" {{ old('display_layout', $productAttributeSet->display_layout) === 'swatch_dropdown' ? 'selected' : '' }}>Swatch desplegable</option>
                                <option value="text" {{ old('display_layout', $productAttributeSet->display_layout) === 'text' ? 'selected' : '' }}>Texto</option>
                                <option value="color" {{ old('display_layout', $productAttributeSet->display_layout) === 'color' ? 'selected' : '' }}>Color</option>
                                <option value="image" {{ old('display_layout', $productAttributeSet->display_layout) === 'image' ? 'selected' : '' }}>Imagen</option>
                            </select>
                            @error('display_layout')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="published" {{ old('status', $productAttributeSet->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="draft" {{ old('status', $productAttributeSet->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Orden</label>
                            <input type="number" name="order" class="form-control @error('order') is-invalid @enderror" value="{{ old('order', $productAttributeSet->order) }}" min="0">
                            @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_searchable" value="1" id="is_searchable"
                                    {{ old('is_searchable', $productAttributeSet->is_searchable) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_searchable">Es buscable</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_comparable" value="1" id="is_comparable"
                                    {{ old('is_comparable', $productAttributeSet->is_comparable) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_comparable">Es comparable</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_use_in_product_listing" value="1" id="is_use_in_product_listing"
                                    {{ old('is_use_in_product_listing', $productAttributeSet->is_use_in_product_listing) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_use_in_product_listing">Usar en listado de productos</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('ecommerce.product-attribute-sets.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </div>
        </form>

        @if($productAttributeSet->attributes->count() > 0)
            <div class="card mt-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Atributos en este conjunto</h6>
                    <small class="text-muted">La gestion de atributos individuales se realiza desde la pagina del producto</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Titulo</th>
                                    <th>Color</th>
                                    <th>Por defecto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productAttributeSet->attributes as $attribute)
                                    <tr>
                                        <td>{{ $attribute->title }}</td>
                                        <td>
                                            @if($attribute->color)
                                                <span class="d-inline-block rounded-circle border" style="width:20px;height:20px;background-color:{{ $attribute->color }}"></span>
                                                <small class="text-muted ms-1">{{ $attribute->color }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attribute->is_default)
                                                <span class="badge bg-success">Si</span>
                                            @else
                                                <span class="text-muted">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $attribute->status === 'published' ? 'success' : 'secondary' }}">{{ $attribute->status }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
