@extends('page::components.layouts.master')

@section('title', 'Páginas')

@section('content')
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4 mb-3">Páginas</h1>
            <p class="lead text-muted">Explora todas nuestras páginas</p>
        </div>
    </div>

    <!-- Search -->
    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            @if(config('page.enable_live_search', true))
                <!-- Enhanced search box with autocomplete -->
                <x-page::search-box
                    placeholder="Buscar páginas..."
                    :action="route('pages.index')"
                    :value="$search ?? ''"
                />
            @else
                <!-- Basic search form -->
                <form method="GET" action="{{ route('pages.index') }}" class="input-group">
                    <input type="text" class="form-control" name="search"
                           placeholder="Buscar páginas..."
                           value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Pages Grid -->
    <div class="row">
        @forelse($pages as $page)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    @if($page->featured_image)
                        <img src="{{ $page->featured_image }}" class="card-img-top"
                             alt="{{ $page->title }}" style="height: 200px; object-fit: cover;">
                    @endif

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">{{ $page->title }}</h5>

                        @if($page->description)
                            <p class="card-text text-muted">
                                {{ Str::limit($page->description, 120) }}
                            </p>
                        @else
                            <p class="card-text text-muted">
                                {{ $page->getExcerpt(120) }}
                            </p>
                        @endif

                        <div class="mt-auto">
                            <small class="text-muted d-block mb-2">
                                <i class="far fa-calendar"></i>
                                {{ $page->published_at->format('d/m/Y') }}
                            </small>
                            <a href="{{ $page->url }}" class="btn btn-primary btn-sm">
                                Leer más <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <p class="mb-0">No se encontraron páginas.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($pages->hasPages())
        <div class="row mt-4">
            <div class="col-12">
                {{ $pages->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
