@include('template::partials.header')

<main class="main" id="main-section">
    @if (Theme::get('hasBreadcrumb', true))
        @include('template::partials.breadcrumb')
    @endif

    {!! Theme::content() !!}
</main>

@include('template::partials.footer')
