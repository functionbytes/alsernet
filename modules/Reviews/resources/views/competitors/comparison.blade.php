@extends('layouts.theme')

@section('title', 'Comparacion de rating')

@section('page_header')
    @include('core::components.card', ['title' => 'Comparacion de rating'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        @php
            $us          = $data['us'] ?? null;
            $competitors = $data['competitors'] ?? [];
            $compRatings = array_filter(array_column($competitors, 'avg_rating'), fn($r) => $r !== null);
            $avgComp     = count($compRatings) ? array_sum($compRatings) / count($compRatings) : null;
            $diff        = ($us && $avgComp !== null) ? $us['avg_rating'] - $avgComp : null;
            $all         = $us ? array_merge(
                [['name' => $us['name'], 'rating' => $us['avg_rating'], 'count' => $us['total_reviews'], 'isUs' => true, 'last_checked_at' => null]],
                array_map(fn($c) => array_merge($c, ['isUs' => false]), $competitors)
            ) : [];
        @endphp

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Comparacion de rating</h5>
                        <p class="small mb-0 text-muted">Tu negocio vs competidores</p>
                    </div>
                    <div>
                        <a href="{{ route('reviews.competitors.index') }}" class="btn btn-outline-secondary ">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Tu negocio</h6>
                                <h4 class="mb-1 fw-bold">
                                    @if($us)
                                        {{ number_format($us['avg_rating'], 1) }}
                                    @else
                                        —
                                    @endif
                                </h4>
                                <small class="text-muted">{{ $us ? number_format($us['total_reviews']) . ' reseñas' : 'Sin datos' }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Competidores</h6>
                                <h4 class="mb-1 fw-bold">{{ count($competitors) }}</h4>
                                <small class="text-muted">Registrados para esta ubicacion</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Diferencia media</h6>
                                <h4 class="mb-1 fw-bold {{ $diff !== null ? ($diff >= 0 ? 'text-success' : 'text-danger') : '' }}">
                                    @if($diff !== null)
                                        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                                    @else
                                        —
                                    @endif
                                </h4>
                                <small class="text-muted">vs promedio de competidores</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Location filter --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('reviews.competitors.compare') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <select name="location_id" class="form-select select2">
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ $selectedId == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">

                @if($locations->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-chart-bar fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay ubicaciones activas</h6>
                            <p class="text-muted mb-0">Activa al menos una ubicacion de Google para comparar.</p>
                        </div>
                    </div>

                @elseif(empty($all))
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-users fs-7"></i>
                            </div>
                            <h6 class="mb-1">Sin competidores para esta ubicacion</h6>
                            <p class="text-muted mb-3">Agrega competidores a esta ubicacion para ver la comparacion.</p>
                            <a href="{{ route('reviews.competitors.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Agregar competidor
                            </a>
                        </div>
                    </div>

                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Negocio</th>
                                    <th class="text-center">Rating</th>
                                    <th style="width:35%">Comparacion visual</th>
                                    <th class="text-center">Reseñas</th>
                                    <th>Ultima verificacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($all as $entry)
                                    @php
                                        $rating     = $entry['rating'] ?? null;
                                        $ratingPct  = $rating !== null ? ($rating / 5) * 100 : 0;
                                        $badgeClass = match(true) {
                                            $entry['isUs']    => 'bg-primary',
                                            $rating === null  => 'bg-secondary',
                                            $rating >= 4.5    => 'bg-success',
                                            $rating >= 4.0    => 'bg-primary',
                                            $rating >= 3.0    => 'bg-warning',
                                            default           => 'bg-danger',
                                        };
                                    @endphp
                                    <tr class="{{ $entry['isUs'] ? 'table-active' : '' }}">
                                        <td>
                                            <span class="{{ $entry['isUs'] ? 'fw-bold' : '' }}">
                                                {{ $entry['name'] }}
                                                @if($entry['isUs'])
                                                    <span class="badge bg-primary-subtle text-primary ms-1">Tu negocio</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($rating !== null)
                                                <span class="badge {{ $badgeClass }} px-2 py-1">
                                                    {{ number_format($rating, 1) }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($rating !== null)
                                                <div class="progress" style="height:10px;">
                                                    <div class="progress-bar {{ $badgeClass }}" role="progressbar"
                                                         style="width:{{ number_format($ratingPct, 1) }}%"
                                                         aria-valuenow="{{ $ratingPct }}" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                            @else
                                                <small class="text-muted">Sin datos</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ isset($entry['count']) && $entry['count'] !== null ? number_format($entry['count']) : '—' }}
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                @if($entry['isUs'])
                                                    Tiempo real
                                                @elseif($entry['last_checked_at'] ?? null)
                                                    {{ $entry['last_checked_at'] }}
                                                @else
                                                    Nunca
                                                @endif
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('select[name="location_id"]').select2('destroy').select2({
        width: '100%',
        placeholder: 'Seleccionar ubicacion...',
    });
});
</script>
@endpush
