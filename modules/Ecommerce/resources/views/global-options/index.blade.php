@extends('layouts.theme')

@section('title', 'Opciones globales')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Opciones globales'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Opciones globales</h5>
                        <p class="small mb-0 text-muted">Administra las opciones que se aplican a los productos</p>
                    </div>
                    <a href="{{ route('ecommerce.global-options.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva opcion global
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($globalOptions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Valores</th>
                                    <th>Requerido</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($globalOptions as $option)
                                    @php
                                        $typeBadge = match($option->option_type) {
                                            'text'     => 'info',
                                            'dropdown' => 'primary',
                                            'checkbox' => 'warning',
                                            'radio'    => 'success',
                                            default    => 'secondary',
                                        };
                                        $typeLabel = match($option->option_type) {
                                            'text'     => 'Texto',
                                            'dropdown' => 'Desplegable',
                                            'checkbox' => 'Casillas',
                                            'radio'    => 'Radio',
                                            default    => $option->option_type,
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.global-options.edit', $option) }}" class="text-decoration-none fw-semibold">
                                                {{ $option->name }}
                                            </a>
                                        </td>
                                        <td><span class="badge bg-{{ $typeBadge }}">{{ $typeLabel }}</span></td>
                                        <td><span class="badge bg-light text-dark">{{ $option->values_count }}</span></td>
                                        <td>
                                            @if($option->required)
                                                <span class="badge bg-danger">Sí</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.global-options.edit', $option) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.global-options.destroy', $option) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta opcion global?')">Eliminar</button>
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
                        <i class="fas fa-list-check fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay opciones globales</h5>
                        <a href="{{ route('ecommerce.global-options.create') }}" class="btn btn-primary">Crear primera opcion</a>
                    </div>
                @endif
            </div>

            @if($globalOptions->hasPages())
                <div class="card-footer">{{ $globalOptions->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
