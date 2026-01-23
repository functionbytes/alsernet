@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Gestionar usuarios del rol'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Usuarios con rol: {{ $role->name }}</h5>
                        <p class="small mb-0 text-muted">Gestiona los usuarios asignados a este rol</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if($search)
                            <a href="{{ route('settings.roles.show.users', $role->id) }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">
                            Volver
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignUsersModal">
                            Asignar usuarios
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $users->total() }}</h4>
                                        <small class="text-muted">Usuarios asignados</small>
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
                                        <h6 class="card-title text-success mb-2">Activos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $users->where('is_active', true)->count() }}</h4>
                                        <small class="text-muted">Usuarios activos</small>
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
                                        <h6 class="card-title text-warning mb-2">Inactivos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $users->where('is_active', false)->count() }}</h4>
                                        <small class="text-muted">Usuarios inactivos</small>
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
                                        <h6 class="card-title text-info mb-2">Permisos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $role->permissions()->count() }}</h4>
                                        <small class="text-muted">Del rol</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Section --}}
            <div class="card-body border-bottom">
                <form action="{{ route('settings.roles.show.users', $role->id) }}" method="GET">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por nombre, apellido o email..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="25%">Usuario</th>
                                    <th width="20%">Email</th>
                                    <th width="15%">Estado</th>
                                    <th width="15%">Registrado</th>
                                    <th width="15%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $key => $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                {{ Str::title($user->first_name . ' ' . $user->last_name) }}
                                                @if($user->roles()->count() > 1)
                                                    <small class="text-muted d-block">{{ $user->roles()->count() }} roles</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $user->email }}</span>
                                        </td>
                                        <td>
                                            @if($user->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $user->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="{{ route('settings.users.show', $user->id) }}" class="dropdown-item">
                                                            Ver perfil
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('settings.users.edit', $user->id) }}" class="dropdown-item">
                                                            Editar usuario
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger remove-user-btn"
                                                                data-user-id="{{ $user->id }}"
                                                                data-user-name="{{ $user->first_name }} {{ $user->last_name }}"
                                                                data-role-id="{{ $role->id }}">
                                                            Remover del rol
                                                        </button>
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-users fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay usuarios para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Este rol no tiene usuarios asignados
                                @endif
                            </p>
                            @if(!request('search'))
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignUsersModal">
                                    Asignar usuarios
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($users->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $users->firstItem() }}</strong> a <strong>{{ $users->lastItem() }}</strong>
                            de <strong>{{ $users->total() }}</strong> usuarios
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $users->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>

    </div>

    <!-- Modal: Assign Users -->
    <div class="modal fade" id="assignUsersModal" tabindex="-1" aria-labelledby="assignUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignUsersModalLabel">
                        Asignar usuarios al rol
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="assignUsersForm" method="POST" action="{{ route('settings.roles.assign.users', $role->id) }}">
                    @csrf

                    <div class="modal-body">
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-circle-info me-2"></i>
                            <strong>Nota:</strong> Al seleccionar usuarios, se les asignará el rol "<strong>{{ $role->name }}</strong>". Los usuarios podrán acceder a todos los permisos asociados a este rol.
                        </div>

                        <div class="form-group mb-3">
                            <label for="userSelect" class="form-label">Selecciona los usuarios</label>
                            <select id="userSelect" name="user_ids[]" class="form-control" style="width: 100%;" multiple="multiple">
                                <!-- Options loaded via AJAX with Select2 -->
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1" id="saveAssignBtn">
                           Guardar
                        </button>
                        <button type="button" class="btn btn-secondary w-100 " data-bs-dismiss="modal">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function() {
            // Initialize Select2 for User Selection
            $('#userSelect').select2({
                placeholder: 'Busca y selecciona usuarios...',
                allowClear: true,
                multiple: true,
                dropdownParent: $('#assignUsersModal'),
                ajax: {
                    url: '{{ route("settings.users.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                },
                templateResult: function(user) {
                    if (!user.id) {
                        return user.text;
                    }
                    const initials = user.first_name.charAt(0).toUpperCase() + user.last_name.charAt(0).toUpperCase();
                    return $('<span>' +
                        '<span style="display: inline-block; width: 32px; height: 32px; background-color: #90bb13; color: white; border-radius: 50%; text-align: center; line-height: 32px; margin-right: 8px; font-weight: bold; font-size: 12px;">' + initials + '</span>' +
                        '<span>' + user.first_name + ' ' + user.last_name + ' <small class="text-muted">(' + user.email + ')</small></span>' +
                        '</span>');
                },
                templateSelection: function(user) {
                    if (!user.id) {
                        return user.text;
                    }
                    return user.first_name + ' ' + user.last_name;
                }
            });

            // Handle Form Submission
            $('#assignUsersForm').on('submit', function(e) {
                e.preventDefault();

                const formData = $(this).serialize();
                const $submitBtn = $('#saveAssignBtn');

                $submitBtn.prop('disabled', true);
                $submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Éxito', { positionClass: 'toast-bottom-right' });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Error al asignar usuarios.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        toastr.error(message, 'Error', { positionClass: 'toast-bottom-right' });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                        $submitBtn.html('<i class="fas fa-save me-2"></i>Guardar');
                    }
                });
            });

            // Handle Remove User from Role
            $(document).on('click', '.remove-user-btn', function(e) {
                e.preventDefault();

                const userId = $(this).data('user-id');
                const roleId = $(this).data('role-id');

                if (confirm('¿Estás seguro de que deseas remover esto?\n\nEsta acción no se puede deshacer. Todos los datos relacionados pueden eliminarse.')) {
                    $.ajax({
                        url: '{{ url("/settings/roles/" . $role->id . "/users/") }}/' + userId,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, 'Éxito', { positionClass: 'toast-bottom-right' });
                                // Remove the user row from the table
                                $('button[data-user-id="' + userId + '"]').closest('tr').fadeOut(300, function() {
                                    $(this).remove();
                                });
                            } else {
                                toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                            }
                        },
                        error: function(xhr) {
                            let message = 'Error al remover usuario del rol.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            toastr.error(message, 'Error', { positionClass: 'toast-bottom-right' });
                        }
                    });
                }
            });

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endpush
