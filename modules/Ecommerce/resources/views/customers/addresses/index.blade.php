@extends('layouts.theme')

@section('title', 'Direcciones de ' . $customer->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Direcciones'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Direcciones de {{ $customer->name }}</h5>
                        <p class="small mb-0 text-muted">Administra las direcciones del cliente</p>
                    </div>
                    <a href="{{ route('ecommerce.customers.addresses.create', $customer) }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva direccion
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($addresses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Direccion</th>
                                    <th>Ciudad</th>
                                    <th>Pais</th>
                                    <th>Telefono</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($addresses as $address)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.customers.addresses.edit', [$customer, $address]) }}" class="text-decoration-none fw-semibold">{{ $address->name }}</a>
                                            @if($address->is_default)<span class="badge bg-warning ms-1">Predeterminada</span>@endif
                                        </td>
                                        <td><small class="text-muted">{{ $address->address }}</small></td>
                                        <td><small class="text-muted">{{ $address->city ?? '—' }}</small></td>
                                        <td><small class="text-muted">{{ $address->country ?? '—' }}</small></td>
                                        <td><small class="text-muted">{{ $address->phone ?? '—' }}</small></td>
                                        <td class="text-center">
                                            <a href="{{ route('ecommerce.customers.addresses.edit', [$customer, $address]) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('ecommerce.customers.addresses.destroy', [$customer, $address]) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar esta direccion?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-map-marker-alt fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay direcciones</h5>
                        <a href="{{ route('ecommerce.customers.addresses.create', $customer) }}" class="btn btn-primary">Crear primera direccion</a>
                    </div>
                @endif
            </div>

            @if($addresses->hasPages())
                <div class="card-footer">{{ $addresses->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
