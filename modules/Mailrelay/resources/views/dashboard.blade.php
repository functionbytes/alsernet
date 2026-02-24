@extends('layouts.theme')

@section('title', 'Dashboard - Mail Relay')

@section('content')
<div class="widget-content searchable-container list">

    {{-- Header --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 fw-bold">Dashboard Mailrelay</h2>
                    <p class="text-muted mb-0">Bienvenido, aquí está lo que está sucediendo con tus campañas de email</p>
                </div>
                <div>
                    <a href="{{ route('mailrelay.campaigns.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Nueva campaña
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="card mb-3">
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <h6 class="card-title text-primary mb-2">Total suscriptores</h6>
                                    <h4 class="mb-1 fw-bold">{{ number_format($totalSubscribers ?? 0) }}</h4>
                                    <small class="text-muted">Registrados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <h6 class="card-title mb-2">Emails válidos</h6>
                                    <h4 class="mb-1 fw-bold">{{ number_format($validEmails ?? 0) }}</h4>
                                    <small class="text-muted">Validados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <h6 class="card-title text-warning mb-2">Emails inválidos</h6>
                                    <h4 class="mb-1 fw-bold">{{ number_format($invalidEmails ?? 0) }}</h4>
                                    <small class="text-muted">Rechazados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <h6 class="card-title text-info mb-2">Campañas activas</h6>
                                    <h4 class="mb-1 fw-bold">{{ number_format($activeCampaigns ?? 0) }}</h4>
                                    <small class="text-muted">En ejecución</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-3 mb-3">
        {{-- Validation Statistics Chart --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i>Estadísticas de validación</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="validationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Campaign Performance Chart --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2"></i>Rendimiento de campañas</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="campaignChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity Row --}}
    <div class="row g-3">
        {{-- Recent Imports --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-upload me-2"></i>Importaciones recientes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap mb-0">
                            <thead class="header-item">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Archivo</th>
                                    <th>Registros</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentImports ?? [] as $import)
                                <tr>
                                    <td>{{ $import->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <i class="fas fa-file-alt text-muted me-2"></i>
                                        {{ $import->filename }}
                                    </td>
                                    <td>{{ number_format($import->total_records) }}</td>
                                    <td>
                                        @if($import->status === 'completed')
                                            <span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold fs-2">Completado</span>
                                        @elseif($import->status === 'processing')
                                            <span class="badge bg-info-subtle text-info rounded-3 py-2 fw-semibold fs-2">Procesando</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger rounded-3 py-2 fw-semibold fs-2">Fallido</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fs-3 d-block mb-2"></i>
                                            No hay importaciones recientes
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(count($recentImports ?? []) > 0)
                    <div class="text-center mt-3 pt-3 border-top">
                        <a href="{{ route('mailrelay.imports.index') }}" class="btn btn-sm btn-outline-primary">
                            Ver todas las importaciones <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Campaign Metrics --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-envelope-open me-2"></i>Métricas de campañas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap mb-0">
                            <thead class="header-item">
                                <tr>
                                    <th>Campaña</th>
                                    <th>Enviados</th>
                                    <th>Abiertos</th>
                                    <th>Clics</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($campaignMetrics ?? [] as $campaign)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">{{ $campaign->name }}</span>
                                            <small class="text-muted">{{ $campaign->sent_at->format('M d') }}</small>
                                        </div>
                                    </td>
                                    <td>{{ number_format($campaign->sent_count) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="fw-semibold text-success">{{ $campaign->open_rate }}%</small>
                                            <div class="progress" style="width: 60px; height: 6px;">
                                                <div class="progress-bar bg-success" style="width: {{ $campaign->open_rate }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="fw-semibold text-primary">{{ $campaign->click_rate }}%</small>
                                            <div class="progress" style="width: 60px; height: 6px;">
                                                <div class="progress-bar bg-primary" style="width: {{ $campaign->click_rate }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-bullhorn fs-3 d-block mb-2"></i>
                                            No hay campañas enviadas todavía
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(count($campaignMetrics ?? []) > 0)
                    <div class="text-center mt-3 pt-3 border-top">
                        <a href="{{ route('mailrelay.campaigns.index') }}" class="btn btn-sm btn-outline-primary">
                            Ver todas las campañas <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation Statistics Chart
    const validationCtx = document.getElementById('validationChart');
    if (validationCtx) {
        new Chart(validationCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Válidos', 'Inválidos', 'Pendientes', 'Desechables'],
                datasets: [{
                    data: [
                        {{ $validEmails ?? 850 }},
                        {{ $invalidEmails ?? 45 }},
                        {{ $pendingEmails ?? 30 }},
                        {{ $disposableEmails ?? 25 }}
                    ],
                    backgroundColor: ['#198754', '#dc3545', '#ffc107', '#6c757d'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Campaign Performance Chart
    const campaignCtx = document.getElementById('campaignChart');
    if (campaignCtx) {
        new Chart(campaignCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels ?? ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun']) !!},
                datasets: [{
                    label: 'Emails enviados',
                    data: {!! json_encode($emailsSent ?? [1200, 1900, 1500, 2100, 2400, 2800]) !!},
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Aperturas',
                    data: {!! json_encode($emailsOpened ?? [800, 1300, 1000, 1500, 1700, 2000]) !!},
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Clics',
                    data: {!! json_encode($emailsClicked ?? [300, 500, 400, 600, 700, 850]) !!},
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.parsed.y || 0;
                                return label + ': ' + value.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { drawBorder: false, color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
@endpush
