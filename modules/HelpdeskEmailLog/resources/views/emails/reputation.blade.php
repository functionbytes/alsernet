@extends('layouts.theme')

@section('title', 'Log de emails — Reputación')

@section('page_header')
    @include('core::components.card', ['title' => 'Log de emails — Reputación'])
@endsection

@php
    $badge = fn (string $status) => match ($status) {
        'pass' => 'bg-success-subtle text-success',
        'warning' => 'bg-warning-subtle text-warning',
        'missing' => 'bg-danger-subtle text-danger',
        default => 'bg-secondary-subtle text-secondary-emphasis', // unknown
    };
    $rateClass = fn (float $rate, float $warning, float $critical) => match (true) {
        $rate >= $critical => 'text-danger',
        $rate >= $warning => 'text-warning',
        default => 'text-success',
    };
@endphp

@section('content')
    @include('core::components.alerts')

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-shield-halved me-2 text-primary"></i>Reputación de envío</h4>
            <small class="text-muted">Últimos {{ $days }} días · {{ number_format($overall['attempted']) }} envíos con resultado terminal</small>
        </div>
        <a href="{{ route('settings.helpdeskemaillog.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-gear me-1"></i> Configurar dominios y umbrales
        </a>
    </div>

    {{-- Tasas globales --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-0 {{ $rateClass($overall['bounce_rate'], $thresholds['bounce_warning'], $thresholds['bounce_critical']) }}">
                        {{ $overall['bounce_rate'] }}%
                    </h3>
                    <small class="text-muted">Tasa de rebote (límite {{ $thresholds['bounce_critical'] }}%)</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-0 {{ $rateClass($overall['complaint_rate'], $thresholds['complaint_warning'], $thresholds['complaint_critical']) }}">
                        {{ $overall['complaint_rate'] }}%
                    </h3>
                    <small class="text-muted">Tasa de quejas de spam (límite {{ $thresholds['complaint_critical'] }}%)</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-0">{{ number_format($overall['bounced']) }}</h3>
                    <small class="text-muted">Rebotes</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-0">{{ number_format($overall['complained']) }}</h3>
                    <small class="text-muted">Quejas</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Dominios monitorizados --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
            <h6 class="fw-semibold mb-0">Autenticación por dominio</h6>
            <small class="text-muted">SPF/DMARC vía DNS · DKIM solo si se conoce el selector</small>
        </div>
        <div class="card-body pt-3">
            @if($rows->isEmpty())
                <p class="text-muted mb-0">
                    Sin dominios configurados.
                    <a href="{{ route('settings.helpdeskemaillog.index') }}">Añadir uno</a> en la configuración del módulo.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Dominio</th>
                                <th>SPF</th>
                                <th>DMARC</th>
                                <th>DKIM</th>
                                <th class="text-center">Tasa rebote</th>
                                <th class="text-center">Tasa quejas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['domain'] }}</td>
                                    <td><span class="badge {{ $badge($row['auth']['spf']['status']) }}">{{ $row['auth']['spf']['status'] }}</span></td>
                                    <td>
                                        <span class="badge {{ $badge($row['auth']['dmarc']['status']) }}">{{ $row['auth']['dmarc']['status'] }}</span>
                                        @if($row['auth']['dmarc']['policy'])
                                            <span class="text-muted small">p={{ $row['auth']['dmarc']['policy'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $badge($row['auth']['dkim']['status']) }}">{{ $row['auth']['dkim']['status'] }}</span>
                                        @if($row['auth']['dkim']['selector'])
                                            <span class="text-muted small">({{ $row['auth']['dkim']['selector'] }})</span>
                                        @endif
                                    </td>
                                    <td class="text-center {{ $rateClass($row['stats']['bounce_rate'], $thresholds['bounce_warning'], $thresholds['bounce_critical']) }}">
                                        {{ $row['stats']['bounce_rate'] }}%
                                    </td>
                                    <td class="text-center {{ $rateClass($row['stats']['complaint_rate'], $thresholds['complaint_warning'], $thresholds['complaint_critical']) }}">
                                        {{ $row['stats']['complaint_rate'] }}%
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('helpdeskemaillog.reputation.refresh') }}">
                                            @csrf
                                            <input type="hidden" name="domain" value="{{ $row['domain'] }}">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-rotate" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if(!empty($suggestions))
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold mb-0">Dominios vistos, aún sin monitorizar</h6>
                <small class="text-muted">Detectados en el remitente de envíos de los últimos 30 días</small>
            </div>
            <div class="card-body pt-3">
                @foreach($suggestions as $suggestion)
                    <span class="badge bg-light text-dark border me-1 mb-1">{{ $suggestion }}</span>
                @endforeach
            </div>
        </div>
    @endif
@endsection
