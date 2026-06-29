@if (is_plugin_active('blog'))
    @php
        $tags = \Modules\Blog\Models\BlogTag::query()
            ->withCount(['posts' => fn($q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->orderByDesc('posts_count')
            ->limit($config['number_display'] ?? 15)
            ->get();
    @endphp
    @if ($tags->isNotEmpty())
    <div class="tags-area">
        <h3>{{ $config['name'] }}</h3>
        <ul>
            @foreach ($tags as $tag)
                <li><a href="{{ $tag->url }}">{{ $tag->name }}</a></li>
            @endforeach
        </ul>
    </div>
    @endif
@endif
