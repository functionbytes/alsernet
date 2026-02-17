@extends('page::components.layouts.master')

@section('title', $page->seo_title ?? $page->title)
@section('meta_description', $page->seo_description ?? $page->description)
@section('meta_keywords', $page->seo_keywords)

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <article class="page-content">
                <header class="page-header mb-5 text-center">
                    <h1 class="display-4 mb-3">{{ $page->title }}</h1>

                    @if($page->description)
                        <p class="lead text-muted">{{ $page->description }}</p>
                    @endif

                    <div class="page-meta text-muted small mt-3">
                        <span>
                            <i class="far fa-calendar"></i>
                            {{ $page->published_at->format('d/m/Y') }}
                        </span>
                        @if($page->user)
                            <span class="ms-3">
                                <i class="far fa-user"></i>
                                {{ $page->user->full_name }}
                            </span>
                        @endif
                    </div>
                </header>

                @if($page->featured_image)
                    <div class="featured-image mb-5 text-center">
                        <img src="{{ $page->featured_image }}" alt="{{ $page->title }}"
                             class="img-fluid rounded shadow-lg" style="max-height: 500px;">
                    </div>
                @endif

                <div class="page-body">
                    {!! $page->content !!}
                </div>

                <footer class="page-footer mt-5 pt-4 border-top text-center">
                    <small class="text-muted">
                        Última actualización: {{ $page->updated_at->format('d/m/Y H:i') }}
                    </small>
                </footer>
            </article>
        </div>
    </div>
</div>

@push('styles')
<style>
    .page-body {
        font-size: 1.125rem;
        line-height: 1.8;
    }
</style>
@endpush
@endsection
