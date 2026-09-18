/**
 * Listado de incumplimientos de SLA (breaches/index.blade.php). Extraido
 * del <script> inline de esa vista.
 *
 * Depende de que #filters lleve data-url (listado paginado) y
 * data-resolve-url-template (con el placeholder "__ID__") — lo unico que
 * este fichero no puede resolver por su cuenta.
 */
(function () {
    'use strict';

    $(function () {
        var $filters = $('#filters');
        var dataUrl = $filters.data('url');
        var resolveUrlTpl = $filters.data('resolve-url-template');
        var csrf = $('meta[name="csrf-token"]').attr('content');
        var currentPage = 1;
        var lastPage = 1;

        function fmt(iso) {
            if (!iso) return '—';
            var d = new Date(iso);
            return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        function overdue(min) {
            if (!min && min !== 0) return '—';
            if (min < 60) return min + 'm';
            if (min < 1440) return Math.floor(min / 60) + 'h';
            return Math.floor(min / 1440) + 'd';
        }

        function render(rows) {
            if (!rows.length) {
                $('#breach-rows').html('<tr><td colspan="8" class="text-center text-muted py-4">Sin incumplimientos para los filtros seleccionados.</td></tr>');
                return;
            }
            var html = rows.map(function (r) {
                var badge = r.resolved
                    ? '<span class="badge bg-success-subtle text-success">Resuelto</span>'
                    : '<span class="badge bg-danger-subtle text-danger">Sin resolver</span>';
                var actions = r.resolved ? '' :
                    '<div class="dropdown">' +
                      '<button class="btn btn-sm btn-link text-body" data-bs-toggle="dropdown" aria-expanded="false">' +
                        '<i class="fas fa-ellipsis-vertical"></i></button>' +
                      '<ul class="dropdown-menu dropdown-menu-end">' +
                        '<li><button class="dropdown-item btn-resolve" data-id="' + r.id + '">Marcar resuelto</button></li>' +
                      '</ul></div>';
                return '<tr>' +
                    '<td>#' + r.conversationId + ' <span class="text-muted">' + $('<div>').text(r.subject).html() + '</span></td>' +
                    '<td>' + $('<div>').text(r.customer).html() + '</td>' +
                    '<td>' + $('<div>').text(r.slaTypeLabel).html() + '</td>' +
                    '<td>' + fmt(r.dueAt) + '</td>' +
                    '<td>' + fmt(r.breachedAt) + '</td>' +
                    '<td>' + overdue(r.minutesOver) + '</td>' +
                    '<td>' + badge + '</td>' +
                    '<td class="text-end">' + actions + '</td>' +
                    '</tr>';
            }).join('');
            $('#breach-rows').html(html);
        }

        // Igual patron que #products-pagination en Supplier/settings/suppliers/detail.blade.php,
        // pero la pagina la calcula el servidor (meta.currentPage/lastPage) en vez del cliente.
        function renderPagination(meta) {
            var $pag = $('#breach-pagination').empty();
            if (!meta || meta.lastPage <= 1) {
                $('#breach-pagination-info').addClass('d-none');
                return;
            }
            $('#breach-pagination-info').removeClass('d-none');
            $('#breach-pagination-summary').text('Pagina ' + meta.currentPage + ' de ' + meta.lastPage + ' — ' + meta.total + ' incumplimiento(s)');
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
            $.get(dataUrl, $filters.serialize() + '&page=' + currentPage)
                .done(function (res) {
                    render(res.data || []);
                    $('#stat-total').text(res.meta ? res.meta.total : '—');
                    $('#stat-unresolved').text(res.meta ? res.meta.unresolved : '—');
                    renderPagination(res.meta);
                })
                .fail(function () {
                    toastr.error('No se pudieron cargar los incumplimientos.');
                });
        }

        $filters.on('submit', function (e) { e.preventDefault(); load(1); });
        $('#btn-refresh').on('click', function () { load(1); });

        $('#breach-pagination').on('click', '.page-link', function (e) {
            e.preventDefault();
            var page = parseInt($(this).data('page'), 10);
            if (page >= 1 && page <= lastPage) { load(page); }
        });

        $(document).on('click', '.btn-resolve', function () {
            var id = $(this).data('id');
            $.ajax({
                url: resolveUrlTpl.replace('__ID__', id),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function (res) {
                toastr.success(res.message || 'Marcado como resuelto.');
                load();
            }).fail(function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo marcar como resuelto.');
            });
        });

        load();
    });
})();
