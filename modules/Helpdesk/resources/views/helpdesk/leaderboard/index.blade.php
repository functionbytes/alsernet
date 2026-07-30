@extends('layouts.theme')

@section('title', 'Leaderboard del equipo · Helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Leaderboard del equipo · Helpdesk'])
@endsection

@section('content')

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-trophy me-2 text-warning"></i>
                Leaderboard del equipo
            </h4>
            <p class="text-muted mb-0">Rendimiento del equipo por conversaciones resueltas</p>
        </div>
        <div>
            <form method="GET" action="{{ route('manager.helpdesk.team.leaderboard') }}" class="d-flex gap-2 align-items-center">
                <label class="form-label mb-0 small text-muted fw-semibold">Período:</label>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="?period=1d"
                       class="btn {{ $period === '1d' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Hoy
                    </a>
                    <a href="?period=7d"
                       class="btn {{ $period === '7d' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        7 días
                    </a>
                    <a href="?period=30d"
                       class="btn {{ $period === '30d' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        30 días
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($leaderboard->isEmpty())
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-chart-bar fa-3x text-muted opacity-50"></i>
                    </div>
                    <h6 class="text-muted mb-1">Sin datos para el período seleccionado</h6>
                    <p class="text-muted small mb-0">No hay conversaciones asignadas registradas en este rango de fechas.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-4" width="60">#</th>
                                <th scope="col">Agente</th>
                                <th scope="col" class="text-center">Total asignadas</th>
                                <th scope="col" class="text-center">Resueltas</th>
                                <th scope="col" class="text-center">% resolución</th>
                                <th scope="col" class="text-center">Tiempo promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaderboard as $index => $agent)
                                @php
                                    $position = $index + 1;
                                    $medal = match($position) {
                                        1 => '🥇',
                                        2 => '🥈',
                                        3 => '🥉',
                                        default => null,
                                    };
                                    $resolved = (int) $agent->resolved;
                                    $total = (int) $agent->total;
                                    $pct = $total > 0 ? round($resolved / $total * 100) : 0;
                                    $avgMinutes = $agent->avg_resolution_minutes;
                                    $avgHours = $avgMinutes !== null ? round($avgMinutes / 60, 1) : null;
                                    $initial = strtoupper(substr($agent->name, 0, 1));
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        @if($medal)
                                            <span class="fs-5">{{ $medal }}</span>
                                        @else
                                            <span class="text-muted fw-semibold">{{ $position }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center flex-shrink-0"
                                                 style="width:36px;height:36px">
                                                <span class="text-white fw-bold small">{{ $initial }}</span>
                                            </div>
                                            <span class="fw-semibold">{{ $agent->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary">{{ number_format($total) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success fw-semibold">{{ number_format($resolved) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $pctClass = $pct >= 75 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                                        @endphp
                                        <div class="d-flex align-items-center gap-2 justify-content-center">
                                            <div class="progress flex-grow-1" style="height:6px;max-width:80px">
                                                <div class="progress-bar bg-{{ $pctClass }}"
                                                     style="width:{{ $pct }}%"></div>
                                            </div>
                                            <small class="fw-semibold text-{{ $pctClass }}">{{ $pct }}%</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($avgHours !== null)
                                            <span class="text-muted small">
                                                {{ $avgHours }}h
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <div class="card-footer bg-white border-top py-2 px-3">
            <small class="text-muted">
                Mostrando datos del período:
                @if($period === '1d') últimas 24 horas
                @elseif($period === '30d') últimos 30 días
                @else últimos 7 días
                @endif
                · Ordenado por conversaciones resueltas (descendente)
            </small>
        </div>
    </div>

@endsection
