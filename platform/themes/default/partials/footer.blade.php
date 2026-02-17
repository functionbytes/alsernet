<footer class="theme-footer bg-dark text-light py-5 mt-5 border-top">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5 style="color: #90bb13;">{{ config('app.name') }}</h5>
                <p class="text-muted">{{ theme_trans('about_us_description') }}</p>
            </div>

            <div class="col-md-4">
                <h5>{{ theme_trans('quick_links') }}</h5>
                <ul class="list-unstyled">
                    <li><a href="{{ url('/') }}" class="text-decoration-none text-light">{{ theme_trans('home') }}</a></li>
                    <li><a href="{{ url('/about') }}" class="text-decoration-none text-light">{{ theme_trans('about') }}</a></li>
                    <li><a href="{{ url('/contact') }}" class="text-decoration-none text-light">{{ theme_trans('contact') }}</a></li>
                </ul>
            </div>

            <div class="col-md-4">
                <h5>{{ theme_trans('contact_info') }}</h5>
                <p class="text-muted mb-1">
                    <i class="fas fa-envelope"></i> <a href="mailto:info@example.com" class="text-decoration-none text-light">info@example.com</a>
                </p>
                <p class="text-muted">
                    <i class="fas fa-phone"></i> +1 (555) 000-0000
                </p>
            </div>
        </div>

        <hr class="bg-secondary">

        <div class="row">
            <div class="col-12 text-center text-muted">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ theme_trans('all_rights_reserved') }}</p>
            </div>
        </div>
    </div>
</footer>
