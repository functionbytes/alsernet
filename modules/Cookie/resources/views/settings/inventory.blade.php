@extends('layouts.theme')

@section('title', 'Inventario de cookies')

@section('content')

    @include('core::components.card', ['title' => 'Inventario de cookies'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Inventario de cookies</h5>
                        <p class="small mb-0 text-muted">Listado de cookies utilizadas en el sitio web</p>
                    </div>
                    @can('cookie.inventory.create')
                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary" id="btn-add-cookie">
                            Agregar cookie
                        </button>
                    </div>
                    @endcan
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">

                @if($items->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="inventory-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Proveedor</th>
                                    <th>Categoría</th>
                                    <th>Propósito</th>
                                    <th>Duración</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr data-id="{{ $item->id }}">
                                        <td class="fw-semibold">{{ $item->name }}</td>
                                        <td>{{ $item->provider ?? '—' }}</td>
                                        <td>
                                            @php
                                                $catBadge = match($item->category) {
                                                    'essential'   => ['bg-primary', 'Esencial'],
                                                    'analytics'   => ['bg-info text-dark', 'Analítica'],
                                                    'marketing'   => ['bg-warning text-dark', 'Marketing'],
                                                    default       => ['bg-secondary', 'Preferencias'],
                                                };
                                            @endphp
                                            <span class="badge {{ $catBadge[0] }}">{{ $catBadge[1] }}</span>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;"
                                                  title="{{ $item->purpose }}">
                                                {{ $item->purpose ?? '—' }}
                                            </span>
                                        </td>
                                        <td>{{ $item->duration ?? '—' }}</td>
                                        <td>
                                            @if($item->is_active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('cookie.inventory.update')
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-edit-cookie"
                                                                data-id="{{ $item->id }}"
                                                                data-name="{{ $item->name }}"
                                                                data-provider="{{ $item->provider }}"
                                                                data-category="{{ $item->category }}"
                                                                data-purpose="{{ $item->purpose }}"
                                                                data-duration="{{ $item->duration }}"
                                                                data-active="{{ $item->is_active ? '1' : '0' }}">
                                                            Editar
                                                        </button>
                                                    </li>
                                                    @endcan
                                                    @can('cookie.inventory.delete')
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-delete-cookie"
                                                                data-id="{{ $item->id }}"
                                                                data-name="{{ $item->name }}">
                                                            Eliminar
                                                        </button>
                                                    </li>
                                                    @endcan
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
                        <i class="fas fa-cookie-bite fa-3x text-muted opacity-50 mb-3 d-block"></i>
                        <h6 class="text-muted mb-1">No hay cookies en el inventario todavía</h6>
                        @can('cookie.inventory.create')
                            <button type="button" class="btn btn-sm btn-primary mt-2" id="btn-add-cookie-empty">
                                Agregar primera cookie
                            </button>
                        @endcan
                    </div>
                @endif

            </div>

            @if($items->hasPages())
                <div class="card-footer">{{ $items->links() }}</div>
            @endif

        </div>
    </div>

    {{-- Modal crear/editar --}}
    <div class="modal fade" id="inventory-modal" tabindex="-1" aria-labelledby="inventory-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="inventory-modal-label">Agregar cookie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="inventory-form" novalidate>
                        <input type="hidden" id="inventory-id">

                        <div class="mb-3">
                            <label for="inv-name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="inv-name" name="name"
                                   placeholder="p.ej. _ga" maxlength="100">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="inv-provider" class="form-label">Proveedor</label>
                            <input type="text" class="form-control" id="inv-provider" name="provider"
                                   placeholder="p.ej. Google" maxlength="100">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="inv-category" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="inv-category" name="category">
                                <option value="">Seleccionar categoría...</option>
                                <option value="essential">Esencial</option>
                                <option value="analytics">Analítica</option>
                                <option value="marketing">Marketing</option>
                                <option value="preferences">Preferencias</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="inv-purpose" class="form-label">Propósito</label>
                            <input type="text" class="form-control" id="inv-purpose" name="purpose"
                                   placeholder="Descripción del uso de esta cookie" maxlength="255">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="inv-duration" class="form-label">Duración</label>
                            <input type="text" class="form-control" id="inv-duration" name="duration"
                                   placeholder="p.ej. 1 año, Sesión" maxlength="50">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="inv-active">Estado</label>
                            <select class="form-select" id="inv-active" name="is_active">
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="btn-save-cookie">
                        Guardar
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var modal = new bootstrap.Modal(document.getElementById('inventory-modal'));

    function clearFormErrors() {
        $('#inventory-form .is-invalid').removeClass('is-invalid');
        $('#inventory-form .invalid-feedback').text('');
    }

    function openModal(title, data) {
        clearFormErrors();
        $('#inventory-modal-label').text(title);
        $('#inventory-id').val(data.id || '');
        $('#inv-name').val(data.name || '');
        $('#inv-provider').val(data.provider || '');
        $('#inv-category').val(data.category || '');
        $('#inv-purpose').val(data.purpose || '');
        $('#inv-duration').val(data.duration || '');
        $('#inv-active').prop('checked', data.is_active === '1' || data.is_active === true);
        modal.show();
    }

    function showValidationErrors(errors) {
        $.each(errors, function (field, messages) {
            var $field = $('#inv-' + field.replace('_', '-'));
            if (!$field.length) {
                $field = $('[name="' + field + '"]');
            }
            $field.addClass('is-invalid').next('.invalid-feedback').text(messages[0]);
        });
    }

    // Open modal for create
    $(document).on('click', '#btn-add-cookie, #btn-add-cookie-empty', function () {
        openModal('Agregar cookie', {});
    });

    // Open modal for edit
    $(document).on('click', '.btn-edit-cookie', function () {
        var $btn = $(this);
        openModal('Editar cookie', {
            id:        $btn.data('id'),
            name:      $btn.data('name'),
            provider:  $btn.data('provider'),
            category:  $btn.data('category'),
            purpose:   $btn.data('purpose'),
            duration:  $btn.data('duration'),
            is_active: $btn.data('active'),
        });
    });

    // Save (create or update)
    $('#btn-save-cookie').on('click', function () {
        clearFormErrors();

        var id = $('#inventory-id').val();
        var isEdit = id !== '';
        var url = isEdit
            ? '{{ route("settings.cookie.inventory.update", ":id") }}'.replace(':id', id)
            : '{{ route("settings.cookie.inventory.store") }}';
        var method = isEdit ? 'PUT' : 'POST';

        var formData = {
            name:      $('#inv-name').val(),
            provider:  $('#inv-provider').val(),
            category:  $('#inv-category').val(),
            purpose:   $('#inv-purpose').val(),
            duration:  $('#inv-duration').val(),
            is_active: $('#inv-active').is(':checked') ? '1' : null,
        };

        var $btn = $(this);
        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                modal.hide();
                toastr.success(response.message);
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showValidationErrors(xhr.responseJSON.errors);
                } else {
                    toastr.error('Error al guardar la cookie.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).text('Guardar');
            },
        });
    });

    // Delete
    $(document).on('click', '.btn-delete-cookie', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');

        if (!confirm('¿Eliminar la cookie "' + name + '"? Esta acción no se puede deshacer.')) {
            return;
        }

        var url = '{{ route("settings.cookie.inventory.destroy", ":id") }}'.replace(':id', id);

        $.ajax({
            url: url,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                toastr.success(response.message);
                $('tr[data-id="' + id + '"]').fadeOut(300, function () { $(this).remove(); });
            },
            error: function () {
                toastr.error('Error al eliminar la cookie.');
            },
        });
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
