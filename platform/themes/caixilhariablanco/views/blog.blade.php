@extends('template::layouts.blog-right-sidebar')

@section('title', __('Blog') . ' | ' . theme_option('site_title', config('app.name')))
@section('description', __('Latest articles, tips and news from our blog.'))

@section('seo_head')
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __('Blog') . ' | ' . theme_option('site_title', config('app.name')) }}">
    <meta property="og:description" content="{{ __('Latest articles, tips and news from our blog.') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ theme_option('site_title', config('app.name')) }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ __('Blog') . ' | ' . theme_option('site_title', config('app.name')) }}">
@endsection

@section('content')
    {{-- Breadcrumb / Hero --}}
    <div class="blog-page-header mb-30">
        <h4 class="mb-5">{{ __('Our Blog') }}</h4>
        <p class="text-muted mb-0" class="fs-13">
            <a href="{{ url('/') }}" class="text-brand">{{ __('Home') }}</a>
            <i class="fa-solid fa-angle-right mx-1"></i>
            <span>{{ __('Blog') }}</span>
        </p>
    </div>

    {{-- Posts destacados --}}
    @if(isset($featuredPosts) && $featuredPosts->isNotEmpty())
    <div class="featured-posts-section mb-40">
        <h5 class="mb-20 fw-bold">
            <i class="fas fa-star text-warning me-2"></i>{{ __('Posts destacados') }}
        </h5>
        <div class="row">
            @foreach($featuredPosts as $featured)
                @php $firstCategory = $featured->categories->first(); @endphp
                <div class="col-lg-4 col-md-4 mb-30">
                    <div class="blog-auhtor-boxarea">
                        <div class="img1">
                            <a href="{{ $featured->url }}">
                                <img alt="" src="{{ RvMedia::getImageUrl($featured->image, 'medium', false, RvMedia::getDefaultImage()) }}"
                                     alt="{{ $featured->title }}"
                                     loading="lazy">
                            </a>
                        </div>
                        <div class="blog-position">
                            <a href="{{ $featured->url }}" class="heading">{{ $featured->title }}</a>
                            <div class="blog-content-area">
                                <ul>
                                    <li>
                                        <a href="#"><i class="fa-solid fa-calendar-days"></i> {{ $featured->created_at->translatedFormat('d M, Y') }}</a>
                                    </li>
                                    @if($firstCategory)
                                        <li>
                                            <a href="{{ $firstCategory->url }}"><i class="fa-solid fa-tag"></i> {{ $firstCategory->name }}</a>
                                        </li>
                                    @endif
                                </ul>
                                <p>{{ $featured->excerpt }}</p>
                                <a href="{{ $featured->url }}" class="learnmore">{{ __('Learn more') }} <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <hr class="mb-30">
    </div>
    @endif

    {{-- Grid de posts --}}
    <div class="blog1-section-area">
        <div class="row">
            @forelse ($posts as $post)
                @php $firstCategory = $post->categories->first(); @endphp
                <div class="col-lg-6 col-md-6 mb-30">
                    <div class="blog-auhtor-boxarea">
                        <div class="img1">
                            <a href="{{ $post->url }}">
                                <img alt="" src="{{ RvMedia::getImageUrl($post->image, 'medium', false, RvMedia::getDefaultImage()) }}"
                                     alt="{{ $post->title }}"
                                     loading="lazy">
                            </a>
                        </div>
                        <div class="blog-position">
                            <a href="{{ $post->url }}" class="heading">{{ $post->title }}</a>
                            <div class="blog-content-area">
                                <ul>
                                    <li>
                                        <a href="#"><i class="fa-solid fa-calendar-days"></i> {{ $post->created_at->translatedFormat('d M, Y') }}</a>
                                    </li>
                                    @if ($firstCategory)
                                        <li>
                                            <a href="{{ $firstCategory->url }}"><i class="fa-solid fa-tag"></i> {{ $firstCategory->name }}</a>
                                        </li>
                                    @endif
                                </ul>
                                <p>{{ $post->excerpt }}</p>
                                <a href="{{ $post->url }}" class="learnmore">{{ __('Learn more') }} <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p>{{ __('No posts found.') }}</p>
                </div>
            @endforelse
        </div>

        @if ($posts->hasPages())
            <div class="row">
                <div class="col-12">
                    {!! $posts->withQueryString()->links(Theme::getThemeNamespace() . '::partials.custom-pagination') !!}
                </div>
            </div>
        @endif
    </div>
@endsection
