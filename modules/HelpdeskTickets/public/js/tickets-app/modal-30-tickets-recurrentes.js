'use strict';

    // ── Modal 30: Ticket recurrente ───────────────────────────
    // ── Modal 30: Tickets recurrentes ─────────────────────────
    function openRecurringModal() {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-repeat',
            kicker: 'Tickets · recurrentes',
            title: 'Tickets recurrentes',
            width: '2xl',
            body: '<div class="tkt-skeleton"></div><div class="tkt-skeleton"></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // Lista + catálogos de los desplegables, ambos de la misma petición.
        var lista = [];
        var catalogos = null;
        // Recurrencia que se está editando (null = alta nueva o pantalla lista).
        var editando = null;

        function urlDe(plantilla, id) {
            return String(plantilla || '').replace('__REC__', id);
        }

        function porId(id) {
            return lista.find(function (r) { return String(r.id) === String(id); }) || null;
        }

        // El pie cambia entre pantalla de lista y pantalla de formulario, así que
        // se repintan las dos zonas del modal ya abierto en vez de cerrarlo y
        // volver a abrirlo (perdería el scroll y parpadearía).
        function pintar(bodyHtml, footHtml) {
            $backdrop.find('.tkt-modal-body').html(bodyHtml);
            $backdrop.find('.tkt-modal-foot').html(footHtml);
            initSelect2($backdrop.find('.tkt-modal-body'));
        }

        // ── Pantalla 1: la lista ──────────────────────────────
        function filaHtml(r) {
            var meta = [
                r.frequency_label,
                r.next_run_at_human ? 'próxima ' + r.next_run_at_human : 'sin próxima ejecución',
                r.tickets_created ? r.tickets_created + ' creados' : null,
            ].filter(Boolean).join(' · ');

            return '<div class="tkt-mailitem tkt-rec-row' + (r.is_active ? '' : ' off') + '">' +
                '<span class="av light"><i class="fa-solid fa-repeat"></i></span>' +
                '<span class="who"><span class="n">' + escapeHtml(r.name || r.subject) + '</span>' +
                    '<span class="s">' + escapeHtml(meta) + '</span></span>' +
                (r.is_active ? '' : chip('Pausada', 'tkt-chip-muted')) +
                '<span class="tkt-rec-actions">' +
                    '<button type="button" class="tkt-btn-icon sm" data-rec-toggle="' + r.id + '" ' +
                        'title="' + (r.is_active ? 'Pausar' : 'Reanudar') + '" ' +
                        'aria-label="' + (r.is_active ? 'Pausar' : 'Reanudar') + ' ' + escapeHtml(r.name || '') + '">' +
                        '<i class="fa-solid ' + (r.is_active ? 'fa-pause' : 'fa-play') + '"></i></button>' +
                    '<button type="button" class="tkt-btn-icon sm" data-rec-edit="' + r.id + '" ' +
                        'title="Editar" aria-label="Editar ' + escapeHtml(r.name || '') + '">' +
                        '<i class="fa-solid fa-pen"></i></button>' +
                '</span></div>';
        }

        function renderLista() {
            editando = null;

            var cuerpo = lista.length
                ? '<div class="tkt-mailitems">' + lista.map(filaHtml).join('') + '</div>'
                : '<div class="tkt-empty-box">No hay ninguna recurrencia programada. Una recurrencia crea un ticket cada día, semana o mes sin que nadie tenga que acordarse.</div>';

            pintar(
                cuerpo,
                '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-rec-new">Programar recurrencia</button>' +
                '<a class="tkt-btn" href="' + escapeHtml(TKA.urls.recurring || '#') + '">Gestionar en Ajustes</a>' +
                '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>'
            );
        }

        // ── Pantalla 2: el formulario ─────────────────────────
        function opcionesFrecuencia(r) {
            var todas = (catalogos && catalogos.frequencies) || [
                { value: 'daily', label: 'Diaria' },
                { value: 'weekly', label: 'Semanal' },
                { value: 'monthly', label: 'Mensual' },
            ];
            // 'custom' solo se ofrece si la recurrencia YA lo era: el modal no
            // edita expresiones cron (el backend rechaza crearlas desde aquí),
            // pero tampoco puede obligar a cambiarle la frecuencia a una que ya
            // la tiene solo para tocarle el asunto.
            return todas.filter(function (f) {
                return f.value !== 'custom' || (r && r.frequency === 'custom');
            }).map(function (f) {
                return '<option value="' + f.value + '"' +
                    (r && r.frequency === f.value ? ' selected' : '') + '>' + escapeHtml(f.label) + '</option>';
            }).join('');
        }

        function agentesConAsignado(r) {
            var agentes = ((catalogos && catalogos.agents) || TKA.state.agentsFull || []).slice();
            // El agente asignado puede no estar en el catálogo (se le retiró el
            // rol, o dejó de estar disponible): sin esto el <select> lo perdería
            // en silencio al primer guardado.
            if (r && r.assignee_id && !agentes.some(function (a) { return String(a.id) === String(r.assignee_id); })) {
                agentes.unshift({ id: r.assignee_id, name: r.assignee_name || ('Usuario #' + r.assignee_id) });
            }
            return agentes;
        }

        function renderEditor(r) {
            editando = r || null;

            var categorias = (catalogos && catalogos.categories) || TKA.state.categories || [];
            var prioridades = (catalogos && catalogos.priorities) || [];
            var plantillas = TKA.state.ticketTemplates || [];

            var cuerpo = '' +
                // La plantilla solo rellena campos: no queda guardada en la
                // recurrencia (no hay columna), así que se ofrece únicamente al
                // crear, donde ahorra teclear, y no al editar, donde machacaría
                // lo que ya hay.
                (!r && plantillas.length
                    ? '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-tpl">Plantilla de ticket' +
                        '<span class="hint">solo rellena los campos</span></label>' +
                        '<select class="tkt-select" id="tkt-rec-tpl"><option value="">Empezar en blanco…</option>' +
                        optionsHtml(plantillas, 'id', '') + '</select></div>'
                    : '') +

                '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-name">Nombre<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-rec-name" maxlength="255" ' +
                    'placeholder="Control diario de pedidos sin salir" value="' + escapeHtml(r ? r.name : '') + '"></div>' +

                '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-subject">Asunto del ticket<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-rec-subject" maxlength="255" ' +
                    'value="' + escapeHtml(r ? r.subject : '') + '"></div>' +

                '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-desc">Descripción</label>' +
                    '<textarea class="tkt-input" id="tkt-rec-desc" rows="3" maxlength="5000">' +
                    escapeHtml(r ? (r.description || '') : '') + '</textarea></div>' +

                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-freq">Frecuencia<span class="req">*</span></label>' +
                        '<select class="tkt-select" id="tkt-rec-freq">' + opcionesFrecuencia(r) + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-next">' +
                        (r ? 'Próxima ejecución' : 'Primera ejecución') + '</label>' +
                        '<input type="datetime-local" class="tkt-input" id="tkt-rec-next" ' +
                        'value="' + escapeHtml(r ? (r.next_run_at || '') : '') + '"></div>' +
                '</div>' +

                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-cat">Categoría</label>' +
                        '<select class="tkt-select" id="tkt-rec-cat"><option value="">Sin categoría</option>' +
                        optionsHtml(categorias, 'id', r ? r.category_id : '') + '</select></div>' +
                    (prioridades.length
                        ? '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-pri">Prioridad</label>' +
                            '<select class="tkt-select" id="tkt-rec-pri"><option value="">Sin prioridad</option>' +
                            optionsHtml(prioridades, 'id', r ? r.priority_id : '') + '</select></div>'
                        : '') +
                '</div>' +

                '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-agent">Agente asignado</label>' +
                    '<select class="tkt-select" id="tkt-rec-agent"><option value="">Sin asignar</option>' +
                    optionsHtml(agentesConAsignado(r), 'id', r ? r.assignee_id : '') + '</select></div>' +

                (r && r.frequency === 'custom'
                    ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Esta recurrencia se rige por una expresión cron (' +
                        escapeHtml(r.cron_expression || '—') + '). Se cambia desde Ajustes; aquí se conserva tal cual.</div>'
                    : '') +

                // Solo con datos reales: en una recurrencia nueva no hay próxima
                // ejecución ni contador que enseñar, así que no se pinta la caja.
                (r
                    ? '<div class="tkt-kv-grid">' +
                        '<span>próxima ejecución</span><span>' + escapeHtml(r.next_run_at_label || 'sin programar') + '</span>' +
                        '<span>creados</span><span>' + (r.tickets_created || 0) + ' tickets</span>' +
                        (r.last_run_at_human ? '<span>última</span><span>' + escapeHtml(r.last_run_at_human) + '</span>' : '') +
                      '</div>'
                    : '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> El ticket se crea con el asunto y la descripción de arriba. Si no indicas la primera ejecución, se programa a partir de la frecuencia elegida.</div>');

            pintar(
                cuerpo,
                '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-rec-save">Guardar recurrencia</button>' +
                (r ? '<button type="button" class="tkt-btn" id="tkt-rec-pause" data-rec-toggle="' + r.id + '">' +
                    (r.is_active ? 'Pausar' : 'Reanudar') + '</button>' : '') +
                '<button type="button" class="tkt-btn" id="tkt-rec-back">' + (r ? 'Volver' : 'Cancelar') + '</button>'
            );
        }

        // ── Datos ─────────────────────────────────────────────
        function cargar(alTerminar) {
            $.getJSON(TKA.urls.recurringOps)
                .done(function (d) {
                    lista = (d && d.data) || [];
                    catalogos = (d && d.catalogs) || null;
                    (alTerminar || renderLista)();
                })
                .fail(function () {
                    pintar(
                        '<div class="tkt-empty-box">No se pudieron cargar las recurrencias.</div>',
                        '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>'
                    );
                });
        }

        function guardar(r) {
            var $save = $backdrop.find('#tkt-rec-save');
            var datos = {
                name: $.trim($backdrop.find('#tkt-rec-name').val()),
                subject: $.trim($backdrop.find('#tkt-rec-subject').val()),
                frequency: $backdrop.find('#tkt-rec-freq').val(),
            };

            if (!datos.name || !datos.subject) {
                if (window.toastr) toastr.error('El nombre y el asunto son obligatorios');
                return;
            }

            // Los opcionales solo viajan si tienen valor: así el backend no
            // recibe cadenas vacías donde espera un id o una fecha.
            [['description', '#tkt-rec-desc'], ['category_id', '#tkt-rec-cat'],
                ['priority_id', '#tkt-rec-pri'], ['assignee_id', '#tkt-rec-agent'],
                ['next_run_at', '#tkt-rec-next']].forEach(function (par) {
                var valor = $.trim($backdrop.find(par[1]).val() || '');
                if (valor) datos[par[0]] = valor;
            });

            $save.prop('disabled', true);
            $.ajax({
                url: r ? urlDe(TKA.urls.recurringUpdateTemplate, r.id) : TKA.urls.recurringOps,
                method: 'POST',
                data: datos,
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Recurrencia guardada');
                    cargar();
                },
                error: function (xhr) {
                    $save.prop('disabled', false);
                    var json = xhr.responseJSON || {};
                    var primerError = json.errors ? json.errors[Object.keys(json.errors)[0]][0] : null;
                    var msg = primerError || json.message || 'No se pudo guardar la recurrencia';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        }

        function alternar(id) {
            var r = porId(id);
            if (!r) return;

            $backdrop.find('[data-rec-toggle="' + id + '"]').prop('disabled', true);
            $.ajax({
                url: urlDe(TKA.urls.recurringToggleTemplate, id),
                method: 'POST',
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Recurrencia actualizada');
                    // Se recarga y se vuelve a la lista: pausar desde el
                    // formulario cambia además la próxima ejecución (el backend
                    // la reprograma al reanudar), y hay que verla al día.
                    cargar();
                },
                error: function (xhr) {
                    $backdrop.find('[data-rec-toggle="' + id + '"]').prop('disabled', false);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo cambiar el estado de la recurrencia';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        }

        // ── Eventos (delegados: el cuerpo se repinta entero) ──
        $backdrop.on('click', '#tkt-rec-new', function () { renderEditor(null); });
        $backdrop.on('click', '#tkt-rec-back', function () { renderLista(); });
        $backdrop.on('click', '[data-rec-edit]', function () { renderEditor(porId($(this).data('rec-edit'))); });
        $backdrop.on('click', '[data-rec-toggle]', function () { alternar($(this).data('rec-toggle')); });
        $backdrop.on('click', '#tkt-rec-save', function () { guardar(editando); });

        $backdrop.on('change', '#tkt-rec-tpl', function () {
            var elegida = $(this).val();
            var tpl = (TKA.state.ticketTemplates || []).find(function (p) { return String(p.id) === String(elegida); });
            if (!tpl) return;
            if (!$.trim($backdrop.find('#tkt-rec-name').val())) $backdrop.find('#tkt-rec-name').val(tpl.name || '');
            if (tpl.subject) $backdrop.find('#tkt-rec-subject').val(tpl.subject);
            // El cuerpo de la plantilla puede venir con HTML y la descripción del
            // ticket recurrente es texto plano.
            if (tpl.body) $backdrop.find('#tkt-rec-desc').val($('<div>').html(tpl.body).text());
            if (tpl.category_id) $backdrop.find('#tkt-rec-cat').val(tpl.category_id).trigger('change');
        });

        // Sin las URLs nuevas cableadas en el blade, el modal se comporta como
        // antes (lista de solo lectura) en vez de quedarse en blanco.
        if (!TKA.urls.recurringOps) {
            withSettings(function (d) {
                var soloLectura = (d && d.recurring) || [];
                pintar(
                    soloLectura.length
                        ? '<div class="tkt-mailitems">' + soloLectura.map(function (r) {
                            return '<div class="tkt-mailitem"><span class="av light"><i class="fa-solid fa-repeat"></i></span>' +
                                '<span class="who"><span class="n">' + escapeHtml(r.name || r.subject) + '</span>' +
                                '<span class="s">' + escapeHtml([r.frequency,
                                    r.next_run_at_human ? 'próxima ' + r.next_run_at_human : null,
                                    r.tickets_created ? r.tickets_created + ' creados' : null].filter(Boolean).join(' · ')) +
                                '</span></span></div>';
                        }).join('') + '</div>'
                        : '<div class="tkt-empty-box">No hay ninguna recurrencia activa.</div>',
                    '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(TKA.urls.recurring || '#') + '">Programar recurrencia</a>' +
                    '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>'
                );
            });
            return;
        }

        cargar();
    }


