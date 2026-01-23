@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Matriz de permisos por rol'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Matriz de permisos</h5>
                        <p class="small mb-0 text-muted">Vista completa de todos los permisos asignados a cada rol del sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.roles.index') }}" class="btn btn-light">
                            Atrás
                        </a>
                    </div>
                </div>
            </div>

            {{-- Statistics Cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    @php
                        $totalPermissions = count($permissions);
                        $totalRoles = count($roles);
                        $totalAssignments = collect($rolePermissions)->flatten()->count();
                        $maxPossible = $totalPermissions * $totalRoles;
                        $coveragePercentage = $maxPossible > 0 ? round(($totalAssignments / $maxPossible) * 100) : 0;
                    @endphp
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title  mb-2">Total permisos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $totalPermissions }}</h4>
                                        <small class="text-muted">Permisos en sistema</small>
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
                                        <h6 class="card-title text-success mb-2">Total roles</h6>
                                        <h4 class="mb-1 fw-bold">{{ $totalRoles }}</h4>
                                        <small class="text-muted">Roles configurados</small>
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
                                        <h6 class="card-title text-warning mb-2">Asignaciones</h6>
                                        <h4 class="mb-1 fw-bold">{{ $totalAssignments }}</h4>
                                        <small class="text-muted">Permisos asignados</small>
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
                                        <h6 class="card-title text-info mb-2">Cobertura</h6>
                                        <h4 class="mb-1 fw-bold">{{ $coveragePercentage }}%</h4>
                                        <small class="text-muted">De asignaciones posibles</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Control de permisos por rol</h6>
                            <p class="mb-0">
                                Esta matriz muestra todos los permisos disponibles en el sistema y su asignación a cada rol.
                                Haz clic en las celdas para asignar o revocar permisos en tiempo real.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">

            {{-- Roles Grid --}}
            <div class="row g-3">
                @foreach ($roles as $role)
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header bg-light border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ Str::title($role->name) }}</h6>
                                    </div>
                                    <div>
                                        <span class="badge bg-primary permission-count-{{ $role->id }}">
                                            {{ count($rolePermissions[$role->id]) }} permisos
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @foreach ($permissionsByModule as $module => $categories)
                                        <div class="col-12 col-md-12 col-lg-12 col-xl-12">
                                            <div class="card border h-100">
                                                <div class="card-header bg-light bg-opacity-10 border-bottom">
                                                    <h6 class="mb-0 fw-bold  text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                                        {{ Str::title(str_replace('_', ' ', $module)) }}
                                                    </h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    @foreach ($categories as $category => $perms)
                                                        <div class="permission-category mb-3">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <span class="badge bg-light bg-opacity-10 text-dark" style="font-size: 0.7rem;">
                                                                    {{ Str::title(str_replace('_', ' ', $category)) }}
                                                                </span>
                                                                <small class="text-muted" style="font-size: 0.65rem;">{{ count($perms) }}</small>
                                                            </div>
                                                            <div class="d-flex flex-column gap-1">
                                                                @foreach ($perms as $permission)
                                                                    @php
                                                                        $permAction = Str::afterLast($permission->name, '.');
                                                                        $isChecked = in_array($permission->id, $rolePermissions[$role->id]);
                                                                    @endphp
                                                                    <label class="permission-item d-flex align-items-center gap-2 p-2 rounded"
                                                                           for="perm_{{ $role->id }}_{{ $permission->id }}"
                                                                           style="cursor: pointer; transition: all 0.2s;">
                                                                        <input
                                                                            class="form-check-input permission-checkbox m-0"
                                                                            type="checkbox"
                                                                            id="perm_{{ $role->id }}_{{ $permission->id }}"
                                                                            data-role-id="{{ $role->id }}"
                                                                            data-permission-id="{{ $permission->id }}"
                                                                            data-role-name="{{ $role->name }}"
                                                                            data-permission-name="{{ $permission->name }}"
                                                                            {{ $isChecked ? 'checked' : '' }}>
                                                                        <span class="small flex-grow-1" style="font-size: 0.75rem;">
                                                                            {{ Str::title(str_replace('_', ' ', $permAction)) }}
                                                                        </span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>

        <div class="card-footer bg-white border-top">
            <div class="row">
                <div class="col-md-6">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="exportMatrix">
                        Exportar matriz
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted small">
                        Total: <strong>{{ count($permissions) }}</strong> permisos | <strong>{{ count($roles) }}</strong> roles
                    </span>
                </div>
            </div>
        </div>
    </div>

    </div>

@endsection

@push('styles')
<style>
    .form-check-input {
        width: 1rem;
        height: 1rem;
        cursor: pointer;
        border-width: 2px;
    }

    .form-check-input:checked {
        background-color: #90bb13;
        border-color: #90bb13;
    }

    .form-check-input:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .permission-item {
        border: 1px solid transparent;
        transition: all 0.15s ease-in-out;
    }

    .permission-item:hover {
        background-color: rgba(144, 187, 19, 0.05) !important;
        border-color: rgba(144, 187, 19, 0.2);
    }

    .permission-category {
        border-bottom: 1px dashed #e9ecef;
        padding-bottom: 0.75rem;
    }

    .permission-category:last-child {
        border-bottom: none;
        margin-bottom: 0 !important;
        padding-bottom: 0;
    }

    .card-header {
        padding: 0.75rem 1rem;
    }

    .badge.bg-secondary.bg-opacity-10 {
        font-weight: 600;
        padding: 0.25rem 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle permission checkbox changes
        $('.permission-checkbox').on('change', function() {
            const roleId = $(this).data('role-id');
            const permissionId = $(this).data('permission-id');
            const roleName = $(this).data('role-name');
            const permissionName = $(this).data('permission-name');
            const isChecked = $(this).is(':checked');

            // Disable checkbox while processing
            const $checkbox = $(this);
            $checkbox.prop('disabled', true);

            // Send AJAX request
            $.ajax({
                url: `/settings/roles/${roleId}/permissions`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    permission_id: permissionId,
                    action: isChecked ? 'attach' : 'detach'
                },
                success: function(response) {
                    $checkbox.prop('disabled', false);

                    // Update permission count
                    updateRolePermissionCount(roleId);

                    const message = isChecked
                        ? `Permiso "${permissionName}" asignado a "${roleName}"`
                        : `Permiso "${permissionName}" revocado de "${roleName}"`;

                    toastr.success(message, 'Éxito', { positionClass: 'toast-bottom-right' });
                },
                error: function(xhr) {
                    $checkbox.prop('disabled', false);
                    // Revert checkbox state
                    $checkbox.prop('checked', !isChecked);

                    const errorMessage = xhr.responseJSON?.message || 'Error al actualizar permiso. Intente nuevamente.';
                    toastr.error(errorMessage, 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

        // Update permission count for a role
        function updateRolePermissionCount(roleId) {
            const count = $(`input[data-role-id="${roleId}"]:checked`).length;
            $(`.permission-count-${roleId}`).text(count + ' permisos');
        }

        // Export matrix as CSV
        $('#exportMatrix').on('click', function() {
            const csv = [
                ['Role', ...@json($permissions->pluck('name'))]
            ];

            @foreach ($roles as $role)
                const row = ['{{ $role->name }}'];
                @foreach ($permissions as $permission)
                    row.push(
                        $(`#perm_{{ $role->id }}_{{ $permission->id }}`).is(':checked') ? '✓' : ''
                    );
                @endforeach
                csv.push(row);
            @endforeach

            // Convert to CSV string
            const csvContent = csv.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');

            // Create download link
            const link = document.createElement('a');
            link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
            link.download = `permission-matrix-${new Date().toISOString().split('T')[0]}.csv`;
            link.click();

            toastr.success('Matriz exportada correctamente', 'Éxito', { positionClass: 'toast-bottom-right' });
        });
    });
</script>
@endpush
