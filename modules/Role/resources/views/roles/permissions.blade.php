@extends('layouts.theme')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="assignPermissionsForm">
                    @csrf
                    <input type="hidden" name="role_id" id="role_id" value="{{ $role->id }}">
                    <div class="card-body">
                        <h5 class="mb-0">Gestionar permisos para el rol: <strong>{{ $role->name }}</strong></h5>
                        <p class="card-subtitle mt-1 mb-3">Selecciona los permisos que deseas asignar a este rol.</p>
                    </div>

                    <div class="card-body border-bottom">
                        <div class="row g-3">
                            @php
                                $totalPermissions = $permissions->count();
                                $assignedPermissions = count(is_array($rolePermissions) ? $rolePermissions : $rolePermissions->toArray());
                                $unassignedPermissions = $totalPermissions - $assignedPermissions;
                                $groupedCount = $permissions->groupBy(fn($perm) => explode('.', $perm->name)[0])->count();
                            @endphp
                            <div class="col-md-3">
                                <div class="card bg-light-secondary stat-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div>
                                                <h6 class="card-title text-primary mb-2">Total</h6>
                                                <h4 class="mb-1 fw-bold">{{ $totalPermissions }}</h4>
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
                                                <h4 class="mb-1 fw-bold">{{ $assignedPermissions }}</h4>
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
                                                <h6 class="card-title text-warning mb-2">Sin asignar</h6>
                                                <h4 class="mb-1 fw-bold">{{ $unassignedPermissions }}</h4>
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
                                                <h6 class="card-title text-info mb-2">Grupos</h6>
                                                <h4 class="mb-1 fw-bold">{{ $groupedCount }}</h4>
                                                <small class="text-muted">Categorías de permisos</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row" id="permissionsContainer">
                            @php
                                $rolePermissionsArray = is_array($rolePermissions) ? $rolePermissions : $rolePermissions->toArray();
                                $groupedPermissions = $permissions->groupBy(fn($perm) => explode('.', $perm->name)[0]);
                            @endphp

                            @foreach($groupedPermissions as $group => $groupPermissions)
                                <div class="col-md-12 mb-4">
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light">
                                            <strong class="text-uppercase">
                                                {{ ucwords(str_replace(['_', '-'], ' ', $group)) }}
                                            </strong>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                @foreach($groupPermissions as $perm)
                                                    <div class="col-md-4">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input permission-checkbox"
                                                                   type="checkbox"
                                                                   id="permission_{{ $perm->id }}"
                                                                   name="permissions[]"
                                                                   value="{{ $perm->id }}"
                                                                {{ in_array($perm->id, $rolePermissionsArray) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="permission_{{ $perm->id }}">
                                                                {{ ucwords(str_replace(['.', '_'], ' ', $perm->name)) }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>


                        <div class="border-top pt-1 mt-4">
                            <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                Guardar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#assignPermissionsForm').on('submit', function(e) {
                e.preventDefault();
                const roleId = $('#role_id').val();
                const formData = $(this).serialize();
                const $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('settings.roles.update.permissions', $role->id) }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Éxito', { positionClass: 'toast-bottom-right' });
                            setTimeout(() => window.location.href = "{{ route('settings.roles.index') }}", 1500);
                        } else {
                            toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                        }
                        $submitBtn.prop('disabled', false);
                    },
                    error: function(xhr) {
                        toastr.error('Error al actualizar los permisos.', 'Error', { positionClass: 'toast-bottom-right' });
                        $submitBtn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
