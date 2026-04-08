@extends('layouts.theme')

@section('title', 'Idiomas')

@section('content')

    @include('core::components.card', ['title' => 'Idiomas'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Idiomas</h5>
                        <p class="small mb-0 text-muted">Gestiona los idiomas disponibles en el sitio</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('locales.create') }}">Nuevo idioma</a>
                                <a class="dropdown-item" href="{{ route('locales.translations.index') }}">Ver traducciones</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total idiomas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Registrados en el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">Disponibles en el sitio</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                <small class="text-muted">No visibles en el sitio</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Predeterminado</h6>
                                <h4 class="mb-1 fw-bold" style="font-size:1.1rem;">{{ $stats['default'] }}</h4>
                                <small class="text-muted">Idioma principal del sitio</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if ($locales->isEmpty())
                    <div class="text-center py-5">
                        <h6 class="mb-1">No hay idiomas registrados</h6>
                        <p class="text-muted mb-3">Crea el primer idioma para comenzar</p>
                        <a href="{{ route('locales.create') }}" class="btn btn-sm btn-primary">Nuevo idioma</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th width="60">Orden</th>
                                    <th>Idioma</th>
                                    <th>Código</th>
                                    <th class="text-center">Por defecto</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center" width="60">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($locales as $locale)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox"
                                                   value="{{ $locale->id }}"
                                                   {{ $locale->is_default ? 'disabled' : '' }}>
                                        </td>
                                        <td class="text-muted">{{ $locale->order }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fs-5">{{ $locale->flag }}</span>
                                                <div>
                                                    <div class="fw-semibold">{{ $locale->name }}</div>
                                                    <small class="text-muted">{{ $locale->native_name }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><code>{{ $locale->code }}</code></td>
                                        <td class="text-center">
                                            @if ($locale->is_default)
                                                <span class="badge bg-primary-subtle text-primary">Predeterminado</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block mb-0">
                                                <input class="form-check-input toggle-active"
                                                       type="checkbox"
                                                       role="switch"
                                                       data-url="{{ route('locales.toggle', $locale) }}"
                                                       {{ $locale->is_active ? 'checked' : '' }}
                                                       {{ $locale->is_default ? 'disabled' : '' }}>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown"
                                                   data-bs-auto-close="true" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('locales.edit', $locale) }}">Editar</a>
                                                    </li>
                                                    @if (!$locale->is_default)
                                                        <li>
                                                            <button type="button" class="dropdown-item btn-set-default"
                                                                    data-url="{{ route('locales.default', $locale) }}"
                                                                    data-name="{{ $locale->name }}">
                                                                Establecer como predeterminado
                                                            </button>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item btn-delete"
                                                                data-url="{{ route('locales.destroy', $locale) }}"
                                                                data-name="{{ $locale->name }}"
                                                                {{ $locale->is_default ? 'disabled' : '' }}>
                                                            Eliminar
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
                @endif
            </div>

            @if ($locales->hasPages())
                <div class="card-footer">{{ $locales->withQueryString()->links() }}</div>
            @endif

        </div>
    </div>

    {{-- Form oculto para eliminar individual --}}
    <form id="delete-form" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> idioma(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Bulk actions
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un registro.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' idioma(s) seleccionados? Los predeterminados no se eliminarán.')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('locales.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // Toggle activo
    $(document).on('change', '.toggle-active', function () {
        const $switch = $(this);
        $.ajax({
            url: $switch.data('url'),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.is_active ? 'Idioma activado.' : 'Idioma desactivado.');
                } else {
                    toastr.error(res.message ?? 'No se pudo cambiar el estado.');
                    $switch.prop('checked', !$switch.prop('checked'));
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al cambiar el estado.');
                $switch.prop('checked', !$switch.prop('checked'));
            }
        });
    });

    // Establecer predeterminado
    $(document).on('click', '.btn-set-default', function () {
        const url  = $(this).data('url');
        const name = $(this).data('name');
        if (!confirm('¿Establecer "' + name + '" como idioma predeterminado?')) { return; }
        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) {
                    toastr.success('Idioma predeterminado actualizado.');
                    setTimeout(() => location.reload(), 800);
                }
            },
            error: function () { toastr.error('Error al actualizar el idioma predeterminado.'); }
        });
    });

    // Eliminar individual
    $(document).on('click', '.btn-delete', function () {
        const url  = $(this).data('url');
        const name = $(this).data('name');
        if (!confirm('¿Eliminar el idioma "' + name + '"?')) { return; }
        $('#delete-form').attr('action', url).submit();
    });
});
</script>
@endpush
