@php
    $title = $shortcode->title ?: __('Stay Updated!');
    $subtitle = $shortcode->subtitle ?: __('Subscribe');
    $description = $shortcode->description ?: __('Join our newsletter to get the latest news and updates');
    $showImage = $shortcode->show_image == 'yes';
    $image = $shortcode->image;
    $style = $shortcode->style ?: 'default';
    $newsletterForm = \Botble\Newsletter\Forms\Fronts\NewsletterForm::create();
@endphp

<div class="newsletter-inline-shortcode {{ $style }}-style">
    <div class="newsletter-inline-container">
        @if ($showImage && $image)
            <div class="newsletter-inline-image">
                {!! RvMedia::image($image, $title, 'newsletter-image') !!}
            </div>
        @endif

        <div class="newsletter-inline-content">
            @if ($subtitle)
                <span class="newsletter-inline-subtitle">{{ $subtitle }}</span>
            @endif

            @if ($title)
                <h3 class="newsletter-inline-title">{{ $title }}</h3>
            @endif

            @if ($description)
                <p class="newsletter-inline-description">{{ $description }}</p>
            @endif

            <div class="newsletter-inline-form">
                {!! $newsletterForm->setFormOption('class', 'bb-newsletter-inline-form')->renderForm() !!}
            </div>
        </div>
    </div>
</div>

