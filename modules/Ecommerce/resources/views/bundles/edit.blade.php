@extends('layouts.theme')

@section('title', 'Editar bundle')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Editar bundle'])
@endsection

@section('content')
    <form action="{{ route('ecommerce.bundles.update', $bundle) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card mb-4">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion del bundle</h5>
                        <small class="text-muted">Edita los campos del bundle.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="nameInput"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $bundle->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" id="slugInput"
                                    class="form-control @error('slug') is-invalid @enderror"
                                    value="{{ old('slug', $bundle->slug) }}" required>
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripcion</label>
                                <textarea name="description" rows="3"
                                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $bundle->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de descuento <span class="text-danger">*</span></label>
                                <select name="discount_type" class="form-select @error('discount_type') is-invalid @enderror">
                                    <option value="percentage" {{ old('discount_type', $bundle->discount_type) === 'percentage' ? 'selected' : '' }}>Porcentaje (%)</option>
                                    <option value="fixed" {{ old('discount_type', $bundle->discount_type) === 'fixed' ? 'selected' : '' }}>Monto fijo ($)</option>
                                </select>
                                @error('discount_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor de descuento <span class="text-danger">*</span></label>
                                <input type="number" name="discount_value" step="0.01" min="0"
                                    class="form-control @error('discount_value') is-invalid @enderror"
                                    value="{{ old('discount_value', $bundle->discount_value) }}" required>
                                @error('discount_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">URL de imagen</label>
                                <input type="text" name="image"
                                    class="form-control @error('image') is-invalid @enderror"
                                    value="{{ old('image', $bundle->image) }}" placeholder="https://...">
                                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Productos del bundle</h5>
                        <small class="text-muted">Selecciona los productos que forman parte del bundle.</small>
                    </div>
                    <div class="card-body">
                        @php
                            $bundleProductIds = $bundle->products->pluck('id')->toArray();
                            $bundleQtys = $bundle->products->pluck('pivot.qty', 'id')->toArray();
                        @endphp

                        @if($products->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40"></th>
                                            <th>Producto</th>
                                            <th>Precio</th>
                                            <th width="100">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($products as $product)
                                            @php
                                                $oldProducts = old('products', $bundleProductIds);
                                                $isChecked = in_array($product->id, $oldProducts);
                                                $oldQty = old("quantities.{$product->id}", $bundleQtys[$product->id] ?? 1);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="products[]"
                                                        class="form-check-input product-checkbox"
                                                        value="{{ $product->id }}"
                                                        id="product_{{ $product->id }}"
                                                        {{ $isChecked ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <label class="form-check-label" for="product_{{ $product->id }}">
                                                        {{ $product->name }}
                                                    </label>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        ${{ number_format($product->sale_price ?? $product->price, 2) }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <input type="number" name="quantities[{{ $product->id }}]"
                                                        class="form-control form-control-sm"
                                                        value="{{ $oldQty }}"
                                                        min="1">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No hay productos publicados disponibles.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4" style="position: sticky; top: 80px;">
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Publicar</h6>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar cambios</button>
                        <a href="{{ route('ecommerce.bundles.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Estado</h6>
                    </div>
                    <div class="card-body">
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="published" {{ old('status', $bundle->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                            <option value="draft" {{ old('status', $bundle->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
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
    $('#nameInput').on('input', function () {
        const slug = $(this).val()
            .toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^\w-]/g, '')
            .replace(/--+/g, '-');
        $('#slugInput').val(slug);
    });
});
</script>
@endpush
