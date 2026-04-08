@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Gestionar usuarios del rol'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Usuarios con rol: {{ $role->name }}</h5>
                        <p class="small mb-0 text-muted">Gestiona los usuarios asignados a este rol</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">Volver</a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignUsersModal">
                            Asignar usuarios
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $users->total() }}</h4>
                                <small class="text-muted">Usuarios asignados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $users->where('is_active', true)->count() }}</h4>
                                <small class="text-muted">Usuarios activos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-warning mb-2">Inactivos</h6>
                                <h4 class="mb-1 fw-bold">{{ $users->where('is_active', false)->count() }}</h4>
                                <small class="text-muted">Usuarios inactivos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-info mb-2">Permisos</h6>
                                <h4 class="mb-1 fw-bold">{{ $role->permissions()->count() }}</h4>
                                <small class="text-muted">Del rol</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form action="{{ route('settings.roles.show.users', $role->id) }}" method="GET">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre, apellido o email..."
                                       value="{{ $search }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if($search)
                                <a href="{{ route('settings.roles.show.users', $role->id) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th class="text-center">Estado</th>
                                    <th>Registrado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $user->id }}">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span>{{ Str::title($user->first_name . ' ' . $user->last_name) }}</span>
                                                @if($user->roles()->count() > 1)
                                                    <small class="text-muted">{{ $user->roles()->count() }} roles</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $user->email }}</span>
                                        </td>
                                        <td class="text-center">
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
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
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
                                                        <button type="button" class="dropdown-item remove-user-btn"
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
                            <h6 class="mb-1">
                                @if($search)
                                    No se encontraron usuarios
                                @else
                                    Este rol no tiene usuarios asignados
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if($search)
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Asigna usuarios para que puedan usar este rol
                                @endif
                            </p>
                            @if(!$search)
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
                        <div class="text-muted">
                            Mostrando <strong>{{ $users->firstItem() }}</strong> a <strong>{{ $users->lastItem() }}</strong>
                            de <strong>{{ $users->total() }}</strong> usuarios
                        </div>
                        <nav>{{ $users->links() }}</nav>
                    </div>
                </div>
            @endif

        </div>

    </div>

    {{-- Bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <div class="card shadow-lg border-0">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
                <span class="text-muted"><span id="bulk-count">0</span> seleccionado(s)</span>
                <button type="button" id="bulk-remove-btn" class="btn btn-danger btn-sm">
                    <i class="fas fa-user-minus me-1"></i> Remover del rol
                </button>
                <button type="button" id="bulk-cancel-btn" class="btn btn-outline-secondary btn-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Assign Users --}}
    <div class="modal fade" id="assignUsersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar usuarios al rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignUsersForm" method="POST" action="{{ route('settings.roles.assign.users', $role->id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info border-0">
                            <i class="fas fa-circle-info me-2"></i>
                            Al seleccionar usuarios, se les asignará el rol <strong>{{ $role->name }}</strong>.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Selecciona los usuarios</label>
                            <select class="select2" id="userSelect" name="user_ids[]" style="width:100%;" multiple>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2" id="saveAssignBtn">Guardar</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {

    // ── Select-all ──────────────────────────────────────────────────────────
    $('#select-all').on('change', function () {
        $('.bulk-checkbox').prop('checked', this.checked);
        updateBulkToolbar();
    });

    $(document).on('change', '.bulk-checkbox', function () {
        var total   = $('.bulk-checkbox').length;
        var checked = $('.bulk-checkbox:checked').length;
        $('#select-all').prop('indeterminate', checked > 0 && checked < total);
        $('#select-all').prop('checked', checked === total);
        updateBulkToolbar();
    });

    function updateBulkToolbar() {
        var count = $('.bulk-checkbox:checked').length;
        $('#bulk-count').text(count);
        if (count > 0) {
            $('#bulk-toolbar').removeClass('d-none');
        } else {
            $('#bulk-toolbar').addClass('d-none');
        }
    }

    $('#bulk-cancel-btn').on('click', function () {
        $('.bulk-checkbox, #select-all').prop('checked', false);
        $('#select-all').prop('indeterminate', false);
        $('#bulk-toolbar').addClass('d-none');
    });

    // ── Bulk remove ─────────────────────────────────────────────────────────
    $('#bulk-remove-btn').on('click', function () {
        var ids = $('.bulk-checkbox:checked').map(function () { return $(this).val(); }).get();
        if (!ids.length) { return; }

        if (!confirm('¿Remover ' + ids.length + ' usuario(s) del rol? Esta acción no se puede deshacer.')) { return; }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Removiendo...');

        $.ajax({
            url: '{{ route("settings.roles.users.bulk-remove", $role->id) }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', user_ids: ids },
            success: function (r) {
                if (r.success) {
                    toastr.success(r.message, 'Éxito', { positionClass: 'toast-bottom-right' });
                    ids.forEach(function (id) {
                        $('input.bulk-checkbox[value="' + id + '"]').closest('tr').fadeOut(300, function () { $(this).remove(); });
                    });
                    $('#bulk-toolbar').addClass('d-none');
                    $('#select-all').prop('checked', false).prop('indeterminate', false);
                } else {
                    toastr.error(r.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? 'Error al remover usuarios.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-user-minus me-1"></i> Remover del rol');
            }
        });
    });

    // ── Single remove ────────────────────────────────────────────────────────
    $(document).on('click', '.remove-user-btn', function (e) {
        e.preventDefault();
        var userId   = $(this).data('user-id');
        var roleId   = $(this).data('role-id');
        var userName = $(this).data('user-name');

        if (!confirm('¿Remover a ' + userName + ' del rol?')) { return; }

        $.ajax({
            url: '{{ url("/settings/roles/" . $role->id . "/users/") }}/' + userId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (r) {
                if (r.success) {
                    toastr.success(r.message, 'Éxito', { positionClass: 'toast-bottom-right' });
                    $('button[data-user-id="' + userId + '"]').closest('tr').fadeOut(300, function () { $(this).remove(); });
                } else {
                    toastr.error(r.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? 'Error al remover usuario.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    // ── Assign users modal ───────────────────────────────────────────────────
    $('#userSelect').select2({
        placeholder: 'Busca y selecciona usuarios...',
        allowClear: true,
        dropdownParent: $('#assignUsersModal'),
        ajax: {
            url: '{{ route("settings.users.search") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data }; }
        },
        templateResult: function (user) {
            if (!user.id) { return user.text; }
            var initials = user.first_name.charAt(0).toUpperCase() + user.last_name.charAt(0).toUpperCase();
            return $('<span><span style="display:inline-block;width:32px;height:32px;background:#b10100;color:#fff;border-radius:50%;text-align:center;line-height:32px;margin-right:8px;font-weight:bold;font-size:12px;">' + initials + '</span>' + user.first_name + ' ' + user.last_name + ' <small class="text-muted">(' + user.email + ')</small></span>');
        },
        templateSelection: function (user) {
            return user.id ? user.first_name + ' ' + user.last_name : user.text;
        }
    });

    $('#assignUsersForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#saveAssignBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function (r) {
                if (r.success) {
                    toastr.success(r.message, 'Éxito', { positionClass: 'toast-bottom-right' });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(r.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? 'Error al asignar usuarios.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('Guardar');
            }
        });
    });

});
</script>
@endpush
