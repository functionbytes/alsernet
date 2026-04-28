@php
    $layout   = in_array($attrs['layout'] ?? '', ['carousel', 'grid', 'masonry'], true) ? $attrs['layout'] : 'carousel';
    $cols     = is_numeric($attrs['cols'] ?? null) ? max(2, min(6, (int) $attrs['cols'])) : 5;
    $autoplay = filter_var($attrs['autoplay'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $showInfo = filter_var($attrs['show-info'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $gutter   = in_array($attrs['gutter'] ?? '', ['none', 'xs', 'sm', 'default'], true) ? $attrs['gutter'] : 'default';
    $figClass = 'instagram' . ($showInfo ? ' instagram-info' : '');

    $gutterClass = match ($gutter) {
        'none'    => 'gutter-no',
        'xs'      => 'gutter-xs',
        'sm'      => 'gutter-sm',
        default   => '',
    };
@endphp

@if ($placeholder ?? false)
    {{-- Token ausente (source=api) o imágenes no configuradas (source=manual) --}}
    <div class="instagram-feed-placeholder py-4 text-center text-muted">
        <i class="fab fa-instagram fa-2x mb-2" aria-hidden="true"></i>
        <p class="mb-0">
            @if (($attrs['source'] ?? 'manual') === 'api')
                Configura <code>INSTAGRAM_ACCESS_TOKEN</code> en el archivo <code>.env</code> para activar el feed en tiempo real.
            @else
                Añade el atributo <code>images</code> con un JSON array o usa <code>source="api"</code>.
            @endif
        </p>
    </div>
@elseif (!empty($items))

    @if ($layout === 'carousel')
        @php
            $owlOptions = json_encode([
                'autoplay'        => $autoplay,
                'autoplayTimeout' => 5000,
                'loop'            => true,
                'margin'          => 0,
                'responsive'      => [
                    '0'   => ['items' => 2],
                    '576' => ['items' => 3],
                    '768' => ['items' => 4],
                    '992' => ['items' => $cols],
                ],
            ]);
        @endphp
        <div class="owl-carousel owl-theme {{ $gutterClass }}"
             data-owl-options='{!! $owlOptions !!}'>
            @foreach ($items as $item)
                <figure class="{{ $figClass }}">
                    <a href="{{ $item['link'] ?? '#' }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $item['url'] }}"
                             alt="{{ $item['alt'] ?? 'Instagram' }}"
                             width="220" height="220"
                             loading="lazy">
                    </a>
                </figure>
            @endforeach
        </div>

    @elseif ($layout === 'grid')
        <div class="row cols-{{ $cols }} {{ $gutterClass }}">
            @foreach ($items as $item)
                <figure class="{{ $figClass }}">
                    <a href="{{ $item['link'] ?? '#' }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $item['url'] }}"
                             alt="{{ $item['alt'] ?? 'Instagram' }}"
                             width="220" height="220"
                             loading="lazy">
                    </a>
                </figure>
            @endforeach
        </div>

    @else
        {{-- masonry --}}
        <div class="row grid instagram-masonry {{ $gutterClass }}">
            @foreach ($items as $item)
                @php
                    $size  = in_array($item['size'] ?? '', ['x1', 'x15', 'x2', 'x25'], true) ? $item['size'] : 'x1';
                    $colMd = $size === 'x2' ? 'col-md-6' : 'col-md-3 col-6';
                @endphp
                <div class="grid-item {{ $colMd }} height-{{ $size }}">
                    <figure class="{{ $figClass }}">
                        <a href="{{ $item['link'] ?? '#' }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $item['url'] }}"
                                 alt="{{ $item['alt'] ?? 'Instagram' }}"
                                 loading="lazy">
                        </a>
                    </figure>
                </div>
            @endforeach
            <div class="col-1 grid-space"></div>
        </div>
    @endif

@endif
