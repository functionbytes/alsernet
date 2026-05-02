@extends('theme::layouts.admin')

@section('title', 'Rendimiento de agentes')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Rendimiento de agentes</h1>
        <div class="btn-group">
            <a href="?days=7" class="btn btn-outline-secondary {{ $days == 7 ? 'active' : '' }}">7 días</a>
            <a href="?days=30" class="btn btn-outline-secondary {{ $days == 30 ? 'active' : '' }}">30 días</a>
            <a href="?days=90" class="btn btn-outline-secondary {{ $days == 90 ? 'active' : '' }}">90 días</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4 class="mb-1">{{ $agents->sum('assigned_count') }}</h4>
                    <small>Comentarios asignados</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4 class="mb-1">{{ $agents->sum('replied_count') }}</h4>
                    <small>Respuestas enviadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h4 class="mb-1">
                        @php
                            $totalAvg = $agents->count() > 0 ? $agents->avg('avg_response_time') : null;
                        @endphp
                        {{ $totalAvg ? round($totalAvg / 60, 1) . ' min' : '-' }}
                    </h4>
                    <small>Tiempo promedio global</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Agente</th>
                            <th>Asignados</th>
                            <th>Respondidos</th>
                            <th>Tasa de respuesta</th>
                            <th>Tiempo promedio</th>
                            <th>Rendimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agents as $agent)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 14px;">
                                        {{ strtoupper(substr($agent->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $agent->name }}</div>
                                        <small class="text-muted">{{ $agent->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $agent->assigned_count }}</td>
                            <td>{{ $agent->replied_count }}</td>
                            <td>
                                @if($agent->assigned_count > 0)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px; max-width: 120px;">
                                            <div class="progress-bar bg-success" style="width: {{ min(100, ($agent->replied_count / $agent->assigned_count) * 100) }}%"></div>
                                        </div>
                                        <small>{{ round(($agent->replied_count / $agent->assigned_count) * 100, 1) }}%</small>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($agent->avg_response_time)
                                    <span class="badge bg-{{ $agent->avg_response_time < 300 ? 'success' : ($agent->avg_response_time < 900 ? 'warning text-dark' : 'danger') }}">
                                        {{ round($agent->avg_response_time / 60, 1) }} min
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $score = 0;
                                    if ($agent->assigned_count > 0) {
                                        $score += ($agent->replied_count / $agent->assigned_count) * 50;
                                    }
                                    if ($agent->avg_response_time) {
                                        $score += max(0, 50 - ($agent->avg_response_time / 60));
                                    }
                                    $score = min(100, max(0, $score));
                                @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px; max-width: 120px;">
                                        <div class="progress-bar bg-{{ $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger') }}" style="width: {{ $score }}%"></div>
                                    </div>
                                    <small>{{ round($score, 1) }}</small>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-user-tie fa-2x mb-2 d-block"></i>
                                No hay datos de agentes para este período
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $agents->links() }}
        </div>
    </div>
</div>
@endsection
