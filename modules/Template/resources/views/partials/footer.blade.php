@if(\Modules\Core\Models\Setting::get('cookie.enabled') === '1')
    @include('cookie::index')
    <link rel="stylesheet" href="{{ url('modules/Cookie/css/cookie-consent.css') }}">
    <script src="{{ url('modules/Cookie/js/cookie-consent.js') }}"></script>
@endif

<footer class="footer bg-light border-top mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0 text-muted">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                </p>
            </div>
            <div class="col-md-6 text-end">
                <p class="mb-0 text-muted">
                    Powered by <a href="#" class="text-decoration-none">Alsernet</a>
                </p>
            </div>
        </div>
    </div>
</footer>
