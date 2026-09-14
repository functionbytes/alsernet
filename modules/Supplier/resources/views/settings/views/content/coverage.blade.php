@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header border-bottom p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Cobertura de contenido IA</h5>
                        <p class="small mb-0 text-muted">
                            Productos registrados que <strong>no tienen ningún contenido IA</strong> asociado.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.content.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric global -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card h-100 {{ $total > 0 ? '' : 'bg-light-secondary' }}"
                             style="{{ $total > 0 ? 'background:#fce4ec;border-color:#f48fb1;' : '' }}">
                            <div class="card-body">
                                <h6 class="card-title mb-2" style="{{ $total > 0 ? 'color:#880e4f;' : '' }}">
                                    Productos sin contenido
                                </h6>
                                <h2 class="mb-1 fw-bold" style="{{ $total > 0 ? 'color:#880e4f;' : '' }}">
                                    {{ number_format($total) }}
                                </h2>
                                <small class="text-muted">Sin ninguna entrada en ai_contents</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desglose por proveedor -->
            <div class="card-body p-0">
                @if($bySupplier->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                        <p class="mb-0">Todos los productos tienen contenido IA asociado.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Proveedor</th>
                                    <th class="text-end">Productos sin contenido</th>
                                    <th class="text-end">% del total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bySupplier as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['supplier_label'] }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-danger">{{ number_format($row['count']) }}</span>
                                        </td>
                                        <td class="text-end text-muted small">
                                            @if($total > 0)
                                                {{ number_format(($row['count'] / $total) * 100, 1) }}%
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>Total</td>
                                    <td class="text-end">{{ number_format($total) }}</td>
                                    <td class="text-end">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>

@endsection
