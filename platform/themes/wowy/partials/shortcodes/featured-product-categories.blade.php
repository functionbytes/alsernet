<section class="popular-categories  section-padding-60" id="featured-product-categories">
    <div class="container wow fadeIn animated">
        <div class="section-content">
            <h3 class="section-title ">{{ __('Title Popular Categories') }}</h3>
            <p class="section-description">{{ __('Description Popular Categories') }}</p>
        </div>

        <div class="carousel-6-columns-cover position-relative">
            <div class="slider-arrow slider-arrow-2 carousel-6-columns-arrow" id="carousel-6-columns-categories-arrows"></div>

            <div class="carousel-slider-wrapper carousel-6-columns" id="carousel-6-columns-categories"
                 data-slick="{{ json_encode([
                    'autoplay' => $shortcode->is_autoplay == 'yes',
                    'infinite' => $shortcode->infinite == 'yes' || $shortcode->is_infinite == 'yes',
                    'autoplaySpeed' => (int)(in_array($shortcode->autoplay_speed, theme_get_autoplay_speed_options()) ? $shortcode->autoplay_speed : 3000),
                    'speed' => 800,
                    'arrows' => true,
                    'slidesToShow' => 7,
                    'slidesToScroll' => 1,
                    'responsive' => [
                        ['breakpoint' => 1024, 'settings' => ['slidesToShow' => 3]],
                        ['breakpoint' => 768, 'settings' => ['slidesToShow' => 2]],
                        ['breakpoint' => 480, 'settings' => ['slidesToShow' => 1]],
                    ],
                ]) }}">
                @foreach($categories as $category)
                    <div class="category-item">
                        <div class="card-1  hover-up p-20 category-thumb">
                            <a href="{{ $category->url }}">
                                <img src="{{ RvMedia::getImageUrl($category->image, 'thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $category->name }}" >
                            </a>
                        </div>
                        <div class="category-content">
                            <a href="{{ $category->url }}">
                                {{ $category->name }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
