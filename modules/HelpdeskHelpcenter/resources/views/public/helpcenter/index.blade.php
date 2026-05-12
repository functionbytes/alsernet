<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Centro de ayuda</title>
    <meta name="description" content="Encuentra respuestas rápidas a tus preguntas en nuestro centro de ayuda.">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="Centro de ayuda">
    <meta property="og:description" content="Encuentra respuestas rápidas a tus preguntas.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="stylesheet" href="{{ themeAsset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('css/fontawesome.min.css') }}">
    <style>
        body { background: #f8f9fa; }
        .helpcenter-hero { background: #b10100; color: #fff; padding: 64px 0 48px; }
        .helpcenter-hero input { border: none; border-radius: 30px; padding: 12px 20px; font-size: 1rem; }
        .helpcenter-hero .btn { border-radius: 30px; }
        .article-card { transition: box-shadow .2s, transform .2s; border: 1px solid rgba(0, 0, 0, 0.05); }
        .article-card:hover { box-shadow: 0 4px 18px rgba(0, 0, 0, .1); transform: translateY(-2px); }
        .helpcenter-search { max-width: 560px; margin: 0 auto; }
        .category-link { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; color: #444; text-decoration: none; border-radius: .5rem; }
        .category-link:hover { background: #f1f3f5; color: #b10100; }
        .category-link i { color: #b10100; }
        .empty-illustration { font-size: 3rem; opacity: .25; }
    </style>
</head>
<body>

<header class="helpcenter-hero text-center">
    <div class="container">
        <h1 class="fw-bold mb-2">Centro de ayuda</h1>
        <p class="mb-4 opacity-75">Encuentra respuestas rápidas a tus preguntas</p>
        <form action="{{ route('public.helpcenter.index') }}" method="GET" class="mb-0">
            <div class="input-group helpcenter-search">
                <input type="text" name="q" class="form-control" placeholder="Busca un artículo..." value="{{ request('q') }}" autofocus>
                <button class="btn btn-dark px-4" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</header>

<main class="container py-5">
    <div class="row g-4">

        {{-- Sidebar de categorías --}}
        @if($categories->isNotEmpty())
            <aside class="col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom fw-bold">
                        <i class="far fa-folder-tree me-2 text-muted"></i> Categorías
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($categories as $category)
                            <a href="{{ route('public.helpcenter.index', ['category' => $category->slug]) }}"
                               class="category-link">
                                @if($category->icon)
                                    <i class="{{ $category->icon }}"></i>
                                @else
                                    <i class="far fa-folder"></i>
                                @endif
                                <span class="flex-grow-1">{{ $category->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>
        @endif

        <section class="{{ $categories->isNotEmpty() ? 'col-lg-9' : 'col-12' }}">

            @if(request()->filled('q'))
                <h5 class="mb-4 text-muted">
                    Resultados para: <strong>{{ request('q') }}</strong>
                    <a href="{{ route('public.helpcenter.index') }}" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="fas fa-times me-1"></i> Limpiar
                    </a>
                </h5>
            @endif

            @if($articles->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-3 empty-illustration">
                            <i class="fas fa-search"></i>
                        </div>
                        <h5 class="mb-2">No se encontraron artículos</h5>
                        <p class="text-muted mb-3">
                            @if(request()->filled('q'))
                                Prueba con otros términos de búsqueda
                            @else
                                Aún no hay artículos publicados
                            @endif
                        </p>
                        @if(request()->filled('q'))
                            <a href="{{ route('public.helpcenter.index') }}" class="btn btn-outline-secondary btn-sm">
                                Ver todos los artículos
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach($articles as $article)
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route('public.helpcenter.show', $article->slug) }}" class="text-decoration-none text-dark">
                                <article class="card article-card h-100">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-2">{{ $article->title }}</h6>
                                        @if($article->description)
                                            <p class="text-muted small mb-3">{{ Str::limit($article->description, 120) }}</p>
                                        @endif
                                        <div class="d-flex align-items-center gap-3 text-muted small mt-auto">
                                            <span><i class="fas fa-eye me-1"></i> {{ number_format($article->views_count) }}</span>
                                            @if($article->published_at)
                                                <span><i class="far fa-calendar me-1"></i> {{ $article->published_at->format('d/m/Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            </a>
                        </div>
                    @endforeach
                </div>

                @if($articles->hasPages())
                    <div class="mt-5 d-flex justify-content-center">
                        {{ $articles->appends(request()->query())->links() }}
                    </div>
                @endif
            @endif

        </section>

    </div>
</main>

</body>
</html>
