@extends('layouts.theme')

@section('title', 'Impuestos')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Impuestos'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Impuestos</h5>
                        <p class="small mb-0 text-muted">Administra los impuestos</p>
                    </div>
                    <a href="{{ route('ecommerce.taxes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo impuesto
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($taxes->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Titulo</th>
                                    <th>Porcentaje</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($taxes as $tax)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.taxes.edit', $tax) }}" class="text-decoration-none fw-semibold">{{ $tax->title ?? 'Sin titulo' }}</a>
                                        </td>
                                        <td><span class="badge bg-info">{{ $tax->percentage }}%</span></td>
                                        <td><small class="text-muted">{{ $tax->priority ?? '—' }}</small></td>
                                        <td><span class="badge bg-{{ $tax->status === 'published' ? 'success' : 'secondary' }}">{{ $tax->status === 'published' ? 'Publicado' : 'Borrador' }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.taxes.edit', $tax) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.taxes.destroy', $tax) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este impuesto?')">Eliminar</button>
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
                        <i class="fas fa-percentage fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay impuestos</h5>
                        <a href="{{ route('ecommerce.taxes.create') }}" class="btn btn-primary">Crear primer impuesto</a>
                    </div>
                @endif
            </div>

            @if($taxes->hasPages())
                <div class="card-footer">{{ $taxes->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
