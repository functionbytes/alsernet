<section class="product-tabs section-padding position-relative">
    <div class="container">
        <h3 class="section-title style-1 mb-30">{{ $shortcode->title }}</h3>
        <div class="row">
            @foreach($products as $product)
                <div class="col-lg-3 col-md-4 col-12 col-sm-6">
                    @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.product-item', ['product' => $product])
                </div>
            @endforeach
        </div>
    </div>
</section>
