@extends('layouts.full')
@section('title', 'Performance agentes · Helpdesk')
@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4"><i class="fas fa-user-tie text-primary me-2"></i>Performance por agente</h1>
    <p class="text-muted">Métricas del último mes.</p>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Agente</th>
                        <th class="text-end">Cerradas</th>
                        <th class="text-end">CSAT avg</th>
                        <th class="text-end">1ª resp. avg (s)</th>
                        <th class="text-end">Mensajes enviados</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents ?? [] as $agent)
                        <tr>
                            <td><strong>{{ $agent->name }}</strong></td>
                            <td class="text-end">{{ $agent->closed_count ?? 0 }}</td>
                            <td class="text-end">
                                @if($agent->csat_avg)
                                    @php $r = round($agent->csat_avg, 2); @endphp
                                    <span class="badge bg-{{ $r >= 4 ? 'success' : ($r >= 3 ? 'warning' : 'danger') }}">{{ $r }}/5</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">{{ (int) ($agent->avg_first_response_seconds ?? 0) }}</td>
                            <td class="text-end">{{ $agent->messages_sent ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Sin datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
