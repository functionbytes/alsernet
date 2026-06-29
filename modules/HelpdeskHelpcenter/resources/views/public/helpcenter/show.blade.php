<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $translation?->title ?? $article->title }} — Centro de ayuda</title>

    @php
        $metaDescription = $translation?->meta_description ?? $article->meta_description ?? Str::limit(strip_tags($article->body ?? $article->content ?? ''), 160);
        $metaKeywords = $translation?->meta_keywords ?? null;
        $articleTitle = $translation?->title ?? $article->title;
        $articleBody = $translation?->body ?? ($article->body ?? $article->content ?? '');
        $publishedAt = $translation?->published_at ?? $article->published_at;
    @endphp

    <meta name="description" content="{{ $metaDescription }}">
    @if($metaKeywords)
        <meta name="keywords" content="{{ $metaKeywords }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $articleTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($publishedAt)
        <meta property="article:published_time" content="{{ $publishedAt->toIso8601String() }}">
    @endif

    {{-- JSON-LD schema --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $articleTitle,
        'description' => $metaDescription,
        'datePublished' => $publishedAt?->toIso8601String(),
        'dateModified' => ($translation?->updated_at ?? $article->updated_at)?->toIso8601String(),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => url()->current(),
        ],
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <link rel="stylesheet" href="{{ themeAsset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ themeAsset('css/fontawesome.min.css') }}">
    <style>
        body { background: #f8f9fa; }
        .article-body img { max-width: 100%; height: auto; border-radius: .5rem; }
        .article-body h2, .article-body h3 { margin-top: 1.75rem; margin-bottom: .75rem; }
        .article-body p { line-height: 1.7; }
        .article-body code { background: #f1f3f5; padding: .2rem .4rem; border-radius: .25rem; font-size: .9em; }
        .article-body pre { background: #1e2329; color: #f8f9fa; padding: 1rem; border-radius: .5rem; overflow-x: auto; }
        .article-body pre code { background: transparent; color: inherit; padding: 0; }
        .helpcenter-topbar { background: #90bb13; }
        .helpcenter-vote-extra { display: none; }
        .helpcenter-breadcrumb a { color: rgba(255, 255, 255, .85); }
        .helpcenter-breadcrumb a:hover { color: #fff; }
    </style>
</head>
<body>

<div class="helpcenter-topbar py-3 px-0">
    <div class="container">
        <nav aria-label="breadcrumb" class="helpcenter-breadcrumb">
            <a href="{{ route('public.helpcenter.index') }}" class="text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Volver al centro de ayuda
            </a>
        </nav>
    </div>
</div>

<main class="container py-5">
    <div class="row">

        <article class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold mb-3">{{ $articleTitle }}</h1>

                    <div class="d-flex flex-wrap gap-3 text-muted small mb-4">
                        @if($publishedAt)
                            <span><i class="far fa-calendar me-1"></i> {{ $publishedAt->format('d/m/Y') }}</span>
                        @endif
                        <span><i class="fas fa-eye me-1"></i> {{ number_format($article->views_count) }} vistas</span>
                        @if($article->author)
                            <span><i class="far fa-user me-1"></i> {{ $article->author->name }}</span>
                        @endif
                    </div>

                    <hr>

                    <div class="article-body mt-4">
                        {!! clean($articleBody) !!}
                    </div>

                    <div class="article-feedback py-4 border-top mt-4">
                        <h6 class="fw-bold">¿Te resultó útil este artículo?</h6>
                        <div class="d-flex gap-2 mt-2" id="article-vote">
                            <button class="btn btn-outline-success" data-vote="1">
                                <i class="fas fa-thumbs-up me-1"></i> Sí
                            </button>
                            <button class="btn btn-outline-secondary" data-vote="-1">
                                <i class="fas fa-thumbs-down me-1"></i> No
                            </button>
                        </div>
                        <div id="article-vote-comment" class="mt-3 helpcenter-vote-extra">
                            <textarea id="vote-comment-text" class="form-control" placeholder="Cuéntanos cómo podemos mejorar..." rows="2"></textarea>
                            <button class="btn btn-primary btn-sm mt-2" id="article-vote-submit">Enviar</button>
                        </div>
                        <p class="small text-success mt-2 mb-0 helpcenter-vote-extra" id="article-vote-thanks">
                            <i class="fas fa-check-circle me-1"></i>Gracias por tu opinión.
                        </p>
                        <p class="small text-danger mt-2 mb-0 helpcenter-vote-extra" id="article-vote-error">
                            <i class="fas fa-circle-exclamation me-1"></i>No se pudo registrar tu voto. Inténtalo de nuevo.
                        </p>
                    </div>
                </div>
            </div>
        </article>

        @if($related->isNotEmpty())
            <aside class="col-lg-4 mt-4 mt-lg-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom fw-bold">
                        <i class="far fa-bookmark me-2 text-muted"></i> Artículos relacionados
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($related as $item)
                            <a href="{{ route('public.helpcenter.show', $item->slug) }}" class="list-group-item list-group-item-action small py-3">
                                <i class="far fa-file-lines me-2 text-muted"></i>{{ $item->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>
        @endif

    </div>
</main>

<script src="{{ themeAsset('libs/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ themeAsset('libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script>
$(function () {
    const articleSlug = '{{ $article->slug }}';
    let selectedVote = null;

    $('[data-vote]').on('click', function () {
        selectedVote = parseInt($(this).data('vote'));

        if (selectedVote === -1) {
            $('#article-vote-comment').slideDown();
        } else {
            submitVote(null);
        }
    });

    $('#article-vote-submit').on('click', function () {
        submitVote($('#vote-comment-text').val());
    });

    function submitVote(comment) {
        $.ajax({
            url: '/api/helpcenter/articles/' + articleSlug + '/vote',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { vote: selectedVote, comment: comment },
            success: function () {
                $('#article-vote').hide();
                $('#article-vote-comment').hide();
                $('#article-vote-thanks').show();
            },
            error: function () {
                $('#article-vote-error').show();
            }
        });
    }
});
</script>
</body>
</html>
