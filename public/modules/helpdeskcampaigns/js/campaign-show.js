/**
 * HelpdeskCampaigns — campaign-show.js
 * Detalle de campaña: flash toasts, formulario de rechazo, gráfico de
 * tendencia (Chart.js) y carga diferida de la pestaña de actividad.
 * Espera `window.HcmCampaignShow = { timelineUrl, activityUrl, flashSuccess, flashError }`
 * inyectado desde managers/campaigns/show.blade.php.
 */
$(function () {
    const config = window.HcmCampaignShow || {};

    if (config.flashSuccess) {
        toastr.success(config.flashSuccess, 'Exito');
    }
    if (config.flashError) {
        toastr.error(config.flashError, 'Error');
    }

    $(document).on('submit', '.js-campaign-reject-form', function (e) {
        const reason = window.prompt('Indica el motivo del rechazo:');
        if (reason === null || reason.trim() === '') {
            e.preventDefault();
            return;
        }
        $(this).find('input[name="reason"]').val(reason.trim());
    });

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    $.getJSON(`${config.timelineUrl}?days=30`, function (data) {
        const ctx = document.getElementById('campaign-chart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Impresiones',
                        data: data.impressions,
                        borderColor: '#90bb13',
                        backgroundColor: 'rgba(144, 187, 19, 0.1)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Clics',
                        data: data.clicks,
                        borderColor: '#13C672',
                        backgroundColor: 'rgba(19, 198, 114, 0.1)',
                        tension: 0.3,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    });

    let activityLoaded = false;
    $('button[data-bs-target="#tab-activity"]').on('shown.bs.tab', function () {
        if (activityLoaded) return;
        activityLoaded = true;

        $.getJSON(config.activityUrl, function (res) {
            const list = $('#campaign-activity-list').empty();
            if (!res.data || res.data.length === 0) {
                list.html('<div class="text-center text-muted py-4">Sin actividad registrada.</div>');
                return;
            }
            res.data.forEach(item => {
                list.append(`
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>${item.description}</strong>
                            <small class="text-muted">${item.time_ago}</small>
                        </div>
                        <small class="text-muted">${item.causer}</small>
                    </div>`);
            });
        });
    });
});
