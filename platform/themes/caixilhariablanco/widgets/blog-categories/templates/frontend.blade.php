@php
    $categories = \Modules\Blog\Models\BlogCategory::published()
        ->withCount(['posts' => fn($q) => $q->published()])
        ->ordered()
        ->limit($config['number_display'] ?? 10)
        ->get();
@endphp
@if ($categories->isNotEmpty())
<div class="blog-author-list">
    <h3>{{ $config['name'] }}</h3>
    <ul>
        @foreach ($categories as $category)
            <li>
                <a href="{{ $category->url }}">
                    {{ $category->name }}
                    @if ($category->posts_count)
                        ({{ $category->posts_count }})
                    @endif
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endif
