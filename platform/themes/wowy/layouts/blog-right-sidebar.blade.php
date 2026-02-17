

@include('template::partials.header')

<main class="main" id="main-section">
    <section class="mt-60 mb-60">
        <div class="container custom">
            <div class="row">
                <div class="col-lg-9">
                    @yield('content')
                </div>
                <div class="col-lg-3 primary-sidebar sticky-sidebar">
                    <div class="widget-area">
                        {!! dynamic_sidebar('primary_sidebar') !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@include('template::partials.footer')


