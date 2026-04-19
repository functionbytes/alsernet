@extends('layouts.theme')

@section('page_title', 'Alertas SEO')

@section('content')
    @include('core::components.alerts')

    <div class="row g-3 mb-4">
        <div class="col"><div class="card"><div class="card-body text-center">
            <div class="fs-4 fw-bold {{ $stats['unacknowledged'] > 0 ? '' : 'text-muted' }}">{{ $stats['unacknowledged'] }}</div>
            <small class="text-muted">Sin revisar</small>
        </div></div></div>
        <div class="col"><div class="card"><div class="card-body text-center">
            <div class="fs-4 fw-bold {{ $stats['critical'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $stats['critical'] }}</div>
            <small class="text-muted">Críticas</small>
        </div></div></div>
        <div class="col"><div class="card"><div class="card-body text-center">
            <div class="fs-4 fw-bold {{ $stats['warning'] > 0 ? 'text-warning' : 'text-muted' }}">{{ $stats['warning'] }}</div>
            <small class="text-muted">Advertencias</small>
        </div></div></div>
        <div class="col"><div class="card"><div class="card-body text-center">
            <div class="fs-4 fw-bold text-muted">{{ $stats['info'] }}</div>
            <small class="text-muted">Info</small>
        </div></div></div>
    </div>

    <div class="card">
        <div class="card-header p-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1">Alertas</h5>
                <p class="small mb-0 text-muted">Eventos SEO recientes. Deduplicadas por (tipo, URL) en 24h.</p>
            </div>
            @if($stats['unacknowledged'] > 0)
                <form method="POST" action="{{ route('setting.seo.alerts.acknowledge-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Marcar todas como revisadas</button>
                </form>
            @endif
        </div>

        @if($alerts->count() === 0)
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <p class="text-muted mb-0">Sin alertas. Todo tranquilo.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="100">Severidad</th>
                            <th>Alerta</th>
                            <th width="160">Fecha</th>
                            <th width="120"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                            @php
                                $sevClass = ['info' => 'secondary', 'warning' => 'warning', 'critical' => 'danger'][$alert->severity] ?? 'secondary';
                            @endphp
                            <tr class="{{ $alert->acknowledged_at ? 'text-muted' : '' }}">
                                <td><span class="badge bg-{{ $sevClass }}-subtle text-{{ $sevClass }}">{{ strtoupper($alert->severity) }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $alert->title }}</div>
                                    @if($alert->message)<div class="small text-muted">{{ $alert->message }}</div>@endif
                                    @if($alert->url)<code class="small d-block text-break">{{ $alert->url }}</code>@endif
                                </td>
                                <td class="small">{{ $alert->created_at->diffForHumans() }}</td>
                                <td>
                                    @if(! $alert->acknowledged_at)
                                        <form method="POST" action="{{ route('setting.seo.alerts.acknowledge', $alert) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Revisar</button>
                                        </form>
                                    @else
                                        <small class="text-muted">Revisada</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $alerts->links() }}</div>
        @endif
    </div>
@endsection
