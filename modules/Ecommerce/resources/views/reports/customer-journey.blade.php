@extends('layouts.theme')

@section('title', 'Customer journey')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Reportes'])

    @include('core::components.alerts')

    {{-- Report navigation --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <ul class="nav nav-pills nav-fill">
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.index') }}">Resumen</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.comparison') }}">Comparativa</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.funnel') }}">Embudo</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.customer-ltv') }}">LTV clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.abandoned-carts') }}">Carritos abandonados</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.search') }}">Busquedas</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.margin') }}">Margen</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.forecast') }}">Pronostico</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ route('ecommerce.reports.journey') }}">Journey</a></li>
            </ul>
        </div>
    </div>

    {{-- Period filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label small mb-1">Periodo</label>
                    <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="7"  @selected($period == 7)>Ultimos 7 dias</option>
                        <option value="14" @selected($period == 14)>Ultimos 14 dias</option>
                        <option value="30" @selected($period == 30)>Ultimos 30 dias</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Sesiones promedio antes de comprar</p>
                    <h3 class="mb-0 fw-bold">{{ $avgSessions > 0 ? $avgSessions : '—' }}</h3>
                    <small class="text-muted">Para ordenes pagadas en los ultimos {{ $period }} dias</small>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">URLs unicas visitadas</p>
                    <h3 class="mb-0 fw-bold">{{ number_format($topUrls->count()) }}</h3>
                    <small class="text-muted">Top 20 mostradas</small>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Productos vistos</p>
                    <h3 class="mb-0 fw-bold">{{ number_format($topProducts->count()) }}</h3>
                    <small class="text-muted">Con al menos una vista</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Note if no data --}}
    @if($topUrls->isEmpty())
        <div class="alert alert-warning d-flex gap-2 align-items-center mb-4">
            <i class="fas fa-triangle-exclamation"></i>
            <span>
                Sin datos de page views. El middleware <code>TrackPageView</code> debe estar registrado en las rutas de la tienda para recopilar datos.
            </span>
        </div>
    @endif

    <div class="row g-3">
        {{-- Top URLs --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-bold mb-0">Top URLs visitadas (ultimos {{ $period }} dias)</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>URL</th>
                                <th class="text-end">Vistas</th>
                                <th class="text-end">Sesiones unicas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topUrls as $u)
                                <tr>
                                    <td>
                                        <span class="text-break small" title="{{ $u->url }}">
                                            {{ Str::limit(parse_url($u->url, PHP_URL_PATH) ?: $u->url, 60) }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($u->views) }}</td>
                                    <td class="text-end">{{ number_format($u->unique_sessions) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Sin datos</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Top products viewed --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-bold mb-0">Productos mas vistos (ultimos {{ $period }} dias)</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-end">Vistas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $p)
                                <tr>
                                    <td>{{ $p->name }}</td>
                                    <td class="text-end">{{ number_format($p->views) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">Sin datos</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
