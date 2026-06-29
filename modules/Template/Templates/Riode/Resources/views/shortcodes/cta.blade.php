@php
    $layout    = in_array($attrs['layout'] ?? '', ['promo','subscribe','one-col','2-cols','3-cols','parallax'])
        ? $attrs['layout']
        : 'promo';
    $background      = $attrs['background'] ?? 'white';
    $backgroundImage = $attrs['background-image'] ?? null;
    $align           = in_array($attrs['align'] ?? '', ['left','center','right']) ? $attrs['align'] : 'left';
    $title           = $attrs['title'] ?? null;
    $subtitle        = $attrs['subtitle'] ?? null;
    $description     = $attrs['description'] ?? null;
    $buttonText      = $attrs['button-text'] ?? null;
    $buttonHref      = $attrs['button-href'] ?? '#';
    $buttonStyle     = in_array($attrs['button-style'] ?? '', ['primary','dark','white-outline']) ? $attrs['button-style'] : 'primary';
    $formAction      = $attrs['form-action'] ?? null;
    $formPlaceholder = $attrs['form-placeholder'] ?? __('shortcode::shortcode.cta_form_placeholder');
    $formButton      = $attrs['form-button'] ?? __('shortcode::shortcode.cta_form_button');
    $showSocial      = filter_var($attrs['show-social'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $extraClass      = $attrs['class'] ?? null;

    $bgClass = match ($background) {
        'grey'  => 'bg-grey',
        'dark'  => 'bg-dark text-white',
        'image', 'parallax' => '',
        default => 'bg-white',
    };

    $wrapperClasses = collect([
        'banner',
        $layout === 'promo'     ? 'cta-simple' : null,
        $layout === 'subscribe' ? 'banner-newsletter' : null,
        $layout === 'one-col'   ? 'banner-one-col' : null,
        $layout === '2-cols'    ? 'banner-2 banner-fixed content-center content-middle' : null,
        $layout === '3-cols'    ? 'banner-group' : null,
        $layout === 'parallax'  ? 'banner-background parallax' : null,
        'text-' . $align,
        $extraClass,
    ])->filter()->implode(' ');
@endphp

<div class="{{ $wrapperClasses }}"
     @if ($backgroundImage) data-image-src="{{ $backgroundImage }}" data-parallax='{"speed":1.5,"horizontalPosition":"50%"}' @endif>

    @if ($layout === '3-cols')
        <div class="row g-0">
            {!! $content !!}
        </div>
    @else
        <div class="banner-content {{ $bgClass }} @if ($layout === 'promo') d-lg-flex align-items-center @endif">

            @if ($title || $subtitle)
                <div class="banner-header">
                    @if ($title)
                        <h3 class="banner-title font-weight-bold ls-s text-uppercase">{{ $title }}</h3>
                    @endif
                    @if ($subtitle)
                        <h4 class="banner-subtitle font-weight-normal ls-s">{{ $subtitle }}</h4>
                    @endif
                </div>
            @endif

            @if ($description)
                <div class="banner-text">
                    <p class="ls-m mb-0">{{ $description }}</p>
                </div>
            @endif

            @if (in_array($layout, ['subscribe','parallax']) && $formAction)
                <form action="{{ $formAction }}" method="POST" class="input-wrapper input-wrapper-round form-solid">
                    @csrf
                    <input type="email" name="email" class="form-control" placeholder="{{ $formPlaceholder }}" required>
                    <button type="submit" class="btn btn-primary btn-rounded">{{ $formButton }}</button>
                </form>
            @endif

            @if ($buttonText)
                <a href="{{ $buttonHref }}" class="btn btn-{{ $buttonStyle }} btn-ellipse">
                    {{ $buttonText }}<i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
                </a>
            @endif

            @if ($showSocial && $layout === 'parallax')
                {{-- Placeholder: integrar [social-links] shortcode cuando esté disponible --}}
            @endif

            @if (in_array($layout, ['promo','one-col','2-cols']) && $content)
                {!! $content !!}
            @endif
        </div>
    @endif

    <noscript>
        @if ($buttonText)
            <a href="{{ $buttonHref }}">{{ $buttonText }}</a>
        @endif
    </noscript>
</div>

@once
@push('scripts')
<script>
$(document).on('submit', '.banner-newsletter form, .banner-background form', function (e) {
    e.preventDefault();
    var $form = $(this);
    $.post($form.attr('action'), $form.serialize() + '&_token=' + $('meta[name="csrf-token"]').attr('content'))
        .done(function () {
            toastr.success('{{ __("shortcode::shortcode.cta_subscribe_success") }}');
            $form[0].reset();
        })
        .fail(function (xhr) {
            var msg = xhr.responseJSON?.message ?? '{{ __("shortcode::shortcode.cta_subscribe_error") }}';
            toastr.error(msg);
        });
});
</script>
@endpush
@endonce
