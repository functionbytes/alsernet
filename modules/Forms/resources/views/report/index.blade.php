@extends('layouts.theme')
@section('title', 'Formularios · Reporte')
@section('page_header')
    @include('core::components.card', ['title' => 'Formularios · Reporte'])
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/forms/css/report.css') }}?v={{ filemtime(module_path('Forms', 'public/css/report.css')) }}">
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
    <div>
        <h1 class="h4 mb-0 fw-bold">
            <i class="far fa-file-lines text-primary me-2"></i>Formularios del sitio Alvarez
        </h1>
        <p class="text-muted small mb-0 mt-1">
            Cada formulario del sitio (módulo alsernetforms) llega aquí como un ticket, agrupado por categoría. Esta pantalla es solo lectura; para crear/editar formularios ve a <a href="{{ route('forms.manage.index') }}">Gestionar formularios</a>.
        </p>
    </div>
</div>

@unless($formsEnabled)
    <div class="alert alert-warning">
        La integración de Formularios está desactivada en Settings → Integraciones. Los envíos que lleguen desde alsernetforms se rechazan (503) mientras esté así.
    </div>
@endunless

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Tickets totales</div>
                <div class="h3 fw-bold mb-0">{{ $totalTickets }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Abiertos ahora</div>
                <div class="h3 fw-bold mb-0">{{ $totalOpen }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Formularios activos</div>
                <div class="h3 fw-bold mb-0">{{ $rows->where('form.active', true)->count() }} / {{ $rows->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
            <h6 class="fw-bold mb-0">Tickets por día (últimos {{ count($trend['labels']) }} días)</h6>
            <div class="small">
                <span class="text-muted">{{ $trend['current_total'] }} vs {{ $trend['previous_total'] }} periodo anterior</span>
                @if($trend['change_percent'] > 0)
                    <span class="badge bg-success-subtle text-success">+{{ $trend['change_percent'] }}%</span>
                @elseif($trend['change_percent'] < 0)
                    <span class="badge bg-danger-subtle text-danger">{{ $trend['change_percent'] }}%</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary">Sin cambio</span>
                @endif
            </div>
        </div>
        <canvas id="formsTrendChart" height="70"></canvas>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Formulario</th>
                        <th>Estado</th>
                        <th class="text-end">Tickets totales</th>
                        <th class="text-end">Abiertos</th>
                        <th>Último envío</th>
                        <th class="text-end">Tickets</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>
                                @if($row['category'])
                                    <span class="forms-cat-dot forms-cat-{{ $row['category']->slug }}">
                                        <i class="{{ $row['category']->icon }}"></i>
                                    </span>
                                @else
                                    <span class="forms-cat-dot forms-cat-sin-categoria">
                                        <i class="far fa-circle-question"></i>
                                    </span>
                                @endif
                                {{ $row['form']->name }}
                                <div class="text-muted small"><code>{{ $row['form']->form_key }}</code></div>
                            </td>
                            <td>
                                @if(! $row['form']->active)
                                    <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                @elseif(! $row['category'])
                                    <span class="badge bg-danger-subtle text-danger">Sin categoría</span>
                                @else
                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $row['total'] }}</td>
                            <td class="text-end">
                                @if($row['open'] > 0)
                                    <span class="badge bg-primary-subtle text-primary">{{ $row['open'] }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td>
                                {{ $row['last_submitted_at']?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="text-end">
                                @if($row['category'])
                                    <a href="{{ route('manager.helpdesk.tickets.index', ['category' => $row['category']->id, 'source' => 'formulario']) }}"
                                       class="btn btn-sm btn-light">
                                        Ver tickets
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No hay formularios creados todavía. <a href="{{ route('forms.manage.index') }}">Crear el primero</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var canvas = document.getElementById('formsTrendChart');
    if (!canvas || typeof Chart === 'undefined') return;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json($trend['labels']),
            datasets: [{
                label: 'Tickets',
                data: @json($trend['series']),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.1)',
                tension: 0.3,
                fill: true,
                pointRadius: 2,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    });
})();
</script>
@endpush
