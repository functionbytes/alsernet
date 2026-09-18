/*
 * Detalle completo del ticket (managers.tickets.show, servida por showFull()).
 *
 * Estas líneas vivían embebidas en show.blade.php repartidas en seis bloques
 * @push('scripts'), mientras la vista hermana — el listado — ya tenía su
 * JavaScript aquí al lado, en tickets-app.js. Sacarlo lo hace cacheable,
 * versionable con ?v=filemtime y analizable por un linter.
 *
 * Los pocos valores que venían interpolados de Blade se reciben ahora en
 * window.TicketDetailConfig, que la vista define en un <script> corto justo
 * antes de cargar este fichero.
 */
(function () {
    'use strict';

    var CFG = window.TicketDetailConfig || {};

            $(function () {
                var senderEmail = CFG.senderEmail;
                var senderDomain = CFG.senderDomain;

                $('#block-sender-type').on('change', function () {
                    var isDomain = $(this).val() === 'domain';
                    var $target = $('<strong>').text(isDomain ? ('@' + senderDomain) : senderEmail);
                    var suffix = isDomain
                        ? ' (y sus subdominios) se descartarán automáticamente, sin crear tickets nuevos ni de respuesta.'
                        : ' se descartarán automáticamente, sin crear tickets nuevos ni de respuesta.';

                    $('#block-sender-value').val(isDomain ? senderDomain : senderEmail);
                    $('#block-sender-description').empty()
                        .append(isDomain ? 'Todos los correos de ' : 'Los próximos correos de ')
                        .append($target)
                        .append(suffix);
                });
            });

        // Auto-scroll to bottom of messages
        const messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Real-time via Laravel Echo
        if (CFG.broadcastingEnabled) {
            Echo.channel('ticket.' + CFG.ticketId)
                .listen('TicketMessageReceived', () => location.reload());
        }

        // ── Time Tracking ──────────────────────────────────────────────
        const timeEntriesUrl = CFG.timeEntriesUrl;
        const currentUserId = CFG.currentUserId;

        function formatLoggedAt(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }

        function renderEntries(data) {
            const $list = $('#time-entries-list');
            $list.empty();
            $('#total-time-badge').text(data.formatted_total || '0m');

            if (!data.entries || data.entries.length === 0) {
                $list.html('<div class="list-group-item text-muted text-center py-3">Sin registros de tiempo</div>');
                return;
            }

            data.entries.forEach(function (entry) {
                const userName = entry.user ? entry.user.name : 'Desconocido';

                const $info = $('<div class="small">').append(
                    $('<span class="fw-semibold">').text(entry.formatted_duration),
                    $('<span class="text-muted ms-1">').text('· ' + userName)
                );

                if (entry.description) {
                    $info.append($('<div class="text-muted">').css('font-size', '11px').text(entry.description));
                }

                $info.append($('<div class="text-muted">').css('font-size', '10px').text(formatLoggedAt(entry.logged_at)));

                const $actions = $('<div class="flex-shrink-0">');
                if (entry.user_id === currentUserId) {
                    $actions.append(
                        $('<button class="btn btn-link btn-sm text-danger p-0 ms-1 delete-time-entry">')
                            .attr({ 'data-id': entry.id, title: 'Eliminar' })
                            .append($('<i class="fas fa-trash-alt">'))
                    );
                }

                $list.append(
                    $('<div class="list-group-item px-2 py-1 d-flex justify-content-between align-items-start">').append($info, $actions)
                );
            });
        }

        function loadTimeEntries() {
            $.get(timeEntriesUrl)
                .done(renderEntries)
                .fail(function () {
                    $('#time-entries-list').html('<div class="list-group-item text-danger small">Error al cargar registros</div>');
                });
        }

        $(document).on('click', '.delete-time-entry', function () {
            const id = $(this).data('id');
            $.ajax({
                method: 'DELETE',
                url: timeEntriesUrl + '/' + id,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            })
            .done(loadTimeEntries)
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error al eliminar');
            });
        });

        $('#log-time-form').on('submit', function (e) {
            e.preventDefault();

            const minutes = parseInt($('#time-minutes').val(), 10);
            const description = $('#time-description').val().trim();

            if (!minutes || minutes < 1 || minutes > 480) {
                toastr.warning('Los minutos deben estar entre 1 y 480.');
                $('#time-minutes').focus();
                return;
            }

            $.ajax({
                method: 'POST',
                url: timeEntriesUrl,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { minutes, description },
            })
            .done(function () {
                $('#time-minutes').val('');
                $('#time-description').val('');
                toastr.success('Tiempo registrado correctamente.');
                loadTimeEntries();
            })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    toastr.error(Object.values(xhr.responseJSON.errors).flat().join(' '));
                } else {
                    toastr.error('Error al registrar tiempo.');
                }
            });
        });

        loadTimeEntries();

        // ── SLA Countdown ──────────────────────────────────────────────
        function updateSlaCountdowns() {
            $('[data-sla-countdown]').each(function () {
                const due  = new Date($(this).data('sla-countdown'));
                const mins = Math.round((due - new Date()) / 60000);

                if (mins < 0) {
                    $(this).text('Vencido hace ' + Math.abs(mins) + ' min');
                } else if (mins < 60) {
                    $(this).text('En ' + mins + ' min');
                } else {
                    $(this).text('En ' + Math.round(mins / 60) + ' h');
                }
            });
        }

        updateSlaCountdowns();
        setInterval(updateSlaCountdowns, 30000);

        // ── @Mentions ──────────────────────────────────────────────────
        (function () {
            const users = CFG.mentionableUsers;
            const $textarea = $('textarea[name="body"]').first();
            if (!$textarea.length) return;

            const $dropdown = $('<div class="dropdown-menu shadow" style="max-height:200px;overflow-y:auto;display:none;position:fixed;z-index:9999"></div>')
                .appendTo('body');

            function getAtContext(text, cursor) {
                const lastAt = text.lastIndexOf('@', cursor - 1);
                if (lastAt < 0) return null;
                if (lastAt > 0 && !/\s/.test(text[lastAt - 1])) return null;
                const query = text.substring(lastAt + 1, cursor);
                if (/\s/.test(query)) return null;
                return { lastAt, query };
            }

            $textarea.on('input keyup', function () {
                const ctx = getAtContext(this.value, this.selectionStart);
                if (!ctx) { $dropdown.hide(); return; }

                const matches = users.filter(u => u.name.toLowerCase().includes(ctx.query.toLowerCase())).slice(0, 8);
                if (!matches.length) { $dropdown.hide(); return; }

                $dropdown.empty();
                matches.forEach(function (u) {
                    const rect = $textarea[0].getBoundingClientRect();
                    $dropdown.css({ top: rect.bottom + 5, left: rect.left, minWidth: 260 });

                    $('<a class="dropdown-item d-flex align-items-center gap-2 py-2"></a>')
                        .html('<span class="fw-semibold">' + $('<span>').text(u.name).html() + '</span><small class="text-muted">' + $('<span>').text(u.email).html() + '</small>')
                        .on('mousedown', function (ev) {
                            ev.preventDefault();
                            const val = $textarea.val();
                            const cursor = $textarea[0].selectionStart;
                            $textarea.val(val.substring(0, ctx.lastAt) + '@' + u.name + ' ' + val.substring(cursor));
                            $dropdown.hide();
                            $textarea.focus();
                        })
                        .appendTo($dropdown);
                });

                const rect = $textarea[0].getBoundingClientRect();
                $dropdown.css({ top: rect.bottom + 5, left: rect.left, minWidth: 260 }).show();
            });

            $textarea.on('blur', function () {
                setTimeout(function () { $dropdown.hide(); }, 200);
            });
        }());

    $(function () {
        // link_type y macro-select viven fuera de cualquier modal; los dos
        // participantes de "conversación lateral" están dentro de #sideConversationModal
        // y necesitan dropdownParent (si no, el desplegable se renderiza detrás del modal).
        $('.select2').not('#side-participant-type, #side-participant-user').select2({ width: '100%' });
        $('#side-participant-type, #side-participant-user').select2({
            width: '100%',
            dropdownParent: $('#sideConversationModal'),
        });

        const $sel = $('#macro-select');
        const $btn = $('#macro-apply-btn');

        $.get(CFG.macrosListUrl).done(function (data) {
            $.each(data.macros, function (i, m) {
                $sel.append($('<option>', { value: m.id, text: m.name }));
            });
        });

        $sel.on('change', function () {
            $btn.prop('disabled', !this.value);
        });

        $btn.on('click', function () {
            const macroId = $sel.val();
            if (!macroId) return;

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Aplicando...');

            $.ajax({
                url: CFG.macroApplyUrlBase + '/' + macroId + '/apply',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (res) {
                toastr.success(res.message);
                setTimeout(function () { location.reload(); }, 800);
            }).fail(function () {
                toastr.error('Error al aplicar la macro.');
                $btn.prop('disabled', false).html('<i class="fas fa-play me-1"></i> Aplicar');
            });
        });

        // Merge confirmation
        $('#merge-ticket-form').on('submit', function (e) {
            const targetId = $(this).find('[name="merge_into_id"]').val();
            if (!targetId) return;
            e.preventDefault();
            const form = this;
            window.__confirm('¿Fusionar este ticket en el ticket #' + targetId + '? Esta acción no se puede deshacer.', function () {
                form.submit();
            });
        });

        // ── Canned replies ──────────────────────────────────────────────
        $(document).on('click', '.canned-reply-item', function () {
            const content = $(this).data('content');
            const $body = $('#reply-body');
            const current = $body.val().trim();
            $body.val(current ? current + '\n\n' + content : content);
            $body.focus();
        });

        // ── Internal note toggle ────────────────────────────────────────
        $('#isInternal').on('change', function () {
            const isInternal = $(this).is(':checked');
            $('#internal-indicator').toggleClass('d-none', !isInternal);
            $('#reply-form-wrapper').toggleClass('bg-warning-subtle', isInternal).toggleClass('bg-light', !isInternal);
        });

        // ── Attachment preview ──────────────────────────────────────────
        $('#attachments').on('change', function () {
            const $preview = $('#attachment-preview');
            $preview.empty();
            $.each(this.files, function (i, file) {
                const ext = file.name.split('.').pop().toLowerCase();
                const iconMap = { pdf: 'fa-file-pdf', doc: 'fa-file-word', docx: 'fa-file-word', xls: 'fa-file-excel', xlsx: 'fa-file-excel', jpg: 'fa-file-image', jpeg: 'fa-file-image', png: 'fa-file-image', gif: 'fa-file-image', zip: 'fa-file-archive', rar: 'fa-file-archive' };
                const icon = iconMap[ext] || 'fa-file';
                const size = file.size > 1048576 ? (file.size / 1048576).toFixed(1) + ' MB' : (file.size / 1024).toFixed(0) + ' KB';
                $preview.append(
                    '<div class="d-flex align-items-center gap-1 border rounded px-2 py-1 bg-white small">' +
                    '<i class="fas ' + icon + ' text-muted"></i>' +
                    '<span class="text-truncate" style="max-width:120px">' + $('<span>').text(file.name).html() + '</span>' +
                    '<span class="text-muted">(' + size + ')</span>' +
                    '</div>'
                );
            });
        });
    });

    $(function () {
        if (typeof window.Echo === 'undefined') {
            console.warn('Echo not available — skipping real-time features');
            return;
        }

        const ticketId = CFG.ticketId;
        const meId     = CFG.currentUserId;

        const $banner = $('<div class="alert alert-warning py-2 mb-3 d-none" id="collision-banner">' +
            '<i class="fas fa-users me-1"></i><span id="collision-msg"></span>' +
            '</div>');

        // Insert banner before the messages container
        $('#messagesContainer').before($banner);

        const $typing = $('<div class="small text-muted fst-italic mt-1 d-none" id="typing-indicator"></div>');
        $('textarea[name="body"]').closest('form').before($typing);

        let otherUsers = [];

        function updateCollisionBanner() {
            if (!otherUsers.length) {
                $('#collision-banner').addClass('d-none');
                return;
            }

            const names = otherUsers.map(function (u) { return u.name; }).join(', ');
            const verb  = otherUsers.length > 1 ? 'estan' : 'esta';
            $('#collision-msg').text(names + ' ' + verb + ' viendo este ticket');
            $('#collision-banner').removeClass('d-none');
        }

        window.Echo.join('ticket.' + ticketId)
            .here(function (users) {
                otherUsers = users.filter(function (u) { return u.id !== meId; });
                updateCollisionBanner();
            })
            .joining(function (user) {
                if (user.id !== meId) {
                    otherUsers.push(user);
                    updateCollisionBanner();
                }
            })
            .leaving(function (user) {
                otherUsers = otherUsers.filter(function (u) { return u.id !== user.id; });
                updateCollisionBanner();
            })
            .listen('.typing', function (e) {
                if (e.userId === meId) { return; }

                if (e.isTyping) {
                    $('#typing-indicator').text(e.userName + ' esta escribiendo...').removeClass('d-none');
                } else {
                    $('#typing-indicator').addClass('d-none');
                }
            });

        // Emit typing indicator while agent types in the reply textarea
        let typingTimer;
        $('textarea[name="body"]').on('input', function () {
            clearTimeout(typingTimer);

            $.post(CFG.typingUrl, {
                _token:    $('meta[name="csrf-token"]').attr('content'),
                is_typing: 1,
            });

            typingTimer = setTimeout(function () {
                $.post(CFG.typingUrl, {
                    _token:    $('meta[name="csrf-token"]').attr('content'),
                    is_typing: 0,
                });
            }, 2500);
        });
    });

    $(function () {
        const $box = $('#suggested-articles-box');
        if (!$box.length) return;

        const $list = $('#suggested-articles-list');
        let loaded = false;

        function esc(s) {
            return $('<div>').text(s == null ? '' : s).html();
        }

        function load() {
            $list.html('<div class="text-muted small py-1">Buscando…</div>');
            $.ajax({
                url: $box.data('url'),
                method: 'GET',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (r) {
                const items = (r && r.data) || [];
                if (!items.length) {
                    $list.html('<div class="text-muted small py-1">No hay artículos relacionados.</div>');
                    return;
                }
                const html = items.map(function (a) {
                    return '<div class="d-flex align-items-start justify-content-between gap-2 py-1 border-bottom">'
                        + '<a href="' + esc(a.url) + '" target="_blank" class="small text-decoration-none">'
                        + '<i class="fas fa-file-lines me-1"></i>' + esc(a.title) + '</a>'
                        + '<button type="button" class="btn btn-sm btn-light py-0 px-1 insert-article-link" '
                        + 'data-url="' + esc(a.url) + '" data-title="' + esc(a.title) + '" title="Insertar enlace">'
                        + '<i class="fas fa-plus"></i></button></div>';
                }).join('');
                $list.html(html);
            }).fail(function () {
                $list.html('<div class="text-danger small py-1">No se pudieron cargar las sugerencias.</div>');
            });
        }

        $('#suggested-articles-toggle').on('click', function () {
            $list.toggleClass('d-none');
            if (!loaded && !$list.hasClass('d-none')) {
                loaded = true;
                load();
            }
        });

        $(document).on('click', '.insert-article-link', function () {
            const $t = $('#reply-body');
            const link = $(this).data('title') + ': ' + $(this).data('url');
            $t.val(($t.val() ? $t.val() + '\n' : '') + link).trigger('input').focus();
            if (window.toastr) { toastr.success('Enlace del artículo insertado'); }
        });

        // ── Aplicar sugerencias de IA (categoría / prioridad) ──
        $(document).on('click', '.apply-ai-suggestion', function () {
            const $btn = $(this);
            const $box = $('#ai-suggestions');
            $btn.prop('disabled', true);
            $.ajax({
                url: $box.data('url'),
                method: 'POST',
                dataType: 'json',
                data: { field: $btn.data('field') },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function () {
                if (window.toastr) { toastr.success('Sugerencia aplicada'); }
                setTimeout(function () { window.location.reload(); }, 500);
            }).fail(function () {
                $btn.prop('disabled', false);
                if (window.toastr) { toastr.error('No se pudo aplicar la sugerencia'); }
            });
        });

        // ── Resumen del caso bajo demanda ──
        $('#ai-summary-btn').on('click', function () {
            const $btn = $(this);
            const $box = $('#ai-summary-box');
            const $text = $('#ai-summary-text');

            $btn.prop('disabled', true).text('Generando…');

            $.get($box.data('url')).done(function (res) {
                if (!res.summary) {
                    // Sin agente IA configurado el endpoint responde 200 con
                    // summary null: se avisa, no se inventa un resumen.
                    $text.removeClass('d-none').addClass('text-muted')
                        .text('No hay resumen disponible. Revisa que haya un agente de IA configurado.');
                    return;
                }
                $text.removeClass('d-none text-muted').text(res.summary);
                $btn.text('Actualizar');
            }).fail(function () {
                if (window.toastr) { toastr.error('No se pudo generar el resumen'); }
            }).always(function () {
                $btn.prop('disabled', false);
                if ($btn.text() === 'Generando…') { $btn.text('Generar'); }
            });
        });

        // ── Sugerir respuesta con IA ──
        // El borrador NO se envia: solo se escribe en el composer para que el
        // agente lo revise. Ver TicketReplySuggestionService.
        (function () {
            const $btn = $('#ai-suggest-reply-btn');
            if (!$btn.length) return;

            // Etiquetas legibles de las herramientas MCP: al agente le sirve saber
            // "se consulto el pedido", no el slug interno de la tool.
            const SOURCE_LABELS = {
                'get-customer-context': 'Ficha del cliente (ERP y tienda)',
                'get-contact360': 'Historial de soporte',
                'search-orders': 'Pedidos del cliente',
                'get-order-detail': 'Detalle de pedido',
                'get-customer-timeline': 'Cronología del ERP',
                'search-products': 'Catálogo de productos',
                'get-ticket-history': 'Tickets anteriores',
                'search-knowledge': 'Base de conocimiento',
                'list-reply-templates': 'Plantillas de respuesta',
            };

            function escAi(s) { return $('<div>').text(s == null ? '' : s).html(); }

            function renderMeta(suggestion) {
                const $meta = $('#ai-reply-meta');
                const bits = [];

                if (suggestion.template) {
                    bits.push('Basado en <strong>' + escAi(suggestion.template.name) + '</strong>');
                } else {
                    bits.push('Redactado sin plantilla');
                }

                if (suggestion.language) {
                    bits.push('idioma <strong>' + escAi(suggestion.language) + '</strong>');
                }

                let html = '<i class="fas fa-wand-magic-sparkles me-1"></i>' + bits.join(' · ');

                if (suggestion.sources && suggestion.sources.length) {
                    const labels = $.map(suggestion.sources, function (name) {
                        return escAi(SOURCE_LABELS[name] || name);
                    });
                    html += '<div class="text-muted mt-1">Datos consultados: ' + labels.join(', ') + '</div>';
                }

                html += '<div class="text-muted mt-1">Revisa el texto antes de enviarlo: la IA puede equivocarse.</div>';

                $meta.html(html).removeClass('d-none');
            }

            function request() {
                const original = $btn.html();
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Redactando…');

                $.ajax({
                    url: $btn.data('url'),
                    method: 'POST',
                    dataType: 'json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                }).done(function (res) {
                    if (!res.suggestion) {
                        if (window.toastr) { toastr.info(res.message || 'No hay sugerencia disponible'); }
                        return;
                    }
                    $('#reply-body').val(res.suggestion.draft).trigger('input').focus();
                    renderMeta(res.suggestion);
                }).fail(function (xhr) {
                    const msg = xhr.status === 429
                        ? 'Demasiadas sugerencias seguidas. Espera un momento.'
                        : 'No se pudo generar la sugerencia';
                    if (window.toastr) { toastr.error(msg); }
                }).always(function () {
                    $btn.prop('disabled', false).html(original);
                });
            }

            $btn.on('click', function () {
                // Un borrador a medio escribir es trabajo del agente: no se pisa
                // sin preguntar.
                if ($('#reply-body').val().trim() && window.__confirm) {
                    window.__confirm('Ya has escrito una respuesta. ¿Reemplazarla por la sugerencia de la IA?', request);
                    return;
                }
                request();
            });
        })();

        // ── Disputar la revisión de calidad ──
        $('#ai-review-dispute').on('click', function () {
            const $btn = $(this);
            const $box = $('#ai-review-box');

            // Se pide el motivo: una disputa sin razón no le sirve a quien
            // después mire por qué la métrica no cuadra.
            const note = window.prompt('¿Qué no encaja en esta revisión? (opcional)') ;

            if (note === null) return;

            $btn.prop('disabled', true);

            $.ajax({
                url: $box.data('url'),
                method: 'POST',
                dataType: 'json',
                data: { note: note },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (res) {
                $btn.replaceWith('<div class="small text-muted mt-2">Disputada — no cuenta para las medias.</div>');
                if (window.toastr) { toastr.success(res.message); }
            }).fail(function () {
                $btn.prop('disabled', false);
                if (window.toastr) { toastr.error('No se pudo registrar la disputa'); }
            });
        });

        // ── Posibles duplicados ──
        // Se carga en diferido: la ficha no debe esperar a una búsqueda por
        // similitud. Si no hay candidatos, la caja no llega a aparecer.
        (function () {
            const $box = $('#ai-duplicates-box');
            if (!$box.length) return;

            function escD(s) { return $('<div>').text(s == null ? '' : s).html(); }

            $.get($box.data('url')).done(function (res) {
                if (!res.duplicates || !res.duplicates.length) return;

                $('#ai-duplicates-list').html($.map(res.duplicates, function (d) {
                    const pct = Math.round(d.similarity * 100);
                    const who = d.same_customer ? 'mismo cliente' : 'otro cliente';
                    return '<div class="d-flex align-items-start justify-content-between gap-2 py-1 border-bottom">'
                        + '<div>'
                        + '<a href="' + escD(d.url) + '" class="text-decoration-none fw-semibold">#'
                        + escD(d.ticket_number) + '</a>'
                        + '<div class="text-muted">' + escD(d.subject) + '</div>'
                        + '<div class="text-muted">' + pct + '% · ' + who
                        + (d.status ? ' · ' + escD(d.status) : '') + '</div>'
                        + '</div></div>';
                }).join(''));

                $box.removeClass('d-none');
            });
        })();

        // ── Guardia de salida: revisar el borrador antes de enviar ──
        // Avisa, NUNCA bloquea. Cualquier fallo (proveedor caído, sin agente
        // IA, timeout) deja pasar el envío: impedir contestar a un cliente es
        // peor que el problema que esto resuelve. Ver ReplyGuardService.
        (function () {
            const $modal = $('#replyGuardModal');
            const $form = $('#reply-form');
            if (!$modal.length || !$form.length) return;

            const KIND_LABELS = {
                dato_no_verificable: 'Dato sin respaldo',
                promesa: 'Promesa sin plantilla',
                contradiccion: 'Contradice el hilo',
            };

            let cleared = false;
            let modalInstance = null;

            function escG(s) { return $('<div>').text(s == null ? '' : s).html(); }

            function render(warnings) {
                $('#reply-guard-warnings').html($.map(warnings, function (w) {
                    let item = '<div class="border rounded p-2 mb-2">'
                        + '<div class="fw-semibold small">' + escG(KIND_LABELS[w.kind] || w.kind) + '</div>'
                        + '<div class="small">' + escG(w.message) + '</div>';
                    if (w.excerpt) {
                        item += '<div class="small text-muted mt-1">«' + escG(w.excerpt) + '»</div>';
                    }
                    return item + '</div>';
                }).join(''));
            }

            function submitForm() {
                cleared = true;
                $form.trigger('submit');
            }

            $form.on('submit', function (e) {
                // Ya revisado, o nota interna (el cliente no la ve), o sin
                // cuerpo: no hay nada que revisar.
                if (cleared || $('#isInternal').is(':checked')) return;

                const body = $('#reply-body').val();
                if (!body || !body.trim()) return;

                e.preventDefault();

                const $submit = $form.find('[type="submit"]');
                $submit.prop('disabled', true);

                $.ajax({
                    url: $modal.data('url'),
                    method: 'POST',
                    dataType: 'json',
                    data: { body: body },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    timeout: 20000,
                }).done(function (res) {
                    if (!res.warnings || !res.warnings.length) {
                        submitForm();
                        return;
                    }
                    render(res.warnings);
                    modalInstance = modalInstance || new bootstrap.Modal($modal[0]);
                    modalInstance.show();
                }).fail(function () {
                    // Sin revisión posible, se envía igual.
                    submitForm();
                }).always(function () {
                    $submit.prop('disabled', false);
                });
            });

            $('#reply-guard-send').on('click', function () {
                if (modalInstance) { modalInstance.hide(); }
                submitForm();
            });

            $('#reply-guard-review').on('click', function () {
                if (modalInstance) { modalInstance.hide(); }
                $('#reply-body').focus();
            });
        })();

        // ── Seguimientos programados ──
        const $fBox = $('#followups-box');
        function escF(s) { return $('<div>').text(s == null ? '' : s).html(); }

        $('#add-followup').on('click', function () {
            const when = $('#followup-when').val();
            if (!when) { if (window.toastr) { toastr.info('Elige fecha y hora'); } return; }
            const note = $('#followup-note').val();
            const $btn = $(this).prop('disabled', true);
            $.ajax({
                url: $fBox.data('store-url'),
                method: 'POST',
                dataType: 'json',
                data: { scheduled_at: when, note: note },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (r) {
                const f = (r && r.data) || {};
                const dt = new Date(f.scheduled_at).toLocaleString();
                $('#followups-empty').remove();
                $('#followups-list').append(
                    '<div class="d-flex align-items-start justify-content-between gap-2 py-1 border-bottom" data-followup-id="' + f.id + '">'
                    + '<div class="small"><span class="fw-semibold">' + escF(dt) + '</span>'
                    + (f.note ? '<div class="text-muted">' + escF(f.note) + '</div>' : '') + '</div>'
                    + '<button type="button" class="btn btn-sm btn-light py-0 px-1 cancel-followup" title="Cancelar"><i class="fas fa-xmark"></i></button></div>');
                $('#followup-when').val(''); $('#followup-note').val('');
                if (window.toastr) { toastr.success('Seguimiento programado'); }
            }).fail(function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.errors
                    ? Object.values(xhr.responseJSON.errors)[0][0] : 'No se pudo programar';
                if (window.toastr) { toastr.error(msg); }
            }).always(function () { $btn.prop('disabled', false); });
        });

        $(document).on('click', '.cancel-followup', function () {
            const $row = $(this).closest('[data-followup-id]');
            $.ajax({
                url: $fBox.data('base-url') + '/' + $row.data('followup-id'),
                method: 'DELETE',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function () {
                $row.remove();
                if (!$('#followups-list').children().length) {
                    $('#followups-list').html('<div class="text-muted small py-1" id="followups-empty">Sin seguimientos programados.</div>');
                }
                if (window.toastr) { toastr.success('Seguimiento cancelado'); }
            }).fail(function () { if (window.toastr) { toastr.error('No se pudo cancelar'); } });
        });

        // ── Traducir el borrador de respuesta ──
        $('#translate-reply-btn').on('click', function () {
            const $btn = $(this);
            const $t = $('#reply-body');
            const text = ($t.val() || '').trim();
            if (!text) { if (window.toastr) { toastr.info('Escribe algo para traducir'); } return; }
            $btn.prop('disabled', true);
            $.ajax({
                url: $btn.data('url'),
                method: 'POST',
                dataType: 'json',
                data: { text: text, target_lang: (document.documentElement.lang || 'es') },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (r) {
                if (r && r.text) { $t.val(r.text).trigger('input'); }
                if (window.toastr) {
                    r && r.translated ? toastr.success('Borrador traducido') : toastr.info('Traducción no disponible');
                }
            }).fail(function () {
                if (window.toastr) { toastr.error('No se pudo traducir'); }
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        // ── Redactar/programar un correo suelto ligado a este ticket ──
        // Mismo endpoint que usaba la antigua bandeja global de emails
        // (TicketMailDispatcher::send() vía TicketMailsController::store()),
        // ahora también alcanzable desde la propia ficha del ticket.
        $('#tkt-compose-mail-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var formData = new FormData(this);

            var cc = ($('#tkt-compose-cc').val() || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            var bcc = ($('#tkt-compose-bcc').val() || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            cc.forEach(function (email) { formData.append('cc[]', email); });
            bcc.forEach(function (email) { formData.append('bcc[]', email); });

            var $submit = $('#tkt-compose-submit').prop('disabled', true);
            $('#tkt-compose-error').hide();

            $.ajax({
                url: window.TicketDetailConfig.emailComposeStoreUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (resp) {
                if (window.toastr) { toastr.success(resp.message || 'Email enviado'); }
                var modalEl = document.getElementById('tkt-compose-mail-modal');
                var instance = bootstrap.Modal.getInstance(modalEl);
                if (instance) { instance.hide(); }
                window.location.reload();
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo enviar el email';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                }
                $('#tkt-compose-error').text(msg).show();
            }).always(function () {
                $submit.prop('disabled', false);
            });
        });

        // ── Selector de plantilla dentro del compose de email ────────────
        // Reutiliza TicketMailsController::templates() (macros con acción
        // "reply"), pasando ya el ticket_id por query string desde Blade para
        // que el body llegue con las variables del ticket interpoladas en
        // servidor. Se pide una única vez por carga de página (cache en
        // templatesCache) porque las plantillas no cambian mientras el agente
        // tiene el ticket abierto.
        var templatesCache = null;

        function loadEmailTemplates() {
            if (templatesCache !== null) { return; }
            templatesCache = []; // marca "ya en curso / cargado" para no repetir la petición

            $.getJSON(CFG.emailTemplatesUrl).done(function (data) {
                templatesCache = (data && data.templates) || [];
                var $select = $('#tkt-compose-template');
                $.each(templatesCache, function (i, tpl) {
                    $select.append($('<option>', { value: i, text: tpl.name }));
                });
            }).fail(function () {
                // Sin plantillas no bloqueamos el compose; el agente sigue pudiendo escribir a mano.
                if (window.toastr) { toastr.error('No se pudieron cargar las plantillas'); }
            });
        }

        $('#tkt-compose-mail-modal').on('show.bs.modal', loadEmailTemplates);

        $('#tkt-compose-template').on('change', function () {
            var index = $(this).val();
            if (index === '' || !templatesCache || !templatesCache[index]) { return; }

            var tpl = templatesCache[index];
            var $subject = $('#tkt-compose-mail-form [name="subject"]');

            // El asunto no se pisa si el agente ya escribió el suyo; el cuerpo
            // sí se sustituye siempre, porque elegir una plantilla es una
            // acción explícita para cargar su contenido en el mensaje.
            // tpl.subject es el asunto real configurado en la macro (opcional,
            // ver Macro::actionSpecs()['reply']['optional']); las plantillas
            // que no lo traen (macros antiguas) siguen cayendo al nombre de
            // la macro como aproximación, igual que antes de que existiera.
            if (!$subject.val().trim()) {
                $subject.val(tpl.subject ? tpl.subject : tpl.name);
            }
            $('#tkt-compose-mail-form [name="body"]').val(tpl.body);
        });
    });

    $(function () {
        var CSRF = { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') };
        function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

        // ── Colisión de agentes: heartbeat cada 15s ──
        var $collision = $('#collision-banner');
        if ($collision.length) {
            function renderViewers(viewers) {
                if (!viewers || !viewers.length) { $collision.addClass('d-none'); return; }
                var parts = viewers.map(function (v) {
                    return esc(v.name) + (v.action === 'replying' ? ' (respondiendo)' : ' (viendo)');
                });
                $('#collision-text').text(parts.join(', ') + ' en este ticket');
                $collision.removeClass('d-none');
            }
            function beat() {
                var action = ($('#reply-body').val() || '').trim().length ? 'replying' : 'viewing';
                $.ajax({
                    url: $collision.data('heartbeat-url'), method: 'POST', dataType: 'json',
                    data: { action: action }, headers: CSRF,
                }).done(function (r) { renderViewers(r && r.data ? r.data.viewers : []); });
            }
            beat();
            setInterval(beat, 15000);
            // La salida no se notifica activamente: la presencia se purga sola a los
            // 35s (TTL del servicio), evitando un beacon que no puede cumplir CSRF/DELETE.
        }

        // ── Posponer / reactivar ticket (snooze) ──
        var $snoozeActions = $('#snooze-actions');
        $('#snooze-confirm-btn').on('click', function () {
            var until = $('#snooze-until').val();
            if (!until) { toastr.info('Elige fecha y hora'); return; }
            var $btn = $(this).prop('disabled', true);
            $.ajax({ url: $snoozeActions.data('snooze-url'), method: 'POST', dataType: 'json', data: { snoozed_until: until }, headers: CSRF })
            .done(function () { toastr.success('Ticket pospuesto'); location.reload(); })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors)[0][0] : 'No se pudo posponer');
                $btn.prop('disabled', false);
            });
        });
        $('#unsnooze-btn').on('click', function () {
            var $btn = $(this).prop('disabled', true);
            $.ajax({ url: $snoozeActions.data('unsnooze-url'), method: 'DELETE', dataType: 'json', headers: CSRF })
            .done(function () { toastr.success('Ticket reactivado'); location.reload(); })
            .fail(function () { toastr.error('No se pudo reactivar'); $btn.prop('disabled', false); });
        });

        // ── Respuestas programadas (send later) ──
        var $sch = $('#scheduled-box');
        function schRow(r) {
            var dt = new Date(r.deliver_at).toLocaleString();
            return '<div class="d-flex align-items-start justify-content-between gap-2 py-1 border-bottom" data-scheduled-id="' + r.id + '">'
                + '<div class="small"><span class="fw-semibold">' + esc(dt) + '</span>'
                + '<div class="text-muted">' + esc((r.body || '').substring(0, 60)) + '</div></div>'
                + '<button type="button" class="btn btn-sm btn-light py-0 px-1 cancel-scheduled" title="Cancelar"><i class="fas fa-xmark"></i></button></div>';
        }
        function loadScheduled() {
            $.getJSON($sch.data('index-url')).done(function (r) {
                var list = (r && r.data) || [];
                if (!list.length) { $('#scheduled-list').html('<div class="text-muted small py-1" id="scheduled-empty">Sin respuestas programadas.</div>'); return; }
                $('#scheduled-list').html(list.map(schRow).join(''));
            });
        }
        if ($sch.length) { loadScheduled(); }
        $('#add-scheduled').on('click', function () {
            var body = ($('#scheduled-body').val() || '').trim();
            var when = $('#scheduled-when').val();
            if (!body) { toastr.info('Escribe la respuesta'); return; }
            if (!when) { toastr.info('Elige fecha y hora'); return; }
            var $btn = $(this).prop('disabled', true);
            $.ajax({ url: $sch.data('store-url'), method: 'POST', dataType: 'json', data: { body: body, deliver_at: when }, headers: CSRF })
            .done(function () {
                $('#scheduled-body').val(''); $('#scheduled-when').val('');
                toastr.success('Respuesta programada'); loadScheduled();
            }).fail(function (xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors)[0][0] : 'No se pudo programar');
            }).always(function () { $btn.prop('disabled', false); });
        });
        $(document).on('click', '.cancel-scheduled', function () {
            var $row = $(this).closest('[data-scheduled-id]');
            $.ajax({ url: $sch.data('base-url') + '/' + $row.data('scheduled-id'), method: 'DELETE', dataType: 'json', headers: CSRF })
            .done(function () { $row.remove(); if (!$('#scheduled-list').children().length) { loadScheduled(); } toastr.success('Cancelada'); })
            .fail(function () { toastr.error('No se pudo cancelar'); });
        });

        // ── Conversaciones laterales ──
        var $side = $('#side-box');
        $('#side-participant-type').on('change', function () {
            var team = $(this).val() === 'team';
            $('#side-team-wrap').toggleClass('d-none', !team);
            $('#side-email-wrap').toggleClass('d-none', team);
        });
        function sideRow(s) {
            var badge = s.status === 'open' ? '<span class="badge bg-success-subtle text-success">abierta</span>' : '<span class="badge bg-secondary-subtle text-secondary">cerrada</span>';
            var who = s.participant_type === 'team' ? esc(s.participant || 'Compañero') : esc(s.participant_email || '');
            var msgs = (s.messages || []).map(function (m) {
                return '<div class="small text-muted border-start ps-2 mt-1">' + esc(m.body) + '</div>';
            }).join('');
            var reply = s.status === 'open'
                ? '<div class="input-group input-group-sm mt-1"><input type="text" class="form-control side-reply-input" placeholder="Responder..."><button class="btn btn-outline-primary side-reply-btn" type="button"><i class="fas fa-paper-plane"></i></button></div>'
                + '<button type="button" class="btn btn-sm btn-light w-100 mt-1 side-close-btn">Cerrar conversación</button>'
                : '';
            return '<div class="py-2 border-bottom" data-side-id="' + s.id + '">'
                + '<div class="d-flex justify-content-between align-items-center"><span class="small fw-semibold">' + esc(s.subject) + '</span>' + badge + '</div>'
                + '<div class="text-muted small"><i class="fas fa-user me-1"></i>' + who + '</div>' + msgs + reply + '</div>';
        }
        function loadSide() {
            $.getJSON($side.data('index-url')).done(function (r) {
                var list = (r && r.data) || [];
                if (!list.length) { $('#side-list').html('<div class="text-muted small py-1" id="side-empty">Sin conversaciones laterales.</div>'); return; }
                $('#side-list').html(list.map(sideRow).join(''));
            });
        }
        if ($side.length) { loadSide(); }
        $('#side-create-btn').on('click', function () {
            var type = $('#side-participant-type').val();
            var payload = {
                subject: ($('#side-subject').val() || '').trim(),
                participant_type: type,
                body: ($('#side-message').val() || '').trim(),
            };
            if (type === 'team') { payload.participant_user_id = $('#side-participant-user').val(); }
            else { payload.participant_email = ($('#side-participant-email').val() || '').trim(); }
            var $btn = $(this).prop('disabled', true);
            $.ajax({ url: $side.data('store-url'), method: 'POST', dataType: 'json', data: payload, headers: CSRF })
            .done(function () {
                $('#sideConversationModal').modal('hide');
                $('#side-subject, #side-message, #side-participant-email').val('');
                toastr.success('Conversación lateral creada'); loadSide();
            }).fail(function (xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors)[0][0] : 'No se pudo crear');
            }).always(function () { $btn.prop('disabled', false); });
        });
        $(document).on('click', '.side-reply-btn', function () {
            var $wrap = $(this).closest('[data-side-id]');
            var $input = $wrap.find('.side-reply-input');
            var body = ($input.val() || '').trim();
            if (!body) { return; }
            $.ajax({ url: $side.data('base-url') + '/' + $wrap.data('side-id') + '/messages', method: 'POST', dataType: 'json', data: { body: body }, headers: CSRF })
            .done(function () { $input.val(''); toastr.success('Mensaje enviado'); loadSide(); })
            .fail(function () { toastr.error('No se pudo enviar'); });
        });
        $(document).on('click', '.side-close-btn', function () {
            var $wrap = $(this).closest('[data-side-id]');
            $.ajax({ url: $side.data('base-url') + '/' + $wrap.data('side-id') + '/close', method: 'POST', dataType: 'json', headers: CSRF })
            .done(function () { toastr.success('Conversación cerrada'); loadSide(); })
            .fail(function () { toastr.error('No se pudo cerrar'); });
        });
    });
})();
