@extends('layouts.theme')

@section('title', 'Permisos directos: ' . $user->firstname . ' ' . $user->lastname)

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">

                {{-- Header --}}
                <div class="card-header p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Permisos directos de: {{ $user->firstname }} {{ $user->lastname }}</h5>
                            <p class="small mb-0 text-muted">{{ $user->email }}</p>
                        </div>
                        <a href="{{ route('settings.user-permissions.index') }}" class="btn btn-secondary">Volver</a>
                    </div>
                </div>

                {{-- Stats (JS-computed after toggles) --}}
                @php
                    $totalPermissions = $permissions->count();
                    $groupedCount = $permissions->groupBy(fn($p) => explode('.', $p->name)[0])->count();
                @endphp
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-primary mb-2">Total</h6>
                                    <h4 class="mb-1 fw-bold">{{ $totalPermissions }}</h4>
                                    <small class="text-muted">Permisos disponibles</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Asignados</h6>
                                    <h4 class="mb-1 fw-bold" id="stat-assigned">{{ count($userPermissions) }}</h4>
                                    <small class="text-muted">Asignados directamente</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-warning mb-2">Sin asignar</h6>
                                    <h4 class="mb-1 fw-bold" id="stat-unassigned">{{ $totalPermissions - count($userPermissions) }}</h4>
                                    <small class="text-muted">Permisos disponibles</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-info mb-2">Grupos</h6>
                                    <h4 class="mb-1 fw-bold">{{ $groupedCount }}</h4>
                                    <small class="text-muted">Categorías de permisos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Nota informativa --}}
                <div class="card-body border-bottom pb-0">
                    <div class="alert alert-info border-0">
                        <i class="fas fa-circle-info me-2"></i>
                        Estos son permisos <strong>adicionales</strong> al rol del usuario. Se aplican junto con los permisos del rol asignado.
                    </div>
                </div>

                {{-- Permisos agrupados --}}
                <div class="card-body">
                    <div class="row" id="permissionsContainer">
                        @php
                            $groupedPermissions = $permissions->groupBy(fn($p) => explode('.', $p->name)[0]);
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
                                                               data-permission-id="{{ $perm->id }}"
                                                               {{ in_array($perm->id, $userPermissions) ? 'checked' : '' }}>
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

                    <div class="border-top pt-3 mt-2">
                        <button type="button" id="syncPermissionsBtn" class="btn btn-primary w-100 mb-2">
                            Guardar permisos
                        </button>
                        <a href="{{ route('settings.user-permissions.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const updateUrl  = "{{ route('settings.user-permissions.update', $user->id) }}";
    const syncUrl    = "{{ route('settings.user-permissions.sync', $user->id) }}";
    const totalPerms = {{ $totalPermissions }};

    function refreshStats() {
        var assigned = $('.permission-checkbox:checked').length;
        $('#stat-assigned').text(assigned);
        $('#stat-unassigned').text(totalPerms - assigned);
    }

    // ── Toggle individual ────────────────────────────────────────────────────
    $('.permission-checkbox').on('change', function () {
        var permId    = $(this).data('permission-id');
        var isChecked = $(this).is(':checked');
        var $cb       = $(this).prop('disabled', true);

        $.ajax({
            url: updateUrl,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { permission_id: permId, action: isChecked ? 'attach' : 'detach' },
            success: function (r) {
                $cb.prop('disabled', false);
                refreshStats();
                toastr.success(r.message, 'Éxito', { positionClass: 'toast-bottom-right' });
            },
            error: function (xhr) {
                $cb.prop('disabled', false).prop('checked', !isChecked);
                toastr.error(xhr.responseJSON?.message ?? 'Error al actualizar.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    // ── Sync (guardar todos) ─────────────────────────────────────────────────
    $('#syncPermissionsBtn').on('click', function () {
        var ids = [];
        $('.permission-checkbox:checked').each(function () {
            ids.push($(this).data('permission-id'));
        });

        var $btn = $(this).prop('disabled', true).text('Guardando...');

        $.ajax({
            url: syncUrl,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { permissions: ids },
            success: function (r) {
                refreshStats();
                toastr.success(r.message, 'Éxito', { positionClass: 'toast-bottom-right' });
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al guardar permisos.', 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () {
                $btn.prop('disabled', false).text('Guardar permisos');
            }
        });
    });
});
</script>
@endpush
