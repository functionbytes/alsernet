/**
 * translate-thread.js — HelpdeskTranslate module
 *
 * Adds a visible "Traducir" button on incoming message bubbles so agents can
 * translate individual messages on demand without opening the translation panel.
 *
 * Depends on: jQuery (global), toastr (optional global)
 *
 * NOTE: The .bv-bubble-translation-toggle click handler and the context-menu
 * translate action are already managed by conversations.js (scoped inside its
 * own IIFE). This file does NOT duplicate those handlers.
 *
 * The detected source language is taken from the translate-item response
 * (`detected`), which the controller resolves server-side from the message
 * text — so there is no fragile client-side text extraction.
 *
 * User-facing strings come from window.HelpdeskTranslateI18n, emitted by the
 * translate-panel partial (Blade `__('helpdesktranslate::messages.thread.*')`).
 * Hardcoded Spanish fallbacks keep the file working if that script is absent.
 */
(function ($) {
    'use strict';

    var I18N = window.HelpdeskTranslateI18n || {};

    /** Translate a key with `:param` replacement, falling back to a literal. */
    function t(key, fallback, replacements) {
        var str = (I18N && I18N[key]) ? I18N[key] : fallback;
        if (replacements) {
            Object.keys(replacements).forEach(function (k) {
                str = str.replace(':' + k, replacements[k]);
            });
        }
        return str;
    }

    /** Markup for the button label (icon + text), used to restore it after errors. */
    function buttonLabel() {
        return '<i class="fas fa-language"></i> ' + t('btnTranslate', 'Traducir');
    }

    /**
     * Return the target language from the translate panel selector when visible,
     * falling back to sessionStorage settings and finally 'es'.
     */
    function resolveTargetLang() {
        var $sel = $('#bv-tp-to');
        if ($sel.length && $sel.val()) {
            return $sel.val();
        }
        try {
            var stored = JSON.parse(sessionStorage.getItem('inbox_translation_settings') || '{}');
            if (stored.to) { return stored.to; }
        } catch (e) { /* ignore */ }
        return 'es';
    }

    /**
     * Inject the translated block into a bubble after a successful AJAX response,
     * replicating the same markup structure that thread.blade.php and conversations.js use.
     */
    function injectTranslation($bubble, originalText, translatedText, detectedLang) {
        var $tr = $('<div class="bv-bubble-translation"></div>');
        $tr.attr('data-bv-original', originalText);
        $tr.attr('data-bv-translated', translatedText);
        if (detectedLang) {
            var lang = String(detectedLang).toUpperCase();
            $tr.attr('data-bv-detected', detectedLang);
            $tr.attr('title', t('from', 'Traducido desde :lang', { lang: lang }));
        }
        $tr.append('<span class="bv-bubble-translation-lbl">&#8627; </span>');
        $tr.append(document.createTextNode(translatedText));
        if (detectedLang) {
            $tr.append(
                ' <span class="bv-bubble-translation-src text-muted small">(' +
                String(detectedLang).toUpperCase() + ')</span>'
            );
        }
        $tr.append(
            ' <button type="button" class="bv-bubble-translation-toggle" title="Ver original">' +
            '<i class="fas fa-arrows-rotate"></i></button>'
        );
        $bubble.append($tr);
    }

    // ── Translate button click ────────────────────────────────────────────
    $(document).on('click', '.bv-translate-item-btn', function () {
        var $btn    = $(this);
        var itemId  = $btn.data('item-id');
        var $bubble = $btn.closest('.bv-bubble');

        if (!itemId || !$bubble.length) { return; }

        // Guard: already translated while button was visible
        if ($bubble.find('.bv-bubble-translation').length) {
            $btn.remove();
            return;
        }

        var originalText = ($bubble.data('bv-body') || '').toString();
        var target       = resolveTargetLang();

        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '/panel/helpdesk/conversations/items/' + itemId + '/translate',
            method: 'POST',
            dataType: 'json',
            data: { target: target },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        })
        .done(function (resp) {
            if (!resp.success || !resp.translated) {
                if (window.toastr) { toastr.error(resp.message || t('error', 'No se pudo traducir.')); }
                $btn.prop('disabled', false).html(buttonLabel());
                return;
            }

            injectTranslation($bubble, originalText, resp.translated, resp.detected);
            $btn.remove();

            if (window.toastr) { toastr.success(t('translated', 'Mensaje traducido')); }
        })
        .fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : t('error', 'No se pudo traducir.');
            if (window.toastr) { toastr.error(msg); }
            $btn.prop('disabled', false).html(buttonLabel());
        });
    });

}(jQuery));
