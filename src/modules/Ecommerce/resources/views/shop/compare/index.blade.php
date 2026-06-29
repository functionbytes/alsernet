@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Comparar productos')

@section('content')
    <div class="container py-5">
        <h3 class="mb-4">Comparar productos</h3>

        @include('core::components.alerts')

        @if($products->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:150px;">Caracteristica</th>
                            @foreach($products as $product)
                                <th class="text-center" style="min-width:200px;">
                                    @if($product->featured_image)
                                        <img src="{{ $product->featured_image }}" class="rounded mb-2" width="80" height="80" style="object-fit:cover;">
                                    @endif
                                    <div><a href="{{ route('shop.product', $product->slug) }}" class="fw-semibold text-decoration-none">{{ $product->name }}</a></div>
                                    <form action="{{ route('ecommerce.compare.destroy', $product) }}" method="POST" class="d-inline mt-2">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Quitar</button>
                                    </form>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold">Precio</td>
                            @foreach($products as $product)
                                <td class="text-center">
                                    @if($product->is_on_sale)
                                        <span class="text-decoration-line-through text-muted">${{ number_format($product->price, 2) }}</span>
                                        <span class="fw-bold text-danger">${{ number_format($product->final_price, 2) }}</span>
                                    @else
                                        <span class="fw-semibold">${{ number_format($product->price, 2) }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="fw-bold">Marca</td>
                            @foreach($products as $product)
                                <td class="text-center">{{ $product->brand?->name ?? '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="fw-bold">SKU</td>
                            @foreach($products as $product)
                                <td class="text-center"><code>{{ $product->sku ?? '—' }}</code></td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="fw-bold">Stock</td>
                            @foreach($products as $product)
                                <td class="text-center">
                                    @if($product->with_storehouse_management)
                                        <span class="badge bg-{{ $product->quantity > 0 ? 'success' : 'danger' }}">{{ $product->quantity }} disponibles</span>
                                    @else
                                        <span class="badge bg-secondary">En stock</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="fw-bold">Descripcion</td>
                            @foreach($products as $product)
                                <td class="text-center"><small class="text-muted">{{ Str::limit(strip_tags($product->description), 100) }}</small></td>
                            @endforeach
                        </tr>
                        <tr>
                            <td></td>
                            @foreach($products as $product)
                                <td class="text-center">
                                    <a href="{{ route('cart.add', $product) }}" class="btn btn-primary btn-sm"><i class="fas fa-shopping-cart me-1"></i> Agregar al carrito</a>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            <form action="{{ route('ecommerce.compare.clear') }}" method="POST" class="mt-3">
                @csrf
                <button class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i> Vaciar comparacion</button>
            </form>
        @else
            <div class="text-center py-5">
                <i class="fas fa-exchange-alt fa-4x text-muted opacity-50 mb-4"></i>
                <h5 class="text-muted mb-2">No hay productos para comparar</h5>
                <a href="{{ route('shop.index') }}" class="btn btn-primary">Explorar productos</a>
            </div>
        @endif
    </div>
@endsection
