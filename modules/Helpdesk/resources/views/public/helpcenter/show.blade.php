<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $article->title }} - Centro de ayuda</title>
    @if($article->meta_description)
        <meta name="description" content="{{ $article->meta_description }}">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .article-body img { max-width: 100%; height: auto; }
        .article-body h2, .article-body h3 { margin-top: 1.5rem; }
    </style>
</head>
<body>
<div style="background:#90bb13;" class="py-3 px-0">
    <div class="container">
        <a href="{{ route('helpcenter.index') }}" class="text-white text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Volver al centro de ayuda
        </a>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold mb-3">{{ $article->title }}</h1>
                    <div class="d-flex gap-3 text-muted small mb-4">
                        @if($article->published_at)
                            <span><i class="fas fa-calendar me-1"></i> {{ $article->published_at->format('d/m/Y') }}</span>
                        @endif
                        <span><i class="fas fa-eye me-1"></i> {{ number_format($article->views_count) }} vistas</span>
                    </div>
                    <hr>
                    <div class="article-body mt-4">
                        {!! clean($article->content) !!
                    </div>
                </div>
            </div>
        </div>

        @if($related->isNotEmpty())
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom fw-bold">Articulos relacionados</div>
                    <div class="list-group list-group-flush">
                        @foreach($related as $item)
                            <a href="{{ route('helpcenter.show', $item->slug) }}" class="list-group-item list-group-item-action small py-3">
                                <i class="fas fa-book me-2 text-muted"></i>{{ $item->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
</body>
</html>
