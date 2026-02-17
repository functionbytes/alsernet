<div class="col-lg-4 col-md-6">
    <div class="widget-about font-md mb-md-5 mb-lg-0">
        @if (theme_option('address') || theme_option('phone') || theme_option('working_hours'))
            <h4 class="mb-10 fw-600 widget-title mb-30  wow fadeIn animated">{{ __('Contact') }}</h4>
            @if (theme_option('address'))
                <p class="wow fadeIn animated">
                    <strong class="d-inline-block">{{ __('Address') }}:</strong> {{ theme_option('address') }}
                </p>
            @endif
            @if (theme_option('phone'))
                <p class="wow fadeIn animated">
                    <strong class="d-inline-block">{{ __('Phone') }}:</strong> {{ theme_option('phone') }}
                </p>
            @endif
                @if (theme_option('contact_email'))
                    <p class="wow fadeIn animated">
                        <strong class="d-inline-block">{{ __('Email') }}:</strong> {{ theme_option('contact_email') }}
                    </p>
                @endif
            @if (theme_option('working_hours'))
                <p class="wow fadeIn animated">
                    <strong class="d-inline-block">{{ __('Working hours') }}:</strong> {{ theme_option('working_hours') }}
                </p>
            @endif
        @endif
            @if (($socialLinks = theme_option('social_links')) && $socialLinks = json_decode($socialLinks, true))
            <h4 class="mb-10 mt-20 fw-600 wow fadeIn animated">{{ __('Follow Us') }}</h4>
            <div class="mobile-social-icon wow fadeIn animated mb-sm-5 mb-md-0">
                @foreach($socialLinks as $socialLink)
                    @if (count($socialLink) == 4 && isset($socialLink[0]['value']) && isset($socialLink[1]['value']) && isset($socialLink[2]['value']) && isset($socialLink[3]['value']))
                        <a href="{{ $socialLink[2]['value'] }}"
                           title="{{ $socialLink[0]['value'] }}" style="background-color: {{ $socialLink[3]['value'] }}; border: 1px solid {{ $socialLink[3]['value'] }};">
                            {!! BaseHelper::renderIcon($socialLink[1]['value']) !!}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
