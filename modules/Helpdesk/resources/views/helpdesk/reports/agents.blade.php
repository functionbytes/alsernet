@extends('layouts.theme')
@section('title', 'Rendimiento de agentes · Helpdesk')
@section('page_header')
    @include('core::components.card', ['title' => 'Rendimiento de agentes · Helpdesk'])
@endsection

@section('content')

<div class="card">

    {{-- Cabecera --}}
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Rendimiento de agentes</h5>
                <p class="small mb-0 text-muted">
                    Conversaciones cerradas del {{ $from->translatedFormat('d \d\e F') }} al {{ $to->translatedFormat('d \d\e F') }}, ordenadas por volumen.
                </p>
            </div>
        </div>
    </div>

    {{-- Totales --}}
    <div class="card-body border-bottom">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Agentes activos</h6>
                        <h4 class="mb-1 fw-bold">{{ number_format($stats['agents']) }}</h4>
                        <small class="text-muted">Con conversaciones cerradas</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Cerradas este mes</h6>
                        <h4 class="mb-1 fw-bold">{{ number_format($stats['closed']) }}</h4>
                        <small class="text-muted">Entre todos los agentes</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">CSAT medio</h6>
                        <h4 class="mb-1 fw-bold">{{ $stats['avgCsat'] !== null ? number_format($stats['avgCsat'], 2).'/5' : '—' }}</h4>
                        <small class="text-muted">Entre agentes con valoraciones</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">1.ª respuesta media</h6>
                        <h4 class="mb-1 fw-bold">{{ $stats['avgResponseSeconds'] > 0 ? gmdate('H:i:s', $stats['avgResponseSeconds']) : '—' }}</h4>
                        <small class="text-muted">Tiempo hasta responder</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Agente</th>
                        <th scope="col" class="text-end">Cerradas</th>
                        <th scope="col" class="text-end">CSAT</th>
                        <th scope="col" class="text-end">1.ª respuesta</th>
                        <th scope="col" class="text-end">Mensajes enviados</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                        <tr>
                            <td class="fw-semibold">{{ $agent['name'] }}</td>
                            <td class="text-end">{{ number_format($agent['closed_count']) }}</td>
                            <td class="text-end">
                                @if($agent['csat_avg'] > 0)
                                    @php $cls = $agent['csat_avg'] >= 4 ? 'bg-success-subtle text-success' : ($agent['csat_avg'] >= 3 ? 'bg-warning-subtle text-warning' : 'bg-info-subtle text-info'); @endphp
                                    <span class="badge {{ $cls }}">{{ number_format($agent['csat_avg'], 2) }}/5</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end text-muted">
                                {{ $agent['avg_response_seconds'] > 0 ? gmdate('H:i:s', $agent['avg_response_seconds']) : '—' }}
                            </td>
                            <td class="text-end">{{ number_format($agent['message_count']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-chart-column fa-2x mb-2 d-block opacity-25"></i>
                                Ningún agente ha cerrado conversaciones este mes todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
