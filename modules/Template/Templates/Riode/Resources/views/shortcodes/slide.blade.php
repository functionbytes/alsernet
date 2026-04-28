@php
    $validTextAligns = ['left', 'center', 'right'];
    $validTextColors = ['dark', 'light'];
    $validVerticalPositions = ['top', 'middle', 'bottom'];
    $validButtonStyles = ['primary', 'dark', 'outline-white', 'outline-dark'];

    $bgColor = ! empty($attrs['bg-color']) ? $attrs['bg-color'] : null;
    $image = $attrs['image'] ?? null;
    $imageAlt = $attrs['image-alt'] ?? ($attrs['title'] ?? '');
    $title = $attrs['title'] ?? null;
    $subtitle = $attrs['subtitle'] ?? null;
    $description = $attrs['description'] ?? null;
    $buttonText = $attrs['button-text'] ?? null;
    $buttonHref = $attrs['button-href'] ?? null;
    $buttonStyle = in_array($attrs['button-style'] ?? 'dark', $validButtonStyles) ? ($attrs['button-style'] ?? 'dark') : 'dark';
    $textAlign = in_array($attrs['text-align'] ?? 'left', $validTextAligns) ? ($attrs['text-align'] ?? 'left') : 'left';
    $textColor = in_array($attrs['text-color'] ?? 'dark', $validTextColors) ? ($attrs['text-color'] ?? 'dark') : 'dark';
    $verticalPosition = in_array($attrs['vertical-position'] ?? 'middle', $validVerticalPositions) ? ($attrs['vertical-position'] ?? 'middle') : 'middle';

    $animationsRaw = $attrs['animations'] ?? null;
    $animations = [];
    if ($animationsRaw) {
        $decoded = json_decode($animationsRaw, true);
        $animations = is_array($decoded) ? $decoded : [];
    }

    $contentClasses = collect([
        'banner-content',
        $verticalPosition === 'middle' ? 'y-50' : null,
        $verticalPosition === 'bottom' ? 'y-bottom' : null,
        $textAlign === 'center' ? 'text-center' : null,
        $textAlign === 'right' ? 'text-right' : null,
        $textColor === 'light' ? 'text-white' : null,
    ])->filter()->implode(' ');

    $btnClasses = collect([
        'btn',
        'slide-animate',
        'appear-animate',
        $buttonStyle === 'primary' ? 'btn-primary' : null,
        $buttonStyle === 'dark' ? 'btn-dark' : null,
        $buttonStyle === 'outline-white' ? 'btn-outline btn-white' : null,
        $buttonStyle === 'outline-dark' ? 'btn-outline btn-dark' : null,
    ])->filter()->implode(' ');

    $extraClass = ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : '';
@endphp

<div class="banner banner-fixed slide-item {{ $extraClass }}"
     @if($bgColor) data-bg-color="{{ htmlspecialchars($bgColor) }}" @endif>

    @if(! empty($image))
        <figure>
            <img src="{{ htmlspecialchars($image) }}"
                 alt="{{ htmlspecialchars($imageAlt) }}"
                 width="1180"
                 height="444"
                 loading="eager">
        </figure>
    @endif

    <div class="container">
        <div class="{{ $contentClasses }}">
            <div class="slide-animate">

                @if(! empty($subtitle))
                    <h4 class="banner-subtitle text-uppercase slide-animate appear-animate mb-1"
                        @if(! empty($animations['subtitle']))
                            data-animation-options='{"name":"{{ $animations['subtitle'] }}","duration":"1s","delay":".3s"}'
                        @endif>
                        {{ $subtitle }}
                    </h4>
                @endif

                @if(! empty($title))
                    <h3 class="banner-title font-weight-bold slide-animate appear-animate"
                        @if(! empty($animations['title']))
                            data-animation-options='{"name":"{{ $animations['title'] }}","duration":"1s","delay":".5s"}'
                        @endif>
                        {!! nl2br(e($title)) !!}
                    </h3>
                @endif

                @if(! empty($description))
                    <p class="slide-animate appear-animate mb-4"
                       @if(! empty($animations['description']))
                           data-animation-options='{"name":"{{ $animations['description'] }}","duration":"1s","delay":".7s"}'
                       @endif>
                        {{ $description }}
                    </p>
                @endif

                @if(! empty($buttonText) && ! empty($buttonHref))
                    <a href="{{ htmlspecialchars($buttonHref) }}"
                       class="{{ $btnClasses }}"
                       @if(! empty($animations['button']))
                           data-animation-options='{"name":"{{ $animations['button'] }}","duration":"1s","delay":".9s"}'
                       @endif>
                        {{ $buttonText }}<i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
                    </a>
                @endif

            </div>
        </div>
    </div>
</div>
