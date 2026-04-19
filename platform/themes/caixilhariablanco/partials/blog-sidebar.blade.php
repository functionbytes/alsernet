@php
    use Modules\Blog\Models\BlogCategory;
    use Modules\Blog\Models\BlogPost;
    use Modules\Blog\Models\BlogTag;

    $sidebarCategories = BlogCategory::query()
        ->where('status', 'published')
        ->withCount(['posts' => fn ($q) => $q->published()])
        ->orderBy('name')
        ->limit(8)
        ->get();

    $recentPosts = BlogPost::query()
        ->published()
        ->recent()
        ->limit(4)
        ->get();

    $sidebarTags = BlogTag::query()
        ->where('status', 'published')
        ->withCount(['posts' => fn ($q) => $q->published()])
        ->having('posts_count', '>', 0)
        ->orderByDesc('posts_count')
        ->limit(12)
        ->get();
@endphp

<div class="blog-auhtor-side-area">
    {{-- Búsqueda --}}
    <div class="search-boxarea">
        <h3>{{ __('Search Post') }}</h3>
        <form action="{{ route('blog.public.search') }}" method="GET">
            <input type="text" name="q" placeholder="{{ __('Search here...') }}" value="{{ request('q') }}">
            <button type="submit" aria-label="{{ __('Search') }}"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
        </form>
    </div>

    {{-- Categorías --}}
    @if ($sidebarCategories->isNotEmpty())
        <div class="blog-author-list">
            <h3>{{ __('Our Categories') }}</h3>
            <ul>
                @foreach ($sidebarCategories as $cat)
                    <li>
                        <a href="{{ $cat->url }}">
                            {{ $cat->name }}
                            @if ($cat->posts_count > 0)
                                <span class="count">({{ $cat->posts_count }})</span>
                            @endif
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Posts recientes --}}
    @if ($recentPosts->isNotEmpty())
        <div class="recent-posts-area">
            <h3>{{ __('Recent Posts') }}</h3>
            @foreach ($recentPosts as $recent)
                <div class="recent-single-posts{{ $loop->last ? ' last' : '' }}">
                    <div class="img1">
                        <a href="{{ $recent->url }}">
                            <img alt="" src="{{ RvMedia::getImageUrl($recent->image, 'thumb', false, RvMedia::getDefaultImage()) }}"
                                 alt="{{ $recent->title }}"
                                 loading="lazy">
                        </a>
                    </div>
                    <div class="content">
                        <a href="#"><i class="fa-solid fa-calendar-days"></i> {{ $recent->created_at->translatedFormat('d M, Y') }}</a>
                        <a href="{{ $recent->url }}" class="heading">{{ $recent->title }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Tags --}}
    @if ($sidebarTags->isNotEmpty())
        <div class="tags-area">
            <h3>{{ __('Our Tags') }}</h3>
            @foreach ($sidebarTags->chunk(3) as $tagGroup)
                <ul>
                    @foreach ($tagGroup as $tag)
                        <li><a href="{{ $tag->url }}">{{ $tag->name }}</a></li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    @endif
</div>
