/**
 * Helpdesk · Misc — pestañas "Conversaciones" / "Emails" del panel de
 * detalle de customers/partials/detail.
 *
 * No requiere config global: lee las URLs desde atributos data-* de los
 * propios contenedores (#conv-list-container / #email-list-container), que
 * el partial solo renderiza cuando esa pestaña esta activa:
 *
 *   <div id="conv-list-container"
 *        data-conversations-url="{{ route('manager.helpdesk.customers.conversations', $selected->id) }}"
 *        data-conversation-link-base="{{ route('manager.helpdesk.conversations.show', '') }}">
 *
 *   <div id="email-list-container"
 *        data-emails-url="{{ route('manager.helpdesk.customers.emails-data', $selected->id) }}">
 */
(function ($) {
    'use strict';

    function escHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function initConversationsTab() {
        const $container = $('#conv-list-container');

        if (!$container.length) {
            return;
        }

        const url = $container.data('conversationsUrl');
        const linkBase = $container.data('conversationLinkBase') || '';

        $.getJSON(url, function (res) {
            if (!res.success || !res.data.length) {
                $container.html(
                    '<div class="bv-page-empty bv-py-32">' +
                    '<div class="icon bv-av-44"><i class="fas fa-comments"></i></div>' +
                    '<div class="title bv-text-13">Sin conversaciones</div>' +
                    '<div class="hint">Este contacto aún no ha iniciado conversaciones.</div>' +
                    '</div>'
                );
                return;
            }
            var html = '';
            $.each(res.data, function (i, c) {
                var statusClass = c.status_open ? 'bv-status-open' : 'bv-status-closed';
                html += '<a href="' + linkBase + '/' + c.id + '" ' +
                    'class="bv-conv text-decoration-none bv-d-flex">' +
                    '<div class="bv-av c3 bv-text-11"><i class="fas fa-comment"></i></div>' +
                    '<div class="body">' +
                    '<div class="row1"><span class="name">' + (c.subject || '#' + c.id) + '</span><span class="time">' + c.time + '</span></div>' +
                    '<div class="row2"><span class="preview">' + (c.preview || '—') + '</span>' +
                    '<span class="bv-status-pill ' + statusClass + '">' + (c.status || '—') + '</span>' +
                    '</div></div></a>';
            });
            $container.html(html);
        }).fail(function () {
            $container.html('<div class="bv-error-box">Error al cargar conversaciones.</div>');
        });
    }

    function initEmailsTab() {
        const $container = $('#email-list-container');

        if (!$container.length) {
            return;
        }

        const url = $container.data('emailsUrl');
        const statusColors = { sent: 'c5', failed: 'c1', queued: 'c4' };

        $.getJSON(url, function (res) {
            if (!res.success || !res.data.length) {
                $container.html(
                    '<div class="bv-page-empty bv-py-32">' +
                    '<div class="icon bv-av-44"><i class="fas fa-envelope-open"></i></div>' +
                    '<div class="title bv-text-13">Sin emails enviados</div>' +
                    '<div class="hint">No se han enviado emails a este contacto.</div>' +
                    '</div>'
                );
                return;
            }
            var html = '';
            $.each(res.data, function (i, m) {
                var avColor = statusColors[m.status] || 'c3';
                var icon = m.status === 'sent' ? 'fa-check' : (m.status === 'failed' ? 'fa-times' : 'fa-clock');
                html += '<a href="' + m.preview_url + '" target="_blank" ' +
                    'class="bv-conv text-decoration-none bv-d-flex">' +
                    '<div class="bv-av ' + avColor + ' bv-text-11"><i class="fas ' + icon + '"></i></div>' +
                    '<div class="body">' +
                    '<div class="row1"><span class="name">' + escHtml(m.subject) + '</span><span class="time">' + m.time + '</span></div>' +
                    '<div class="row2"><span class="preview">' + (m.module || '—') + '</span>' +
                    '<span class="bv-status-pill ' + (m.status === 'sent' ? 'bv-status-open' : 'bv-status-closed') + '">' + m.status_label + '</span>' +
                    '</div></div></a>';
            });
            $container.html(html);
        }).fail(function () {
            $container.html('<div class="bv-error-box">Error al cargar emails.</div>');
        });
    }

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    onReady(function () {
        initConversationsTab();
        initEmailsTab();
    });
})(jQuery);
