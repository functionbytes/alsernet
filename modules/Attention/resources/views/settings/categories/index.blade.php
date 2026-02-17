@extends('layouts.theme')

@section('title', 'Categorías')

@section('content')
    @include('core::components.card', ['title' => 'Categorías de PQRSF'])
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Categorías</h5>
                        <p class="small mb-0 text-muted">Clasifica las solicitudes por temas (Servicios, Infraestructura, Salud, etc.)</p>
                    </div>
                    <a href="{{ route('settings.attention.categories.create') }}" class="btn btn-primary">Nueva categoría</a>
                </div>
            </div>
            <div class="card-body">
                @if($categories->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr>
                                        <td><strong>{{ $category->name }}</strong></td>
                                        <td><small class="text-muted">{{ Str::limit($category->description, 60) }}</small></td>
                                        <td class="text-center">
                                            @if($category->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-vertical"></i></a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('settings.attention.categories.edit', $category->id) }}">Editar</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger delete-btn" data-bs-toggle="modal" data-bs-target="#delete-modal" data-url="{{ route('settings.attention.categories.destroy', $category->id) }}" data-title="Eliminar: {{ $category->name }}">Eliminar</a></li>
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
                        <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay categorías creadas aún</p>
                        <a href="{{ route('settings.attention.categories.create') }}" class="btn btn-sm btn-primary">Crear primera categoría</a>
                    </div>
                @endif
            </div>
            @if($categories->hasPages())
                <div class="card-footer">{{ $categories->links() }}</div>
            @endif
        </div>
    </div>
    @include('core::components.delete')
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });
});
</script>
@endpush
