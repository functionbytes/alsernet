<section class="product-tabs pt-40 pb-30 wow fadeIn animated">
    <div class="container">
        <ul class="nav nav-tabs" role="tablist">
            @foreach($productCollections as $item)
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($loop->first) active @endif" type="button" data-url="{{ route('public.ajax.products-by-collection', $item->id, ['limit' => $limit]) }}" role="tab" aria-controls="product-collections-tab" aria-selected="true">{{ $item->name }}</button>
                </li>
            @endforeach
        </ul>
        <div class="tab-content wow fadeIn animated">
            <div class="half-circle-spinner loading-spinner d-none">
                <div class="circle circle-1"></div>
                <div class="circle circle-2"></div>
            </div>

            <div class="tab-pane fade show active" role="tabpanel" aria-labelledby="product-collections-tab">
                <div class="row product-grid-4">
                    @foreach($products as $product)
                        <div class="col-lg-3 col-md-4 col-sm-6 col-{{ theme_option('number_of_products_per_row_on_mobile', 2) == 2 ? 6 : 12 }}">
                            @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.product-item', compact('product'))
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
