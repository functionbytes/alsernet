<section class="section-padding-60 product-tabs">
    <div class="container">
        <div class="heading-tab d-flex">
            <div class="heading-tab-left wow fadeIn animated">
                <h3 class="section-title style-1">{{ $category->name }}</h3>
            </div>
            <div class="heading-tab-right wow fadeIn animated">
                @if ($category->activeChildren->isNotEmpty())
                    <ul class="nav nav-tabs right no-border" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" type="button" data-url="{{ route('public.ajax.products-by-category', $category->id, ['limit' => $limit]) }}" role="tab" aria-controls="product-collections-tab" aria-selected="true">{{ __('All') }}</button>
                        </li>
                        @foreach($category->activeChildren as $item)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" type="button" data-url="{{ route('public.ajax.products-by-category', $item->id, ['limit' => $limit]) }}" role="tab" aria-controls="product-categories-product" aria-selected="true">{{ $item->name }}</button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <a href="{{ $category->url }}" class="nav-link me-1">{{ __('View all') }} <i class="fa fa-arrow-right font-xxs ml-5"></i></a>
                @endif
            </div>
        </div>
        <div class="tab-content wow fadeIn animated">
            <div class="half-circle-spinner loading-spinner d-none">
                <div class="circle circle-1"></div>
                <div class="circle circle-2"></div>
            </div>

            <div class="tab-pane fade show active" id="product-categories-product" role="tabpanel" aria-labelledby="product-categories-product-tab">
                <div class="row product-grid-4">
                    @foreach($products as $product)
                        <div class="col-lg-3 col-md-4 col-sm-6 ">
                            @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.product-item', compact('product'))
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
