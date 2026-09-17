/**
 * Helpdesk · Misc — dashboard en vivo (dashboard/live.blade.php): hace
 * polling cada 10s del endpoint de metricas y actualiza los data-bv-metric
 * y el desglose de conversaciones abiertas por canal.
 *
 * Requiere `window.HdDashboardLiveConfig` definido antes de cargar este
 * script:
 *
 *   window.HdDashboardLiveConfig = {
 *       metricsUrl: '{{ route("manager.helpdesk.dashboard.live.metrics") }}',
 *   };
 */
(function ($) {
    'use strict';

    const cfg = window.HdDashboardLiveConfig;

    if (!cfg) {
        return;
    }

    const channelLabels = {
        whatsapp: { label: 'WhatsApp', icon: 'fab fa-whatsapp', color: '#25D366' },
        facebook: { label: 'Facebook', icon: 'fab fa-facebook-messenger', color: '#0084FF' },
        instagram: { label: 'Instagram', icon: 'fab fa-instagram', color: '#E4405F' },
        email: { label: 'Email', icon: 'far fa-envelope', color: '#6c757d' },
        widget: { label: 'Widget', icon: 'far fa-comment-dots', color: '#90bb13' },
        web: { label: 'Web', icon: 'far fa-comment-dots', color: '#90bb13' },
    };

    function renderChannels(byChannel) {
        const $container = $('#bv-channel-list').empty();
        const entries = Object.entries(byChannel || {});
        if (!entries.length) {
            $container.append('<span class="text-muted">Sin conversaciones abiertas</span>');
            return;
        }
        entries.forEach(([channel, count]) => {
            const meta = channelLabels[channel] || { label: channel, icon: 'fas fa-circle', color: '#6c757d' };
            $container.append(
                $('<span class="bv-channel-pill"></span>')
                    .append('<i class="' + meta.icon + ' bv-icon-dyn" style="--bv-icon-color: ' + meta.color + '"></i>')
                    .append(' ' + meta.label + ' ')
                    .append('<span class="count">' + count + '</span>')
            );
        });
    }

    function loadMetrics() {
        $.get(cfg.metricsUrl).done(function (data) {
            $('[data-bv-metric]').each(function () {
                const key = $(this).data('bv-metric');
                if (data[key] !== undefined) {
                    $(this).text(data[key]);
                }
            });
            renderChannels(data.open_by_channel);
            $('#bv-live-updated').text('Actualizado: ' + new Date().toLocaleTimeString());
        }).fail(function () {
            $('#bv-live-updated').text('Error al cargar');
        });
    }

    function init() {
        loadMetrics();
        setInterval(loadMetrics, 10000);
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})(jQuery);
