@extends('layouts.theme')

@section('page_title', 'Web Vitals · '.$normalizedPath)

@section('content')
    @include('core::components.alerts')

    <div class="mb-3">
        <a href="{{ route('settings.seo.web-vitals.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1">{{ $normalizedPath }}</h5>
                    <p class="small mb-0 text-muted">p75 de los últimos 28 días (desde {{ $since->format('d/m/Y') }}).</p>
                </div>
            </div>

            <div class="row g-3">
                @foreach($p75 as $metric => $data)
                    @php
                        $color = match($data['rating']) {
                            'good' => 'success',
                            'needs-improvement' => 'warning',
                            'poor' => 'danger',
                            default => 'secondary',
                        };
                        $unit = in_array($metric, ['LCP', 'INP', 'FCP', 'TTFB']) ? 'ms' : '';
                        $display = $metric === 'CLS' ? number_format((float)$data['value'], 3) : number_format((float)$data['value']);
                    @endphp
                    <div class="col-lg col-md-6">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0 fw-bold">{{ $metric }}</h6>
                                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                        {{ ['good' => 'Bueno', 'needs-improvement' => 'Mejorable', 'poor' => 'Pobre', 'unknown' => '—'][$data['rating']] }}
                                    </span>
                                </div>
                                <h4 class="mb-1 fw-bold">{{ $display }}<small class="text-muted fs-6">{{ $unit }}</small></h4>
                                <small class="text-muted">{{ number_format($data['samples']) }} muestras</small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($trend->count() > 0)
        <div class="card">
            <div class="card-header p-4 border-bottom">
                <h6 class="fw-bold mb-0">Evolución diaria</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Métrica</th>
                            <th class="text-end">Promedio</th>
                            <th class="text-end">Muestras</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trend as $row)
                            @php
                                $unit = in_array($row->metric, ['LCP', 'INP', 'FCP', 'TTFB']) ? 'ms' : '';
                                $avg = $row->metric === 'CLS' ? number_format((float)$row->avg_value, 3) : number_format((float)$row->avg_value);
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                                <td>{{ $row->metric }}</td>
                                <td class="text-end">{{ $avg }}{{ $unit }}</td>
                                <td class="text-end">{{ number_format($row->samples) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
