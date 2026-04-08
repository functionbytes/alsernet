@extends('layouts.theme')

@section('title', 'Buscar en el blog')

@section('content')
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('blog.public.index') }}">Blog</a></li>
            <li class="breadcrumb-item active" aria-current="page">Buscar</li>
        </ol>
    </nav>

    <h4 class="fw-bold mb-4">Buscar en el blog</h4>

    {{-- Search form --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('blog.public.search') }}">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-start-0 ps-0"
                           placeholder="Buscar publicaciones..."
                           value="{{ request('q') }}"
                           autofocus>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Results --}}
    @if(!$query)
        <div class="text-center py-5 text-muted">
            <i class="fas fa-search fa-4x opacity-50 mb-4"></i>
            <h5>Ingresa un término para buscar</h5>
        </div>
    @elseif($posts->count() > 0)
        <div class="mb-3">
            <small class="text-muted">
                {{ $posts->total() }} {{ $posts->total() === 1 ? 'resultado' : 'resultados' }} para
                "<strong>{{ $query }}</strong>"
            </small>
        </div>

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
        <div class="text-center py-5 text-muted">
            <i class="fas fa-search fa-4x opacity-50 mb-4"></i>
            <h5>No se encontraron resultados para "{{ $query }}"</h5>
            <p class="small">Prueba con otras palabras clave o revisa la ortografía.</p>
            <a href="{{ route('blog.public.index') }}" class="btn btn-outline-secondary mt-2">
                Ver todas las publicaciones
            </a>
        </div>
    @endif
@endsection
