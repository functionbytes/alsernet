@include('template::partials.header')

<main class="main" id="main-section">
    @if (Theme::get('hasBreadcrumb', true))
        @include('template::partials.breadcrumb')
    @endif

    <section class="mt-60 mb-60">
        <div class="container custom">
                {!! Theme::content() !!}
        </div>
    </section>
</main>
@include('template::partials.footer')
