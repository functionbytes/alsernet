@php
    use Modules\Blog\Models\BlogPost;

    $validLayouts = ['grid', 'list', 'sm', 'mask', 'framed', 'carousel'];
    $validOrderBy = ['published_at', 'views', 'title', 'created_at'];
    $validOrderDir = ['asc', 'desc'];

    $layout     = in_array($attrs['layout'] ?? 'grid', $validLayouts) ? ($attrs['layout'] ?? 'grid') : 'grid';
    $cols       = min(4, max(1, (int) ($attrs['cols'] ?? 3)));
    $colsTablet = min(4, max(1, (int) ($attrs['cols-tablet'] ?? 2)));
    $colsMobile = min(2, max(1, (int) ($attrs['cols-mobile'] ?? 1)));
    $source     = in_array($attrs['source'] ?? 'latest', ['latest', 'category', 'featured', 'manual'])
        ? ($attrs['source'] ?? 'latest') : 'latest';
    $category   = $attrs['category'] ?? null;
    $ids        = $attrs['ids'] ?? null;
    $limit      = min(24, max(1, (int) ($attrs['limit'] ?? 6)));
    $orderBy    = in_array($attrs['order-by'] ?? 'published_at', $validOrderBy)
        ? ($attrs['order-by'] ?? 'published_at') : 'published_at';
    $orderDir   = in_array($attrs['order-dir'] ?? 'desc', $validOrderDir)
        ? ($attrs['order-dir'] ?? 'desc') : 'desc';

    $showDate        = filter_var($attrs['show-date'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $showMeta        = filter_var($attrs['show-meta'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $showDescription = filter_var($attrs['show-description'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $showButton      = filter_var($attrs['show-button'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $buttonText      = $attrs['button-text'] ?? __('shortcode::shortcode.blog_posts.read_more');
    $carousel        = $layout === 'carousel' || filter_var($attrs['carousel'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $autoplay        = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $autoplaySpeed   = (int) ($attrs['autoplay-speed'] ?? 5000);
    $extraClass      = $attrs['class'] ?? null;

    $colDesktop = (int) (12 / max($cols, 1));
    $colTablet  = (int) (12 / max($colsTablet, 1));
    $colMobile  = (int) (12 / max($colsMobile, 1));

    $postClass = collect([
        'post',
        $layout === 'list' ? 'post-list overlay-dark' : null,
        $layout === 'sm' ? 'post-sm overlay-zoom' : null,
        $layout === 'mask' ? 'post-mask gradient' : null,
        $layout === 'framed' ? 'post-framed' : null,
        $layout === 'grid' ? 'text-center overlay-zoom overlay-dark' : null,
    ])->filter()->implode(' ');

    // Query Blog posts (eager load to avoid N+1)
    $posts = BlogPost::query()
        ->published()
        ->with(['user:id,name', 'categories:id,slug,name'])
        ->withCount('comments')
        ->when(
            $source === 'category' && ! empty($category),
            fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('slug', $category))
        )
        ->when($source === 'featured', fn ($q) => $q->featured())
        ->when(
            $source === 'manual' && ! empty($ids),
            fn ($q) => $q->whereIn('id', array_map('intval', explode(',', $ids)))
        )
        ->orderBy($orderBy, $orderDir)
        ->limit($limit)
        ->get();
@endphp

@if ($posts->isNotEmpty())
    <div class="blog-posts blog-posts-{{ $layout }}{{ $extraClass ? ' '.$extraClass : '' }}">
        <div class="{{ $carousel ? 'owl-carousel owl-theme' : 'row' }}"
             @if ($carousel)
                 data-owl-options="{{ json_encode([
                     'items' => $cols,
                     'loop' => $posts->count() > $cols,
                     'autoplay' => $autoplay,
                     'autoplayTimeout' => $autoplaySpeed,
                     'nav' => true,
                     'dots' => false,
                     'responsive' => [
                         '0'   => ['items' => $colsMobile],
                         '768' => ['items' => $colsTablet],
                         '992' => ['items' => $cols],
                     ],
                 ]) }}"
             @endif>

            @foreach ($posts as $post)
                <div class="{{ $carousel ? '' : 'col-'.$colMobile.' col-md-'.$colTablet.' col-lg-'.$colDesktop }}">
                    <div class="{{ $postClass }}">
                        <figure class="post-media">
                            <a href="{{ $post->url }}">
                                @if ($post->image)
                                    <img src="{{ $post->image }}" width="380" height="200"
                                         alt="{{ e($post->title) }}" loading="lazy">
                                @endif
                            </a>
                            @if ($showDate && $post->published_at && $layout !== 'mask')
                                <div class="post-calendar">
                                    <span class="post-day">{{ $post->published_at->format('d') }}</span>
                                    <span class="post-month">{{ strtoupper($post->published_at->format('M')) }}</span>
                                </div>
                            @endif
                        </figure>

                        <div class="post-details">
                            @if ($showMeta)
                                <div class="post-meta">
                                    @if ($post->published_at)
                                        <a class="post-date">{{ $post->published_at->isoFormat('D MMM YYYY') }}</a>
                                    @endif
                                    @if ($post->comments_count > 0)
                                        | <a class="post-comment">
                                            <span>{{ $post->comments_count }}</span>
                                            {{ __('shortcode::shortcode.blog_posts.comments') }}
                                        </a>
                                    @endif
                                </div>
                            @endif

                            <h4 class="post-title">
                                <a href="{{ $post->url }}">{{ $post->title }}</a>
                            </h4>

                            @if ($showDescription && $post->excerpt)
                                <p class="post-content">{{ $post->excerpt }}</p>
                            @endif

                            @if ($showButton)
                                <a href="{{ $post->url }}"
                                   class="btn btn-link btn-underline btn-{{ $layout === 'mask' ? 'white' : 'dark' }}">
                                    {{ $buttonText }}<i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($carousel)
        @once
        @push('scripts')
        <script>
        $(document).ready(function () {
            $('.blog-posts .owl-carousel').each(function () {
                var opts = {};
                try { opts = JSON.parse($(this).attr('data-owl-options') || '{}'); } catch (e) {}
                $(this).owlCarousel(opts);
            });
        });
        </script>
        @endpush
        @endonce
    @endif
@endif
