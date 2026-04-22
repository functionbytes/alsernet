@extends('layouts.theme')

@section('title', 'Reportes - Helpdesk')

@section('content')
    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="mb-1 fw-bold">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>
                    Reportes
                </h4>
                <small class="text-muted">
                    {{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}
                </small>
            </div>
            <a href="{{ route('manager.helpdesk.reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-file-csv me-1"></i> Exportar CSV
            </a>
        </div>

        {{-- Date Range Filter --}}
        <div class="card mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('manager.helpdesk.reports.index') }}" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small mb-1">Desde</label>
                        <input type="date" name="from" class="form-control form-control-sm"
                               value="{{ $from->toDateString() }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-1">Hasta</label>
                        <input type="date" name="to" class="form-control form-control-sm"
                               value="{{ $to->toDateString() }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('manager.helpdesk.reports.index') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-undo me-1"></i> Últimos 30 días
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="rounded-circle bg-primary-subtle d-inline-flex align-items-center justify-content-center mb-2"
                             style="width: 48px; height: 48px;">
                            <i class="fas fa-ticket-alt text-primary"></i>
                        </div>
                        <h3 class="fw-bold mb-0">{{ number_format($totalCreated) }}</h3>
                        <small class="text-muted">Tickets creados</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="rounded-circle bg-success-subtle d-inline-flex align-items-center justify-content-center mb-2"
                             style="width: 48px; height: 48px;">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <h3 class="fw-bold mb-0">{{ number_format($totalClosed) }}</h3>
                        <small class="text-muted">Tickets cerrados</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="rounded-circle bg-danger-subtle d-inline-flex align-items-center justify-content-center mb-2"
                             style="width: 48px; height: 48px;">
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                        </div>
                        <h3 class="fw-bold mb-0">{{ number_format($slaBreached) }}</h3>
                        <small class="text-muted">SLA incumplidos</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="rounded-circle bg-warning-subtle d-inline-flex align-items-center justify-content-center mb-2"
                             style="width: 48px; height: 48px;">
                            <i class="far fa-clock text-warning"></i>
                        </div>
                        <h3 class="fw-bold mb-0">{{ $avgResponseTime }}<small class="fs-6 text-muted"> min</small></h3>
                        <small class="text-muted">Tiempo respuesta promedio</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- By Status & By Category --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-tags me-1 text-primary"></i>
                            Tickets por estado
                        </h6>
                    </div>
                    <div class="card-body pt-3">
                        @forelse($byStatus as $row)
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    @if($row->status)
                                        <span class="badge rounded-pill"
                                              style="background-color: {{ $row->status->color }}; min-width: 70px;">
                                            {{ $row->status->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">Sin estado</span>
                                    @endif
                                </div>
                                <span class="fw-semibold">{{ number_format($row->count) }}</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Sin datos para el periodo seleccionado.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-folder me-1 text-primary"></i>
                            Tickets por categoría
                        </h6>
                    </div>
                    <div class="card-body pt-3">
                        @forelse($byCategory as $row)
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    @if($row->category)
                                        <i class="{{ $row->category->icon ?? 'fas fa-tag' }}"
                                           style="color: {{ $row->category->color ?? '#90bb13' }}"></i>
                                        <span>{{ $row->category->name }}</span>
                                    @else
                                        <span class="text-muted">Sin categoría</span>
                                    @endif
                                </div>
                                <span class="fw-semibold">{{ number_format($row->count) }}</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Sin datos para el periodo seleccionado.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- By Priority --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-flag me-1 text-primary"></i>
                            Tickets por prioridad
                        </h6>
                    </div>
                    <div class="card-body pt-3">
                        @php
                            $priorityLabels = [
                                'urgent' => ['label' => 'Urgente', 'class' => 'danger'],
                                'high' => ['label' => 'Alta', 'class' => 'warning'],
                                'normal' => ['label' => 'Normal', 'class' => 'info'],
                                'low' => ['label' => 'Baja', 'class' => 'secondary'],
                            ];
                        @endphp
                        @forelse($byPriority as $row)
                            @php $meta = $priorityLabels[$row->priority] ?? ['label' => ucfirst($row->priority), 'class' => 'secondary']; @endphp
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <span class="badge bg-{{ $meta['class'] }}-subtle text-{{ $meta['class'] }} px-3">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="fw-semibold">{{ number_format($row->count) }}</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Sin datos para el periodo seleccionado.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-info-circle me-1 text-primary"></i>
                            Resumen adicional
                        </h6>
                    </div>
                    <div class="card-body pt-3">
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                            <span class="text-muted">Tickets resueltos</span>
                            <span class="fw-semibold">{{ number_format($totalResolved) }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                            <span class="text-muted">Tiempo resolución promedio</span>
                            <span class="fw-semibold">{{ $avgResolutionTime }} min</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                            <span class="text-muted">Tasa de SLA incumplido</span>
                            <span class="fw-semibold text-{{ $totalCreated > 0 && ($slaBreached / $totalCreated) > 0.1 ? 'danger' : 'success' }}">
                                {{ $totalCreated > 0 ? round(($slaBreached / $totalCreated) * 100, 1) : 0 }}%
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-2">
                            <span class="text-muted">Tasa de cierre</span>
                            <span class="fw-semibold text-{{ $totalCreated > 0 && ($totalClosed / $totalCreated) > 0.7 ? 'success' : 'warning' }}">
                                {{ $totalCreated > 0 ? round(($totalClosed / $totalCreated) * 100, 1) : 0 }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Customer satisfaction --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold mb-0">
                    <i class="fas fa-star me-1 text-warning"></i>
                    Satisfaccion del cliente
                </h6>
            </div>
            <div class="card-body pt-3">
                @if ($ratedCount === 0)
                    <p class="text-muted mb-0">Sin valoraciones para el periodo seleccionado.</p>
                @else
                    <div class="row g-3 align-items-start">
                        <div class="col-md-4 text-center">
                            <div class="display-4 fw-bold text-warning">{{ $avgRating }}</div>
                            <div class="text-muted">promedio de {{ number_format($ratedCount) }} valoraciones</div>
                            <div class="mt-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star{{ $i <= round($avgRating) ? '' : '-half-alt' }} text-warning" style="{{ $i > ceil($avgRating) ? 'opacity:.3' : '' }}"></i>
                                @endfor
                            </div>
                        </div>
                        <div class="col-md-8">
                            @for ($star = 5; $star >= 1; $star--)
                                @php $count = $ratingDistribution[$star] ?? 0; $pct = $ratedCount > 0 ? round(($count / $ratedCount) * 100) : 0; @endphp
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="text-muted" style="width: 16px;">{{ $star }}</span>
                                    <i class="fas fa-star text-warning small"></i>
                                    <div class="progress flex-grow-1" style="height: 10px;">
                                        <div class="progress-bar bg-warning" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-muted" style="width: 32px;">{{ $count }}</span>
                                </div>
                            @endfor
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Agent Performance --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="fw-semibold mb-0">
                    <i class="fas fa-users me-1 text-primary"></i>
                    Rendimiento de agentes (top 5 por tickets cerrados)
                </h6>
            </div>
            <div class="card-body pt-3">
                @if($topAgents->isEmpty())
                    <p class="text-muted mb-0">Sin datos de agentes para el periodo seleccionado.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Agente</th>
                                    <th class="text-center">Tickets cerrados</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topAgents as $row)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold"
                                                     style="width: 32px; height: 32px; font-size: 13px; flex-shrink: 0;">
                                                    {{ strtoupper(substr($row->agent->name, 0, 1)) }}
                                                </div>
                                                <span>{{ $row->agent->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success px-3">
                                                {{ number_format($row->closed_count) }}
                                            </span>
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
        @if(session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}', 'Error');
        @endif
    });
</script>
@endpush
