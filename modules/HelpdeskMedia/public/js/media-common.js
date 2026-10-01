/**
 * HelpdeskMedia · helpers compartidos (bandeja y tickets).
 * Genera el markup de badges y del bloque de transcripción a partir del
 * meta de un adjunto. Todo el texto de usuario pasa por escapeHtml().
 */
(function ($) {
    'use strict';

    if (window.HdMedia) return;

    var cfg = window.HdMediaConfig || { i18n: {} };

    function t(key, params) {
        var text = (cfg.i18n && cfg.i18n[key]) || key;
        $.each(params || {}, function (name, value) {
            text = text.split(':' + name).join(value);
        });
        return text;
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function badge(modifier, icon, label, title) {
        return '<span class="hdm-badge hdm-badge--' + modifier + '"' + (title ? ' title="' + escapeHtml(title) + '"' : '') + '>' +
            '<i class="fas ' + icon + '" aria-hidden="true"></i>' + escapeHtml(label) + '</span>';
    }

    function scanBadge(meta) {
        if (meta.scan === 'clean') return badge('clean', 'fa-shield-halved', t('scan_clean'), t('scan_clean_title'));
        if (meta.scan === 'infected') {
            var title = meta.scan_signature ? t('scan_infected_title', { signature: meta.scan_signature }) : t('scan_infected_untitled');
            return badge('infected', 'fa-triangle-exclamation', t('scan_infected'), title);
        }
        if (meta.scan === 'unavailable') return badge('unavailable', 'fa-circle-question', t('scan_unavailable'), t('scan_unavailable_title'));
        return '';
    }

    function conversionBadges(meta) {
        var html = '';
        if (meta.converted_from) {
            html += badge('info', 'fa-rotate', t('converted_from', { format: String(meta.converted_from).toUpperCase() }));
        }
        var original = Number(meta.original_bytes), final = Number(meta.final_bytes);
        if (original > 0 && final > 0 && final < original) {
            var percent = Math.round((1 - final / original) * 100);
            if (percent > 0) {
                html += badge('info', 'fa-down-long', '−' + percent + ' %',
                    t('saving', { percent: percent, original: formatBytes(original), final: formatBytes(final) }));
            }
        }
        return html;
    }

    /** Badges de antivirus y conversión; cadena vacía si no hay nada que mostrar. */
    function badgesHtml(meta) {
        var inner = scanBadge(meta || {}) + conversionBadges(meta || {});
        return inner ? '<div class="hdm-badges">' + inner + '</div>' : '';
    }

    /** Bloque "Transcripción" colapsable; pending=true → "Transcribiendo…". */
    function transcriptHtml(meta, pending) {
        var text = meta && meta.transcript;
        if (!text) {
            return pending ? '<div class="hdm-transcript hdm-transcript--pending"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> ' + escapeHtml(t('transcript_pending')) + '</div>' : '';
        }
        var lang = meta.transcript_lang ? '<span>' + escapeHtml(t('transcript_language', { lang: String(meta.transcript_lang).toUpperCase() })) + '</span>' : '<span></span>';
        return '<details class="hdm-transcript">' +
            '<summary><i class="fas fa-closed-captioning" aria-hidden="true"></i> ' + escapeHtml(t('transcript')) + '</summary>' +
            '<p class="hdm-transcript-body">' + escapeHtml(text) + '</p>' +
            '<div class="hdm-transcript-foot">' + lang +
                '<button type="button" class="hdm-copy" data-hdm-copy><i class="far fa-copy" aria-hidden="true"></i> ' + escapeHtml(t('copy')) + '</button>' +
            '</div></details>';
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
        var $area = $('<textarea readonly class="hdm-clipboard">').val(text).appendTo('body');
        $area[0].select();
        var ok = document.execCommand('copy');
        $area.remove();
        return ok ? Promise.resolve() : Promise.reject();
    }

    $(document).on('click', '[data-hdm-copy]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var text = $btn.closest('.hdm-transcript').find('.hdm-transcript-body').text();
        var original = $btn.html();
        var done = function (label) {
            $btn.text(label);
            setTimeout(function () { $btn.html(original); }, 1500);
        };
        copyText(text).then(function () { done(t('copied')); }, function () { done(t('copy_failed')); });
    });

    /** Normaliza una URL a su ruta para casar href relativos y absolutos. */
    function pathOf(url) {
        try {
            return decodeURIComponent(new URL(url, window.location.origin).pathname);
        } catch (err) {
            return String(url || '');
        }
    }

    /** Sustituye (o crea) el bloque .hdm-extra que sigue a $anchor. */
    function renderAfter($anchor, html) {
        var $next = $anchor.next('.hdm-extra');
        if (!html) {
            $next.remove();
            return;
        }
        var $extra = $('<div class="hdm-extra">').html(html);
        if ($next.length) $next.replaceWith($extra);
        else $anchor.after($extra);
    }

    window.HdMedia = {
        cfg: cfg,
        t: t,
        pathOf: pathOf,
        badgesHtml: badgesHtml,
        transcriptHtml: transcriptHtml,
        renderAfter: renderAfter
    };
})(window.jQuery);
