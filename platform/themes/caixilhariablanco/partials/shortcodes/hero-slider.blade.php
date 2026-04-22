@if(!empty($slides))
<style>
    .slider-header-carousel .heading1 h1.main-heading,
    .slider-header-carousel .heading1 h5 {
        text-transform: none;
    }
    .slider-header-carousel .heading1 h1.main-heading::first-letter,
    .slider-header-carousel .heading1 h5::first-letter {
        text-transform: uppercase;
    }
</style>
<div class="slider-header-carousel owl-carousel">
    @foreach($slides as $slide)
    <div class="hero1-section-area">
        <img src="{{ $slide['image'] }}" alt="" class="header-img1"
             {{ $loop->first ? 'fetchpriority="high"' : 'loading="lazy"' }}>
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="hero-heading-area heading1">
                        @if(!empty($slide['badge']))
                            <h5>{{ $slide['badge'] }}</h5>
                        @endif
                        <h1 class="main-heading">{{ $slide['title'] ?? '' }}</h1>
                        @if(!empty($slide['text']))
                            <p class="pera">{{ $slide['text'] }}</p>
                        @endif
                        <div class="btn-area">
                            @if(!empty($slide['btn1_text']))
                                <a href="{{ $slide['btn1_url'] ?? '#' }}" class="header-btn1">
                                    {{ $slide['btn1_text'] }} <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            @endif
                            @if(!empty($slide['btn2_text']))
                                <a href="{{ $slide['btn2_url'] ?? '#' }}" class="header-btn2">
                                    {{ $slide['btn2_text'] }} <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            @endif
                        </div>
                        @if(!empty($slide['stat']))
                        <div class="header-bottom-images">
                            <div class="img1">
                                <img src="/pages/images/all-images/header-bottom.png" alt="" loading="lazy">
                            </div>
                            <div class="content">
                                <ul>
                                    @for($i = 0; $i < 5; $i++)
                                        <li><i class="fa-solid fa-star"></i></li>
                                    @endfor
                                </ul>
                                <p><span>{{ $slide['stat'] }}</span> {{ $slide['stat_label'] ?? '' }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
