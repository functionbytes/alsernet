@include('template::partials.header')

<main class="main" id="main-section">
    <section class="mt-60 mb-60">
        <div class="container custom">
                @yield('content')
        </div>
    </section>
</main>
@include('template::partials.footer')


