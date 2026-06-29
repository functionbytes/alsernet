@include('template::partials.header')

<main class="main" id="main-section">
    <div class="blog-leftside-section-area sp1">
        <div class="container custom">
            <div class="row">
                <div class="col-lg-4">
                    <div class="blog-auhtor-side-area">
                        {!! dynamic_sidebar('primary_sidebar') !!}

                        @if (theme_option('phone'))
                            <div class="helping-area">
                                <h3>{{ __('If You Need Any Help Contact With Us') }}</h3>
                                <div class="btn-area">
                                    <a href="tel:{{ theme_option('phone') }}" class="header-btn1">
                                        <i class="fa-solid fa-phone-volume"></i>
                                        {{ theme_option('phone') }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @php
                            $socialLinks = theme_option('social_links') ? json_decode(theme_option('social_links'), true) : [];
                            $socialLinks = is_array($socialLinks) ? array_filter($socialLinks, fn($s) => !empty($s['url'])) : [];
                        @endphp
                        @if (count($socialLinks))
                            <div class="social-icons">
                                <h3>{{ __('Follow Us') }}</h3>
                                <ul>
                                    @foreach ($socialLinks as $social)
                                        <li>
                                            <a href="{{ $social['url'] }}" title="{{ $social['name'] ?? '' }}" target="_blank" rel="noopener">
                                                {!! BaseHelper::renderIcon($social['icon'] ?? 'fa-solid fa-link') !!}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-lg-8">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</main>
@include('template::partials.footer')
