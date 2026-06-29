@extends('layouts.theme')

@section('title', $post->seo_title ?? $post->title)

@push('meta')
    <meta property="og:title" content="{{ $post->seo_title ?? $post->title }}">
    <meta property="og:description" content="{{ $post->seo_description ?? $post->excerpt }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $post->image ?? config('Seo.default_og_image') }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
    <meta property="article:author" content="{{ $post->user?->name }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $post->seo_title ?? $post->title }}">
    <meta name="twitter:description" content="{{ $post->seo_description ?? $post->excerpt }}">
    <meta name="twitter:image" content="{{ $post->image ?? config('Seo.default_og_image') }}">
@endpush

@section('content')
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('blog.public.index') }}">Blog</a></li>
            <li class="breadcrumb-item active text-truncate" style="max-width:300px;" aria-current="page">
                {{ $post->title }}
            </li>
        </ol>
    </nav>

    <div class="row g-4">
        {{-- Main content --}}
        <div class="col-md-8">
            <article class="card shadow-sm border-0">
                @if($post->image)
                    <img src="{{ $post->image }}" alt="{{ $post->title }}"
                         class="card-img-top img-fluid" style="max-height:420px; object-fit:cover;">
                @endif
                <div class="card-body">
                    {{-- Categories --}}
                    @if($post->categories->isNotEmpty())
                        <div class="mb-3 d-flex flex-wrap gap-1">
                            @foreach($post->categories as $category)
                                <a href="{{ $category->url }}"
                                   class="badge bg-primary-subtle text-primary text-decoration-none">
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <h1 class="h3 fw-bold mb-3">{{ $post->title }}</h1>

                    {{-- Meta --}}
                    <div class="d-flex flex-wrap gap-3 text-muted mb-4 pb-3 border-bottom">
                        @if($post->user)
                            <span><i class="fas fa-user me-1"></i>{{ $post->user->name }}</span>
                        @endif
                        <span><i class="fas fa-calendar me-1"></i>{{ $post->published_at?->format('d/m/Y') ?? $post->created_at->format('d/m/Y') }}</span>
                        <span><i class="fas fa-clock me-1"></i>{{ $post->reading_time }} min de lectura</span>
                        <span><i class="fas fa-eye me-1"></i>{{ number_format($post->views) }} vistas</span>
                    </div>

                    {{-- Content --}}
                    <div class="ck-content">
                        {!! clean_html($post->content) !!}
                    </div>

                    {{-- Tags --}}
                    @if($post->tags->isNotEmpty())
                        <div class="mt-4 pt-3 border-top">
                            <span class="text-muted me-2"><i class="fas fa-tags me-1"></i>Etiquetas:</span>
                            @foreach($post->tags as $tag)
                                <a href="{{ $tag->url }}"
                                   class="badge bg-secondary text-decoration-none me-1">{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>

            {{-- Related posts --}}
            @if($related->isNotEmpty())
                <div class="mt-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Posts relacionados</h6>
                    <div class="row g-3">
                        @foreach($related as $relatedPost)
                            <div class="col-sm-4">
                                <div class="card h-100 border-0 shadow-sm">
                                    <a href="{{ $relatedPost->url }}">
                                        @if($relatedPost->image)
                                            <img src="{{ $relatedPost->image }}" alt="{{ $relatedPost->title }}"
                                                 class="card-img-top" style="height:130px; object-fit:cover;">
                                        @else
                                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                                 style="height:130px;">
                                                <i class="fas fa-image fa-2x text-muted opacity-50"></i>
                                            </div>
                                        @endif
                                    </a>
                                    <div class="card-body p-2">
                                        <p class="small fw-semibold mb-1">
                                            <a href="{{ $relatedPost->url }}" class="text-body text-decoration-none">
                                                {{ $relatedPost->title }}
                                            </a>
                                        </p>
                                        <div class="d-flex gap-2 text-muted" style="font-size:.75rem;">
                                            <span>{{ $relatedPost->published_at?->format('d/m/Y') ?? $relatedPost->created_at->format('d/m/Y') }}</span>
                                            <span><i class="fas fa-clock me-1"></i>{{ $relatedPost->reading_time }} min</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-md-4">
            {{-- Categories sidebar --}}
            @if($post->categories->isNotEmpty())
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Categorías</h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($post->categories as $category)
                                <li class="mb-2">
                                    <a href="{{ $category->url }}" class="text-decoration-none text-body">
                                        <i class="fas fa-folder-open me-2 text-primary"></i>{{ $category->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Tags sidebar --}}
            @if($post->tags->isNotEmpty())
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Tags</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($post->tags as $tag)
                                <a href="{{ $tag->url }}"
                                   class="badge bg-secondary-subtle text-secondary text-decoration-none">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
