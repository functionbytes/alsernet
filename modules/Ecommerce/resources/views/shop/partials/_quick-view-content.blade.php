@php
    use Modules\Ecommerce\Supports\ProductImageHelper;
    $firstCategory = $product->categories->first();
@endphp

<div class="row g-0">
    <div class="col-md-5">
        <div class="p-3 h-100 d-flex align-items-center justify-content-center bg-light rounded-start">
            <img src="{{ ProductImageHelper::url($product->featured_image, 'large', 'jpg') }}"
                 alt="{{ $product->name }}"
                 class="img-fluid rounded"
                 style="max-height: 320px; object-fit: contain;">
        </div>
    </div>
    <div class="col-md-7">
        <div class="p-4">
            @if($firstCategory)
                <div class="mb-1">
                    <a href="{{ route('shop.category', $firstCategory->slug) }}"
                       class="text-muted small text-decoration-none">{{ $firstCategory->name }}</a>
                </div>
            @endif

            <h5 class="fw-bold mb-2">{{ $product->name }}</h5>

            <div class="mb-3">
                <span class="fs-5 fw-bold text-brand">${{ number_format($product->final_price, 2) }}</span>
                @if($product->is_on_sale)
                    <span class="text-muted text-decoration-line-through ms-2 small">${{ number_format($product->price, 2) }}</span>
                    <span class="badge bg-danger ms-1 small">-{{ round((1 - $product->final_price / $product->price) * 100) }}%</span>
                @endif
            </div>

            @if($product->description)
                <p class="text-muted small mb-3">{{ \Illuminate\Support\Str::limit($product->description, 200) }}</p>
            @endif

            @php
                $isOutOfStock = $product->with_storehouse_management
                    && $product->quantity <= 0
                    && ! $product->allow_checkout_when_out_of_stock;
            @endphp

            @if($isOutOfStock)
                <p class="text-danger fw-semibold"><i class="fas fa-times-circle me-1"></i>Agotado</p>
            @else
                <form action="{{ route('cart.add', $product) }}" method="POST" class="js-add-to-cart mb-3">
                    @csrf
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" name="qty" value="1" min="1"
                               class="form-control" style="width: 75px;">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-cart-plus me-1"></i>Agregar al carrito
                        </button>
                    </div>
                </form>
            @endif

            <a href="{{ route('shop.product', $product->slug) }}"
               class="btn btn-outline-secondary w-100">
                Ver detalle completo
            </a>

            @if($product->brand)
                <p class="text-muted small mt-3 mb-0">
                    Marca: <strong>{{ $product->brand->name }}</strong>
                </p>
            @endif
        </div>
    </div>
</div>
