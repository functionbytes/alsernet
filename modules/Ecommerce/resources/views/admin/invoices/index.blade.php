@extends('layouts.theme')

@section('title', 'Facturas')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Facturas'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Facturas</h5>
                        <p class="small mb-0 text-muted">Administra las facturas de ordenes</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($invoices->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Codigo</th>
                                    <th>Referencia</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $invoice)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.invoices.show', $invoice) }}" class="text-decoration-none fw-semibold">{{ $invoice->code ?? $invoice->id }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ class_basename($invoice->reference_type) }} #{{ $invoice->reference_id }}</small></td>
                                        <td><small class="text-muted">{{ $invoice->customer_name ?? '—' }}</small></td>
                                        <td><small class="text-muted">{{ $invoice->created_at?->format('d/m/Y') ?? '—' }}</small></td>
                                        <td>{{ number_format($invoice->amount, 2) }}</td>
                                        <td><span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'pending' ? 'warning' : 'secondary') }}">{{ $invoice->status }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('ecommerce.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-file-invoice fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay facturas</h5>
                    </div>
                @endif
            </div>

            @if($invoices->hasPages())
                <div class="card-footer">{{ $invoices->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
