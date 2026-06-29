@php Theme::fireEventGlobalAssets(); @endphp

{!! Theme::partial('header') !!}

<main class="main page-503">
    <div class="container">
        <div class="row align-items-center text-center">
            <div class="col-lg-8 m-auto mt-50 mb-50">
                <h1 class="font-xxl text-brand">503</h1>
                <h2 class="mb-30">{{ __('Service Unavailable') }}</h2>
                <p class="font-lg text-grey-700 mb-30">{{ __('The site is temporarily offline for maintenance. Please check back soon.') }}</p>
                <a class="btn btn-default submit-auto-width font-xs hover-up" href="{{ BaseHelper::getHomepageUrl() }}">{{ __('Back To Home Page') }}</a>
            </div>
        </div>
    </div>
</main>

{!! Theme::partial('footer') !!}
