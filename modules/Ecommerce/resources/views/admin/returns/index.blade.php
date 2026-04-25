@extends('layouts.theme')

@section('title', 'Devoluciones')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Devoluciones'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Devoluciones</h5>
                        <p class="small mb-0 text-muted">Administra las devoluciones de ordenes</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($returns->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Orden</th>
                                    <th>ID devolucion</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returns as $return)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.orders.show', $return->order) }}" class="text-decoration-none fw-semibold">{{ $return->order?->code ?? '—' }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ $return->return_code ?? $return->id }}</small></td>
                                        <td><small class="text-muted">{{ Str::limit($return->reason, 40) ?? '—' }}</small></td>
                                        <td><span class="badge bg-{{ $return->return_status === 'completed' ? 'success' : ($return->return_status === 'pending' ? 'warning' : 'info') }}">{{ $return->return_status }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('ecommerce.returns.show', $return) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-undo fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay devoluciones</h5>
                    </div>
                @endif
            </div>

            @if($returns->hasPages())
                <div class="card-footer">{{ $returns->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
