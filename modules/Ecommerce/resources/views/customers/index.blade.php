@extends('layouts.theme')

@section('title', 'Clientes')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Clientes'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Clientes</h5>
                        <p class="small mb-0 text-muted">Administra los clientes de la tienda</p>
                    </div>
                    <a href="{{ route('ecommerce.customers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo cliente
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($customers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Email</th>
                                    <th>Telefono</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customers as $customer)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($customer->avatar)
                                                    <img src="{{ $customer->avatar }}" class="rounded-circle object-fit-cover flex-shrink-0" width="36" height="36">
                                                @else
                                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 size-9"><i class="fas fa-user text-muted"></i></div>
                                                @endif
                                                <a href="{{ route('ecommerce.customers.edit', $customer) }}" class="text-decoration-none fw-semibold">{{ $customer->name }}</a>
                                            </div>
                                        </td>
                                        <td><small class="text-muted">{{ $customer->email }}</small></td>
                                        <td><small class="text-muted">{{ $customer->phone ?? '—' }}</small></td>
                                        <td><span class="badge bg-{{ $customer->status->value === 'active' ? 'success' : 'secondary' }}">{{ $customer->status->value }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.customers.edit', $customer) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.customers.destroy', $customer) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este cliente?')">Eliminar</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay clientes</h5>
                        <a href="{{ route('ecommerce.customers.create') }}" class="btn btn-primary">Crear primer cliente</a>
                    </div>
                @endif
            </div>

            @if($customers->hasPages())
                <div class="card-footer">{{ $customers->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
