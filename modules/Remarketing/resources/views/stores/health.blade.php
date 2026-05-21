@extends('layouts.theme')

@section('title', 'Salud de tienda')

@section('page_header')
    @include('core::components.card', ['title' => 'Salud de ' . $store->name])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Chequeo de salud</h5>
                    <small class="text-muted">{{ $store->domain }}</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Check</th>
                                    <th>Estado</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>DNS</td>
                                    <td>
                                        @if($check['dns_valid'] ?? false)
                                            <span class="badge bg-success-subtle text-success">OK</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Fallo</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $check['dns_message'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td>MX</td>
                                    <td>
                                        @if($check['mx_valid'] ?? false)
                                            <span class="badge bg-success-subtle text-success">OK</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Fallo</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $check['mx_message'] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td>SPF</td>
                                    <td>
                                        @if($check['spf_valid'] ?? false)
                                            <span class="badge bg-success-subtle text-success">OK</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Atención</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $check['spf_message'] ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('remarketing.stores.index') }}" class="btn btn-light w-100">Volver</a>
                </div>
            </div>
        </div>
    </div>

@endsection
