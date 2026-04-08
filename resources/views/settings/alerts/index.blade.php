@extends('layouts.theme')

@section('title', 'Alertas por umbral')

@section('content')
    @include('core::components.card', ['title' => 'Alertas por umbral'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Alertas configuradas</h5>
                        <p class="small mb-0 text-muted">Gestiona los umbrales de alerta automáticos del sistema</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#alert-modal">
                        <i class="fas fa-plus"></i> Nueva alerta
                    </button>
                </div>
            </div>

            <div class="card-body">
                @if($thresholds->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Condiciones</th>
                                    <th>Canales</th>
                                    <th class="text-center">Estado</th>
                                    <th>Ultima vez disparada</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($thresholds as $threshold)
                                    @php
                                        $typeLabels = [
                                            'tickets_overdue'   => 'Tickets vencidos',
                                            'reviews_negative'  => 'Reseñas negativas',
                                            'forms_unread'      => 'Formularios sin leer',
                                            'attention_pending' => 'PQRSF pendientes',
                                        ];
                                    @endphp
                                    <tr id="row-{{ $threshold->id }}">
                                        <td>
                                            <strong>{{ $threshold->name }}</strong>
                                            @if($threshold->cooldown_minutes)
                                                <br><small class="text-muted">Cooldown: {{ $threshold->cooldown_minutes }} min</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ $typeLabels[$threshold->type] ?? $threshold->type }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                @foreach($threshold->conditions ?? [] as $key => $value)
                                                    <span class="d-block">{{ $key }}: <strong>{{ $value }}</strong></span>
                                                @endforeach
                                            </small>
                                        </td>
                                        <td>
                                            @foreach($threshold->channels ?? [] as $channel)
                                                <span class="badge bg-light-primary text-primary me-1">{{ $channel }}</span>
                                            @endforeach
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input toggle-active"
                                                       type="checkbox"
                                                       role="switch"
                                                       data-id="{{ $threshold->id }}"
                                                       data-url="{{ route('settings.alerts.toggle', $threshold) }}"
                                                       {{ $threshold->is_active ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            @if($threshold->last_triggered_at)
                                                <small class="text-muted" title="{{ $threshold->last_triggered_at->format('d/m/Y H:i:s') }}">
                                                    {{ $threshold->last_triggered_at->diffForHumans() }}
                                                </small>
                                                @if($threshold->isInCooldown())
                                                    <br><span class="badge bg-warning-subtle text-warning small">En cooldown</span>
                                                @endif
                                            @else
                                                <small class="text-muted">Nunca</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item edit-btn"
                                                           href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#alert-modal"
                                                           data-id="{{ $threshold->id }}"
                                                           data-name="{{ $threshold->name }}"
                                                           data-type="{{ $threshold->type }}"
                                                           data-conditions="{{ json_encode($threshold->conditions) }}"
                                                           data-channels="{{ json_encode($threshold->channels) }}"
                                                           data-recipients="{{ implode(',', $threshold->recipients ?? []) }}"
                                                           data-cooldown="{{ $threshold->cooldown_minutes }}"
                                                           data-active="{{ $threshold->is_active ? '1' : '0' }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-btn"
                                                           href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-id="{{ $threshold->id }}"
                                                           data-name="{{ $threshold->name }}"
                                                           data-url="{{ route('settings.alerts.destroy', $threshold) }}">
                                                            Eliminar
                                                        </a>
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
                        <i class="fas fa-bell-slash fa-4x text-muted opacity-50 mb-3 d-block"></i>
                        <h5 class="text-muted mb-2">No hay alertas configuradas</h5>
                        <p class="text-muted small mb-4">Crea tu primera alerta para recibir notificaciones automáticas</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#alert-modal">
                            <i class="fas fa-plus me-1"></i> Crear primera alerta
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal crear / editar --}}
    <div id="alert-modal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="alert-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">
                    <input type="hidden" name="is_active" id="field-is-active" value="1">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-title">Nueva alerta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="field-name" class="form-control"
                                       placeholder="Ej: Tickets urgentes sin respuesta" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                                <select name="type" id="field-type" class="form-select" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="tickets_overdue">Tickets vencidos</option>
                                    <option value="reviews_negative">Reseñas negativas</option>
                                    <option value="forms_unread">Formularios sin leer</option>
                                    <option value="attention_pending">PQRSF pendientes</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cooldown (minutos) <span class="text-danger">*</span></label>
                                <input type="number" name="cooldown_minutes" id="field-cooldown"
                                       class="form-control" min="5" max="1440" value="60" required>
                                <small class="text-muted">Tiempo mínimo entre disparos de la misma alerta</small>
                            </div>

                            {{-- Condiciones dinámicas --}}
                            <div class="col-12" id="conditions-wrapper">
                                <label class="form-label fw-semibold">Condiciones</label>

                                {{-- tickets_overdue --}}
                                <div class="conditions-panel" data-type="tickets_overdue" style="display:none;">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small">Horas sin respuesta</label>
                                            <input type="number" name="conditions[hours]" class="form-control" min="1" value="24">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Cantidad mínima de tickets</label>
                                            <input type="number" name="conditions[min_count]" class="form-control" min="1" value="1">
                                        </div>
                                    </div>
                                </div>

                                {{-- reviews_negative --}}
                                <div class="conditions-panel" data-type="reviews_negative" style="display:none;">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small">Ventana de horas</label>
                                            <input type="number" name="conditions[hours]" class="form-control" min="1" value="2">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Cantidad mínima</label>
                                            <input type="number" name="conditions[min_count]" class="form-control" min="1" value="3">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Rating máximo (1-5)</label>
                                            <input type="number" name="conditions[max_rating]" class="form-control" min="1" max="5" value="2">
                                        </div>
                                    </div>
                                </div>

                                {{-- forms_unread --}}
                                <div class="conditions-panel" data-type="forms_unread" style="display:none;">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small">Formularios sin leer (mínimo)</label>
                                            <input type="number" name="conditions[min_count]" class="form-control" min="1" value="20">
                                        </div>
                                    </div>
                                </div>

                                {{-- attention_pending --}}
                                <div class="conditions-panel" data-type="attention_pending" style="display:none;">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small">PQRSF pendientes (mínimo)</label>
                                            <input type="number" name="conditions[min_count]" class="form-control" min="1" value="10">
                                        </div>
                                    </div>
                                </div>

                                <p id="conditions-placeholder" class="text-muted small">
                                    Selecciona un tipo para ver las condiciones disponibles.
                                </p>
                            </div>

                            {{-- Canales --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">Canales de notificación <span class="text-danger">*</span></label>
                                <div class="d-flex gap-4 mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="mail" id="ch-mail">
                                        <label class="form-check-label" for="ch-mail">Email</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="database" id="ch-database" checked>
                                        <label class="form-check-label" for="ch-database">Notificacion interna</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Destinatarios adicionales --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">Destinatarios adicionales (emails)</label>
                                <input type="text" name="recipients" id="field-recipients" class="form-control"
                                       placeholder="email1@ejemplo.com, email2@ejemplo.com">
                                <small class="text-muted">Separados por coma. Los admins del sistema siempre reciben las alertas por email.</small>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar alerta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal eliminar --}}
    <div id="delete-modal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form id="delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Eliminar alerta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="display-4 text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5>Eliminar <strong id="delete-name"></strong>?</h5>
                        <p class="text-muted">Esta accion no se puede deshacer.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Confirmar eliminacion</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const createUrl = '{{ route('settings.alerts.store') }}';
    const typeLabels = {
        tickets_overdue:   'Tickets vencidos',
        reviews_negative:  'Reseñas negativas',
        forms_unread:      'Formularios sin leer',
        attention_pending: 'PQRSF pendientes',
    };

    // Show/hide condition panels when type changes
    $('#field-type').on('change', function () {
        const type = $(this).val();
        $('.conditions-panel').hide();
        $('#conditions-placeholder').toggle(type === '');
        if (type) {
            $(`.conditions-panel[data-type="${type}"]`).show();
        }
    });

    // Reset modal to "create" mode
    $('#alert-modal').on('show.bs.modal', function (e) {
        if (!$(e.relatedTarget).hasClass('edit-btn')) {
            resetModal();
        }
    });

    // Populate modal for editing
    $(document).on('click', '.edit-btn', function () {
        const btn        = $(this);
        const id         = btn.data('id');
        const type       = btn.data('type');
        const conditions = btn.data('conditions');
        const channels   = btn.data('channels');

        $('#modal-title').text('Editar alerta');
        $('#alert-form').attr('action', '{{ url('settings/alerts') }}/' + id);
        $('#form-method').val('PUT');

        $('#field-name').val(btn.data('name'));
        $('#field-type').val(type).trigger('change');
        $('#field-cooldown').val(btn.data('cooldown'));
        $('#field-recipients').val(btn.data('recipients'));
        $('#field-is-active').val(btn.data('active'));

        // Fill conditions
        if (conditions) {
            $.each(conditions, function (key, value) {
                $(`.conditions-panel[data-type="${type}"] input[name="conditions[${key}]"]`).val(value);
            });
        }

        // Check channels
        $('input[name="channels[]"]').prop('checked', false);
        if (channels) {
            $.each(channels, function (i, ch) {
                $(`input[name="channels[]"][value="${ch}"]`).prop('checked', true);
            });
        }
    });

    function resetModal() {
        $('#modal-title').text('Nueva alerta');
        $('#alert-form').attr('action', createUrl);
        $('#form-method').val('POST');
        $('#field-name').val('');
        $('#field-type').val('').trigger('change');
        $('#field-cooldown').val('60');
        $('#field-recipients').val('');
        $('#field-is-active').val('1');
        $('input[name="channels[]"]').prop('checked', false);
        $('#ch-database').prop('checked', true);
    }

    // Toggle active state via AJAX
    $(document).on('change', '.toggle-active', function () {
        const cb  = $(this);
        const url = cb.data('url');

        $.ajax({
            url:     url,
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message);
            },
            error: function () {
                cb.prop('checked', !cb.is(':checked'));
                toastr.error('Error al cambiar el estado.');
            },
        });
    });

    // Delete modal
    $(document).on('click', '.delete-btn', function () {
        const btn = $(this);
        $('#delete-name').text(btn.data('name'));
        $('#delete-form').attr('action', btn.data('url'));
    });
});
</script>
@endpush
