@extends('layouts.theme')
@section('title', 'Clientes en riesgo · Helpdesk')
@section('page_header')
    @include('core::components.card', ['title' => 'Clientes en riesgo · Helpdesk'])
@endsection

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-heart-crack text-danger me-2"></i>Clientes en riesgo
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Clientes con mayor número de conversaciones con sentimiento negativo en los últimos 90 días, combinado con su puntuación de salud.
    </p>
    <div class="ms-auto order-2">
        <button id="btn-refresh" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-rotate me-1"></i>Actualizar
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pb-0 pt-3">
        <h6 class="fw-semibold mb-0">
            <i class="fas fa-triangle-exclamation text-danger me-1"></i>
            Top 50 clientes por riesgo
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-at-risk">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Cliente</th>
                        <th scope="col">Email</th>
                        <th scope="col" class="text-center">Sentimientos negativos</th>
                        <th scope="col" class="text-center">Salud</th>
                        <th scope="col">Último negativo</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-at-risk">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Cargando datos...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const dataUrl = "{{ route('manager.helpdesk.reports.at-risk.data') }}";

    function esc(value) {
        return $('<span>').text(value == null ? '' : value).html();
    }

    function healthBadge(score) {
        let cls = 'bg-danger';
        if (score >= 70) {
            cls = 'bg-primary';
        } else if (score >= 40) {
            cls = 'bg-warning text-dark';
        }
        return '<span class="badge ' + cls + '">' + score + '</span>';
    }

    function negativeBadge(count) {
        return '<span class="badge bg-danger">' + count + '</span>';
    }

    function formatDate(iso) {
        if (!iso) {
            return '—';
        }
        return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function actionCell(row) {
        if (!row.contactUrl) {
            return '<span class="text-muted small">—</span>';
        }
        return '<a href="' + row.contactUrl + '" class="btn btn-sm btn-outline-primary">' +
            '<i class="fas fa-user me-1"></i>Ver ficha</a>';
    }

    function load() {
        const tbody = $('#tbody-at-risk');
        tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">Cargando datos...</td></tr>');

        $.getJSON(dataUrl).done(function (data) {
            const customers = data.customers || [];
            tbody.empty();

            if (customers.length === 0) {
                tbody.html(
                    '<tr><td colspan="6" class="text-center text-muted py-5">' +
                    '<i class="fas fa-heart fa-2x mb-2 d-block text-success"></i>' +
                    'No hay clientes en riesgo en los últimos 90 días.' +
                    '</td></tr>'
                );
                return;
            }

            customers.forEach(function (row) {
                tbody.append(
                    '<tr>' +
                    '<td class="fw-semibold">' + esc(row.name) + '</td>' +
                    '<td class="text-muted">' + esc(row.email || '—') + '</td>' +
                    '<td class="text-center">' + negativeBadge(row.negativeCount) + '</td>' +
                    '<td class="text-center">' + healthBadge(row.healthScore) + '</td>' +
                    '<td class="text-nowrap text-muted small">' + formatDate(row.lastNegativeAt) + '</td>' +
                    '<td class="text-end">' + actionCell(row) + '</td>' +
                    '</tr>'
                );
            });
        }).fail(function () {
            tbody.html('<tr><td colspan="6" class="text-center text-danger py-4">Error al cargar los datos.</td></tr>');
            toastr.error('Error al cargar el reporte de clientes en riesgo.');
        });
    }

    $('#btn-refresh').on('click', load);

    load();
})();
</script>
@endpush
@endsection
