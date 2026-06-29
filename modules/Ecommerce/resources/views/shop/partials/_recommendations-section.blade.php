@props(['products', 'title' => 'Quizás te interese', 'icon' => 'fas fa-magic'])

@if($products && $products->count() > 0)
    <div class="row mt-60" id="recommendations-section">
        <div class="col-12">
            <h3 class="section-title style-1 mb-30">
                <i class="{{ $icon }} me-2"></i>{{ $title }}
            </h3>
        </div>
        @foreach($products as $product)
            <div class="col-lg-3 col-md-4 col-sm-6 col-12">
                @include('ecommerce::shop.partials._product-card', ['product' => $product])
            </div>
        @endforeach
    </div>
@endif
