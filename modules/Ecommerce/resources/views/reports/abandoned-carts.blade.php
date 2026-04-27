@extends('layouts.theme')

@section('title', 'Carritos abandonados')

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
                <li class="nav-item"><a class="nav-link active" href="{{ route('ecommerce.reports.abandoned-carts') }}">Carritos abandonados</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.search') }}">Busquedas</a></li>
            </ul>
        </div>
    </div>

    {{-- Inactivity filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label small mb-1">Sin actividad por mas de</label>
                    <select name="hours" class="form-select form-select-sm">
                        <option value="6"   @selected($hours == 6)>6 horas</option>
                        <option value="24"  @selected($hours == 24)>24 horas</option>
                        <option value="48"  @selected($hours == 48)>48 horas</option>
                        <option value="168" @selected($hours == 168)>1 semana</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
            </form>
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Carritos abandonados</p>
                    <h3 class="mb-0 fw-bold">{{ number_format($stats['count']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Valor total perdido</p>
                    <h3 class="mb-0 fw-bold text-danger">${{ number_format($stats['total_value'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Carts table --}}
    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h6 class="fw-bold mb-0">Clientes con carritos abandonados</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">Cliente</th>
                            <th>Email</th>
                            <th class="text-end">Items</th>
                            <th class="text-end">Valor estimado</th>
                            <th>Ultima actividad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($carts as $c)
                            <tr>
                                <td class="px-4">{{ $c->name }}</td>
                                <td class="small text-muted">{{ $c->email }}</td>
                                <td class="text-end">{{ $c->items_count }}</td>
                                <td class="text-end fw-bold">${{ number_format($c->cart_total, 2) }}</td>
                                <td class="small text-muted">{{ \Carbon\Carbon::parse($c->last_activity)->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay carritos abandonados en este período</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($carts->hasPages())
            <div class="card-footer bg-white border-top">
                {{ $carts->links() }}
            </div>
        @endif
    </div>
@endsection
