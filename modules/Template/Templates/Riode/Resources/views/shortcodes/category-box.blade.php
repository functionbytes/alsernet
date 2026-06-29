@php
    $title = $attrs['title'] ?? '';
@endphp

<div class="category-box">
    <h6 class="category-name">{{ $title }}</h6>
    @foreach ($links as $link)
        <a href="{{ $link['url'] ?? '#' }}">{{ $link['label'] ?? '' }}</a>
    @endforeach
</div>
