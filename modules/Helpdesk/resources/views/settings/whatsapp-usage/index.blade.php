@extends('layouts.theme')

@section('title', 'Consumo de WhatsApp')

@section('content')

    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Consumo de WhatsApp</h5>
                    <p class="small mb-0 text-muted">
                        Plantillas HSM enviadas (categoría que Meta factura: Marketing/Utilidad/Autenticación) y
                        respuestas de texto del agente dentro de la ventana de servicio de 24h (categoría Service,
                        gratis).
                    </p>
                </div>
                <a href="{{ route('settings.helpdesk.whatsapp-templates.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Plantillas WhatsApp
                </a>
            </div>
        </div>

        {{-- Tarifas por categoría --}}
        <div class="card-body border-bottom">
            <h6 class="small fw-bold text-muted mb-2">Tarifas por categoría (€ por conversación)</h6>
            <p class="small text-muted mb-3">
                Meta no expone tarifas por país vía API — introduce aquí lo que realmente te factura Meta por cada
                categoría (varía según el país del destinatario) para que el reporte estime el gasto real.
                "Service" siempre es gratis y no se configura.
            </p>
            <form id="wa-pricing-form" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <label for="wa-price-marketing" class="form-label small mb-1">Marketing</label>
                    <div class="input-group input-group-sm" style="width: 140px;">
                        <span class="input-group-text">€</span>
                        <input type="number" step="0.0001" min="0" class="form-control" id="wa-price-marketing" value="{{ $pricing['marketing'] }}">
                    </div>
                </div>
                <div class="col-auto">
                    <label for="wa-price-utility" class="form-label small mb-1">Utilidad</label>
                    <div class="input-group input-group-sm" style="width: 140px;">
                        <span class="input-group-text">€</span>
                        <input type="number" step="0.0001" min="0" class="form-control" id="wa-price-utility" value="{{ $pricing['utility'] }}">
                    </div>
                </div>
                <div class="col-auto">
                    <label for="wa-price-authentication" class="form-label small mb-1">Autenticación</label>
                    <div class="input-group input-group-sm" style="width: 140px;">
                        <span class="input-group-text">€</span>
                        <input type="number" step="0.0001" min="0" class="form-control" id="wa-price-authentication" value="{{ $pricing['authentication'] }}">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar tarifas
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-auto">
                    <label for="wa-usage-from" class="form-label small mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" id="wa-usage-from">
                </div>
                <div class="col-auto">
                    <label for="wa-usage-to" class="form-label small mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="wa-usage-to">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="wa-usage-filter">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
                <div class="col-auto ms-auto">
                    <a href="#" id="wa-usage-export" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> Exportar CSV
                    </a>
                </div>
            </div>

            <div id="wa-usage-loading" class="text-muted small">
                <i class="fas fa-spinner fa-spin"></i> Cargando…
            </div>

            <div id="wa-usage-content" class="d-none">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Enviados</h6>
                                <h4 class="mb-1 fw-bold" id="wa-usage-sent">0</h4>
                                <small class="text-muted">Plantillas + respuestas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-success mb-2">Confirmados</h6>
                                <h4 class="mb-1 fw-bold" id="wa-usage-success">0</h4>
                                <small class="text-muted">Aceptados por Meta</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-danger mb-2">Fallidos</h6>
                                <h4 class="mb-1 fw-bold" id="wa-usage-failed">0</h4>
                                <small class="text-muted">Rechazados por Meta</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-success mb-2">Gasto estimado</h6>
                                <h4 class="mb-1 fw-bold" id="wa-usage-cost">€0.00</h4>
                                <small class="text-muted">Según tarifas configuradas</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="small fw-bold text-muted mb-2">Tendencia diaria</h6>
                    <canvas id="wa-usage-chart" height="80"></canvas>
                </div>

                <div class="row g-3">
                    <div class="col-md-5">
                        <h6 class="small fw-bold text-muted mb-2">Por categoría</h6>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-end">Enviados</th>
                                </tr>
                            </thead>
                            <tbody id="wa-usage-by-category"></tbody>
                        </table>
                    </div>
                    <div class="col-md-7">
                        <h6 class="small fw-bold text-muted mb-2">Plantillas más usadas</h6>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Plantilla</th>
                                    <th class="text-center">Categoría</th>
                                    <th class="text-end">Enviados</th>
                                </tr>
                            </thead>
                            <tbody id="wa-usage-top-templates"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="wa-usage-empty" class="text-muted small d-none">
                Sin envíos registrados en este rango de fechas.
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function () {
    var waCategoryLabels = {
        marketing: 'Marketing',
        utility: 'Utilidad',
        authentication: 'Autenticación',
        service: 'Servicio (gratis)',
        desconocida: 'Desconocida'
    };
    var waChart = null;

    function waRenderUsage(data) {
        $('#wa-usage-sent').text(Number(data.totals.sent).toLocaleString());
        $('#wa-usage-success').text(Number(data.totals.success_sent).toLocaleString());
        $('#wa-usage-failed').text(Number(data.totals.failed_sent).toLocaleString());
        $('#wa-usage-cost').text('€' + Number(data.totals.estimated_cost_eur).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 }));

        var $byCategory = $('#wa-usage-by-category').empty();
        (data.by_category || []).forEach(function (row) {
            var label = waCategoryLabels[row.category] || row.category;
            $byCategory.append(
                $('<tr>').append(
                    $('<td>').text(label),
                    $('<td class="text-end">').text(Number(row.sent).toLocaleString())
                )
            );
        });

        var $topTemplates = $('#wa-usage-top-templates').empty();
        (data.top_templates || []).forEach(function (row) {
            var label = waCategoryLabels[row.category] || row.category || '—';
            $topTemplates.append(
                $('<tr>').append(
                    $('<td>').text(row.template_name || '—'),
                    $('<td class="text-center">').text(label),
                    $('<td class="text-end">').text(Number(row.sent).toLocaleString())
                )
            );
        });

        var daily = data.daily || [];
        if (waChart) {
            waChart.destroy();
            waChart = null;
        }
        waChart = new Chart(document.getElementById('wa-usage-chart'), {
            type: 'line',
            data: {
                labels: daily.map(function (d) { return d.date; }),
                datasets: [
                    { label: 'Enviados', data: daily.map(function (d) { return d.sent; }), borderColor: '#90bb13', tension: 0.3 },
                    { label: 'Fallidos', data: daily.map(function (d) { return d.failed; }), borderColor: '#FA896B', tension: 0.3 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });

        var isEmpty = data.totals.sent === 0;
        $('#wa-usage-content').toggleClass('d-none', isEmpty);
        $('#wa-usage-empty').toggleClass('d-none', !isEmpty);
    }

    function waCurrentRange() {
        return { from: $('#wa-usage-from').val(), to: $('#wa-usage-to').val() };
    }

    function waLoadUsage() {
        $('#wa-usage-loading').removeClass('d-none');
        $('#wa-usage-content, #wa-usage-empty').addClass('d-none');

        $.ajax({
            url: "{{ route('settings.helpdesk.whatsapp-usage.data') }}",
            method: 'GET',
            data: waCurrentRange(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (resp) {
                waRenderUsage(resp);
            },
            error: function () {
                if (window.toastr) {
                    toastr.error('No se pudo cargar el consumo de WhatsApp.');
                }
            },
            complete: function () {
                $('#wa-usage-loading').addClass('d-none');
            }
        });
    }

    function waUpdateExportLink() {
        var range = waCurrentRange();
        $('#wa-usage-export').attr('href', "{{ route('settings.helpdesk.whatsapp-usage.export') }}?" + $.param(range));
    }

    $('#wa-usage-filter').on('click', function () {
        waUpdateExportLink();
        waLoadUsage();
    });

    $('#wa-pricing-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true);

        $.ajax({
            url: "{{ route('settings.helpdesk.whatsapp-usage.pricing') }}",
            method: 'POST',
            data: {
                marketing: $('#wa-price-marketing').val(),
                utility: $('#wa-price-utility').val(),
                authentication: $('#wa-price-authentication').val(),
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                if (window.toastr) {
                    toastr.success('Tarifas actualizadas.');
                }
                waLoadUsage();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudieron guardar las tarifas.';
                if (window.toastr) {
                    toastr.error(msg);
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    (function () {
        var today = new Date();
        var from = new Date();
        from.setDate(today.getDate() - 29);
        $('#wa-usage-to').val(today.toISOString().slice(0, 10));
        $('#wa-usage-from').val(from.toISOString().slice(0, 10));
        waUpdateExportLink();
        waLoadUsage();
    })();
});
</script>
@endpush
