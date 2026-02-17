    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-3 mb-4">
                    <h5 class="fw-bold mb-3">{{ config('app.name') }}</h5>
                    <p class="text-muted">{{ theme_trans('about_us_description') }}</p>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="fw-bold mb-3">{{ theme_trans('quick_links') }}</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ url('/') }}" class="text-muted text-decoration-none">{{ theme_trans('home') }}</a></li>
                        <li><a href="{{ url('/about') }}" class="text-muted text-decoration-none">{{ theme_trans('about_us') }}</a></li>
                        <li><a href="{{ url('/contact') }}" class="text-muted text-decoration-none">{{ theme_trans('contact') }}</a></li>
                        <li><a href="{{ url('/privacidad') }}" class="text-muted text-decoration-none">{{ theme_trans('privacy') }}</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="fw-bold mb-3">{{ theme_trans('contact_info') }}</h5>
                    <p class="text-muted small mb-2">
                        <i class="fas fa-map-marker-alt"></i> Alsernet, Inc.
                    </p>
                    <p class="text-muted small mb-2">
                        <i class="fas fa-phone"></i> +1 (555) 000-0000
                    </p>
                    <p class="text-muted small">
                        <i class="fas fa-envelope"></i> info@alsernet.com
                    </p>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="fw-bold mb-3">{{ theme_trans('follow_us') }}</h5>
                    <div class="d-flex gap-2">
                        <a href="#" class="text-muted text-decoration-none"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-muted text-decoration-none"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted text-decoration-none"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-muted text-decoration-none"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0 small">
                        &copy; {{ date('Y') }} {{ config('app.name') }} - {{ theme_trans('all_rights_reserved') }}
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="text-muted mb-0 small">
                        <a href="{{ url('/terminos-y-condiciones') }}" class="text-muted text-decoration-none">{{ theme_trans('terms') }}</a> |
                        <a href="{{ url('/privacidad') }}" class="text-muted text-decoration-none">{{ theme_trans('privacy') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Wowy Custom Scripts -->
    <script src="{{ theme_asset('js/script.js') }}"></script>

    @yield('js')

    <!-- Custom Footer HTML from settings -->
    @php $customFooterHtml = setting('theme.custom_footer_html'); @endphp
    @if($customFooterHtml)
        {!! $customFooterHtml !!}
    @endif

    <!-- Custom Footer JS from settings -->
    @php $customFooterJs = setting('theme.custom_footer_js'); @endphp
    @if($customFooterJs)
        <script>{!! $customFooterJs !!}</script>
    @endif

    <div id="scrollUp">
        <i class="fas fa-arrow-up"></i>
    </div>
</body>
</html>
