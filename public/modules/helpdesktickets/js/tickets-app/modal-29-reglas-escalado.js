'use strict';

    // ── Modal 29: Reglas de escalado ──────────────────────────
    // Estado del editor mientras el modal está abierto. Guarda solo la ESTRUCTURA
    // de las filas (qué campo y qué operador tiene cada una); los valores se leen
    // del DOM al guardar, así repintar una fila no borra lo tecleado en las otras.
    var TKT_ESC = {
        canManage: false,
        rules: [],
        catalog: null,
        tab: 'list',
        draft: null,
        conds: [],
        acts: [],
        preview: null,
        errors: null,
    };

    // Las URLs pueden venir ya en TKA.urls (initTicketsApp) o directamente de los
    // data-* de #tkt-data. Se aceptan las dos para que el modal funcione aunque
    // el initTicketsApp todavía no las exponga.
    function escUrl(key, attr) {
        return TKA.urls[key] || $('#tkt-data').attr(attr) || null;
    }

    function openEscalationModal() {
        var listUrl = escUrl('automationsList', 'data-automations-list-url');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-arrow-up-right-dots', kicker: 'Automatización · escalado',
            title: 'Reglas de escalado', width: '2xl',
            body: '<div id="tkt-esc-body"><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // Sin endpoint (rutas aún no registradas) se conserva el comportamiento
        // anterior: listar lo que trae el settings-snapshot y salir a Ajustes.
        if (!listUrl) { escLegacyList($backdrop); return; }

        TKT_ESC.tab = 'list';
        TKT_ESC.preview = null;
        TKT_ESC.errors = null;
        escResetDraft();

        $.getJSON(listUrl).done(function (d) {
            TKT_ESC.canManage = !!(d && d.can_manage);
            TKT_ESC.rules = (d && d.rules) || [];
            TKT_ESC.catalog = (d && d.catalog) || null;
            escRender($backdrop);
        }).fail(function () {
            $backdrop.find('#tkt-esc-body').html('<div class="tkt-empty-box">No se pudieron cargar las reglas de escalado.</div>');
        });

        escBind($backdrop);
    }

    // ── Fallback: el modal de solo lectura de antes ───────────────
    function escLegacyList($backdrop) {
        $backdrop.find('.tkt-modal-foot').html(
            '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(TKA.urls.automationsIndex || '#') + '">Crear o editar reglas</a>' +
            '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>');
        withSettings(function (d) {
            var list = (d && d.automations) || [];
            if (!list.length) { $backdrop.find('#tkt-esc-body').html('<div class="tkt-empty-box">No hay reglas de automatización definidas.</div>'); return; }
            $backdrop.find('#tkt-esc-body').html('<div class="tkt-mailitems">' + list.map(function (a) {
                return '<div class="tkt-mailitem"><span class="av light"><i class="fa-solid fa-gears"></i></span>' +
                    '<span class="who"><span class="n">' + escapeHtml(a.name) + '</span>' +
                    '<span class="s">' + escapeHtml([a.trigger_event, a.run_count ? a.run_count + ' ejecuciones' : null, a.last_run_at_human].filter(Boolean).join(' · ')) + '</span></span>' +
                    '<span class="tkt-rchip' + (a.is_active ? ' ok' : '') + '">' + (a.is_active ? 'activa' : 'pausada') + '</span></div>';
            }).join('') + '</div>');
        });
    }

    // ── Catálogos ─────────────────────────────────────────────────
    function escCatalogList(kind) {
        if (kind === 'priorities') return (TKT_ESC.catalog && TKT_ESC.catalog.priorities) || [];
        if (kind === 'statuses') return TKA.state.statuses || [];
        if (kind === 'groups') return TKA.state.groups || [];
        if (kind === 'agents') return TKA.state.agentsFull || [];
        if (kind === 'categories') return TKA.state.categories || [];
        return [];
    }

    function escFieldSpec(field) {
        var fields = (TKT_ESC.catalog && TKT_ESC.catalog.fields) || [];
        for (var i = 0; i < fields.length; i++) if (fields[i].field === field) return fields[i];
        return null;
    }

    function escActionSpec(type) {
        var actions = (TKT_ESC.catalog && TKT_ESC.catalog.actions) || [];
        for (var i = 0; i < actions.length; i++) if (actions[i].type === type) return actions[i];
        return null;
    }

    function escOpLabel(op) {
        return (TKT_ESC.catalog && TKT_ESC.catalog.operators && TKT_ESC.catalog.operators[op]) || op;
    }

    function escTriggerLabel(event) {
        var triggers = (TKT_ESC.catalog && TKT_ESC.catalog.triggers) || [];
        for (var i = 0; i < triggers.length; i++) if (triggers[i].value === event) return triggers[i].label;
        return null;
    }

    // Nombre legible de un valor: la prioridad/estado/equipo/agente por su id,
    // con los catálogos que la pantalla ya tiene cargados (sin ir al servidor).
    function escValueLabel(kind, value) {
        if (value === null || typeof value === 'undefined') return '';
        if (Array.isArray(value)) return value.map(function (v) { return escValueLabel(kind, v); }).join(', ');
        var list = escCatalogList(kind);
        for (var i = 0; i < list.length; i++) if (String(list[i].id) === String(value)) return list[i].name;
        return String(value);
    }

    // ── Resumen legible de una regla ──────────────────────────────
    function escConditionText(cond) {
        // Las reglas escritas a mano en Ajustes traen a veces 'operator' en vez
        // de 'op' (el motor solo lee 'op': esas condiciones no se evalúan nunca).
        var op = cond.op || cond.operator;
        var spec = escFieldSpec(cond.field);
        var label = spec ? spec.label : cond.field;
        if (op === 'is_null') return label + ' está vacío';
        if (op === 'is_not_null') return label + ' tiene valor';
        var valor = spec && spec.input === 'bool'
            ? (cond.value ? 'sí' : 'no')
            : (spec && spec.options ? escValueLabel(spec.options, cond.value) : String(cond.value));
        return label + ' ' + escOpLabel(op) + ' ' + valor;
    }

    function escActionText(action) {
        var spec = escActionSpec(action.type);
        if (!spec) return action.type;
        if (spec.input === 'none') return spec.label;
        return spec.label + ' ' + (spec.options ? escValueLabel(spec.options, action.value) : String(action.value));
    }

    function escRuleSummary(rule) {
        var conds = (rule.conditions || []).map(escConditionText);
        var acts = (rule.actions || []).map(escActionText);
        var si = escTriggerLabel(rule.trigger_event) || rule.trigger_event;
        return (conds.length ? si + ' y ' + conds.join(' y ') : si) + ' → ' + (acts.join(', ') || 'nada');
    }

    // ── Render ────────────────────────────────────────────────────
    function escRender($backdrop) {
        $backdrop.find('.tkt-modal-title').text(TKT_ESC.tab === 'form' ? 'Nueva regla' : 'Reglas de escalado');
        $backdrop.find('#tkt-esc-body').html(TKT_ESC.tab === 'form' ? escFormHtml() : escListHtml());
        $backdrop.find('.tkt-modal-foot').html(TKT_ESC.tab === 'form' ? escFormFootHtml() : escListFootHtml());
        initSelect2($backdrop.find('#tkt-esc-body'));
    }

    function escListHtml() {
        if (!TKT_ESC.rules.length) {
            return '<div class="tkt-empty-box">No hay reglas de automatización definidas.</div>' + escEvalNoteHtml();
        }

        var html = '<div class="tkt-mailitems">' + TKT_ESC.rules.map(function (r) {
            var huerfana = !escTriggerLabel(r.trigger_event);
            var meta = [
                escTriggerLabel(r.trigger_event) || r.trigger_event,
                r.run_count ? r.run_count + ' ejecuciones' : null,
                r.last_run_at_human,
            ].filter(Boolean).join(' · ');
            var chipCls = 'tkt-rchip' + (r.is_active ? ' ok' : '');
            var estado = r.is_active ? 'activa' : 'pausada';
            // El estado es el botón de activar/pausar para quien puede gestionar;
            // para el resto es una etiqueta y nada más.
            var chip = TKT_ESC.canManage
                ? '<button type="button" class="' + chipCls + ' tkt-esc-toggle" data-esc-toggle="' + r.id + '" ' +
                  'title="' + (r.is_active ? 'Pausar la regla' : 'Activar la regla') + '">' + estado + '</button>'
                : '<span class="' + chipCls + '">' + estado + '</span>';

            return '<div class="tkt-mailitem tkt-esc-item"><span class="av light"><i class="fa-solid fa-gears"></i></span>' +
                '<span class="who"><span class="n">' + escapeHtml(r.name) + '</span>' +
                '<span class="s">' + escapeHtml(meta) + '</span>' +
                '<span class="s tkt-esc-rule-sum">' + escapeHtml(escRuleSummary(r)) + '</span></span>' +
                (huerfana ? '<span class="tkt-rchip" title="Su disparador no existe en el motor: nunca se ejecuta">sin disparador</span>' : '') +
                chip + '</div>';
        }).join('') + '</div>';

        if (TKT_ESC.canManage) {
            html += '<div class="tkt-cap">Toca el estado de una regla para activarla o pausarla.</div>';
        }

        // Solo se avisa de reglas huérfanas si de verdad las hay.
        var huerfanas = TKT_ESC.rules.filter(function (r) { return !escTriggerLabel(r.trigger_event); }).length;
        if (huerfanas) {
            html += '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i> ' +
                'Hay ' + huerfanas + ' regla(s) con un disparador que el motor de tickets no conoce: están guardadas pero no se ejecutan nunca.</div>';
        }

        return html + escEvalNoteHtml();
    }

    function escEvalNoteHtml() {
        // El pie del mockup decía "Evaluado por helpdesk:mark-overdue cada 15
        // minutos": ese comando no existe (el real es ticket:autooverdue) y
        // además no evalúa reglas, solo marca incumplimientos de SLA. Lo que se
        // cuenta aquí es lo que de verdad pasa.
        return '<div class="tkt-note"><i class="fa-solid fa-gauge-high"></i><div>' +
            'Las reglas se evalúan en cuanto ocurre el evento elegido (en la cola <span class="tkt-esc-mono">default</span>), no por reloj. ' +
            'El aviso <span class="tkt-esc-mono">SlaBreachMail</span> a los managers ya sale solo al incumplirse el SLA: no hace falta ninguna regla.' +
            '</div></div>';
    }

    function escListFootHtml() {
        return (TKT_ESC.canManage ? '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-esc-new">Crear regla</button>' : '') +
            '<a class="tkt-btn" href="' + escapeHtml(TKA.urls.automationsIndex || '#') + '">Ver todas en Ajustes</a>' +
            '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>';
    }

    // ── Formulario "Si… Entonces…" ────────────────────────────────
    function escResetDraft() {
        TKT_ESC.draft = { name: '', trigger_event: null, is_active: true };
        TKT_ESC.conds = [];
        TKT_ESC.acts = [{ type: 'set_priority', value: null }];
    }

    function escFormHtml() {
        var triggers = (TKT_ESC.catalog && TKT_ESC.catalog.triggers) || [];

        var html = '';

        if (TKT_ESC.errors && TKT_ESC.errors.length) {
            html += '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i><div>' +
                TKT_ESC.errors.map(escapeHtml).join('<br>') + '</div></div>';
        }

        html += '<div class="tkt-field"><label class="tkt-label">Nombre de la regla</label>' +
            '<input type="text" class="tkt-input" id="tkt-esc-name" maxlength="255" placeholder="Escalar los urgentes sin agente"' +
            ' value="' + escapeHtml(TKT_ESC.draft.name || '') + '"></div>';

        html += '<div class="tkt-field"><label class="tkt-label">Si</label>' +
            '<select class="tkt-select" id="tkt-esc-trigger">' + triggers.map(function (t) {
                return '<option value="' + escapeHtml(t.value) + '"' + (t.value === TKT_ESC.draft.trigger_event ? ' selected' : '') + '>' + escapeHtml(t.label) + '</option>';
            }).join('') + '</select></div>';

        html += '<div class="tkt-field"><label class="tkt-label">Y se cumple<button type="button" class="tkt-label-action" id="tkt-esc-add-cond">+ añadir condición</button></label>' +
            '<div id="tkt-esc-conds">' + (TKT_ESC.conds.length
                ? TKT_ESC.conds.map(function (c, i) { return escCondHtml(i, c); }).join('')
                : '<div class="tkt-empty-box">Sin condiciones: la regla vale para cualquier ticket.</div>') +
            '</div></div>';

        html += '<div class="tkt-field"><label class="tkt-label">Entonces<button type="button" class="tkt-label-action" id="tkt-esc-add-act">+ añadir acción</button></label>' +
            '<div id="tkt-esc-acts">' + TKT_ESC.acts.map(function (a, i) { return escActHtml(i, a); }).join('') + '</div></div>';

        html += '<label class="tkt-check"><input type="checkbox" id="tkt-esc-active"' +
            (TKT_ESC.draft.is_active ? ' checked' : '') + '> Activar la regla al crearla</label>';

        if (TKT_ESC.preview) {
            html += '<div class="tkt-note ' + (TKT_ESC.preview.matched ? 'ok' : '') + '"><i class="fa-solid fa-flask"></i><div>' +
                escapeHtml(TKT_ESC.preview.text) + '</div></div>';
        }

        return html + escEvalNoteHtml();
    }

    function escFormFootHtml() {
        return '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-esc-save">Crear regla</button>' +
            '<button type="button" class="tkt-btn" id="tkt-esc-test">Probar regla</button>' +
            '<button type="button" class="tkt-btn" id="tkt-esc-cancel">Cancelar</button>';
    }

    function escCondHtml(i, cond) {
        var fields = (TKT_ESC.catalog && TKT_ESC.catalog.fields) || [];
        var spec = escFieldSpec(cond.field) || fields[0];
        if (!spec) return '';
        var op = cond.op && spec.ops.indexOf(cond.op) >= 0 ? cond.op : spec.ops[0];

        return '<div class="tkt-esc-rule" data-cond="' + i + '">' +
            '<div class="tkt-esc-rule-head"><span class="tkt-cap">Condición ' + (i + 1) + '</span>' +
            '<button type="button" class="tkt-btn-icon tkt-esc-rule-del" data-cond-del="' + i + '" title="Quitar la condición"><i class="fa-solid fa-xmark"></i></button></div>' +
            '<div class="tkt-duo">' +
                '<div class="tkt-field"><select class="tkt-select" data-cond-field="' + i + '">' + fields.map(function (f) {
                    return '<option value="' + escapeHtml(f.field) + '"' + (f.field === spec.field ? ' selected' : '') + '>' + escapeHtml(f.label) + '</option>';
                }).join('') + '</select></div>' +
                '<div class="tkt-field"><select class="tkt-select" data-cond-op="' + i + '">' + spec.ops.map(function (o) {
                    return '<option value="' + escapeHtml(o) + '"' + (o === op ? ' selected' : '') + '>' + escapeHtml(escOpLabel(o)) + '</option>';
                }).join('') + '</select></div>' +
            '</div>' +
            '<div data-cond-value="' + i + '">' + escValueControlHtml(spec, op, 'cond-val-' + i, cond.value) + '</div>' +
            '</div>';
    }

    function escActHtml(i, act) {
        var actions = (TKT_ESC.catalog && TKT_ESC.catalog.actions) || [];
        var spec = escActionSpec(act.type) || actions[0];
        if (!spec) return '';

        return '<div class="tkt-esc-rule" data-act="' + i + '">' +
            '<div class="tkt-esc-rule-head"><span class="tkt-cap">Acción ' + (i + 1) + '</span>' +
            (TKT_ESC.acts.length > 1
                ? '<button type="button" class="tkt-btn-icon tkt-esc-rule-del" data-act-del="' + i + '" title="Quitar la acción"><i class="fa-solid fa-xmark"></i></button>'
                : '') +
            '</div>' +
            '<div class="tkt-field"><select class="tkt-select" data-act-type="' + i + '">' + actions.map(function (a) {
                return '<option value="' + escapeHtml(a.type) + '"' + (a.type === spec.type ? ' selected' : '') + '>' + escapeHtml(a.label) + '</option>';
            }).join('') + '</select></div>' +
            '<div data-act-value="' + i + '">' + escValueControlHtml(spec, null, 'act-val-' + i, act.value) + '</div>' +
            '</div>';
    }

    // Control del valor según lo que admite el campo/acción. Los operadores
    // is_null/is_not_null no llevan valor: preguntan por la ausencia.
    function escValueControlHtml(spec, op, cls, actual) {
        if (op === 'is_null' || op === 'is_not_null') return '';
        if (spec.input === 'none') return '';

        if (op === 'in') {
            // "La prioridad es Alta o Urgente" del mockup: varias a la vez.
            var marcados = Array.isArray(actual) ? actual.map(String) : [];
            return '<div class="tkt-esc-checks">' + escCatalogList(spec.options).map(function (o) {
                return '<label class="tkt-check"><input type="checkbox" class="' + cls + '" value="' + escapeHtml(String(o.id)) + '"' +
                    (marcados.indexOf(String(o.id)) >= 0 ? ' checked' : '') + '> ' + escapeHtml(o.name) + '</label>';
            }).join('') + '</div>';
        }

        if (spec.input === 'bool') {
            return '<select class="tkt-select ' + cls + '"><option value="1">Sí</option>' +
                '<option value="0"' + (String(actual) === '0' ? ' selected' : '') + '>No</option></select>';
        }

        if (spec.input === 'number') {
            return '<input type="number" min="0" step="1" class="tkt-input ' + cls + '" value="' + escapeHtml(String(actual == null ? 0 : actual)) + '">';
        }

        if (spec.input === 'textarea') {
            return '<textarea class="tkt-input ' + cls + '" rows="2" maxlength="2000" placeholder="Texto de la nota interna">' +
                escapeHtml(String(actual == null ? '' : actual)) + '</textarea>';
        }

        if (spec.input === 'text') {
            return '<input type="text" class="tkt-input ' + cls + '" maxlength="255" placeholder="Escribe el valor"' +
                ' value="' + escapeHtml(String(actual == null ? '' : actual)) + '">';
        }

        var list = escCatalogList(spec.options);
        if (!list.length) {
            return '<div class="tkt-empty-box">No hay opciones disponibles para esta elección.</div>';
        }

        return '<select class="tkt-select ' + cls + '">' + list.map(function (o) {
            return '<option value="' + escapeHtml(String(o.id)) + '"' + (String(o.id) === String(actual) ? ' selected' : '') + '>' + escapeHtml(o.name) + '</option>';
        }).join('') + '</select>';
    }

    // ── Lectura del formulario ────────────────────────────────────
    function escReadValue($scope, spec, op, cls) {
        if (op === 'is_null' || op === 'is_not_null') return null;
        if (spec.input === 'none') return null;
        if (op === 'in') {
            return $scope.find('.' + cls + ':checked').map(function () { return this.value; }).get();
        }
        return $scope.find('.' + cls).val();
    }

    function escReadForm($backdrop) {
        var conditions = [];
        $backdrop.find('[data-cond]').each(function () {
            var i = $(this).data('cond');
            var $row = $(this);
            var field = $row.find('[data-cond-field]').val();
            var op = $row.find('[data-cond-op]').val();
            var spec = escFieldSpec(field);
            if (!spec) return;
            conditions.push({ field: field, op: op, value: escReadValue($row, spec, op, 'cond-val-' + i) });
        });

        var actions = [];
        $backdrop.find('[data-act]').each(function () {
            var i = $(this).data('act');
            var $row = $(this);
            var type = $row.find('[data-act-type]').val();
            var spec = escActionSpec(type);
            if (!spec) return;
            actions.push({ type: type, value: escReadValue($row, spec, null, 'act-val-' + i) });
        });

        return {
            name: $.trim($backdrop.find('#tkt-esc-name').val() || ''),
            trigger_event: $backdrop.find('#tkt-esc-trigger').val(),
            conditions: conditions,
            actions: actions,
            is_active: $backdrop.find('#tkt-esc-active').is(':checked') ? 1 : 0,
        };
    }

    // Vuelca a TKT_ESC lo que hay ahora mismo en el formulario (nombre,
    // disparador, filas y sus valores) antes de repintar: sin esto, añadir una
    // condición borraba todo lo tecleado antes.
    function escSyncStructure($backdrop) {
        var leido = escReadForm($backdrop);

        TKT_ESC.draft = {
            name: leido.name,
            trigger_event: leido.trigger_event,
            is_active: !!leido.is_active,
        };
        TKT_ESC.conds = leido.conditions;
        TKT_ESC.acts = leido.actions;
    }

    function escApiError(xhr, porDefecto) {
        var msgs = [];
        if (xhr && xhr.responseJSON && xhr.responseJSON.errors) {
            $.each(xhr.responseJSON.errors, function (k, list) { msgs.push(list[0]); });
        } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            msgs.push(xhr.responseJSON.message);
        }
        if (!msgs.length) msgs.push(xhr && xhr.status === 403 ? 'Hace falta permiso de ajustes del módulo.' : porDefecto);
        return msgs;
    }

    // ── Eventos ───────────────────────────────────────────────────
    function escBind($backdrop) {
        $backdrop.on('click', '#tkt-esc-new', function () {
            TKT_ESC.tab = 'form';
            TKT_ESC.errors = null;
            TKT_ESC.preview = null;
            escResetDraft();
            escRender($backdrop);
        });

        $backdrop.on('click', '#tkt-esc-cancel', function () {
            TKT_ESC.tab = 'list';
            TKT_ESC.errors = null;
            TKT_ESC.preview = null;
            escRender($backdrop);
        });

        // Activar / pausar una regla del listado.
        $backdrop.on('click', '[data-esc-toggle]', function () {
            var id = $(this).data('esc-toggle');
            var tpl = escUrl('automationsToggleTemplate', 'data-automations-toggle-url-template');
            if (!tpl) return;
            var $chip = $(this).prop('disabled', true);
            $.ajax({
                url: tpl.replace('__AUTOMATION__', id),
                method: 'POST',
                headers: { Accept: 'application/json' },
            }).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Regla actualizada.');
                for (var i = 0; i < TKT_ESC.rules.length; i++) {
                    if (TKT_ESC.rules[i].id === id) TKT_ESC.rules[i] = resp.rule;
                }
                escRender($backdrop);
            }).fail(function (xhr) {
                $chip.prop('disabled', false);
                var msg = escApiError(xhr, 'No se pudo cambiar el estado de la regla.')[0];
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            });
        });

        // Añadir / quitar filas. Se sincroniza la estructura antes de repintar
        // para conservar lo ya elegido en las demás filas.
        $backdrop.on('click', '#tkt-esc-add-cond', function () {
            escSyncStructure($backdrop);
            var fields = (TKT_ESC.catalog && TKT_ESC.catalog.fields) || [];
            if (TKT_ESC.conds.length >= 5 || !fields.length) return;
            TKT_ESC.conds.push({ field: fields[0].field, op: fields[0].ops[0] });
            escRender($backdrop);
        });

        $backdrop.on('click', '#tkt-esc-add-act', function () {
            escSyncStructure($backdrop);
            var actions = (TKT_ESC.catalog && TKT_ESC.catalog.actions) || [];
            if (TKT_ESC.acts.length >= 5 || !actions.length) return;
            TKT_ESC.acts.push({ type: actions[0].type });
            escRender($backdrop);
        });

        $backdrop.on('click', '[data-cond-del]', function () {
            escSyncStructure($backdrop);
            TKT_ESC.conds.splice($(this).data('cond-del'), 1);
            escRender($backdrop);
        });

        $backdrop.on('click', '[data-act-del]', function () {
            escSyncStructure($backdrop);
            TKT_ESC.acts.splice($(this).data('act-del'), 1);
            escRender($backdrop);
        });

        // Cambiar de campo cambia los operadores posibles y el control del valor:
        // se repinta solo esa fila, así el resto del formulario no se pierde.
        $backdrop.on('change', '[data-cond-field]', function () {
            var i = $(this).data('cond-field');
            var spec = escFieldSpec($(this).val());
            if (!spec) return;
            var $row = $backdrop.find('[data-cond="' + i + '"]');
            $row.replaceWith(escCondHtml(i, { field: spec.field, op: spec.ops[0] }));
            initSelect2($backdrop.find('[data-cond="' + i + '"]'));
        });

        $backdrop.on('change', '[data-cond-op]', function () {
            var i = $(this).data('cond-op');
            var $row = $backdrop.find('[data-cond="' + i + '"]');
            var spec = escFieldSpec($row.find('[data-cond-field]').val());
            if (!spec) return;
            $row.find('[data-cond-value="' + i + '"]').html(escValueControlHtml(spec, $(this).val(), 'cond-val-' + i));
            initSelect2($row);
        });

        $backdrop.on('change', '[data-act-type]', function () {
            var i = $(this).data('act-type');
            var spec = escActionSpec($(this).val());
            if (!spec) return;
            var $row = $backdrop.find('[data-act="' + i + '"]');
            $row.find('[data-act-value="' + i + '"]').html(escValueControlHtml(spec, null, 'act-val-' + i));
            initSelect2($row);
        });

        // Probar regla: prueba en seco de las condiciones contra los últimos
        // tickets. No ejecuta ninguna acción ni guarda nada.
        $backdrop.on('click', '#tkt-esc-test', function () {
            var url = escUrl('automationsPreview', 'data-automations-preview-url');
            if (!url) return;
            var datos = escReadForm($backdrop);
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: url, method: 'POST', headers: { Accept: 'application/json' },
                data: { conditions: datos.conditions },
            }).done(function (resp) {
                escSyncStructure($backdrop);
                TKT_ESC.errors = null;
                TKT_ESC.preview = {
                    matched: resp.matched,
                    text: resp.matched
                        ? 'Las condiciones coinciden con ' + resp.matched + ' de los últimos ' + resp.scanned + ' tickets' +
                          (resp.sample.length ? ' (' + resp.sample.map(function (s) { return s.ticket_number; }).join(', ') + ')' : '') +
                          '. La prueba no ejecuta ninguna acción.'
                        : 'Ninguno de los últimos ' + resp.scanned + ' tickets cumple estas condiciones.',
                };
                escRender($backdrop);
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                escSyncStructure($backdrop);
                TKT_ESC.errors = escApiError(xhr, 'No se pudo probar la regla.');
                TKT_ESC.preview = null;
                escRender($backdrop);
            });
        });

        $backdrop.on('click', '#tkt-esc-save', function () {
            var url = escUrl('automationsStore', 'data-automations-store-url');
            if (!url) return;
            var datos = escReadForm($backdrop);
            if (!datos.name) {
                escSyncStructure($backdrop);
                TKT_ESC.errors = ['Ponle un nombre a la regla.'];
                escRender($backdrop);
                return;
            }
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: url, method: 'POST', headers: { Accept: 'application/json' }, data: datos,
            }).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Regla creada.');
                TKT_ESC.rules.push(resp.rule);
                TKT_ESC.tab = 'list';
                TKT_ESC.errors = null;
                TKT_ESC.preview = null;
                escResetDraft();
                escRender($backdrop);
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                escSyncStructure($backdrop);
                TKT_ESC.errors = escApiError(xhr, 'No se pudo crear la regla.');
                escRender($backdrop);
            });
        });
    }


