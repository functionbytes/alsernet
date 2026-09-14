'use strict';

    // ── Modal 03: Plantillas de email ─────────────────────────
    /**
     * Se abre desde dos sitios: el botón "Plantilla" del modal "Redactar
     * email" (compose) y el atajo data-comp-act="templates" de la barra del
     * composer del HILO. Igual que le pasaba a "Traducir respuesta", si se
     * asume siempre compose, insertar desde el hilo revienta contra un
     * composeDraft nulo y el clic en "Insertar plantilla" no hace nada.
     */
    function openTemplatesModal(t) {
        // TKA.state.cannedReplies viaja en bruto (con los {{...}} sin
        // resolver) una sola vez para toda la sesión SPA — el JSON del
        // layout no sabe todavía con qué ticket se va a responder. Se usa
        // como arranque inmediato (mismos títulos/atajos) y en cuanto
        // responde url_canned_replies se sustituye por la versión
        // interpolada contra ESTE ticket (bug reportado 11-sep-2026:
        // {{cliente.nombre}} llegaba tal cual al mensaje).
        var all = (TKA.state.cannedReplies || []).map(function (r) { return $.extend({}, r); });
        var selected = null;
        var enCompose = !!(composeDraft && $('#tkt-compose-to').length);
        var $hilo = $('#tkt-reply-body');

        function listHtml(filter) {
            var q = String(filter || '').trim().toLowerCase();
            var list = all.filter(function (r) {
                return !q || String(r.title).toLowerCase().indexOf(q) !== -1 ||
                    String(r.short_code || '').toLowerCase().indexOf(q) !== -1;
            });
            if (!list.length) return '<div class="tkt-empty-box">Ninguna plantilla coincide con la búsqueda.</div>';
            return list.map(function (r) {
                return '<button type="button" class="tkt-pick' + (selected && selected.id === r.id ? ' on' : '') + '" data-tpl="' + r.id + '">' +
                    // short_code se guarda unas veces con "/" y otras sin él,
                    // así que se normaliza en vez de prefijar a ciegas (si no,
                    // salían atajos como "//bienvenida").
                    '<span class="tkt-shortcode mono">' + (r.short_code ? escapeHtml('/' + String(r.short_code).replace(/^\/+/, '')) : '—') + '</span>' +
                    '<span class="who"><span class="n">' + escapeHtml(r.title) + '</span></span>' +
                    (selected && selected.id === r.id ? '<i class="fa-solid fa-check"></i>' : '') +
                '</button>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-file-lines',
            kicker: 'Respuestas · plantillas',
            title: 'Plantillas de email',
            width: 'lg',
            body: '<div class="tkt-field"><input type="search" class="tkt-input" id="tkt-tpl-search" placeholder="Buscar plantilla…" aria-label="Buscar plantilla"></div>' +
                '<div class="tkt-pick-list" id="tkt-tpl-list">' + listHtml('') + '</div>' +
                '<div class="tkt-tpl-preview" id="tkt-tpl-preview">Elige una plantilla para ver su contenido.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-tpl-insert" disabled>Insertar plantilla</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tpl-duplicate" disabled>Duplicar como mía</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tpl-edit">Editar</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tpl-back">Volver</button>',
        }));

        // Se pide aparte (no viaja con el resto del listado) porque hay que
        // interpolar contra un ticket concreto; si la petición tarda o
        // falla, la lista de arriba (sin resolver) se queda tal cual —
        // nunca deja el modal vacío ni bloquea "Insertar".
        if (t && t.url_canned_replies) {
            $.getJSON(t.url_canned_replies).done(function (resolved) {
                all = (resolved || []).map(function (r) {
                    // El editor de plantillas necesita el contenido CRUDO: si
                    // se le pasara el ya interpolado, "Guardar plantilla"
                    // sustituiría los {{...}} por los datos de este ticket
                    // para siempre.
                    var raw = (TKA.state.cannedReplies || []).find(function (o) { return String(o.id) === String(r.id); });

                    return $.extend({}, r, { raw: raw || r });
                });
                if (selected) selected = all.find(function (r) { return String(r.id) === String(selected.id); }) || selected;
                $backdrop.find('#tkt-tpl-list').html(listHtml($('#tkt-tpl-search').val()));
                if (selected) $backdrop.find('#tkt-tpl-preview').text(selected.content);
            });
        }

        $backdrop.on('input', '#tkt-tpl-search', function () {
            $backdrop.find('#tkt-tpl-list').html(listHtml(this.value));
        });
        $backdrop.on('click', '[data-tpl]', function () {
            var id = $(this).data('tpl');
            selected = all.find(function (r) { return String(r.id) === String(id); }) || null;
            $backdrop.find('#tkt-tpl-list').html(listHtml($('#tkt-tpl-search').val()));
            $backdrop.find('#tkt-tpl-preview').text(selected ? selected.content : '');
            $backdrop.find('#tkt-tpl-insert, #tkt-tpl-duplicate').prop('disabled', !selected);
        });
        $backdrop.on('click', '#tkt-tpl-insert', function () {
            if (!selected) return;
            // Se AÑADE al final en vez de reemplazar: si el agente ya había
            // escrito algo, sustituirlo en silencio le borraría el trabajo.
            if (enCompose) {
                composeDraft.body = composeDraft.body
                    ? composeDraft.body.replace(/\s*$/, '') + '\n\n' + selected.content
                    : selected.content;
                closeModal();
                openComposeModal(t);
                return;
            }

            // Viene del composer del hilo: se inserta ahí directamente.
            var actual = $hilo.val();
            $hilo.val(actual ? actual.replace(/\s*$/, '') + '\n\n' + selected.content : selected.content)
                .trigger('input').trigger('focus');
            if (typeof autoResizeTextarea === 'function') autoResizeTextarea($hilo[0]);
            closeModal();
        });
        $backdrop.on('click', '#tkt-tpl-duplicate', function () {
            if (!selected || !TKA.urls.cannedDuplicateTemplate) return;
            var $btn = $(this).prop('disabled', true).text('Duplicando…');
            $.ajax({
                url: TKA.urls.cannedDuplicateTemplate.replace('__REPLY__', selected.id),
                method: 'POST',
                headers: { Accept: 'application/json' },
            }).done(function (res) {
                var copy = res && res.reply;
                if (copy) {
                    // A la lista en bruto (persiste para el resto de la
                    // sesión SPA, igual que el resto de TKA.state.cannedReplies)
                    // y a la vista actual del modal, para que aparezca de
                    // inmediato sin tener que reabrirlo.
                    TKA.state.cannedReplies = (TKA.state.cannedReplies || []).concat([copy]);
                    all = all.concat([$.extend({}, copy, { raw: copy })]);
                    selected = all[all.length - 1];
                    $backdrop.find('#tkt-tpl-list').html(listHtml($('#tkt-tpl-search').val()));
                    $backdrop.find('#tkt-tpl-preview').text(selected.content);
                }
                if (window.toastr) toastr.success((res && res.message) || 'Plantilla duplicada.');
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo duplicar la plantilla.';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            }).always(function () {
                $btn.prop('disabled', false).text('Duplicar como mía');
            });
        });
        $backdrop.on('click', '#tkt-tpl-back', function () {
            closeModal();
            if (enCompose) openComposeModal(t);
        });
        $backdrop.on('click', '#tkt-tpl-edit', function () {
            if (!selected) return;
            closeModal();
            // .raw (sin interpolar) si ya llegó la versión resuelta de este
            // ticket — nunca la interpolada, o "Guardar" la dejaría fija.
            openTemplateEditorModal(selected.raw || selected, function () { openTemplatesModal(t); });
        });
    }


