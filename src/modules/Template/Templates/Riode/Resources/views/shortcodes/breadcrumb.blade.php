@php
    $validSeparators  = ['slash', 'chevron', 'dot'];
    $validBackgrounds = ['grey', 'dark', 'none'];
    $validAligns      = ['left', 'center', 'right'];

    $separator   = in_array($attrs['separator'] ?? 'slash', $validSeparators) ? ($attrs['separator'] ?? 'slash') : 'slash';
    $background  = in_array($attrs['background'] ?? 'grey', $validBackgrounds) ? ($attrs['background'] ?? 'grey') : 'grey';
    $align       = in_array($attrs['align'] ?? 'left', $validAligns) ? ($attrs['align'] ?? 'left') : 'left';
    $showHomeIcon = filter_var($attrs['show-home-icon'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $extraClass  = $attrs['class'] ?? null;

    $raw   = $attrs['items'] ?? '[]';
    $items = is_string($raw) ? (json_decode($raw, true) ?? []) : (array) $raw;
    $items = array_filter($items, fn ($i) => ! empty($i['label']));

    $separatorChar = match ($separator) {
        'chevron' => '>',
        'dot'     => '·',
        default   => '/',
    };

    $wrapperClasses = collect([
        'breadcrumb',
        'default-breadcrumb',
        $background === 'grey' ? 'grey-section' : null,
        $background === 'dark' ? 'dark-section' : null,
        $align === 'center' ? 'justify-content-center' : null,
        $align === 'right' ? 'justify-content-end' : null,
        $extraClass,
    ])->filter()->implode(' ');
@endphp

<nav aria-label="{{ __('breadcrumb.nav_label', [], null) ?? 'Navegación' }}">
    <ul class="{{ $wrapperClasses }}">
        @foreach (array_values($items) as $index => $item)
            @php $isFirst = $index === 0; $isLast = $index === count($items) - 1; @endphp

            <li @if($isLast) class="active" aria-current="page" @endif>
                @if (! $isLast && ! empty($item['url']))
                    <a href="{{ htmlspecialchars($item['url'], ENT_QUOTES) }}">
                        @if ($isFirst && $showHomeIcon)
                            <i class="fas fa-house" aria-label="{{ $item['label'] }}"></i>
                        @else
                            {{ $item['label'] }}
                        @endif
                    </a>
                @else
                    @if ($isFirst && $showHomeIcon && ! $isLast)
                        <i class="fas fa-house" aria-label="{{ $item['label'] }}"></i>
                    @else
                        {{ $item['label'] }}
                    @endif
                @endif
            </li>

            @if (! $isLast)
                <li class="delimiter" aria-hidden="true">{{ $separatorChar }}</li>
            @endif
        @endforeach
    </ul>
</nav>
