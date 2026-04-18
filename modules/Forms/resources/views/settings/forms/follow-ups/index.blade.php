@extends('layouts.theme')

@section('title', 'Follow-ups: ' . $form->name)

@section('content')

    @include('core::components.card', ['title' => 'Follow-ups'])

    <div class="widget-content searchable-container list">

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                            <small class="text-muted">{{ $form->slug }}</small>
                        </div>
                        @if ($form->is_active)
                            <span class="badge bg-light-success text-success">Activo</span>
                        @else
                            <span class="badge bg-light-danger text-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#followUpModal">
                            <i class="fas fa-plus me-1"></i> Añadir follow-up
                        </button>
                        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-desktop me-1"></i> Preview
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-inbox me-1"></i> Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header p-4 border-bottom">
                        <h5 class="mb-1 fw-bold">Follow-ups</h5>
                        <p class="small mb-0 text-muted">Emails automáticos configurados para este formulario</p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Nombre</th>
                                        <th class="text-center">Días</th>
                                        <th>Destinatario</th>
                                        <th>Condición</th>
                                        <th class="text-center">Activo</th>
                                        <th class="text-end pe-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="followUpsList">
                                    @forelse ($followUps as $followUp)
                                        <tr id="row-{{ $followUp->id }}">
                                            <td class="ps-3 fw-semibold small">{{ $followUp->name }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">+{{ $followUp->days }} días</span>
                                            </td>
                                            <td class="small">
                                                @match($followUp->recipient_type)
                                                    'admin'     => 'Administrador',
                                                    'submitter' => 'Remitente',
                                                    'both'      => 'Ambos',
                                                    default     => $followUp->recipient_type,
                                                @endmatch
                                            </td>
                                            <td class="small text-muted">
                                                @if ($followUp->condition_field)
                                                    {{ $followUp->condition_field }}: {{ $followUp->condition_value }}
                                                @else
                                                    Siempre
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                    <input class="form-check-input toggle-active"
                                                           type="checkbox"
                                                           data-id="{{ $followUp->id }}"
                                                           data-url="{{ route('settings.forms.follow-ups.update', [$form, $followUp]) }}"
                                                           {{ $followUp->is_active ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-secondary btn-edit-followup"
                                                            data-followup="{{ json_encode($followUp) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger btn-delete-followup"
                                                            data-id="{{ $followUp->id }}"
                                                            data-url="{{ route('settings.forms.follow-ups.destroy', [$form, $followUp]) }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="emptyRow">
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="fas fa-envelope-open-text fa-2x mb-2 d-block opacity-50"></i>
                                                No hay follow-ups configurados
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer p-4">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#followUpModal">
                            Añadir follow-up
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                @include('forms::settings.partials.tabs', ['active' => 'follow-ups'])

                <div class="card mt-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Sobre los follow-ups</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted ps-3 mb-0 small">
                            <li class="mb-1">Los follow-ups se envían automáticamente tras recibir un envío.</li>
                            <li class="mb-1">Puedes configurar un retraso en días antes del envío.</li>
                            <li>Usa variables de campos para personalizar los mensajes.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

{{-- Modal añadir/editar follow-up --}}
<div class="modal fade" id="followUpModal" tabindex="-1" aria-labelledby="followUpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="followUpModalLabel">Añadir follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="followUpId">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="fuName" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="fuName" class="form-control" placeholder="Ej: Recordatorio a los 3 días">
                    </div>
                    <div class="col-md-4">
                        <label for="fuDays" class="form-label">Días después del envío <span class="text-danger">*</span></label>
                        <input type="number" id="fuDays" class="form-control" min="1" value="3">
                    </div>
                    <div class="col-md-8">
                        <label for="fuRecipient" class="form-label">Destinatario</label>
                        <select id="fuRecipient" class="form-select">
                            <option value="admin">Administrador</option>
                            <option value="submitter">Remitente del formulario</option>
                            <option value="both">Ambos</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="fuTemplate" class="form-label">Plantilla de email</label>
                        <select id="fuTemplate" class="form-select">
                            <option value="">— Seleccionar plantilla —</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="fuConditionField" class="form-label">Condición — campo</label>
                        <input type="text" id="fuConditionField" class="form-control" placeholder="Ej: status">
                    </div>
                    <div class="col-md-6">
                        <label for="fuConditionValue" class="form-label">Condición — valor</label>
                        <input type="text" id="fuConditionValue" class="form-control" placeholder="Ej: new">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="fuActive" checked>
                            <label class="form-check-label" for="fuActive">Activo</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100 mb-2" id="saveFollowUpBtn">
                    <i class="fas fa-save me-1"></i> Guardar
                </button>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const storeUrl  = '{{ route('settings.forms.follow-ups.store', $form) }}';
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Toggle activo via switch
    $(document).on('change', '.toggle-active', function () {
        const url = $(this).data('url');
        const active = $(this).is(':checked');
        $.ajax({
            url: url,
            method: 'PATCH',
            data: { is_active: active ? 1 : 0 },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () { toastr.success('Estado actualizado'); },
            error: function () { toastr.error('Error al actualizar'); }
        });
    });

    // Abrir modal para edición
    $(document).on('click', '.btn-edit-followup', function () {
        const data = $(this).data('followup');
        $('#followUpId').val(data.id);
        $('#fuName').val(data.name);
        $('#fuDays').val(data.days);
        $('#fuRecipient').val(data.recipient_type);
        $('#fuConditionField').val(data.condition_field || '');
        $('#fuConditionValue').val(data.condition_value || '');
        $('#fuActive').prop('checked', !!data.is_active);
        $('#followUpModalLabel').text('Editar follow-up');
        $('#followUpModal').modal('show');
    });

    // Limpiar modal al cerrar
    $('#followUpModal').on('hidden.bs.modal', function () {
        $('#followUpId').val('');
        $('#fuName, #fuConditionField, #fuConditionValue').val('');
        $('#fuDays').val(3);
        $('#fuRecipient').val('admin');
        $('#fuActive').prop('checked', true);
        $('#followUpModalLabel').text('Añadir follow-up');
    });

    // Guardar follow-up (crear o actualizar)
    $('#saveFollowUpBtn').on('click', function () {
        const id = $('#followUpId').val();
        const url = id
            ? '{{ url('settings/forms/' . $form->id . '/follow-ups') }}/' + id
            : storeUrl;
        const method = id ? 'PATCH' : 'POST';

        const payload = {
            name:            $('#fuName').val(),
            days:            $('#fuDays').val(),
            recipient_type:  $('#fuRecipient').val(),
            condition_field: $('#fuConditionField').val() || null,
            condition_value: $('#fuConditionValue').val() || null,
            is_active:       $('#fuActive').is(':checked') ? 1 : 0,
        };

        $.ajax({
            url: url,
            method: method,
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success(id ? 'Follow-up actualizado' : 'Follow-up creado');
                $('#followUpModal').modal('hide');
                location.reload();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    toastr.error(Object.values(errors).flat().join(', '));
                } else {
                    toastr.error('Error al guardar el follow-up');
                }
            }
        });
    });

    // Eliminar follow-up
    $(document).on('click', '.btn-delete-followup', function () {
        if (!confirm('¿Eliminar este follow-up?')) return;
        const url = $(this).data('url');
        const id  = $(this).data('id');
        $.ajax({
            url: url,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success('Follow-up eliminado');
                $('#row-' + id).remove();
            },
            error: function () { toastr.error('Error al eliminar'); }
        });
    });
</script>
@endpush
