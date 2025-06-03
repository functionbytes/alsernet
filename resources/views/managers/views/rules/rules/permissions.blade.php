@extends('layouts.managers')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="assignPermissionsForm">
                    @csrf
                    <input type="hidden" name="id" id="id" value="{{ $role->id }}">
                    <div class="card-body border-top">
                        <h5 class="mb-0">Gestionar permisos para el rol: <strong>{{ $role->name }}</strong></h5>
                        <p class="card-subtitle mt-1 mb-3">Selecciona los permisos que deseas asignar a este rol.</p>

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
                    url: "{{ route('manager.roles.assign.permissions.update') }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Éxito', { positionClass: 'toast-bottom-right' });
                            setTimeout(() => window.location.href = "{{ route('manager.roles') }}", 1500);
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
