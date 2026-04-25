@include('template::partials.header')

<main class="main" id="main-section">
    @if (Theme::get('hasBreadcrumb', true))
        @include('template::partials.breadcrumb')
    @endif

    <div class="container">
        @yield('content')
    </div>
</main>

@include('template::partials.footer')
