<aside class="theme-sidebar bg-light border-{{ get_sidebar_position() }}" style="border-width: 1px; min-height: 400px;">
    <div class="p-4">
        <h5 class="mb-3">{{ theme_trans('sidebar') }}</h5>

        <!-- Widget: Search -->
        <div class="mb-4">
            <form action="{{ url('/search') }}" method="GET">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" placeholder="{{ theme_trans('search') }}" name="q" required>
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Widget: Categories -->
        <div class="mb-4">
            <h6 class="mb-3">{{ theme_trans('categories') }}</h6>
            <ul class="list-unstyled">
                <li><a href="#" class="text-decoration-none">Category 1</a></li>
                <li><a href="#" class="text-decoration-none">Category 2</a></li>
                <li><a href="#" class="text-decoration-none">Category 3</a></li>
            </ul>
        </div>

        <!-- Widget: Recent Posts -->
        <div class="mb-4">
            <h6 class="mb-3">{{ theme_trans('recent_posts') }}</h6>
            <ul class="list-unstyled small">
                <li><a href="#" class="text-decoration-none">Recent post 1</a></li>
                <li><a href="#" class="text-decoration-none">Recent post 2</a></li>
                <li><a href="#" class="text-decoration-none">Recent post 3</a></li>
            </ul>
        </div>
    </div>
</aside>
