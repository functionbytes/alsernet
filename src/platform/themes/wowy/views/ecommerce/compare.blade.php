<section class="mt-60 mb-60">
    <div class="container">
        <div class="row">
            <div class="col-12">
                @if ($products->count())
                    <div class="table-responsive table__compare">
                        <table class="table text-center">
                            <tbody>
                                <tr class="pr_image">
                                    <td class="text-muted font-md fw-600">{{ __('Preview') }}</td>
                                    @foreach($products as $product)
                                        <td class="row_img">
                                            <a href="{{ route('shop.product', $product->slug) }}"><img src="{{ RvMedia::getImageUrl($product->featured_image, 'thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $product->name }}"></a>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr class="pr_title">
                                    <td class="text-muted font-md fw-600">{{ __('Name') }}</td>

                                    @foreach($products as $product)
                                        <td class="product_name">
                                            <h5><a href="{{ route('shop.product', $product->slug) }}">{{ $product->name }}</a></h5>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr class="pr_price">
                                    <td class="text-muted font-md fw-600">{{ __('Price') }}</td>

                                    @foreach($products as $product)
                                        <td class="product_price">
                                            <span class="price">{{ format_price($product->final_price) }}</span> @if ($product->final_price != $product->price) <del>{{ format_price($product->price) }} </del> <small>({{ get_sale_percentage((float) $product->price, (float) $product->final_price) }})</small> @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @if (EcommerceHelper::isReviewEnabled())
                                    <tr class="pr_rating">
                                        <td class="text-muted font-md fw-600">{{ __('Rating') }}</td>
                                        @foreach($products as $product)
                                            @php
                                                $reviewsAvg = $product->reviews->avg('rating') ?? 0;
                                                $reviewsCount = $product->reviews->count();
                                            @endphp
                                            <td>
                                                <div class="rating_wrap">
                                                    <div class="rating">
                                                        <div class="product_rate" style="width: {{ $reviewsAvg * 20 }}%"></div>
                                                    </div>
                                                    <span class="rating_num">({{ $reviewsCount }})</span>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif

                                <tr class="description">
                                    <td class="text-muted font-md fw-600">{{ __('Description') }}</td>
                                    @foreach($products as $product)
                                        <td class="row_text font-xs">
                                            <p>
                                                {!! BaseHelper::clean($product->description) !!}
                                            </p>
                                        </td>
                                    @endforeach
                                </tr>

                                @if (isset($attributeSets))
                                    @foreach($attributeSets as $attributeSet)
                                        @if ($attributeSet->is_comparable)
                                            <tr>
                                                <td class="text-muted font-md fw-600">
                                                    {{ $attributeSet->title }}
                                                </td>

                                                @foreach($products as $product)
                                                    <td>&mdash;</td>
                                                @endforeach
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                @if (EcommerceHelper::isCartEnabled())
                                    <tr class="pr_add_to_cart">
                                        <td class="text-muted font-md fw-600">{{ __('Buy now') }}</td>
                                        @foreach($products as $product)
                                            <td class="row_btn">
                                                <a href="#" class="btn btn-rounded btn-sm add-to-cart-button" data-id="{{ $product->id }}" data-url="{{ route('cart.add', $product->id) }}">
                                                    <i class="fa fa-shopping-bag mr-5"></i>{{ __('Add To Cart') }}
                                                </a>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif

                                <tr class="pr_remove text-muted">
                                    <td class="text-muted font-md fw-600">&nbsp;</td>
                                    @foreach($products as $product)
                                        <td class="row_remove">
                                            <a class="js-remove-from-compare-button" href="#" data-url="{{ route('ecommerce.compare.destroy', $product->id) }}">
                                                <i class="fa fa-trash-alt mr-5"></i>
                                                <span>{{ __('Remove') }}</span>
                                            </a>
                                        </td>
                                    @endforeach
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <p class="text-center">{{ __('No products in compare list!') }}</p>
            @endif
        </div>
    </div>
</section>
