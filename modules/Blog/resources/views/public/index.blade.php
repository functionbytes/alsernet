@extends('layouts.theme')

@section('title', 'Blog')

@section('content')
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Blog</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Blog</h4>
        <a href="{{ route('blog.public.search') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-search me-1"></i> Buscar
        </a>
    </div>

    @if(isset($featuredPosts) && $featuredPosts->isNotEmpty())
        <div class="mb-5">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-star text-warning me-2"></i>Posts destacados
            </h5>
            <div class="row g-4">
                @foreach($featuredPosts as $post)
                    <div class="col-sm-6 col-md-4">
                        <div class="card h-100 shadow-sm border-warning border-2">
                            <a href="{{ $post->url }}">
                                @if($post->image)
                                    <img src="{{ $post->image }}" alt="{{ $post->title }}"
                                         class="card-img-top" style="height:200px; object-fit:cover;">
                                @else
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                         style="height:200px;">
                                        <i class="fas fa-image fa-3x text-muted opacity-50"></i>
                                    </div>
                                @endif
                            </a>
                            <div class="card-body d-flex flex-column">
                                @if($post->categories->isNotEmpty())
                                    <div class="mb-2 d-flex flex-wrap gap-1">
                                        @foreach($post->categories as $category)
                                            <a href="{{ $category->url }}"
                                               class="badge bg-primary-subtle text-primary text-decoration-none">
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                                <h6 class="card-title fw-semibold mb-2">
                                    <a href="{{ $post->url }}" class="text-body text-decoration-none stretched-link">
                                        {{ $post->title }}
                                    </a>
                                </h6>
                                <p class="card-text text-muted flex-grow-1">
                                    {{ mb_substr(strip_tags($post->description ?: $post->content), 0, 150) }}{{ mb_strlen(strip_tags($post->description ?: $post->content)) > 150 ? '...' : '' }}
                                </p>
                                <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top small text-muted">
                                    <span><i class="fas fa-calendar me-1"></i>{{ $post->published_at?->format('d/m/Y') ?? $post->created_at->format('d/m/Y') }}</span>
                                    <span><i class="fas fa-eye me-1"></i>{{ number_format($post->views) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <hr class="mb-4">
    @endif

    @if($posts->count() > 0)
        <div class="row g-4">
            @foreach($posts as $post)
                <div class="col-sm-6 col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <a href="{{ $post->url }}">
                            @if($post->image)
                                <img src="{{ $post->image }}" alt="{{ $post->title }}"
                                     class="card-img-top" style="height:200px; object-fit:cover;">
                            @else
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                     style="height:200px;">
                                    <i class="fas fa-image fa-3x text-muted opacity-50"></i>
                                </div>
                            @endif
                        </a>
                        <div class="card-body d-flex flex-column">
                            {{-- Categories --}}
                            @if($post->categories->isNotEmpty())
                                <div class="mb-2 d-flex flex-wrap gap-1">
                                    @foreach($post->categories as $category)
                                        <a href="{{ $category->url }}"
                                           class="badge bg-primary-subtle text-primary text-decoration-none">
                                            {{ $category->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <h6 class="card-title fw-semibold mb-2">
                                <a href="{{ $post->url }}" class="text-body text-decoration-none stretched-link">
                                    {{ $post->title }}
                                </a>
                            </h6>

                            <p class="card-text text-muted flex-grow-1">
                                {{ mb_substr(strip_tags($post->description ?: $post->content), 0, 150) }}{{ mb_strlen(strip_tags($post->description ?: $post->content)) > 150 ? '...' : '' }}
                            </p>

                            <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top small text-muted">
                                <div class="d-flex align-items-center gap-2">
                                    @if($post->user)
                                        <span><i class="fas fa-user me-1"></i>{{ $post->user->name }}</span>
                                    @endif
                                    <span><i class="fas fa-calendar me-1"></i>{{ $post->published_at?->format('d/m/Y') ?? $post->created_at->format('d/m/Y') }}</span>
                                    <span><i class="fas fa-clock me-1"></i>{{ $post->reading_time }} min</span>
                                </div>
                                <span><i class="fas fa-eye me-1"></i>{{ number_format($post->views) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($posts->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $posts->withQueryString()->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-5">
            <i class="fas fa-newspaper fa-4x text-muted opacity-50 mb-4"></i>
            <h5 class="text-muted">No hay publicaciones disponibles</h5>
        </div>
    @endif
@endsection
