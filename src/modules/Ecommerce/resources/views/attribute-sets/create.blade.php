@extends('layouts.theme')

@section('title', 'Nuevo conjunto de atributos')

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('ecommerce.product-attribute-sets.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo conjunto de atributos</h5>
                        <small class="text-muted">Define los atributos de variantes que se asignarán a productos.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="Se genera automáticamente">
                                <small class="form-text text-muted">Identificador único para URLs</small>
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Layout de visualización</label>
                                <select name="display_layout" class="form-select @error('display_layout') is-invalid @enderror">
                                    <option value="swatch_dropdown" {{ old('display_layout') === 'swatch_dropdown' ? 'selected' : '' }}>Swatch desplegable</option>
                                    <option value="text" {{ old('display_layout') === 'text' ? 'selected' : '' }}>Texto</option>
                                    <option value="color" {{ old('display_layout') === 'color' ? 'selected' : '' }}>Color</option>
                                    <option value="image" {{ old('display_layout') === 'image' ? 'selected' : '' }}>Imagen</option>
                                </select>
                                @error('display_layout')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Publicado</option>
                                    <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Orden</label>
                                <input type="number" name="order" class="form-control @error('order') is-invalid @enderror" value="{{ old('order', 0) }}" min="0">
                                @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <hr class="my-2">
                                <h6 class="fw-semibold mb-3">Opciones</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_searchable" value="1" id="is_searchable" {{ old('is_searchable', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_searchable">Es buscable</label>
                                    <div class="form-text text-muted">Permite filtrar productos por este atributo</div>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_comparable" value="1" id="is_comparable" {{ old('is_comparable') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_comparable">Es comparable</label>
                                    <div class="form-text text-muted">Aparece en la tabla de comparación de productos</div>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_use_in_product_listing" value="1" id="is_use_in_product_listing" {{ old('is_use_in_product_listing', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_use_in_product_listing">Usar en listado de productos</label>
                                    <div class="form-text text-muted">Muestra el atributo en cards de listado</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar</button>
                        <a href="{{ route('ecommerce.product-attribute-sets.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">¿Qué es un conjunto de atributos?</h6>
                    <p class="card-text text-muted">
                        Un conjunto agrupa atributos de variante (talla, color, etc.) que luego asignas a productos. Cada atributo define una opción de selección para el cliente.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Layout de visualización</h6>
                    <p class="card-text text-muted mb-0">
                        Define cómo verá el cliente las opciones: <strong>Swatch</strong> para selección visual con colores/imágenes, <strong>Texto</strong> para botones, o <strong>Imagen</strong> para muestras.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Buscable y comparable</h6>
                    <p class="card-text text-muted mb-0">
                        Marcar como buscable habilita el filtro lateral en el catálogo. Comparable lo incluye en la tabla de comparación de productos.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
