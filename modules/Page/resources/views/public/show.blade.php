@extends('page::components.layouts.master')

@section('title', $page->seo_title ?? $page->title)
@section('meta_description', $page->seo_description ?? $page->description)
@section('meta_keywords', $page->seo_keywords)

@section('content')
<div class="container py-5">
    <article class="page-content">
        <!-- Page Header -->
        <header class="page-header mb-5">
            <h1 class="display-4 mb-3">{{ $page->title }}</h1>

            @if($page->description)
                <p class="lead text-muted">{{ $page->description }}</p>
            @endif

            @if($page->featured_image)
                <div class="featured-image my-4">
                    <img src="{{ $page->featured_image }}" alt="{{ $page->title }}"
                         class="img-fluid rounded shadow-sm">
                </div>
            @endif

            <div class="page-meta text-muted small">
                <span>
                    <i class="far fa-calendar"></i>
                    Publicado: {{ $page->published_at->format('d/m/Y') }}
                </span>
                @if($page->user)
                    <span class="ms-3">
                        <i class="far fa-user"></i>
                        Por: {{ $page->user->full_name }}
                    </span>
                @endif
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-body">
            {!! $page->content !!}
        </div>

        <!-- Page Footer -->
        <footer class="page-footer mt-5 pt-4 border-top">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        Última actualización: {{ $page->updated_at->format('d/m/Y H:i') }}
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </footer>
    </article>
</div>
@endsection

@push('styles')
<style>
    .page-content img {
        max-width: 100%;
        height: auto;
    }

    .page-body {
        font-size: 1.1rem;
        line-height: 1.8;
    }

    .page-body h1,
    .page-body h2,
    .page-body h3,
    .page-body h4,
    .page-body h5,
    .page-body h6 {
        margin-top: 2rem;
        margin-bottom: 1rem;
    }

    .page-body p {
        margin-bottom: 1.5rem;
    }

    .page-body ul,
    .page-body ol {
        margin-bottom: 1.5rem;
    }

    .page-body blockquote {
        border-left: 4px solid #dee2e6;
        padding-left: 1rem;
        margin: 1.5rem 0;
        font-style: italic;
        color: #6c757d;
    }

    .featured-image {
        max-height: 500px;
        overflow: hidden;
    }

    .featured-image img {
        width: 100%;
        object-fit: cover;
    }
</style>
@endpush
