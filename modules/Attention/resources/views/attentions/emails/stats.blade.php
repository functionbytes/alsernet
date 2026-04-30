@extends('layouts.theme')

@section('title', 'Estadísticas de emails')

@section('page_header')
    @include('core::components.card', ['title' => 'Estadísticas de emails'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Header --}}
        <div class="card mb-4">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Estadísticas de emails peticiones</h5>
                        <p class="small mb-0 text-muted">Resumen de todos los correos enviados por el sistema</p>
                    </div>
                    <a href="{{ route('emails.history') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-1"></i> Ver historial
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="card-title text-muted mb-0">Total enviados</h6>
                            <span class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                                <i class="fas fa-envelope text-primary"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-1">{{ number_format($stats['total']) }}</h3>
                        <small class="text-muted">Todos los emails registrados</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="card-title text-muted mb-0">Hoy</h6>
                            <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                                <i class="fas fa-calendar-day text-success"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-1">{{ number_format($stats['today']) }}</h3>
                        <small class="text-muted">Emails registrados hoy</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="card-title text-muted mb-0">Esta semana</h6>
                            <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                                <i class="fas fa-calendar-week text-warning"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-1">{{ number_format($stats['this_week']) }}</h3>
                        <small class="text-muted">Últimos 7 días</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="card-title text-muted mb-0">Este mes</h6>
                            <span class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                                <i class="fas fa-calendar text-info"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-1">{{ number_format($stats['this_month']) }}</h3>
                        <small class="text-muted">Mes actual</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">

            {{-- Estado breakdown --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Estado de envíos</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $total = max($stats['total'], 1);
                            $sentPct = round($stats['sent'] / $total * 100);
                            $failedPct = round($stats['failed'] / $total * 100);
                            $pendingPct = round($stats['pending'] / $total * 100);
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-semibold text-success">Enviados</span>
                                <span class="small fw-bold">{{ number_format($stats['sent']) }} ({{ $sentPct }}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $sentPct }}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-semibold text-danger">Fallidos</span>
                                <span class="small fw-bold">{{ number_format($stats['failed']) }} ({{ $failedPct }}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: {{ $failedPct }}%"></div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-semibold text-warning">En cola</span>
                                <span class="small fw-bold">{{ number_format($stats['pending']) }} ({{ $pendingPct }}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: {{ $pendingPct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Por tipo --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Por tipo de email</h6>
                    </div>
                    <div class="card-body p-0">
                        @php
                            $typeLabels = \Modules\Attention\Models\AttentionMail::EMAIL_TYPE_LABELS;
                        @endphp
                        @forelse($stats['by_type'] as $type => $count)
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <span class="small">{{ $typeLabels[$type] ?? ucfirst($type) }}</span>
                                <span class="badge bg-secondary-subtle text-secondary fw-bold">{{ number_format($count) }}</span>
                            </div>
                        @empty
                            <div class="px-3 py-4 text-center text-muted">Sin datos</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Gráfico últimos 7 días --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Últimos 7 días</h6>
                    </div>
                    <div class="card-body p-2">
                        <div id="emailDailyChart" style="height: 220px;"></div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Últimos 20 emails --}}
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Últimos emails enviados</h5>
                        <p class="small mb-0 text-muted">Los 20 correos más recientes registrados en el sistema</p>
                    </div>
                    <span class="badge bg-light text-dark fs-6">{{ $recent->count() }} registros</span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($recent->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>peticiones</th>
                                    <th>Asunto</th>
                                    <th>Tipo</th>
                                    <th>Destinatario</th>
                                    <th class="text-center">Estado</th>
                                    <th>Enviado por</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent as $mail)
                                    <tr>
                                        <td>
                                            @if($mail->attention)
                                                <a href="{{ route('attention.show', $mail->attention->uid) }}"
                                                   class="text-decoration-none fw-semibold small">
                                                    {{ $mail->attention->radicado }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;"
                                                  title="{{ $mail->subject }}">
                                                {{ Str::limit($mail->subject, 40) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ $mail->email_type_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($mail->recipient_email, 30) }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($mail->status === 'sent')
                                                <span class="badge bg-success-subtle text-success">Enviado</span>
                                            @elseif($mail->status === 'failed')
                                                <span class="badge bg-danger-subtle text-danger">Fallido</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">En cola</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $mail->sender->name ?? 'Sistema' }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $mail->created_at->format('d/m/Y H:i') }}
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-envelope-open-text fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay emails registrados</h6>
                            <p class="text-muted mb-0">Aún no se han enviado correos desde el sistema peticiones.</p>
                        </div>
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

    // Build daily chart data
    var rawData = @json($dailyData);
    var chartData = [];
    var today = new Date();

    for (var i = 6; i >= 0; i--) {
        var d = new Date(today);
        d.setDate(d.getDate() - i);
        var key = d.toISOString().slice(0, 10);
        var label = d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
        chartData.push({ day: label, count: rawData[key] || 0 });
    }

    $('#emailDailyChart').dxChart({
        dataSource: chartData,
        series: [{
            argumentField: 'day',
            valueField: 'count',
            name: 'Emails',
            type: 'bar',
            color: '#b10100',
        }],
        argumentAxis: {
            label: { font: { size: 11 } }
        },
        valueAxis: {
            label: { font: { size: 11 } },
            allowDecimals: false,
        },
        legend: { visible: false },
        tooltip: {
            enabled: true,
            customizeTooltip: function (info) {
                return { text: info.value + ' emails' };
            }
        },
        size: { height: 210 },
    });
});
</script>
@endpush
