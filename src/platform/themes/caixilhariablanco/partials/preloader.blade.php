@if (theme_option('preloader_enabled', 'no') == 'yes')
    @if (theme_option('preloader_version', 'v1') == 'v1' && theme_option('preloader_image'))

        <!-- Preloader Start -->
        <div id="preloader-active">
            <div class="preloader d-flex align-items-center justify-content-center">
                <div class="preloader-inner position-relative">
                    <div class="text-center">
                        @if (theme_option('preloader_image') || theme_option('favicon'))
                            <img class="jump" src="{{ RvMedia::getImageUrl(theme_option('preloader_image') ?: theme_option('favicon')) }}" alt="logo">
                        @endif
                        <h5 class="mb-5">{{ __('Now Loading') }}</h5>
                        <div class="loader">
                            <div class="bar bar1"></div>
                            <div class="bar bar2"></div>
                            <div class="bar bar3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif (theme_option('preloader_version', 'v1') == 'v2')
        <div class="preloader-v2" id="preloader-active">
            <div class="preloader-loading"></div>
        </div>
    @endif

@endif
