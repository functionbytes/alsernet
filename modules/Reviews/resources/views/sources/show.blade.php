@extends('layouts.theme')
@section('title', $source->name)
@section('page_header')
    @include('core::components.card', ['title' => $source->name])
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

<div class="mb-3">
    <a href="{{ route('reviews.sources.index') }}" class="btn btn-light btn-sm">Volver a las fichas</a>
</div>

{{-- Cifras de esta tienda, no del conjunto --}}
<div class="row g-3 mb-4">
    @php
        $tarjetas = [
            ['Reseñas', number_format($stats['total']), null],
            ['Valoración media', $stats['media'].' / 5', null],
            ['Sin revisar', number_format($stats['pending']), $stats['pending'] ? 'pending' : null],
            ['Negativas', number_format($stats['negativas']), null],
            ['Sin responder', number_format($stats['sin_respuesta']), null],
        ];
    @endphp
    @foreach($tarjetas as [$titulo, $valor, $filtro])
        <div class="col-6 col-md">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">{{ $titulo }}</h6>
                    <h4 class="mb-0 fw-bold">{{ $valor }}</h4>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mb-4">
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h5 class="mb-1 fw-bold">Reseñas de esta tienda</h5>
                <p class="small mb-0 text-muted">
                    {{ ucfirst($source->platform) }}
                    @if($source->external_id) · <code>{{ $source->external_id }}</code> @endif
                    · última lectura {{ optional($source->last_fetch_at)->format('d/m/Y H:i') ?? 'nunca' }}
                    @if(! $source->isConfigured())
                        · <span class="badge bg-warning text-dark">faltan credenciales</span>
                    @endif
                </p>
            </div>
            @can('reviews.settings')
                <div class="ms-auto ps-3 d-flex gap-2 flex-shrink-0">
                    <form method="POST" action="{{ route('reviews.sources.fetch', $source) }}">
                        @csrf
                        <button type="submit" class="btn btn-light">Leer ahora</button>
                    </form>
                    <a href="{{ route('reviews.index', ['origin' => 'google', 'source' => $source->id]) }}" class="btn btn-primary">
                        Abrir en la bandeja
                    </a>
                </div>
            @endcan
        </div>
    </div>

    <div class="card-body border-bottom">
        <ul class="nav nav-pills user-profile-tab gap-2">
            @foreach ([
                'all' => 'Todas',
                'pending' => 'Sin revisar',
                'approved' => 'Publicadas',
                'rejected' => 'Retiradas',
            ] as $clave => $etiqueta)
                <li class="nav-item">
                    <a class="nav-link {{ $status === $clave ? 'active' : '' }}"
                       href="{{ route('reviews.sources.show', array_filter(['source' => $source->id, 'status' => $clave, 'q' => request('q')])) }}">
                        {{ $etiqueta }}
                        @if($clave !== 'all' && $stats[$clave])
                            <span class="badge bg-light text-dark ms-1">{{ number_format($stats[$clave]) }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card-body border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="col-12 col-md-6">
                <label class="form-label" for="q">Buscar</label>
                <input type="search" class="form-control" id="q" name="q" value="{{ request('q') }}"
                       placeholder="Texto o autor de la reseña">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="stars">Valoración</label>
                <select class="form-select" id="stars" name="stars">
                    <option value="">Todas</option>
                    @foreach ([10 => '5', 8 => '4', 6 => '3', 4 => '2', 2 => '1'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(request('stars') !== null && request('stars') !== '' && (int) request('stars') === $valor)>
                            {{ $etiqueta }} estrellas ({{ $breakdown[$valor] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('reviews.sources.show', $source) }}" class="btn btn-light">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Reseña</th>
                    <th class="text-center">Valoración</th>
                    <th>Fecha</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($reviews as $review)
                <tr>
                    <td class="ps-4">
                        <a class="fw-semibold" href="{{ route('reviews.show', $review) }}">
                            {{ $review->author ?: 'Anónimo' }}
                        </a>
                        <div class="small text-muted">{{ Str::limit(strip_tags($review->comment ?? ''), 130) }}</div>
                        @if($review->answer)
                            <div class="small text-muted"><strong>Respondida</strong></div>
                        @endif
                    </td>
                    <td class="text-center">{{ $review->rating }} / 5</td>
                    <td class="small">{{ optional($review->ps_date)->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-center">
                        @switch($review->status)
                            @case('approved') <span class="badge bg-success">Publicada</span> @break
                            @case('rejected') <span class="badge bg-secondary">Retirada</span> @break
                            @default <span class="badge bg-warning text-dark">Sin revisar</span>
                        @endswitch
                    </td>
                    <td class="text-end pe-4">
                        <a href="{{ route('reviews.show', $review) }}" class="btn btn-sm btn-light">Abrir</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        No hay reseñas con estos filtros.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($reviews->hasPages())
        <div class="card-body border-top">{{ $reviews->links() }}</div>
    @endif
</div>

@endsection
