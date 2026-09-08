'use strict';

    // ── Modal 03: Plantillas de email ─────────────────────────
    function openTemplatesModal(t) {
        var all = TKA.state.cannedReplies || [];
        var selected = null;

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
                  '<button type="button" class="tkt-btn" id="tkt-tpl-edit">Editar</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tpl-back">Volver</button>',
        }));

        $backdrop.on('input', '#tkt-tpl-search', function () {
            $backdrop.find('#tkt-tpl-list').html(listHtml(this.value));
        });
        $backdrop.on('click', '[data-tpl]', function () {
            var id = $(this).data('tpl');
            selected = all.find(function (r) { return String(r.id) === String(id); }) || null;
            $backdrop.find('#tkt-tpl-list').html(listHtml($('#tkt-tpl-search').val()));
            $backdrop.find('#tkt-tpl-preview').text(selected ? selected.content : '');
            $backdrop.find('#tkt-tpl-insert').prop('disabled', !selected);
        });
        $backdrop.on('click', '#tkt-tpl-insert', function () {
            if (!selected) return;
            // Se AÑADE al final en vez de reemplazar: si el agente ya había
            // escrito algo, sustituirlo en silencio le borraría el trabajo.
            composeDraft.body = composeDraft.body
                ? composeDraft.body.replace(/\s*$/, '') + '\n\n' + selected.content
                : selected.content;
            closeModal();
            openComposeModal(t);
        });
        $backdrop.on('click', '#tkt-tpl-back', function () { closeModal(); openComposeModal(t); });
        $backdrop.on('click', '#tkt-tpl-edit', function () {
            if (!selected) return;
            closeModal();
            openTemplateEditorModal(selected, function () { openTemplatesModal(t); });
        });
    }


