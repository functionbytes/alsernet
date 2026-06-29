@extends('layouts.theme')

@section('title', 'Campañas de solicitud de reseñas')

@section('page_header')
    @include('core::components.card', ['title' => 'Campañas de solicitud de reseñas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Campañas de solicitud de reseñas</h5>
                        <p class="small mb-0 text-muted">Envía solicitudes de reseña a tus clientes por correo electrónico</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('reviews.campaigns.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva campaña
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Campañas creadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">En ejecución</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Programadas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['scheduled'] }}</h4>
                                <small class="text-muted">Con envío programado</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Borradores</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['draft'] }}</h4>
                                <small class="text-muted">Sin activar</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('reviews.campaigns.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="status" class="form-select select2">
                                <option value="">Todos los estados</option>
                                <option value="active"  {{ request('status') === 'active'  ? 'selected' : '' }}>Activa</option>
                                <option value="paused"  {{ request('status') === 'paused'  ? 'selected' : '' }}>Pausada</option>
                                <option value="draft"   {{ request('status') === 'draft'   ? 'selected' : '' }}>Borrador</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('reviews.campaigns.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($campaigns->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-envelope fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('status'))
                                    No se encontraron campañas
                                @else
                                    No hay campañas creadas
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('status'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Crea la primera campaña para empezar a solicitar reseñas
                                @endif
                            </p>
                            @if(!request('search') && !request('status'))
                                <a href="{{ route('reviews.campaigns.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Nueva campaña
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Ubicación</th>
                                    <th class="text-center">Estado</th>
                                    <th>Programado</th>
                                    <th class="text-center">Enviados</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campaigns as $campaign)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $campaign->id }}"></td>
                                        <td>
                                            <strong>{{ $campaign->name }}</strong>
                                            <div class="small text-muted">{{ $campaign->subject }}</div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $campaign->location->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($campaign->status === 'active')
                                                <span class="badge bg-success-subtle text-success campaign-status-badge" data-campaign-id="{{ $campaign->id }}">Activa</span>
                                            @elseif($campaign->status === 'paused')
                                                <span class="badge bg-warning-subtle text-warning campaign-status-badge" data-campaign-id="{{ $campaign->id }}">Pausada</span>
                                            @else
                                                <span class="badge bg-light text-dark campaign-status-badge" data-campaign-id="{{ $campaign->id }}">Borrador</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($campaign->scheduled_at)
                                                <span>{{ $campaign->scheduled_at->translatedFormat('d M Y H:i') }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary">{{ $campaign->sent_sends_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('reviews.campaigns.edit', $campaign) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item btn-enviar" href="javascript:void(0)"
                                                           data-id="{{ $campaign->id }}"
                                                           data-name="{{ e($campaign->name) }}">
                                                            Enviar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item btn-toggle-status" href="javascript:void(0)"
                                                           data-id="{{ $campaign->id }}"
                                                           data-status="{{ $campaign->status }}">
                                                            {{ $campaign->isActive() ? 'Pausar' : 'Activar' }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('reviews.campaigns.qr', $campaign) }}" target="_blank">
                                                            Ver QR
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item btn-stats" href="javascript:void(0)"
                                                           data-id="{{ $campaign->id }}">
                                                            Estadísticas
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('reviews.campaigns.destroy', $campaign) }}"
                                                           data-title="Eliminar: {{ e($campaign->name) }}">
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
                @endif
            </div>

            @if($campaigns->hasPages())
                <div class="card-footer">{{ $campaigns->links() }}</div>
            @endif

        </div>
    </div>

    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> campaña(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
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

    {{-- Modal enviar campaña --}}
    <div class="modal fade" id="modal-enviar-campana" tabindex="-1" aria-labelledby="modal-enviar-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-enviar-label">Enviar campaña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2">
                        Pega la lista de destinatarios, uno por linea en formato <code>Nombre,correo@ejemplo.com</code>
                        (maximo 100).
                    </p>
                    <textarea id="lista-destinatarios" class="form-control font-monospace" rows="10"
                        placeholder="Juan Perez,juan@ejemplo.com&#10;Maria Lopez,maria@ejemplo.com"></textarea>
                    <div id="envio-feedback" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="btn-confirmar-envio">
                        <i class="fas fa-paper-plane me-1"></i> Enviar correos
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

    {{-- Modal estadisticas --}}
    <div class="modal fade" id="modal-stats" tabindex="-1" aria-labelledby="modal-stats-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-stats-label">Estadísticas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="stats-body">
                    <div class="text-center py-3">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    var activeCampaignId = null;

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
        if (!ids.length) { toastr.warning('Selecciona al menos una campaña.'); return; }
        

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('reviews.campaigns.bulk-destroy') }}',
            method: 'POST',
            data: JSON.stringify({ ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.deleted + ' campaña(s) eliminada(s).');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // ── Toggle status ─────────────────────────────────────────────────────────
    $(document).on('click', '.btn-toggle-status', function() {
        const $btn = $(this);
        const id   = $btn.data('id');

        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ rtrim(route('reviews.campaigns.index'), '/') }}/' + id + '/toggle',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                const newStatus = res.status;
                const $badge = $('[data-campaign-id="' + id + '"].campaign-status-badge');

                $badge.removeClass('bg-success-subtle text-success bg-warning-subtle text-warning bg-light text-dark');
                if (newStatus === 'active') {
                    $badge.addClass('bg-success-subtle text-success').text('Activa');
                    $btn.text('Pausar');
                } else {
                    $badge.addClass('bg-warning-subtle text-warning').text('Pausada');
                    $btn.text('Activar');
                }

                $btn.data('status', newStatus);
                toastr.success(res.message || 'Estado actualizado.');
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al cambiar estado.');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // ── Abrir modal de envio ──────────────────────────────────────────────────
    $(document).on('click', '.btn-enviar', function () {
        activeCampaignId = $(this).data('id');
        $('#lista-destinatarios').val('');
        $('#envio-feedback').html('');
        $('#modal-enviar-campana').modal('show');
    });

    // ── Confirmar envio ───────────────────────────────────────────────────────
    $('#btn-confirmar-envio').on('click', function () {
        var lines = $('#lista-destinatarios').val().trim().split('\n').filter(Boolean);

        if (lines.length === 0) {
            toastr.warning('Ingresa al menos un destinatario.');
            return;
        }

        if (lines.length > 100) {
            toastr.warning('El maximo es 100 destinatarios por envio.');
            return;
        }

        var recipients = [];
        var invalid = [];

        lines.forEach(function (line, idx) {
            var parts = line.split(',');
            if (parts.length < 2) { invalid.push(idx + 1); return; }
            var name  = parts[0].trim();
            var email = parts.slice(1).join(',').trim();
            if (!name || !email) { invalid.push(idx + 1); return; }
            recipients.push({ name: name, email: email });
        });

        if (invalid.length) {
            $('#envio-feedback').html(
                '<div class="alert alert-warning alert-sm py-2">Lineas con formato invalido: ' + invalid.join(', ') + '</div>'
            );
            return;
        }

        var $btn = $(this).prop('disabled', true).text('Enviando...');

        $.ajax({
            url: '/reviews/campaigns/' + activeCampaignId + '/send',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ recipients: recipients }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message);
                $('#modal-enviar-campana').modal('hide');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || 'Error al enviar los correos.';
                $('#envio-feedback').html('<div class="alert alert-danger py-2">' + msg + '</div>');
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Enviar correos');
            },
        });
    });

    // ── Ver estadisticas ──────────────────────────────────────────────────────
    $(document).on('click', '.btn-stats', function () {
        var id = $(this).data('id');
        $('#stats-body').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
        $('#modal-stats').modal('show');

        $.get('/reviews/campaigns/' + id + '/stats', function (res) {
            var s = res.stats;
            $('#stats-body').html(
                '<div class="row text-center g-3">' +
                '<div class="col-6"><div class="card border-0 bg-light p-3"><div class="fs-3 fw-bold text-primary">' + s.sent + '</div><div class="small text-muted">Enviados</div></div></div>' +
                '<div class="col-6"><div class="card border-0 bg-light p-3"><div class="fs-3 fw-bold text-danger">' + s.failed + '</div><div class="small text-muted">Fallidos</div></div></div>' +
                '<div class="col-6"><div class="card border-0 bg-light p-3"><div class="fs-3 fw-bold text-success">' + s.opened + '</div><div class="small text-muted">Abiertos</div></div></div>' +
                '<div class="col-6"><div class="card border-0 bg-light p-3"><div class="fs-3 fw-bold text-info">' + s.open_rate + '%</div><div class="small text-muted">Tasa apertura</div></div></div>' +
                '</div>'
            );
        });
    });

    // ── Delete modal handler ──────────────────────────────────────────────────
    $('#delete-modal').on('show.bs.modal', function (e) {
        var $trigger = $(e.relatedTarget);
        $(this).find('.modal-title').text($trigger.data('title'));
        $('#delete-form').attr('action', $trigger.data('url'));
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
