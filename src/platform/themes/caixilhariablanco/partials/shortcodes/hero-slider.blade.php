@if(!empty($slides))
<div class="slider-header-carousel owl-carousel" role="region" aria-label="Carrusel de hero">
    @foreach($slides as $slide)
    <div class="hero1-section-area" style="aspect-ratio: 16/9; overflow: hidden;">
        <img src="{{ $slide['image'] }}?v=2" alt="{{ $slide['title'] ?? 'Imagen del slider' }}" class="header-img1"
             width="1920" height="1080"
             @if($loop->first)
                 fetchpriority="high" loading="eager" decoding="sync"
             @else
                 loading="lazy" decoding="async"
             @endif
             style="width: 100%; height: 100%; object-fit: cover; display: block;">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="hero-heading-area heading1">
                        @if(!empty($slide['badge']))
                            <span >{{ $slide['badge'] }}</span>
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
                                <img src="/pages/images/all-images/header-bottom.webp?v=2" alt="" width="138" height="52" loading="lazy">
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
