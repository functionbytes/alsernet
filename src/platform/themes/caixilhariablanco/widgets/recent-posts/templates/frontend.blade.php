@php
    $recentPosts = \Modules\Blog\Models\BlogPost::published()
        ->orderByDesc('published_at')
        ->limit($config['number_display'] ?? 4)
        ->get();
@endphp
@if ($recentPosts->isNotEmpty())
<div class="recent-posts-area">
    <h3>{{ $config['name'] }}</h3>
    @foreach ($recentPosts as $post)
        <div class="recent-single-posts" @if ($loop->last) style="padding-bottom:0;border:none" @endif>
            <div class="img1">
                <img alt="" src="{{ RvMedia::getImageUrl($post->image, 'thumb', false, RvMedia::getDefaultImage()) }}"
                     alt="{{ $post->title }}" loading="lazy">
            </div>
            <div class="content">
                <a href="#"><i class="fa-solid fa-calendar-days"></i> {{ $post->created_at->translatedFormat('d M, Y') }}</a>
                <a href="{{ $post->url }}" class="heading">{{ $post->title }}</a>
            </div>
        </div>
    @endforeach
</div>
@endif
