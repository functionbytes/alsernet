@extends('layouts.app')

@section('title', 'Asignar Almacenes: ' . $user->full_name)

@section('content')
<div class="container-fluid px-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-warehouse"></i> Almacenes de {{ $user->full_name }}
                    </h1>
                    <p class="text-muted mt-2">{{ $user->email }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.warehouse-assignment.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Almacenes Asignados -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle"></i> Almacenes Asignados
                        <span class="badge bg-light text-dark float-end">{{ $assignedWarehouses->count() }}</span>
                    </h5>
                </div>
                <div class="card-body" id="assignedList" style="max-height: 500px; overflow-y: auto;">
                    @forelse ($assignedWarehouses as $warehouse)
                        <div class="card mb-2 warehouse-item" data-warehouse-id="{{ $warehouse->id }}">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <strong>{{ $warehouse->code }}</strong> - {{ $warehouse->name }}
                                        </h6>
                                        <small class="text-muted">{{ $warehouse->description ?? 'Sin descripción' }}</small>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger unassign-btn"
                                        data-warehouse-id="{{ $warehouse->id }}"
                                        title="Desasignar"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <!-- Permisos -->
                                <div class="mt-3">
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input default-warehouse-check"
                                            type="checkbox"
                                            id="default_{{ $warehouse->id }}"
                                            data-warehouse-id="{{ $warehouse->id }}"
                                            {{ $warehouse->pivot->is_default ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="default_{{ $warehouse->id }}">
                                            <strong>Almacén predeterminado</strong>
                                        </label>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input inventory-check"
                                            type="checkbox"
                                            id="inventory_{{ $warehouse->id }}"
                                            data-warehouse-id="{{ $warehouse->id }}"
                                            {{ $warehouse->pivot->can_inventory ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="inventory_{{ $warehouse->id }}">
                                            Puede hacer inventarios
                                        </label>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input transfer-check"
                                            type="checkbox"
                                            id="transfer_{{ $warehouse->id }}"
                                            data-warehouse-id="{{ $warehouse->id }}"
                                            {{ $warehouse->pivot->can_transfer ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="transfer_{{ $warehouse->id }}">
                                            Puede transferir productos
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Este usuario no tiene almacenes asignados
                        </div>
                    @endforelse
                </div>
                <div class="card-footer text-muted">
                    <small>Desliza para ver más almacenes</small>
                </div>
            </div>
        </div>

        <!-- Almacenes Disponibles -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle"></i> Asignar Almacén
                        <span class="badge bg-light text-dark float-end">{{ $unassignedWarehouses->count() }}</span>
                    </h5>
                </div>
                <div class="card-body" id="unassignedList" style="max-height: 500px; overflow-y: auto;">
                    @forelse ($unassignedWarehouses as $warehouse)
                        <div class="card mb-2">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <strong>{{ $warehouse->code }}</strong> - {{ $warehouse->name }}
                                        </h6>
                                        <small class="text-muted">{{ $warehouse->description ?? 'Sin descripción' }}</small>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-success assign-btn"
                                        data-warehouse-id="{{ $warehouse->id }}"
                                        title="Asignar"
                                    >
                                        <i class="fas fa-plus"></i> Asignar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> Todos los almacenes están asignados
                        </div>
                    @endforelse
                </div>
                <div class="card-footer text-muted">
                    <small>Desliza para ver más almacenes</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Información -->
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle"></i>
                <strong>Permisos:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Almacén predeterminado:</strong> El almacén que se abrirá por defecto cuando el usuario inicie sesión</li>
                    <li><strong>Puede hacer inventarios:</strong> Permite al usuario realizar operaciones de inventario</li>
                    <li><strong>Puede transferir productos:</strong> Permite al usuario transferir productos entre secciones</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .warehouse-item {
        transition: all 0.3s ease;
    }

    .warehouse-item:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    #assignedList, #unassignedList {
        scrollbar-width: thin;
        scrollbar-color: #ccc #f0f0f0;
    }

    #assignedList::-webkit-scrollbar, #unassignedList::-webkit-scrollbar {
        width: 6px;
    }

    #assignedList::-webkit-scrollbar-track, #unassignedList::-webkit-scrollbar-track {
        background: #f0f0f0;
    }

    #assignedList::-webkit-scrollbar-thumb, #unassignedList::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userId = {{ $user->id }};

    // Asignar almacén
    document.querySelectorAll('.assign-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const warehouseId = this.dataset.warehouseId;
            assignWarehouse(warehouseId);
        });
    });

    // Desasignar almacén
    document.querySelectorAll('.unassign-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const warehouseId = this.dataset.warehouseId;
            if (confirm('¿Desasignar este almacén?')) {
                unassignWarehouse(warehouseId);
            }
        });
    });

    // Cambios en permisos
    document.querySelectorAll('.default-warehouse-check, .inventory-check, .transfer-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const warehouseId = this.dataset.warehouseId;
            updatePermissions(warehouseId);
        });
    });

    function assignWarehouse(warehouseId) {
        fetch('{{ route("admin.warehouse-assignment.assign", $user->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                warehouse_id: warehouseId,
                is_default: false,
                can_inventory: true,
                can_transfer: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Almacén asignado correctamente');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error al asignar almacén');
        });
    }

    function unassignWarehouse(warehouseId) {
        fetch('{{ route("admin.warehouse-assignment.unassign", $user->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                warehouse_id: warehouseId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Almacén desasignado correctamente');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error al desasignar almacén');
        });
    }

    function updatePermissions(warehouseId) {
        const isDefault = document.getElementById(`default_${warehouseId}`)?.checked || false;
        const canInventory = document.getElementById(`inventory_${warehouseId}`)?.checked || false;
        const canTransfer = document.getElementById(`transfer_${warehouseId}`)?.checked || false;

        fetch('{{ route("admin.warehouse-assignment.assign", $user->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                warehouse_id: warehouseId,
                is_default: isDefault,
                can_inventory: canInventory,
                can_transfer: canTransfer
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Permisos actualizados');
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function showAlert(type, message) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning'
        }[type] || 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        const alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
        alertContainer.innerHTML = alertHtml;
        document.body.appendChild(alertContainer);

        setTimeout(() => alertContainer.remove(), 5000);
    }
});
</script>
@endsection
