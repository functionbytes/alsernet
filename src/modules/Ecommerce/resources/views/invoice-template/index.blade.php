@extends('layouts.theme')

@section('title', 'Plantilla de factura')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Plantilla de factura'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header p-4 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Plantilla de factura</h5>
                                <p class="small mb-0 text-muted">Vista previa y configuracion de la plantilla de facturas</p>
                            </div>
                            <a href="{{ route('ecommerce.invoices.index') }}" target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-1"></i> Ver facturas
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%">Configuracion</th>
                                        <th>Valor actual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Prefijo de factura</td>
                                        <td>
                                            <code>{{ config('ecommerce.invoice_code_prefix', 'FAC-') }}</code>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Nota en factura</td>
                                        <td>
                                            @php
                                                $invoiceNote = config('ecommerce.invoice_note', '');
                                            @endphp
                                            @if($invoiceNote)
                                                <span class="text-muted small">{{ $invoiceNote }}</span>
                                            @else
                                                <span class="text-muted small fst-italic">Sin nota configurada</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Creacion automatica</td>
                                        <td>
                                            @if(config('ecommerce.auto_create_invoice', false))
                                                <span class="badge bg-success">Habilitada</span>
                                            @else
                                                <span class="badge bg-secondary">Deshabilitada</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('settings.ecommerce.index') }}#invoices" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-cog me-1"></i> Editar configuracion de facturas
                        </a>
                        <a href="{{ route('ecommerce.invoices.index') }}" class="btn btn-light w-100">
                            Ver listado de facturas
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-info-circle me-1 text-primary"></i> Informacion
                        </h6>
                        <div class="alert alert-light border mb-0">
                            <p class="small mb-0 text-muted">
                                Las facturas se generan automaticamente al confirmar el pago de una orden. Puedes ver y descargar cada factura desde el listado de facturas o desde el detalle de la orden correspondiente.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
