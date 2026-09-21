/**
 * Modal "Solicitar documento" insertado en el composer del thread del inbox
 * (helpdeskdocument::partials.thread-extension, incluido desde
 * helpdesk::helpdesk.inbox.partials.thread — ver comentario ahí sobre por
 * qué el markup/script del modal se pushean SIEMPRE, y el botón solo con
 * conversación seleccionada).
 *
 * Busca expedientes (mismos campos que "asignar expediente existente": nº
 * pedido, uid, DNI, email, nombre), autosugiere al abrir los que ya tiene el
 * cliente de la conversación (búsqueda con q vacío), y al elegir uno arma el
 * mensaje de solicitud (instrucciones del tipo de documento + URL de subida)
 * en el idioma elegido, para insertarlo editable en el composer.
 */
(function () {
    var hdDocReqAll = [];
    var hdDocReqSelectedId = null;
    var hdDocReqTimer = null;
    var hdDocReqSearchUrl = '';
    var hdDocReqMessageUrlTpl = '';
    var hdDocReqSyncingLocale = false;
    var hdCsrfDoc = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

    document.getElementById('hdDocReqOverlay').addEventListener('click', function (e) {
        if (e.target === this) { closeDocumentRequestModal(); }
    });

    // select2 sobre el <select> de idioma (mismo tema por defecto que el resto
    // de la bandeja, sin theme bootstrap-5 — ver .hd-modal .select2-container
    // en conversations.css). Se inicializa una sola vez; abrir/cerrar el modal
    // no reinicializa nada.
    $('#hdDocReqLocale').select2({
        width: '100%',
        minimumResultsForSearch: Infinity,
        dropdownParent: $('#hdDocReqOverlay .hd-modal'),
    });

    function setLocaleSelectValue(locale) {
        hdDocReqSyncingLocale = true;
        $('#hdDocReqLocale').val(locale).trigger('change.select2').trigger('change');
        hdDocReqSyncingLocale = false;
    }

    window.openDocumentRequestModal = function (btn) {
        hdDocReqSearchUrl = (btn && btn.dataset.docReqSearchUrl) || '';
        hdDocReqMessageUrlTpl = (btn && btn.dataset.docReqMessageUrlTemplate) || '';
        hdDocReqSelectedId = null;

        document.getElementById('hdDocReqOverlay').classList.add('open');
        document.getElementById('hdDocReqSearch').value = '';
        document.getElementById('hdDocReqLocaleField').classList.add('bv-hidden');
        document.getElementById('hdDocReqPreviewField').classList.add('bv-hidden');
        document.getElementById('hdDocReqPreview').value = '';
        document.getElementById('hdDocReqSearch').focus();

        hdDocReqFetch('');
    };

    window.closeDocumentRequestModal = function () {
        document.getElementById('hdDocReqOverlay').classList.remove('open');
    };

    function esc(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function hdDocReqFetch(q) {
        if (!hdDocReqSearchUrl) { return; }
        var url = hdDocReqSearchUrl + (q ? '?q=' + encodeURIComponent(q) : '');
        document.getElementById('hdDocReqList').innerHTML = '<div class="bv-list-state"><i class="fas fa-spinner fa-spin"></i> Buscando…</div>';
        fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': hdCsrfDoc } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                hdDocReqAll = (data && data.results) || [];
                hdDocReqRender(hdDocReqAll, q);
            })
            .catch(function () {
                document.getElementById('hdDocReqList').innerHTML = '<div class="bv-list-state">Error al buscar</div>';
            });
    }

    function hdDocReqRender(list, q) {
        var el = document.getElementById('hdDocReqList');
        if (!list.length) {
            el.innerHTML = '<div class="bv-list-state">' + (q ? 'Sin resultados' : 'Este cliente aún no tiene expedientes') + '</div>';
            return;
        }
        el.innerHTML = list.map(function (d, i) {
            var sub = [d.type_label, d.customer_name, d.customer_email].filter(Boolean).join(' · ');
            return '<button type="button" class="list-item" data-idx="' + i + '" onclick="hdDocReqSelect(' + i + ')">'
                + '<div class="body">'
                + '<span class="t">#' + esc(d.order_reference) + '</span>'
                + (sub ? '<span class="s">' + esc(sub) + '</span>' : '')
                + '</div></button>';
        }).join('');
    }

    window.hdDocReqSelect = function (idx) {
        var d = hdDocReqAll[idx];
        if (!d) { return; }
        hdDocReqSelectedId = d.id;
        document.querySelectorAll('#hdDocReqList .list-item').forEach(function (el, i) {
            el.classList.toggle('on', i === idx);
        });
        document.getElementById('hdDocReqLocaleField').classList.remove('bv-hidden');
        document.getElementById('hdDocReqPreviewField').classList.remove('bv-hidden');
        hdDocReqLoadMessage();
    };

    function hdDocReqLoadMessage(locale) {
        if (!hdDocReqSelectedId || !hdDocReqMessageUrlTpl) { return; }
        var url = hdDocReqMessageUrlTpl.replace('_DOCID_', hdDocReqSelectedId);
        if (locale) { url += '?locale=' + encodeURIComponent(locale); }
        var $preview = document.getElementById('hdDocReqPreview');
        $preview.value = '';
        $preview.placeholder = 'Cargando mensaje…';
        fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': hdCsrfDoc } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success) {
                    $preview.placeholder = (data && data.message) || 'No se pudo generar el mensaje.';
                    return;
                }
                $preview.value = data.message;
                if (data.locale) { setLocaleSelectValue(data.locale); }
            })
            .catch(function () {
                $preview.placeholder = 'Error al generar el mensaje.';
            });
    }

    window.hdInsertDocumentRequest = function () {
        var text = document.getElementById('hdDocReqPreview').value.trim();
        if (!text) { return; }
        var ta = document.querySelector('.bv-composer-input');
        if (ta) {
            var cur = ta.value;
            ta.value = cur ? cur + '\n' + text : text;
            ta.focus();
            ta.dispatchEvent(new Event('input'));
        }
        closeDocumentRequestModal();
    };

    document.getElementById('hdDocReqSearch').addEventListener('input', function () {
        clearTimeout(hdDocReqTimer);
        var q = this.value.trim();
        hdDocReqTimer = setTimeout(function () { hdDocReqFetch(q); }, 250);
    });

    document.getElementById('hdDocReqLocale').addEventListener('change', function () {
        if (hdDocReqSyncingLocale) { return; }
        hdDocReqLoadMessage(this.value);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('hdDocReqOverlay').classList.contains('open')) {
            closeDocumentRequestModal();
            return;
        }

        // Atajo "D" — abre "Solicitar documento" (ver modal de Atajos de
        // teclado, columna Composer). Mismo guard que el resto de atajos de
        // una letra del inbox (conversations-thread.js): nada de modificadores
        // y no interferir mientras se escribe.
        if (e.metaKey || e.ctrlKey || e.altKey) { return; }
        if (e.key.toLowerCase() !== 'd') { return; }
        var active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable)) { return; }
        var btn = document.querySelector('[onclick="openDocumentRequestModal(this)"]');
        if (!btn) { return; }
        e.preventDefault();
        openDocumentRequestModal(btn);
    });
})();
