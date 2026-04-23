@extends('template::layouts.blog-right-sidebar')

@section('title', __('Search') . ($query ? ': ' . $query : '') . ' | ' . theme_option('site_title', config('app.name')))

@section('seo_head')
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
    {{-- Breadcrumb --}}
    <div class="blog-page-header mb-25">
        <h4 class="mb-5">{{ __('Search results') }}@if ($query): <em>{{ $query }}</em>@endif</h4>
        <p class="text-muted mb-0" class="fs-13">
            <a href="{{ url('/') }}" class="text-brand">{{ __('Home') }}</a>
            <i class="fa-solid fa-angle-right mx-1"></i>
            <a href="{{ route('blog.public.index') }}" class="text-brand">{{ __('Blog') }}</a>
            <i class="fa-solid fa-angle-right mx-1"></i>
            <span>{{ __('Search') }}</span>
        </p>
    </div>

    {{-- Formulario de búsqueda --}}
    <div class="search-boxarea mb-30">
        <form action="{{ route('blog.public.search') }}" method="GET">
            <input type="text" name="q" placeholder="{{ __('Search here...') }}" value="{{ $query }}">
            <button type="submit" aria-label="{{ __('Search') }}"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
        </form>
    </div>

    @include(Theme::getThemeNamespace() . '::views.templates.posts', compact('posts'))
@endsection
