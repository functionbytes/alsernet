@extends('layouts.theme')

@section('title', 'Layouts y componentes')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="card-title fw-bold mb-1">Layouts y componentes</h5>
                    <p class="card-subtitle text-muted mb-0">Gestiona layouts, partials y componentes reutilizables</p>
                </div>
                <a href="{{ route('settings.mailrelay.components.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nuevo componente
                </a>
            </div>

            {{-- Filters --}}
            <div class="card bg-light-info mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('settings.mailrelay.components.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="search" class="form-control" placeholder="Nombre o descripción..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select class="select2" name="type" class="form-select">
                                <option value="">Todos los tipos</option>
                                <option value="layout" {{ request('type') === 'layout' ? 'selected' : '' }}>Layout</option>
                                <option value="partial" {{ request('type') === 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="component" {{ request('type') === 'component' ? 'selected' : '' }}>Component</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="select2" name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-info flex-fill">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('settings.mailrelay.components.index') }}" class="btn btn-light">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Components List --}}
            @if($components->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    No se encontraron componentes. <a href="{{ route('settings.mailrelay.components.create') }}">Crea el primero</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Default</th>
                                <th>Orden</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($components as $component)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $component->name }}</div>
                                        <small class="text-muted">ID: {{ $component->id }}</small>
                                    </td>
                                    <td>
                                        @if($component->type === 'layout')
                                            <span class="badge bg-primary">Layout</span>
                                        @elseif($component->type === 'partial')
                                            <span class="badge bg-info">Partial</span>
                                        @else
                                            <span class="badge bg-secondary">Component</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($component->description, 50) }}</small>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-status"
                                                type="checkbox"
                                                data-id="{{ $component->id }}"
                                                {{ $component->status === 'active' ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        @if($component->is_default)
                                            <i class="fas fa-star text-warning" title="Layout por defecto"></i>
                                        @elseif($component->type === 'layout')
                                            <button type="button" class="btn btn-sm btn-link p-0 set-default" data-id="{{ $component->id }}" title="Establecer como default">
                                                <i class="far fa-star text-muted"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td>{{ $component->sort_order }}</td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button type="button" class="btn btn-sm btn-info preview-btn" data-id="{{ $component->id }}" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="{{ route('settings.mailrelay.components.edit', $component->id) }}" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('settings.mailrelay.components.destroy', $component->id) }}" method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Mostrando {{ $components->firstItem() ?? 0 }} a {{ $components->lastItem() ?? 0 }} de {{ $components->total() }} componentes
                    </div>
                    {{ $components->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista previa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content" style="min-height: 300px;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle status
    $('.toggle-status').on('change', function() {
        const id = $(this).data('id');
        const $switch = $(this);

        $.ajax({
            url: `/mailrelay/setting/components/${id}/toggle-status`,
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                }
            },
            error: function() {
                $switch.prop('checked', !$switch.prop('checked'));
                toastr.error('Error al cambiar el estado');
            }
        });
    });

    // Set as default
    $('.set-default').on('click', function() {
        const id = $(this).data('id');

        if (!confirm('¿Establecer este layout como predeterminado?')) {
            return;
        }

        $.ajax({
            url: `/mailrelay/setting/components/${id}/set-default`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                }
            },
            error: function() {
                toastr.error('Error al establecer como default');
            }
        });
    });

    // Preview
    $('.preview-btn').on('click', function() {
        const id = $(this).data('id');

        $('#previewModal').modal('show');
        $('#preview-content').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

        $.ajax({
            url: `/mailrelay/setting/components/${id}/preview-ajax`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#preview-content').html(response.html);
                } else {
                    $('#preview-content').html('<div class="alert alert-danger">Error: ' + response.error + '</div>');
                }
            },
            error: function() {
                $('#preview-content').html('<div class="alert alert-danger">Error al cargar la vista previa</div>');
            }
        });
    });

    // Delete confirmation
    $('.delete-form').on('submit', function(e) {
        if (!confirm('¿Estás seguro de eliminar este componente?')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
