@extends('layouts.theme')

@section('page_title', 'Permisos del grupo: ' . $group->name)

@section('content')
<div class="container-fluid">

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Permisos del grupo',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Documentos', 'url' => route('settings.documents.configurations')],
            ['label' => 'Grupos de validación', 'url' => route('settings.documents.groups.index')],
            ['label' => $group->name, 'url' => route('settings.documents.groups.edit', $group)],
            ['label' => 'Permisos', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Permisos del grupo: {{ $group->name }}</h5>
                        <p class="small mb-0 text-muted">
                            Gestiona los permisos asignados a este grupo de validación
                            <span class="badge bg-{{ $group->is_active ? 'success' : 'secondary' }}-subtle text-{{ $group->is_active ? 'success' : 'secondary' }} ms-2">
                                {{ $group->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.documents.groups.edit', $group) }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Gestión de permisos</h6>
                            <p class="mb-0">
                                Los permisos asignados a este grupo se aplicarán automáticamente a todos los usuarios que pertenezcan al mismo.
                                Los cambios tendrán efecto inmediato.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if (session('success'))
                <div class="card-body border-bottom">
                    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-check fs-4 me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="card-body border-bottom">
                    <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-exclamation-circle fs-4 me-2"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            <form action="{{ route('settings.documents.groups.permissions.update', $group) }}" method="POST" id="permissionsForm">
                @csrf

                {{-- Statistics Cards --}}
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-primary mb-2">Total</h6>
                                            <h4 class="mb-1 fw-bold" id="totalPermissions">{{ $permissionsByCategory->flatten()->count() }}</h4>
                                            <small class="text-muted">Permisos disponibles</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-success mb-2">Asignados</h6>
                                            <h4 class="mb-1 fw-bold" id="selectedPermissions">{{ count($currentPermissions) }}</h4>
                                            <small class="text-muted">Permisos activos</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-info mb-2">Categorías</h6>
                                            <h4 class="mb-1 fw-bold" id="categoryCount">{{ $permissionsByCategory->count() }}</h4>
                                            <small class="text-muted">Grupos de permisos</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="card-title text-warning mb-2">Usuarios</h6>
                                            <h4 class="mb-1 fw-bold" id="userCount">{{ $group->users()->count() }}</h4>
                                            <small class="text-muted">En este grupo</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="card-body border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-semibold">Acciones rápidas</h6>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="selectAll">
                                Seleccionar todos
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAll">
                                Deseleccionar todos
                            </button>
                            @if ($permissionsByCategory->count() > 1)
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Por categoría
                                    </button>
                                    <ul class="dropdown-menu">
                                        @foreach ($permissionsByCategory as $category => $permissions)
                                            <li>
                                                <a class="dropdown-item select-category" href="#"
                                                    data-category="{{ $category }}">
                                                    Seleccionar {{ ucfirst($category) }}
                                                </a>
                                            </li>
                                        @endforeach
                                        <li><hr class="dropdown-divider"></li>
                                        @foreach ($permissionsByCategory as $category => $permissions)
                                            <li>
                                                <a class="dropdown-item deselect-category" href="#"
                                                    data-category="{{ $category }}">
                                                    Deseleccionar {{ ucfirst($category) }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Permissions by Category --}}
                <div class="card-body">
                    @foreach ($permissionsByCategory as $category => $permissions)
                        <div class="card mb-3 {{ $loop->last ? 'mb-0' : '' }}">
                            <div class="card-header bg-light border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold text-capitalize">
                                        {{ $categoryLabels[$category] ?? ucfirst($category) }}
                                    </h6>
                                    <span class="badge bg-primary category-count" data-category="{{ $category }}">
                                        <span class="selected-count">{{ $permissions->whereIn('id', $currentPermissions)->count() }}</span>
                                        /
                                        <span class="total-count">{{ $permissions->count() }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @foreach ($permissions as $permission)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="form-check p-3 border rounded permission-checkbox {{ in_array($permission->id, $currentPermissions) ? 'border-primary' : '' }}"
                                                data-category="{{ $category }}"
                                                style="{{ in_array($permission->id, $currentPermissions) ? 'background-color: rgba(144, 187, 19, 0.02);' : '' }}">
                                                <input class="form-check-input permission-input" type="checkbox" name="permissions[]"
                                                    value="{{ $permission->id }}" id="permission_{{ $permission->id }}"
                                                    {{ in_array($permission->id, $currentPermissions) ? 'checked' : '' }}>
                                                <label class="form-check-label w-100" for="permission_{{ $permission->id }}" style="cursor: pointer;">
                                                    <strong class="d-block">{{ $permission->label }}</strong>
                                                    @if($permission->description)
                                                        <small class="text-muted d-block mt-1">{{ $permission->description }}</small>
                                                    @endif
                                                    <small class="badge bg-light text-dark mt-2">{{ $permission->name }}</small>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Footer Actions --}}
                <div class="card-footer bg-white border-top">

                            <button type="submit" class="btn btn-primary w-100 mb-1">
                                Guardar
                            </button>

                            <a href="{{ route('settings.documents.groups.edit', $group) }}" class="btn btn-secondary w-100">
                                Cancelar
                            </a>
                </div>
            </form>

        </div>

    </div>

</div>
@endsection

@push('styles')
<style>
    .permission-checkbox {
        transition: all 0.2s ease;
        cursor: pointer;
        border: 2px solid #dee2e6;
        position: relative;
    }

    /* Hide the checkbox input but keep it functional */
    .permission-checkbox.form-check .form-check-input.permission-input,
    .permission-checkbox .permission-input {
        position: absolute !important;
        opacity: 0 !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
        visibility: hidden !important;
    }

    /* Normal hover effect */
    .permission-checkbox:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: #adb5bd;
    }

    /* Checked state - green theme */
    .permission-checkbox.border-primary {
        background-color: rgba(144, 187, 19, 0.05);
        border-color: #90bb13 !important;
    }

    /* Checked hover - enhanced green effect */
    .permission-checkbox.border-primary:hover {
        background-color: rgba(144, 187, 19, 0.1);
        border-color: #7a9e11 !important;
        box-shadow: 0 4px 12px rgba(144, 187, 19, 0.3);
    }

    /* Visual indicator for checked state */
    .permission-checkbox.border-primary::before {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background-color: #90bb13;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    /* Unchecked state visual indicator */
    .permission-checkbox:not(.border-primary)::before {
        content: '';
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background-color: #f8f9fa;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .permission-checkbox:not(.border-primary):hover::before {
        border-color: #adb5bd;
        background-color: #e9ecef;
    }

    /* Make label non-selectable */
    .permission-checkbox .form-check-label {
        user-select: none;
        padding-right: 40px; /* Space for the check indicator */
    }

    .form-check-input:checked {
        background-color: #90bb13;
        border-color: #90bb13;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('permissionsForm');
    const permissionInputs = document.querySelectorAll('.permission-input');
    const selectedCountEl = document.getElementById('selectedPermissions');

    // Update selected count and visual feedback
    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.permission-input:checked').length;
        selectedCountEl.textContent = selectedCount;

        // Update category counts
        document.querySelectorAll('.category-count').forEach(badge => {
            const category = badge.dataset.category;
            const categoryInputs = document.querySelectorAll(
                `.permission-checkbox[data-category="${category}"] .permission-input`);
            const selectedInCategory = Array.from(categoryInputs).filter(input => input.checked).length;
            badge.querySelector('.selected-count').textContent = selectedInCategory;
        });

        // Update visual feedback for checkboxes
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            const input = checkbox.querySelector('.permission-input');
            if (input.checked) {
                checkbox.classList.add('border-primary');
                checkbox.style.backgroundColor = 'rgba(144, 187, 19, 0.05)';
            } else {
                checkbox.classList.remove('border-primary');
                checkbox.style.backgroundColor = '';
            }
        });
    }

    // Make entire card clickable
    document.querySelectorAll('.permission-checkbox').forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't toggle if clicking directly on the checkbox
            if (e.target.classList.contains('permission-input')) {
                return;
            }

            const checkbox = this.querySelector('.permission-input');
            checkbox.checked = !checkbox.checked;
            updateSelectedCount();
        });
    });

    // Select all permissions
    document.getElementById('selectAll').addEventListener('click', function() {
        permissionInputs.forEach(input => input.checked = true);
        updateSelectedCount();
    });

    // Deselect all permissions
    document.getElementById('deselectAll').addEventListener('click', function() {
        permissionInputs.forEach(input => input.checked = false);
        updateSelectedCount();
    });

    // Select by category
    document.querySelectorAll('.select-category').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.dataset.category;
            document.querySelectorAll(
                `.permission-checkbox[data-category="${category}"] .permission-input`
            ).forEach(input => input.checked = true);
            updateSelectedCount();
        });
    });

    // Deselect by category
    document.querySelectorAll('.deselect-category').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.dataset.category;
            document.querySelectorAll(
                `.permission-checkbox[data-category="${category}"] .permission-input`
            ).forEach(input => input.checked = false);
            updateSelectedCount();
        });
    });

    // Update counts on checkbox change
    permissionInputs.forEach(input => {
        input.addEventListener('change', updateSelectedCount);
    });

    // Confirm before submit if many permissions are being removed
    form.addEventListener('submit', function(e) {
        const initialSelected = {{ count($currentPermissions) }};
        const currentSelected = document.querySelectorAll('.permission-input:checked').length;
        const removedCount = initialSelected - currentSelected;

        if (removedCount > 5) {
            if (!confirm(
                `Estás a punto de remover ${removedCount} permisos. Esto afectará a ${document.getElementById('userCount').textContent} usuarios. ¿Deseas continuar?`
            )) {
                e.preventDefault();
            }
        }
    });
});
</script>
@endpush
