@extends('layouts.theme')

@section('title', 'Metodos de envio')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Metodos de envio'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Metodos de envio</h5>
                        <p class="small mb-0 text-muted">Administra los metodos de envio disponibles</p>
                    </div>
                    <a href="{{ route('ecommerce.shipping.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo metodo
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($shippings->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Titulo</th>
                                    <th>Pais</th>
                                    <th>Reglas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shippings as $shipping)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.shipping.edit', $shipping) }}" class="text-decoration-none fw-semibold">{{ $shipping->title }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ $shipping->country ?? 'Global' }}</small></td>
                                        <td><span class="badge bg-info">{{ $shipping->rules?->count() ?? 0 }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.shipping.edit', $shipping) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.shipping.destroy', $shipping) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este metodo?')">Eliminar</button>
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
                        <i class="fas fa-truck fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay metodos de envio</h5>
                        <a href="{{ route('ecommerce.shipping.create') }}" class="btn btn-primary">Crear primer metodo</a>
                    </div>
                @endif
            </div>

            @if($shippings->hasPages())
                <div class="card-footer">{{ $shippings->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
