@extends('layouts.theme')

@section('title', 'Analytics de busqueda')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Reportes'])
@endsection

@section('content')
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
                <li class="nav-item"><a class="nav-link active" href="{{ route('ecommerce.reports.search') }}">Busquedas</a></li>
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
                        <option value="7"   @selected($period == 7)>Ultimos 7 dias</option>
                        <option value="30"  @selected($period == 30)>Ultimos 30 dias</option>
                        <option value="90"  @selected($period == 90)>Ultimos 90 dias</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Top busquedas (ultimos {{ $period }} dias)</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Termino buscado</th>
                                <th class="text-end">Busquedas</th>
                                <th class="text-end">Resultados promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topSearches as $s)
                                <tr>
                                    <td><code>{{ $s->query }}</code></td>
                                    <td class="text-end">{{ number_format($s->searches) }}</td>
                                    <td class="text-end">{{ round($s->avg_results, 1) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Sin datos para este periodo</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">Busquedas sin resultados</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Termino buscado</th>
                                <th class="text-end">Busquedas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($zeroResults as $s)
                                <tr>
                                    <td><code>{{ $s->query }}</code></td>
                                    <td class="text-end">{{ number_format($s->searches) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">
                                        Todas las busquedas devuelven resultados
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
