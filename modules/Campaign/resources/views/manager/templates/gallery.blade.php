@extends('theme::layouts.manager')

@section('title', 'Galería de plantillas')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Galería de plantillas</h2>
                <p class="text-muted mb-0">Empieza con un preset y personalízalo en el builder.</p>
            </div>
            <a href="{{ route('manager.campaigns.templates.index') }}" class="btn btn-outline-secondary">← Plantillas</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row g-4">
            @foreach ($catalog as $key => $meta)
                <div class="col-md-6 col-lg-4">
                    <form method="post" action="{{ route('manager.campaigns.templates.builder.from-prebuilt', $key) }}">
                        @csrf
                        <button type="submit" class="card h-100 text-start w-100 border-0 shadow-sm" style="cursor:pointer;background:transparent;padding:0;">
                            <div class="card-body text-center py-5" style="background:#f9fafb;border-radius:.375rem .375rem 0 0;">
                                <div style="font-size:64px;line-height:1;">{{ $meta['thumbnail'] }}</div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title mb-1">{{ $meta['name'] }}</h5>
                                <p class="card-text small text-muted">{{ $meta['description'] }}</p>
                                <span class="text-primary small">Crear desde esta plantilla →</span>
                            </div>
                        </button>
                    </form>
                </div>
            @endforeach

            <div class="col-md-6 col-lg-4">
                <a href="{{ route('manager.campaigns.templates.create') }}" class="card h-100 text-decoration-none border-2 border-dashed text-muted" style="border-style:dashed!important;">
                    <div class="card-body text-center py-5">
                        <div style="font-size:64px;line-height:1;">＋</div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title mb-1">Empezar en blanco</h5>
                        <p class="card-text small">Plantilla vacía para construir desde cero.</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
@endsection
