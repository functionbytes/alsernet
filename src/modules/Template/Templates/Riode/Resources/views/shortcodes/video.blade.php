@php
    $validModes      = ['popup', 'inline', 'background'];
    $validTextColors = ['light', 'dark'];

    $src       = $attrs['src'] ?? '';
    $mode      = in_array($attrs['mode'] ?? 'inline', $validModes) ? ($attrs['mode'] ?? 'inline') : 'inline';
    $cover     = $attrs['cover'] ?? null;
    $coverAlt  = $attrs['cover-alt'] ?? '';
    $title     = $attrs['title'] ?? null;
    $subtitle  = $attrs['subtitle'] ?? null;
    $autoplay  = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $loop      = filter_var($attrs['loop'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $muted     = filter_var($attrs['muted'] ?? ($autoplay), FILTER_VALIDATE_BOOLEAN);
    $controls  = filter_var($attrs['controls'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $textColor = in_array($attrs['text-color'] ?? 'light', $validTextColors) ? ($attrs['text-color'] ?? 'light') : 'light';
    $width     = (int) ($attrs['width'] ?? 1843);
    $height    = (int) ($attrs['height'] ?? 606);
    $extraClass = $attrs['class'] ?? null;

    $isYouTube = str_contains($src, 'youtube.com') || str_contains($src, 'youtu.be');
    $isVimeo   = str_contains($src, 'vimeo.com');
    $isExternal = $isYouTube || $isVimeo;

    // YouTube/Vimeo en mode=background → degradar a popup
    if ($isExternal && $mode === 'background') {
        $mode = 'popup';
    }

    $videoId = 'video-' . substr(md5(uniqid()), 0, 8);
@endphp

@if ($mode === 'popup')

    <div id="{{ $videoId }}"
         class="banner popup-video {{ $extraClass }}"
         @if ($cover) data-bg-image="{{ htmlspecialchars($cover, ENT_QUOTES) }}" @endif>
        <div class="banner-content text-center {{ $textColor === 'light' ? 'text-white' : '' }}">
            @if ($subtitle)
                <h4 class="banner-subtitle">{{ $subtitle }}</h4>
            @endif
            @if ($title)
                <h3 class="banner-title">{{ $title }}</h3>
            @endif
            <a class="btn-dark btn-iframe btn-play"
               href="{{ htmlspecialchars($src, ENT_QUOTES) }}"
               data-toggle="modal"
               data-target="#{{ $videoId }}-modal"
               aria-label="{{ __('Reproducir vídeo') }}">
                <i class="fas fa-play" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    {{-- Bootstrap 5 Modal para popup --}}
    <div class="modal fade video-modal"
         id="{{ $videoId }}-modal"
         tabindex="-1"
         aria-labelledby="{{ $videoId }}-modal-label"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark border-0">
                <div class="modal-header border-0 pb-0">
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="{{ __('Cerrar') }}"></button>
                </div>
                <div class="modal-body p-0">
                    @if ($isYouTube || $isVimeo)
                        <div class="video-iframe-wrapper">
                            <iframe src=""
                                    data-src="{{ htmlspecialchars($src, ENT_QUOTES) }}"
                                    frameborder="0"
                                    allow="autoplay; encrypted-media; fullscreen"
                                    allowfullscreen
                                    loading="lazy"
                                    id="{{ $videoId }}-iframe"></iframe>
                        </div>
                    @else
                        <video id="{{ $videoId }}-player"
                               width="100%"
                               controls
                               preload="none"
                               @if ($cover) poster="{{ htmlspecialchars($cover, ENT_QUOTES) }}" @endif>
                            <source src="{{ htmlspecialchars($src, ENT_QUOTES) }}" type="video/mp4">
                            {{ __('Tu navegador no soporta vídeo HTML5.') }}
                        </video>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        'use strict';

        var $modal   = $('#{{ $videoId }}-modal');
        var $trigger = $('#{{ $videoId }} .btn-play');

        // Aplicar imagen de fondo via data-bg-image
        var $banner = $('#{{ $videoId }}[data-bg-image]');
        if ($banner.length) {
            $banner.css('background-image', "url('" + $banner.data('bg-image') + "')");
        }

        // Abrir modal con Bootstrap 5
        $trigger.on('click', function (e) {
            e.preventDefault();
            var $iframe = $('#{{ $videoId }}-iframe');

            if ($iframe.length) {
                $iframe.attr('src', $iframe.data('src'));
            }

            $modal.modal('show');
        });

        // Pausar vídeo/iframe al cerrar
        $modal.on('hidden.bs.modal', function () {
            var $iframe = $(this).find('iframe');
            var $video  = $(this).find('video')[0];

            if ($iframe.length) {
                $iframe.attr('src', '');
            }

            if ($video) {
                $video.pause();
            }
        });
    }());
    </script>
    @endpush

@elseif ($mode === 'background')

    {{-- Modo background: autoplay + loop + muted (sin controles) --}}
    <div class="video-background-wrapper {{ $extraClass }}">
        <video class="video-background"
               @if ($autoplay) autoplay @endif
               @if ($loop) loop @endif
               muted
               playsinline
               preload="{{ $autoplay ? 'auto' : 'none' }}"
               width="{{ $width }}"
               height="{{ $height }}">
            <source src="{{ htmlspecialchars($src, ENT_QUOTES) }}" type="video/mp4">
        </video>
    </div>

@else

    {{-- mode=inline --}}
    @if ($isYouTube || $isVimeo)
        <div class="video-iframe-wrapper {{ $extraClass }}">
            <iframe src="{{ htmlspecialchars($src, ENT_QUOTES) }}"
                    frameborder="0"
                    allow="autoplay; encrypted-media; fullscreen"
                    allowfullscreen
                    loading="lazy"
                    title="{{ $title ?? '' }}"></iframe>
        </div>

        @once
        @push('styles')
        <style>
        .video-iframe-wrapper {
            position: relative;
            padding-top: 56.25%;
        }
        .video-iframe-wrapper iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }
        </style>
        @endpush
        @endonce

    @else
        <video class="video-inline {{ $extraClass }}"
               @if ($autoplay) autoplay @endif
               @if ($loop) loop @endif
               @if ($muted) muted @endif
               @if ($controls) controls @endif
               playsinline
               preload="{{ $autoplay ? 'auto' : 'none' }}"
               @if ($cover) poster="{{ htmlspecialchars($cover, ENT_QUOTES) }}" @endif
               width="{{ $width }}"
               height="{{ $height }}">
            <source src="{{ htmlspecialchars($src, ENT_QUOTES) }}" type="video/mp4">
            {{ __('Tu navegador no soporta vídeo HTML5.') }}
        </video>
    @endif

@endif
