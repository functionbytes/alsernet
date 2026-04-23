@extends('template::layouts.blog-right-sidebar')

@section('title', $tag->name)

@section('content')
    {{-- Breadcrumb --}}
    <div class="blog-page-header mb-30">
        <h4 class="mb-5">{{ $tag->name }}</h4>
        <p class="text-muted mb-0" class="fs-13">
            <a href="{{ url('/') }}" class="text-brand">{{ __('Home') }}</a>
            <i class="fa-solid fa-angle-right mx-1"></i>
            <a href="{{ route('blog.public.index') }}" class="text-brand">{{ __('Blog') }}</a>
            <i class="fa-solid fa-angle-right mx-1"></i>
            <span>{{ $tag->name }}</span>
        </p>
    </div>

    @include(Theme::getThemeNamespace() . '::views.templates.posts', compact('posts'))
@endsection
