@extends('layouts.theme')

@section('page_title', 'Core Web Vitals')

@section('content')
    @include('core::components.alerts')

    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Core Web Vitals</h5>
                    <p class="small mb-0 text-muted">
                        p75 de los últimos 28 días. Basado en <strong>{{ number_format($totalSamples) }}</strong> muestras enviadas por los navegadores de los visitantes.
                    </p>
                </div>
                <span class="badge bg-light-secondary text-secondary">Desde {{ $since->format('d/m/Y') }}</span>
            </div>

            @if($totalSamples === 0)
                <div class="alert alert-info mb-0">
                    <strong>Sin datos aún.</strong> Añade <code>@{{'@'}}seoWebVitalsBeacon</code> al layout público para empezar a recopilar métricas desde los navegadores de tus usuarios.
                </div>
            @else
                <div class="row g-3">
                    @foreach($globalP75 as $metric => $data)
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
                                    <h3 class="mb-1 fw-bold">{{ $display }}<small class="text-muted fs-6">{{ $unit }}</small></h3>
                                    <small class="text-muted">{{ number_format($data['samples']) }} muestras</small>
                                    <hr class="my-2">
                                    <small class="text-muted d-block">
                                        Umbral bueno: &le; {{ $thresholds[$metric]['good'] }}{{ $unit }}<br>
                                        Umbral pobre: &gt; {{ $thresholds[$metric]['poor'] }}{{ $unit }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if($worstPages->count() > 0)
        <div class="card mb-4">
            <div class="card-header p-4 border-bottom">
                <h6 class="fw-bold mb-0">Páginas con métricas pobres</h6>
                <p class="small mb-0 text-muted">URL + métrica con más muestras en estado "poor" en los últimos 28 días.</p>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Ruta</th>
                            <th>Métrica</th>
                            <th class="text-end">Muestras</th>
                            <th class="text-end">Promedio</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($worstPages as $row)
                            @php
                                $unit = in_array($row->metric, ['LCP', 'INP', 'FCP', 'TTFB']) ? 'ms' : '';
                                $avg = $row->metric === 'CLS' ? number_format((float)$row->avg_value, 3) : number_format((float)$row->avg_value);
                            @endphp
                            <tr>
                                <td><code class="small">{{ $row->url_path }}</code></td>
                                <td><span class="badge bg-danger-subtle text-danger">{{ $row->metric }}</span></td>
                                <td class="text-end">{{ number_format($row->samples) }}</td>
                                <td class="text-end">{{ $avg }}{{ $unit }}</td>
                                <td class="text-end">
                                    <a href="{{ route('setting.seo.web-vitals.show', ['path' => ltrim($row->url_path, '/')]) }}"
                                       class="btn btn-sm btn-outline-secondary">Ver detalle</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($byDevice->count() > 0)
        <div class="card">
            <div class="card-header p-4 border-bottom">
                <h6 class="fw-bold mb-0">Por dispositivo</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Dispositivo</th>
                            <th>Métrica</th>
                            <th class="text-end">Promedio</th>
                            <th class="text-end">Muestras</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byDevice as $row)
                            @php
                                $unit = in_array($row->metric, ['LCP', 'INP', 'FCP', 'TTFB']) ? 'ms' : '';
                                $avg = $row->metric === 'CLS' ? number_format((float)$row->avg_value, 3) : number_format((float)$row->avg_value);
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ strtoupper($row->device) }}</td>
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
