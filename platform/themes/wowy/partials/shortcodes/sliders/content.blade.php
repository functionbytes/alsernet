@php
    $subtitle = $slider->getMetaData('subtitle', true);
    $highlightText = $slider->getMetaData('highlight_text', true);
    $buttonText = $slider->getMetaData('button_text', true);
@endphp

@if($subtitle || $slider->title || $highlightText || $slider->description || ($buttonText && $slider->link))
    <div class="hero-slider-content-2">
        @if ($subtitle)
            <h4 class="animated">{!! BaseHelper::clean($subtitle) !!}</h4>
        @endif

        @if ($slider->title)
            <h2 class="animated fw-900">{!! BaseHelper::clean($slider->title) !!}</h2>
        @endif

        @if ($highlightText)
            <h1 class="animated fw-900 text-brand">{!! BaseHelper::clean($highlightText) !!}</h1>
        @endif

        @if ($slider->description)
            <p class="animated">{!! BaseHelper::clean($slider->description) !!}</p>
        @endif

        @if ($buttonText && $slider->link)
            <a class="animated btn btn-default " href="{{ url($slider->link) }}">{!! $buttonText !!} <i class="fa fa-arrow-right"></i> </a>
        @endif
    </div>
@endif
