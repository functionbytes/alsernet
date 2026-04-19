@if ($posts->isNotEmpty())
    <div class="row">
        @foreach ($posts as $post)
            @php $firstCategory = $post->categories->first(); @endphp
            <div class="col-12 mb-30">
                <div class="blog-auhtor-boxarea">
                    <div class="img1">
                        <a href="{{ $post->url }}">
                            <img alt="" src="{{ RvMedia::getImageUrl($post->image, 'medium', false, RvMedia::getDefaultImage()) }}"
                                 alt="{{ $post->title }}" loading="lazy">
                        </a>
                    </div>
                    <div class="blog-position">
                        <div class="blog-content-area">
                            <ul>
                                @if ($post->user)
                                    <li><a href="#"><i class="fa-solid fa-user"></i> {{ $post->user->name }}</a></li>
                                @endif
                                <li><a href="#"><i class="fa-solid fa-calendar-days"></i> {{ $post->created_at->translatedFormat('d M, Y') }}</a></li>
                                @if ($firstCategory)
                                    <li><a href="{{ $firstCategory->url }}"><i class="fa-solid fa-tag"></i> {{ $firstCategory->name }}</a></li>
                                @endif
                            </ul>
                            <a href="{{ $post->url }}" class="heading">{{ $post->title }}</a>
                            <div class="space16"></div>
                            <p>{{ $post->excerpt }}</p>
                            <div class="space24"></div>
                            <a href="{{ $post->url }}" class="header-btn1">{{ __('Learn more') }} <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($posts->hasPages())
        <div class="row mt-20">
            <div class="col-12">
                {!! $posts->withQueryString()->links(Theme::getThemeNamespace() . '::partials.custom-pagination') !!}
            </div>
        </div>
    @endif
@else
    <div class="row">
        <div class="col-12 text-center py-5">
            <p>{{ __('No posts found.') }}</p>
        </div>
    </div>
@endif
