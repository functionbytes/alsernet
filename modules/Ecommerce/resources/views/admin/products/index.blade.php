@extends('layouts.theme')

@section('title', 'Productos')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Productos'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Productos</h5>
                        <p class="small mb-0 text-muted">Administra los productos de la tienda</p>
                    </div>
                    <a href="{{ route('ecommerce.products.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo producto
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($products->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($product->featured_image)
                                                    <img src="{{ $product->featured_image }}" alt="" class="rounded" width="40" height="40" style="object-fit:cover;">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="fas fa-image text-muted"></i></div>
                                                @endif
                                                <div>
                                                    <a href="{{ route('ecommerce.products.edit', $product) }}" class="text-decoration-none fw-semibold">{{ $product->name }}</a>
                                                    <div class="text-muted" style="font-size:.75rem;">{{ $product->brand->name ?? 'Sin marca' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><small class="text-muted">{{ $product->sku ?? '—' }}</small></td>
                                        <td>
                                            @if($product->is_on_sale)
                                                <span class="text-decoration-line-through text-muted">${{ number_format($product->price, 2) }}</span>
                                                <span class="fw-bold text-danger">${{ number_format($product->final_price, 2) }}</span>
                                            @else
                                                <span class="fw-semibold">${{ number_format($product->price, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->with_storehouse_management)
                                                <span class="badge bg-{{ $product->quantity > 5 ? 'success' : ($product->quantity > 0 ? 'warning' : 'danger') }}">{{ $product->quantity }}</span>
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-{{ $product->status->value === 'published' ? 'success' : ($product->status->value === 'draft' ? 'secondary' : 'warning') }}">{{ $product->status->value }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('ecommerce.products.edit', $product) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('ecommerce.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar este producto?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay productos</h5>
                        <a href="{{ route('ecommerce.products.create') }}" class="btn btn-primary">Crear primer producto</a>
                    </div>
                @endif
            </div>

            @if($products->hasPages())
                <div class="card-footer">{{ $products->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
