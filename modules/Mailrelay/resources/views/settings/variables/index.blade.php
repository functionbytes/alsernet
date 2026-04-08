@extends('layouts.theme')

@section('title', 'Variables de templates')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="card-title fw-bold mb-1">Variables de templates</h5>
                    <p class="card-subtitle text-muted mb-0">Gestiona variables dinámicas para usar en templates</p>
                </div>
                <a href="{{ route('settings.mailrelay.variables.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nueva variable
                </a>
            </div>

            {{-- Filters --}}
            <div class="card bg-light-info mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('settings.mailrelay.variables.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="search" class="form-control" placeholder="Nombre o clave..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Categoría</label>
                            <select class="select2" name="category" class="form-select">
                                <option value="">Todas</option>
                                <option value="system" {{ request('category') === 'system' ? 'selected' : '' }}>Sistema</option>
                                <option value="customer" {{ request('category') === 'customer' ? 'selected' : '' }}>Cliente</option>
                                <option value="order" {{ request('category') === 'order' ? 'selected' : '' }}>Pedido</option>
                                <option value="document" {{ request('category') === 'document' ? 'selected' : '' }}>Documento</option>
                                <option value="custom" {{ request('category') === 'custom' ? 'selected' : '' }}>Personalizada</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Módulo</label>
                            <input type="text" name="module" class="form-control" placeholder="core, orders..." value="{{ request('module') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select class="select2" name="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Sistema</label>
                            <select class="select2" name="is_system" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" {{ request('is_system') === '1' ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ request('is_system') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-info flex-fill">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('settings.mailrelay.variables.index') }}" class="btn btn-light">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Variables List --}}
            @if($variables->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    No se encontraron variables. <a href="{{ route('settings.mailrelay.variables.create') }}">Crea la primera</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Variable</th>
                                <th>Clave</th>
                                <th>Categoría</th>
                                <th>Módulo</th>
                                <th>Ejemplo</th>
                                <th>Estado</th>
                                <th>Sistema</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($variables as $variable)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $variable->name }}</div>
                                        @if($variable->description)
                                            <small class="text-muted">{{ Str::limit($variable->description, 40) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="small bg-light px-2 py-1 rounded">{{ $variable->getPlaceholder() }}</code>
                                        <button type="button" class="btn btn-sm btn-link p-0 copy-btn" data-text="{{ $variable->getPlaceholder() }}" title="Copiar">
                                            <i class="fas fa-copy text-muted"></i>
                                        </button>
                                    </td>
                                    <td>
                                        @if($variable->category === 'system')
                                            <span class="badge bg-primary">Sistema</span>
                                        @elseif($variable->category === 'customer')
                                            <span class="badge bg-success">Cliente</span>
                                        @elseif($variable->category === 'order')
                                            <span class="badge bg-warning">Pedido</span>
                                        @elseif($variable->category === 'document')
                                            <span class="badge bg-info">Documento</span>
                                        @else
                                            <span class="badge bg-secondary">Personalizada</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $variable->module ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted font-monospace">{{ Str::limit($variable->example_value, 30) }}</small>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-status"
                                                type="checkbox"
                                                data-id="{{ $variable->id }}"
                                                {{ $variable->status === 'active' ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        @if($variable->is_system)
                                            <i class="fas fa-lock text-warning" title="Variable del sistema"></i>
                                        @else
                                            <i class="fas fa-unlock text-muted"></i>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <a href="{{ route('settings.mailrelay.variables.edit', $variable->id) }}" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if(!$variable->is_system)
                                                <form action="{{ route('settings.mailrelay.variables.destroy', $variable->id) }}" method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
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
                        Mostrando {{ $variables->firstItem() ?? 0 }} a {{ $variables->lastItem() ?? 0 }} de {{ $variables->total() }} variables
                    </div>
                    {{ $variables->links() }}
                </div>
            @endif
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
            url: `/mailrelay/setting/variables/${id}/toggle-status`,
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

    // Copy to clipboard
    $('.copy-btn').on('click', function() {
        const text = $(this).data('text');
        navigator.clipboard.writeText(text).then(function() {
            toastr.success('Copiado al portapapeles');
        });
    });

    // Delete confirmation
    $('.delete-form').on('submit', function(e) {
        if (!confirm('¿Estás seguro de eliminar esta variable?')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
