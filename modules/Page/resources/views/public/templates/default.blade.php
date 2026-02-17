@extends('page::components.layouts.master')

@section('title', $page->seo_title ?? $page->title)
@section('meta_description', $page->seo_description ?? $page->description)
@section('meta_keywords', $page->seo_keywords)

@section('content')
<div class="container py-5">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <article class="page-content">
                <header class="page-header mb-4">
                    <h1 class="display-5 mb-3">{{ $page->title }}</h1>

                    @if($page->description)
                        <p class="lead text-muted">{{ $page->description }}</p>
                    @endif

                    @if($page->featured_image)
                        <div class="featured-image my-4">
                            <img src="{{ $page->featured_image }}" alt="{{ $page->title }}"
                                 class="img-fluid rounded shadow">
                        </div>
                    @endif
                </header>

                <div class="page-body">
                    {!! $page->content !!}
                </div>
            </article>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <aside class="sidebar">
                <!-- Meta Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Información</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <small class="text-muted">
                                    <i class="far fa-calendar"></i>
                                    Publicado: {{ $page->published_at->format('d/m/Y') }}
                                </small>
                            </li>
                            <li class="mb-2">
                                <small class="text-muted">
                                    <i class="far fa-clock"></i>
                                    Actualizado: {{ $page->updated_at->format('d/m/Y') }}
                                </small>
                            </li>
                            @if($page->user)
                                <li>
                                    <small class="text-muted">
                                        <i class="far fa-user"></i>
                                        Por: {{ $page->user->full_name }}
                                    </small>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Additional Widget -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">¿Necesitas ayuda?</h5>
                        <p class="card-text">Contáctanos si tienes alguna pregunta.</p>
                        <a href="/contacto" class="btn btn-primary btn-sm">Contactar</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
