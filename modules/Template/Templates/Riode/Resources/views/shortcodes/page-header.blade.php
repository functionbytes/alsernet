@php
    $validLayouts     = ['centered', 'inline', 'start'];
    $validBackgrounds = ['dark', 'grey', 'image', 'none'];
    $validTextColors  = ['light', 'dark'];
    $validMinHeights  = ['sm', 'md', 'lg'];

    $title       = $attrs['title'] ?? '';
    $subtitle    = $attrs['subtitle'] ?? null;
    $layout      = in_array($attrs['layout'] ?? 'centered', $validLayouts) ? ($attrs['layout'] ?? 'centered') : 'centered';
    $background  = in_array($attrs['background'] ?? 'dark', $validBackgrounds) ? ($attrs['background'] ?? 'dark') : 'dark';
    $bgImage     = $attrs['background-image'] ?? null;
    $textColor   = in_array($attrs['text-color'] ?? 'light', $validTextColors) ? ($attrs['text-color'] ?? 'light') : 'light';
    $minHeight   = in_array($attrs['min-height'] ?? 'md', $validMinHeights) ? ($attrs['min-height'] ?? 'md') : 'md';
    $extraClass  = $attrs['class'] ?? null;

    // Degradar background=image si no hay imagen
    if ($background === 'image' && empty($bgImage)) {
        $background = 'dark';
    }

    $crumbsRaw = $attrs['breadcrumb'] ?? null;
    $crumbs = [];
    if ($crumbsRaw) {
        $crumbs = is_string($crumbsRaw) ? (json_decode($crumbsRaw, true) ?? []) : (array) $crumbsRaw;
        $crumbs = is_array($crumbs) ? $crumbs : [];
    }

    $classes = collect([
        'page-header',
        $background === 'dark'  ? 'dark-section'  : null,
        $background === 'grey'  ? 'grey-section'  : null,
        $layout === 'inline' ? 'bread-inline breadcrumb-resize-padding' : null,
        $layout === 'start'  ? 'align-items-start breadcrumb-resize-padding' : null,
        $textColor === 'light' ? 'text-white' : null,
        'page-header-' . $minHeight,
        $extraClass,
    ])->filter()->implode(' ');
@endphp

<div class="{{ $classes }}"
     @if ($background === 'image' && $bgImage) data-bg-image="{{ htmlspecialchars($bgImage, ENT_QUOTES) }}" @endif>

    @if ($background === 'image' && $bgImage)
        <figure class="page-header-bg">
            <img src="{{ htmlspecialchars($bgImage, ENT_QUOTES) }}"
                 alt="{{ htmlspecialchars($title, ENT_QUOTES) }}"
                 loading="eager">
        </figure>
    @endif

    <h2 class="page-title">{{ $title }}</h2>

    @if ($subtitle)
        <p class="page-subtitle">{{ $subtitle }}</p>
    @endif

    @if (! empty($crumbs))
        <ul class="breadcrumb">
            @foreach (array_values($crumbs) as $index => $item)
                @php $isLast = $index === count($crumbs) - 1; @endphp
                <li @if($isLast) class="active" aria-current="page" @endif>
                    @if (! $isLast && ! empty($item['url']))
                        <a href="{{ htmlspecialchars($item['url'], ENT_QUOTES) }}">{{ $item['label'] }}</a>
                    @else
                        {{ $item['label'] }}
                    @endif
                </li>
                @if (! $isLast)
                    <li class="delimiter" aria-hidden="true">/</li>
                @endif
            @endforeach
        </ul>
    @endif

</div>

@if ($background === 'image' && $bgImage)
    @push('scripts')
    <script>
    $(function () {
        $('[data-bg-image]').each(function () {
            var url = $(this).data('bg-image');
            if (url) {
                this.style.backgroundImage = "url('" + url + "')";
            }
        });
    });
    </script>
    @endpush
@endif
