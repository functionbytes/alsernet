<div class="btn-group">
    <button class="btn btn-sm btn-outline-primary edit-supplier me-1" data-id="{{ $supplier->uid }}" title="Editar">
        <i class="fas fa-edit"></i>
    </button>
    <button class="btn btn-sm btn-outline-{{ $supplier->available ? 'warning' : 'success' }} toggle-supplier me-1" data-id="{{ $supplier->uid }}" title="{{ $supplier->available ? 'Desactivar' : 'Activar' }}">
        <i class="fas fa-{{ $supplier->available ? 'pause' : 'play' }}"></i>
    </button>
    <button class="btn btn-sm btn-outline-danger delete-supplier" data-id="{{ $supplier->uid }}" data-name="{{ $supplier->label }}" title="Eliminar">
        <i class="fas fa-trash"></i>
    </button>
</div>
