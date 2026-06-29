@php
    $hasChildren = $item->children->isNotEmpty();
    $isActive = $item->isActive() || $item->hasActiveChild();
    $activeClass = $isActive ? 'active' : '';
    $cssClass = trim("{$item->css_class} {$activeClass}");
@endphp

<li @if($cssClass) class="{{ $cssClass }}" @endif>
    <a href="{{ $item->full_url }}" target="{{ $item->target }}" @if($cssClass) class="{{ $cssClass }}" @endif>
        @if($item->icon)
            <i class="{{ $item->icon }}"></i>
        @endif
        {{ $item->title }}
    </a>

    @if($hasChildren)
        <ul>
            @foreach($item->children as $child)
                @include('template::components.menu-item', ['item' => $child])
            @endforeach
        </ul>
    @endif
</li>
