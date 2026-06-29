<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Centro de ayuda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .helpcenter-hero { background: #90bb13; color: #fff; padding: 60px 0 40px; }
        .helpcenter-hero input { border: none; border-radius: 30px; padding: 12px 20px; font-size: 1rem; }
        .article-card { transition: box-shadow .2s; }
        .article-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.1); }
    </style>
</head>
<body>
<div class="helpcenter-hero">
    <div class="container">
        <h1 class="fw-bold mb-2">Centro de ayuda</h1>
        <p class="mb-4 opacity-75">Encuentra respuestas rapidas a tus preguntas</p>
        <form action="{{ route('helpcenter.index') }}" method="GET" class="mb-0">
            <div class="input-group" style="max-width:520px;">
                <input type="text" name="q" class="form-control" placeholder="Busca un articulo..." value="{{ request('q') }}">
                <button class="btn btn-dark px-4" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="container py-5">
    @if(request()->filled('q'))
        <h5 class="mb-4 text-muted">Resultados para: <strong>{{ request('q') }}</strong></h5>
    @endif

    @if($articles->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <p class="text-muted">No se encontraron articulos.</p>
            @if(request()->filled('q'))
                <a href="{{ route('helpcenter.index') }}" class="btn btn-outline-secondary btn-sm">Ver todos los articulos</a>
            @endif
        </div>
    @else
        <div class="row g-4">
            @foreach($articles as $article)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('helpcenter.show', $article->slug) }}" class="text-decoration-none text-dark">
                        <div class="card article-card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold mb-2">{{ $article->title }}</h6>
                                @if($article->excerpt)
                                    <p class="text-muted small mb-3">{{ \Str::limit($article->excerpt, 120) }}</p>
                                @else
                                    <p class="text-muted small mb-3">{{ \Str::limit(strip_tags($article->content), 120) }}</p>
                                @endif
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="fas fa-eye"></i> {{ number_format($article->views_count) }}
                                </div>
                            </div>
                        </div>
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
</div>
</body>
</html>
