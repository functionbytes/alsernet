@extends('layouts.theme')

@section('page_header')
    @include('core::components.card', ['title' => 'Monitor de colas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Monitor de colas</h5>
                        <p class="small mb-0 text-muted">Supervisión y gestión de trabajos en cola del sistema</p>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Acciones
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="/horizon" target="_blank">
                                Ver Horizon
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="retryAllBtn">
                                Reintentar todos
                            </a>
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
                                <h6 class="card-title mb-2">Fallidos hoy</h6>
                                <h2 class="mb-1 fw-bold" id="stat-failed-24h">—</h2>
                                <small class="text-muted">Trabajos fallidos en las últimas 24h</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Fallidos 7 días</h6>
                                <h2 class="mb-1 fw-bold" id="stat-failed-7d">—</h2>
                                <small class="text-muted">Trabajos fallidos en la última semana</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">
                                    Horizon
                                    <span id="stat-horizon-icon" class="ms-1">
                                        @if($horizonStatus === 'running')
                                            <i class="fas fa-circle small" style="color: #13C672;"></i>
                                        @elseif($horizonStatus === 'paused')
                                            <i class="fas fa-circle small" style="color: #FEC90F;"></i>
                                        @else
                                            <i class="fas fa-circle small" style="color: #FA896B;"></i>
                                        @endif
                                    </span>
                                </h6>
                                <h2 class="mb-1" id="stat-horizon">
                                    <span id="stat-horizon-badge"
                                        class="badge @if($horizonStatus === 'running') bg-success @elseif($horizonStatus === 'paused') bg-warning @else bg-danger @endif">
                                        {{ $horizonStatus }}
                                    </span>
                                </h2>
                                <small class="text-muted">Estado del servicio</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">En cola</h6>
                                <h2 class="mb-1 fw-bold" id="stat-pending">—</h2>
                                <small class="text-muted">Trabajos pendientes de procesar</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Navigation pills --}}
            <ul class="nav nav-tabs border-0 user-profile-tab" id="queueTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                            id="failed-tab" data-bs-toggle="pill"
                            data-bs-target="#failed-panel" type="button" role="tab"
                            aria-controls="failed-panel" aria-selected="true">
                        <span class="d-none d-md-block">
                            Trabajos fallidos
                            <span class="badge bg-danger ms-1" id="failed-count-badge">0</span>
                        </span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="pending-tab" data-bs-toggle="pill"
                            data-bs-target="#pending-panel" type="button" role="tab"
                            aria-controls="pending-panel" aria-selected="false">
                        <span class="d-none d-md-block">
                            Trabajos pendientes
                            <span class="badge bg-danger ms-1" id="pending-count-badge">0</span>
                        </span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="queues-tab" data-bs-toggle="pill"
                            data-bs-target="#queues-panel" type="button" role="tab"
                            aria-controls="queues-panel" aria-selected="false">
                        <span class="d-none d-md-block">Estado de colas</span>
                    </button>
                </li>
            </ul>

            {{-- Tab content --}}
            <div class="card-body">
                <div class="tab-content" id="queueTabsContent">

                    {{-- Tab 1: Trabajos fallidos --}}
                    <div class="tab-pane fade show active" id="failed-panel" role="tabpanel" aria-labelledby="failed-tab">
                        <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch border-bottom pb-3 mb-3">
                            <div class="flex-fill">
                                <div class="input-group h-100">
                                    <span class="input-group-text bg-white border-end-1">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="search" class="form-control border-start-0 ps-0" id="failed-search" placeholder="Buscar por trabajo o error...">
                                </div>
                            </div>
                            <div class="flex-shrink-0" style="min-width: 180px;">
                                <select class="form-select select2 h-100" id="failed-queue-filter">
                                    <option value="">Todas las colas</option>
                                    @foreach($knownQueues as $queue)
                                        <option value="{{ $queue }}">{{ $queue }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-shrink-0" style="min-width: 150px;">
                                <input type="date" class="form-control h-100" id="failed-date-from" placeholder="Desde">
                            </div>
                            <div class="flex-shrink-0" style="min-width: 150px;">
                                <input type="date" class="form-control h-100" id="failed-date-to" placeholder="Hasta">
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                <button type="button" class="btn btn-primary" id="applyFailedFilter">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div id="bulk-actions-bar" class="d-none mb-3 p-2 bg-light rounded d-flex align-items-center gap-2">
                            <span class="text-muted small"><span id="bulk-selected-count">0</span> seleccionados</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="bulkRetryBtn">Reintentar seleccionados</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn">Eliminar seleccionados</button>
                            <button type="button" class="btn btn-sm btn-link text-muted ms-auto" id="clearSelectionBtn">Limpiar selección</button>
                        </div>
                        <div id="failedJobsGrid"></div>
                    </div>

                    {{-- Tab 2: Trabajos pendientes --}}
                    <div class="tab-pane fade" id="pending-panel" role="tabpanel" aria-labelledby="pending-tab">
                        <div id="pendingJobsGrid"></div>
                    </div>

                    {{-- Tab 3: Estado de colas --}}
                    <div class="tab-pane fade" id="queues-panel" role="tabpanel" aria-labelledby="queues-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="queues-status-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cola</th>
                                        <th class="text-center" style="width: 140px;">En espera</th>
                                        <th class="text-center" style="width: 120px;">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($knownQueues as $queue)
                                        <tr id="queue-row-{{ Str::slug($queue) }}">
                                            <td><code>{{ $queue }}</code></td>
                                            <td class="text-center pending-count">—</td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">—</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No hay colas registradas</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    var CSRF = $('meta[name="csrf-token"]').attr('content');

    // --- Helpers ---

    function esc(str) {
        return $('<div>').text(str || '').html();
    }

    // --- Stats ---

    function loadStats() {
        $.ajax({
            url: '{{ route("settings.queue.stats") }}',
            success: function (r) {
                $('#stat-failed-24h').text(r.failed_24h ?? 0);
                $('#stat-failed-7d').text(r.failed_7d ?? 0);
                $('#stat-pending').text(r.pending_total ?? 0);
                $('#failed-count-badge').text(r.failed_total ?? 0);
                $('#pending-count-badge').text(r.pending_total ?? 0);

                var status = r.horizon_status || 'unknown';
                var badgeClass = status === 'running' ? 'bg-success' : (status === 'paused' ? 'bg-warning' : 'bg-danger');
                $('#stat-horizon-badge').attr('class', 'badge ' + badgeClass).text(status);

                var iconColor = status === 'running' ? '#13C672' : (status === 'paused' ? '#FEC90F' : '#FA896B');
                $('#stat-horizon-icon i').css('color', iconColor);

                if (r.queue_pending) {
                    $.each(r.queue_pending, function (queue, count) {
                        var rowId = '#queue-row-' + queue.replace(/[^a-z0-9]/gi, '-').toLowerCase();
                        $(rowId + ' .pending-count').text(count);
                        var statusBadge = count > 0
                            ? '<span class="badge bg-info" >Activa</span>'
                            : '<span class="badge bg-secondary">Inactiva</span>';
                        $(rowId + ' td:last').html(statusBadge);
                    });
                }
            }
        });
    }

    loadStats();
    setInterval(loadStats, 60000);

    // --- Failed jobs table ---

    function getSelectedUuids() {
        var uuids = [];
        $('#failedJobsGrid .job-checkbox:checked').each(function () {
            uuids.push($(this).data('uuid'));
        });
        return uuids;
    }

    function updateBulkBar() {
        var count = $('#failedJobsGrid .job-checkbox:checked').length;
        if (count > 0) {
            $('#bulk-selected-count').text(count);
            $('#bulk-actions-bar').removeClass('d-none').addClass('d-flex');
        } else {
            $('#bulk-actions-bar').addClass('d-none').removeClass('d-flex');
        }
    }

    function renderFailedJobs() {
        var $grid = $('#failedJobsGrid');
        $grid.html('<div class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin me-1"></i> Cargando...</div>');
        $('#bulk-actions-bar').addClass('d-none').removeClass('d-flex');

        $.ajax({
            url: '{{ route("settings.queue.failed-jobs") }}',
            data: {
                skip: 0,
                take: 50,
                search: $('#failed-search').val(),
                queue: $('#failed-queue-filter').val(),
                date_from: $('#failed-date-from').val(),
                date_to: $('#failed-date-to').val()
            },
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (r) {
                var data = r.data || [];
                if (data.length === 0) {
                    $grid.html('<div class="text-center py-5 text-muted">No hay trabajos fallidos</div>');
                    return;
                }
                var rows = '';
                $.each(data, function (i, job) {
                    var errorLine = (job.exception || '').split('\n')[0].substring(0, 120);
                    rows += '<tr>' +
                        '<td class="text-center" style="width:40px;"><input type="checkbox" class="form-check-input job-checkbox" data-uuid="' + esc(job.uuid) + '"></td>' +
                        '<td><code class="small">' + esc(job.queue || '') + '</code></td>' +
                        '<td><small>' + esc(job.job_class || '') + '</small></td>' +
                        '<td><small class="text-muted" title="' + esc(job.exception || '') + '">' + esc(errorLine) + '</small></td>' +
                        '<td><small class="text-muted">' + esc(job.failed_at || '') + '</small></td>' +
                        '<td class="text-center">' +
                            '<div class="dropdown">' +
                                '<a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-vertical"></i></a>' +
                                '<ul class="dropdown-menu dropdown-menu-end">' +
                                    '<li><a class="dropdown-item btn-retry-job" href="javascript:void(0)" data-uuid="' + esc(job.uuid) + '">Reintentar</a></li>' +
                                    '<li><hr class="dropdown-divider"></li>' +
                                    '<li><a class="dropdown-item btn-delete-job" href="javascript:void(0)" data-uuid="' + esc(job.uuid) + '">Eliminar</a></li>' +
                                '</ul>' +
                            '</div>' +
                        '</td>' +
                    '</tr>';
                });
                var html = '<div class="table-responsive">' +
                    '<table class="table table-hover align-middle mb-0">' +
                    '<thead class="table-light"><tr>' +
                        '<th class="text-center" style="width:40px;"><input type="checkbox" class="form-check-input" id="selectAllJobs"></th>' +
                        '<th>Cola</th><th>Trabajo</th><th>Error</th><th>Fecha</th><th class="text-center">Acciones</th>' +
                    '</tr></thead>' +
                    '<tbody>' + rows + '</tbody>' +
                    '</table></div>';
                if (r.totalCount > 50) {
                    html += '<div class="text-center mt-2 text-muted">Mostrando 50 de ' + r.totalCount + ' trabajos fallidos</div>';
                }
                $grid.html(html);
            },
            error: function () {
                $grid.html('<div class="text-center py-3 text-danger">Error al cargar trabajos fallidos</div>');
            }
        });
    }

    renderFailedJobs();

    // Select all / deselect all
    $(document).on('change', '#selectAllJobs', function () {
        var checked = $(this).is(':checked');
        $('#failedJobsGrid .job-checkbox').prop('checked', checked);
        updateBulkBar();
    });

    $(document).on('change', '.job-checkbox', function () {
        var total = $('#failedJobsGrid .job-checkbox').length;
        var checked = $('#failedJobsGrid .job-checkbox:checked').length;
        $('#selectAllJobs').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAllJobs').prop('checked', checked === total && total > 0);
        updateBulkBar();
    });

    $('#clearSelectionBtn').on('click', function () {
        $('#failedJobsGrid .job-checkbox').prop('checked', false);
        $('#selectAllJobs').prop('checked', false).prop('indeterminate', false);
        updateBulkBar();
    });

    // Bulk retry
    $('#bulkRetryBtn').on('click', function () {
        var uuids = getSelectedUuids();
        if (!uuids.length) { return; }
        if (!confirm('¿Reintentar ' + uuids.length + ' trabajos seleccionados?')) { return; }
        $.ajax({
            url: '{{ route("settings.queue.failed-jobs.bulk-retry") }}',
            method: 'POST',
            data: JSON.stringify({ uuids: uuids }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (r) {
                toastr.success(r.message || 'Trabajos enviados a reintentar', 'Cola');
                renderFailedJobs();
                loadStats();
            },
            error: function () { toastr.error('Error al reintentar los trabajos', 'Error'); }
        });
    });

    // Bulk delete
    $('#bulkDeleteBtn').on('click', function () {
        var uuids = getSelectedUuids();
        if (!uuids.length) { return; }
        if (!confirm('¿Eliminar ' + uuids.length + ' trabajos seleccionados?')) { return; }
        $.ajax({
            url: '{{ route("settings.queue.failed-jobs.bulk-delete") }}',
            method: 'DELETE',
            data: JSON.stringify({ uuids: uuids }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (r) {
                toastr.success(r.message || 'Trabajos eliminados', 'Cola');
                renderFailedJobs();
                loadStats();
            },
            error: function () { toastr.error('Error al eliminar los trabajos', 'Error'); }
        });
    });

    // --- Pending jobs table ---

    function renderPendingJobs() {
        var $grid = $('#pendingJobsGrid');
        $grid.html('<div class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin me-1"></i> Cargando...</div>');

        $.ajax({
            url: '{{ route("settings.queue.pending-jobs") }}',
            data: { skip: 0, take: 50 },
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (r) {
                var data = r.data || [];
                if (data.length === 0) {
                    $grid.html('<div class="text-center py-5 text-muted">No hay trabajos pendientes</div>');
                    return;
                }
                var rows = '';
                $.each(data, function (i, job) {
                    rows += '<tr>' +
                        '<td><small class="text-muted">' + esc(job.available_at || '') + '</small></td>' +
                        '<td><code class="small">' + esc(job.queue || '') + '</code></td>' +
                        '<td><small>' + esc(job.job_class || '') + '</small></td>' +
                        '<td class="text-center"><span class="badge bg-light text-dark">' + (job.attempts || 0) + '</span></td>' +
                    '</tr>';
                });
                var html = '<div class="table-responsive">' +
                    '<table class="table table-hover align-middle mb-0">' +
                    '<thead class="table-light"><tr>' +
                        '<th>Disponible en</th><th>Cola</th><th>Trabajo</th><th class="text-center">Intentos</th>' +
                    '</tr></thead>' +
                    '<tbody>' + rows + '</tbody>' +
                    '</table></div>';
                if (r.totalCount > 50) {
                    html += '<div class="text-center mt-2 text-muted">Mostrando 50 de ' + r.totalCount + ' trabajos pendientes</div>';
                }
                $grid.html(html);
            },
            error: function () {
                $grid.html('<div class="text-center py-3 text-danger">Error al cargar trabajos pendientes</div>');
            }
        });
    }

    // Lazy load pending tab
    $('#pending-tab').on('shown.bs.tab', function () {
        renderPendingJobs();
    });

    // --- Filters ---

    $('#applyFailedFilter').on('click', function () {
        renderFailedJobs();
    });

    // --- Actions ---

    function retryJob(uuid) {
        $.ajax({
            url: '{{ url("settings/queue/failed-jobs") }}/' + uuid + '/retry',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (r) {
                if (r.message) {
                    toastr.success('Trabajo enviado a reintentar', 'Cola');
                    renderFailedJobs();
                    loadStats();
                } else {
                    toastr.error(r.error || 'Error al reintentar', 'Error');
                }
            },
            error: function () { toastr.error('Error al reintentar el trabajo', 'Error'); }
        });
    }

    function deleteFailedJob(uuid) {
        if (!confirm('¿Eliminar este trabajo fallido?')) { return; }
        $.ajax({
            url: '{{ url("settings/queue/failed-jobs") }}/' + uuid,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (r) {
                if (r.message) {
                    toastr.success('Trabajo eliminado', 'Cola');
                    renderFailedJobs();
                    loadStats();
                } else {
                    toastr.error(r.error || 'Error al eliminar', 'Error');
                }
            },
            error: function () { toastr.error('Error al eliminar el trabajo', 'Error'); }
        });
    }

    $(document).on('click', '.btn-retry-job', function () { retryJob($(this).data('uuid')); });
    $(document).on('click', '.btn-delete-job', function () { deleteFailedJob($(this).data('uuid')); });

    $('#retryAllBtn').on('click', function (e) {
        e.preventDefault();
        var queue = $('#failed-queue-filter').val();
        var msg = queue
            ? 'reintentar todos los trabajos fallidos de la cola "' + queue + '"'
            : 'reintentar TODOS los trabajos fallidos';

        if (!confirm('¿Confirmar ' + msg + '?')) { return; }

        var btn = $(this);
        btn.addClass('disabled').text('Procesando...');

        $.ajax({
            url: '{{ route("settings.queue.failed-jobs.retry-all") }}',
            method: 'POST',
            data: { queue: queue },
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: function (r) {
                if (r.message) {
                    toastr.success(r.message || 'Trabajos enviados a reintentar', 'Cola');
                    renderFailedJobs();
                    loadStats();
                } else {
                    toastr.error(r.error || 'Error', 'Error');
                }
            },
            error: function () { toastr.error('Error al reintentar trabajos', 'Error'); },
            complete: function () {
                btn.removeClass('disabled').text('Reintentar todos');
            }
        });
    });

});
</script>
@endpush
