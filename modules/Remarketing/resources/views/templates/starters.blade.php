@extends('layouts.theme')

@section('title', 'Plantillas de inicio')

@section('page_header')
    @include('core::components.card', ['title' => 'Plantillas de inicio'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0 small">Elige una plantilla prediseñada para empezar rápido</p>
        <a href="{{ route('remarketing.templates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a mis plantillas
        </a>
    </div>

    <div class="row g-3">
        @foreach($starters as $slug => $starter)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-3 text-center py-3" style="background:#f8f9fa;border-radius:6px;">
                            <i class="{{ $starter['icon'] }} fa-2x" style="color:#b10100;"></i>
                        </div>
                        <h5 class="card-title fw-semibold mb-1">{{ $starter['name'] }}</h5>
                        <p class="card-text text-muted small flex-grow-1">{{ $starter['description'] }}</p>
                        <p class="small text-muted mb-3">
                            <i class="fas fa-envelope me-1"></i>
                            <em>{{ $starter['subject'] }}</em>
                        </p>
                        <form method="POST" action="{{ route('remarketing.templates.starters.clone', $slug) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-copy me-1"></i> Usar esta plantilla
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@endsection
