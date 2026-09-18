/**
 * HelpdeskCompliance — requests.js
 *
 * Logic for modules/HelpdeskCompliance/resources/views/requests/index.blade.php
 * (listado de solicitudes GDPR). Depends on jQuery and toastr.
 *
 * The data endpoint comes from window.HelpdeskComplianceRequests, emitted
 * inline by the view (see the @push('scripts') block) since this file is a
 * static asset with no access to Blade's route().
 */
(function () {
    'use strict';

    var CONFIG = window.HelpdeskComplianceRequests || {};

    $(function () {
        var dataUrl = CONFIG.dataUrl;
        var currentPage = 1;
        var lastPage = 1;

        var typeLabels = {
            delete_soft: 'Anonimizado',
            delete_hard: 'Borrado',
            export: 'Exportacion',
        };

        function fmt(iso) {
            if (!iso) return '—';
            return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        // Sin rojos en la UI (paleta del proyecto): "failed" se resuelve en gris
        // oscuro con icono de aviso, igual que 'failed' => 'secondary' en
        // Supplier/sync/index.blade.php, en vez de bg-danger-subtle.
        function statusBadge(status) {
            var label = $('<div>').text(status).html();
            return status === 'completed'
                ? '<span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>' + label + '</span>'
                : '<span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="fas fa-triangle-exclamation me-1"></i>' + label + '</span>';
        }

        function render(rows) {
            if (!rows.length) {
                $('#request-rows').html('<tr><td colspan="5" class="text-center text-muted py-4">Sin solicitudes registradas.</td></tr>');
                return;
            }
            var html = rows.map(function (r) {
                var mods = (r.modulesAffected || []).map(function (m) {
                    return '<span class="badge bg-light text-dark me-1">' + $('<div>').text(m).html() + '</span>';
                }).join('') || '<span class="text-muted">—</span>';
                var typeBadge = r.type === 'delete_hard'
                    ? '<span class="badge bg-danger-subtle text-danger">' + (typeLabels[r.type] || r.type) + '</span>'
                    : '<span class="badge bg-warning-subtle text-warning">' + (typeLabels[r.type] || r.type) + '</span>';
                return '<tr>' +
                    '<td>' + (r.customer ? $('<div>').text(r.customer).html() : '<span class="text-muted">#' + (r.customerId || '?') + '</span>') + '</td>' +
                    '<td>' + typeBadge + '</td>' +
                    '<td>' + mods + '</td>' +
                    '<td>' + statusBadge(r.status) + '</td>' +
                    '<td>' + fmt(r.completedAt || r.createdAt) + '</td>' +
                    '</tr>';
            }).join('');
            $('#request-rows').html(html);
        }

        // Igual patron que #breach-pagination en HelpdeskSla/breaches/index.blade.php:
        // la pagina la calcula el servidor (meta.currentPage/lastPage), no el cliente.
        function renderPagination(meta) {
            var $pag = $('#request-pagination').empty();
            if (!meta || meta.lastPage <= 1) {
                $('#request-pagination-info').addClass('d-none');
                return;
            }
            $('#request-pagination-info').removeClass('d-none');
            $('#request-pagination-summary').text('Pagina ' + meta.currentPage + ' de ' + meta.lastPage + ' — ' + meta.total + ' solicitud(es)');
            lastPage = meta.lastPage;

            $pag.append('<li class="page-item' + (meta.currentPage === 1 ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (meta.currentPage - 1) + '">&laquo;</a></li>');
            var range = 5;
            var start = Math.max(1, meta.currentPage - Math.floor(range / 2));
            var end = Math.min(meta.lastPage, start + range - 1);
            start = Math.max(1, end - range + 1);
            for (var i = start; i <= end; i++) {
                $pag.append('<li class="page-item' + (i === meta.currentPage ? ' active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>');
            }
            $pag.append('<li class="page-item' + (meta.currentPage === meta.lastPage ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (meta.currentPage + 1) + '">&raquo;</a></li>');
        }

        function load(page) {
            currentPage = page || currentPage;
            $.get(dataUrl, { type: $('#f-type').val(), page: currentPage })
                .done(function (res) {
                    render(res.data || []);
                    renderPagination(res.meta);
                })
                .fail(function () { toastr.error('No se pudieron cargar las solicitudes.'); });
        }

        $('#f-type').on('change', function () { load(1); });
        $('#btn-refresh').on('click', function () { load(1); });
        $('#request-pagination').on('click', '.page-link', function (e) {
            e.preventDefault();
            var page = parseInt($(this).data('page'), 10);
            if (page >= 1 && page <= lastPage) { load(page); }
        });

        load();
    });
})();
