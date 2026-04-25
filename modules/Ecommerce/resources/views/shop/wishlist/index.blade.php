@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Mis favoritos')

@section('content')
    <div class="container py-5">
        <h3 class="mb-4">Mis favoritos</h3>

        @include('core::components.alerts')

        @if($wishlists->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($wishlists as $wishlist)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @if($wishlist->product?->featured_image)
                                            <img src="{{ $wishlist->product->featured_image }}" class="rounded" width="60" height="60" style="object-fit:cover;">
                                        @endif
                                        <div>
                                            <a href="{{ route('shop.product', $wishlist->product?->slug) }}" class="fw-semibold text-decoration-none">{{ $wishlist->product?->name ?? 'Producto no disponible' }}</a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($wishlist->product?->is_on_sale)
                                        <span class="text-decoration-line-through text-muted">${{ number_format($wishlist->product->price, 2) }}</span>
                                        <span class="fw-bold text-danger">${{ number_format($wishlist->product->final_price, 2) }}</span>
                                    @else
                                        <span class="fw-semibold">${{ number_format($wishlist->product?->price ?? 0, 2) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('ecommerce.wishlist.destroy', $wishlist) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar de favoritos?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($wishlists->hasPages())
                <div class="mt-3">{{ $wishlists->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-heart fa-4x text-muted opacity-50 mb-4"></i>
                <h5 class="text-muted mb-2">No tienes favoritos</h5>
                <a href="{{ route('shop.index') }}" class="btn btn-primary">Explorar productos</a>
            </div>
        @endif
    </div>
@endsection
