@extends('layouts.theme')

@section('title', 'Mis reportes')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-primary"></i>Mis reportes</h4>
        <a href="{{ route('agent.helpdesk.reports.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-download me-1"></i>Exportar CSV
        </a>
    </div>

    {{-- Date filter --}}
    <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-auto">
            <label class="form-label small mb-1">Desde</label>
            <input type="date" name="from" class="form-control form-control-sm"
                value="{{ $from->format('Y-m-d') }}">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1">Hasta</label>
            <input type="date" name="to" class="form-control form-control-sm"
                value="{{ $to->format('Y-m-d') }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        </div>
    </form>

    {{-- Summary stats --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Total', 'key' => 'total', 'icon' => 'ticket-alt', 'color' => 'primary'],
            ['label' => 'Abiertos', 'key' => 'open', 'icon' => 'folder-open', 'color' => 'warning'],
            ['label' => 'Cerrados', 'key' => 'closed', 'icon' => 'check-circle', 'color' => 'success'],
            ['label' => 'Resueltos', 'key' => 'resolved', 'icon' => 'thumbs-up', 'color' => 'info'],
            ['label' => 'SLA incumplido', 'key' => 'breached', 'icon' => 'exclamation-triangle', 'color' => 'danger'],
        ] as $stat)
        <div class="col-sm-6 col-lg">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-{{ $stat['color'] }} bg-opacity-10"
                        style="width:48px;height:48px;">
                        <i class="fas fa-{{ $stat['icon'] }} text-{{ $stat['color'] }}"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $stats[$stat['key']] }}</div>
                        <div class="small text-muted">{{ $stat['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- By status --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Por estado</div>
                <div class="card-body">
                    @forelse($byStatus as $name => $count)
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ $name ?: 'Sin estado' }}</span>
                        <span class="fw-semibold">{{ $count }}</span>
                    </div>
                    @empty
                    <p class="text-muted mb-0">Sin datos.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- By priority --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Por prioridad</div>
                <div class="card-body">
                    @forelse($byPriority as $priority => $count)
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ ucfirst($priority ?: 'normal') }}</span>
                        <span class="fw-semibold">{{ $count }}</span>
                    </div>
                    @empty
                    <p class="text-muted mb-0">Sin datos.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
