<div class="search-boxarea">
    <h3>{{ $config['name'] }}</h3>
    <form action="{{ route('blog.public.search') }}" method="GET">
        <input type="text" name="q" value="{{ request()->query('q', '') }}" placeholder="{{ __('Search here...') }}">
        <button type="submit" aria-label="{{ __('Search') }}"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
    </form>
</div>
