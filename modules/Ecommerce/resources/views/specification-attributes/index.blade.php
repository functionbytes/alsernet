@extends('layouts.theme')

@section('title', 'Atributos de especificaciones')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Atributos de especificaciones'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Atributos de especificaciones</h5>
                        <p class="small mb-0 text-muted">Define los campos de especificaciones disponibles para los productos</p>
                    </div>
                    <a href="{{ route('ecommerce.specification-attributes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo atributo
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($specificationAttributes->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Grupo</th>
                                    <th>Tipo</th>
                                    <th>Valor por defecto</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($specificationAttributes as $attribute)
                                    @php
                                        $typeBadges = [
                                            'text'     => 'secondary',
                                            'textarea' => 'info',
                                            'select'   => 'primary',
                                            'checkbox' => 'warning',
                                            'radio'    => 'success',
                                        ];
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $attribute->name }}</td>
                                        <td>
                                            @if($attribute->group)
                                                <span class="badge bg-info">{{ $attribute->group->name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $typeBadges[$attribute->type] ?? 'secondary' }}">{{ $attribute->type }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ $attribute->default_value ?? '—' }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.specification-attributes.edit', $attribute) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.specification-attributes.destroy', $attribute) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este atributo?')">Eliminar</button>
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
                        <i class="fas fa-sliders fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay atributos de especificaciones</h5>
                        <a href="{{ route('ecommerce.specification-attributes.create') }}" class="btn btn-primary">Crear primer atributo</a>
                    </div>
                @endif
            </div>

            @if($specificationAttributes->hasPages())
                <div class="card-footer">{{ $specificationAttributes->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
