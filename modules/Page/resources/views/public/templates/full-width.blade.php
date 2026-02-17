@extends('page::components.layouts.master')

@section('title', $page->seo_title ?? $page->title)
@section('meta_description', $page->seo_description ?? $page->description)
@section('meta_keywords', $page->seo_keywords)

@section('content')
<div class="container-fluid px-0">
    @if($page->featured_image)
        <div class="hero-image" style="background-image: url('{{ $page->featured_image }}');">
            <div class="hero-overlay">
                <div class="container">
                    <h1 class="display-3 text-white fw-bold">{{ $page->title }}</h1>
                    @if($page->description)
                        <p class="lead text-white">{{ $page->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="container py-5">
        <article class="page-content">
            @if(!$page->featured_image)
                <header class="page-header mb-5 text-center">
                    <h1 class="display-3 mb-3">{{ $page->title }}</h1>
                    @if($page->description)
                        <p class="lead text-muted">{{ $page->description }}</p>
                    @endif
                </header>
            @endif

            <div class="page-body">
                {!! $page->content !!}
            </div>
        </article>
    </div>
</div>

@push('styles')
<style>
    .hero-image {
        height: 400px;
        background-size: cover;
        background-position: center;
        position: relative;
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
</style>
@endpush
@endsection
