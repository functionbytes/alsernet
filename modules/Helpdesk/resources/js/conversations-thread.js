/**
 * Bandeja v4 - Helpdesk conversations interactions
 * jQuery + Bootstrap 5.3 (sin React/Babel)
 *
 * Parte de un split de public/vendor/helpdesk/conversations.js (7.138 líneas)
 * en varios archivos por responsabilidad. Ver conversations-core.js para los
 * helpers compartidos (openModal/closeModal/escapeHtml/refreshInboxList/...)
 * expuestos en window para que el resto de archivos los usen tal cual.
 */

(function ($) {
    'use strict';

    $(function () {
        // ─── Búsqueda en thread ──────────────────────────────────────
        let searchHits = [];
        let searchIndex = -1;

        function clearSearchHighlights() {
            $('.bv-th-inner mark.bv-search-hit').each(function () {
                const t = document.createTextNode(this.textContent);
                this.parentNode.replaceChild(t, this);
            });
            $('.bv-th-inner').each(function () { this.normalize(); });
            searchHits = [];
            searchIndex = -1;
            $('#bv-th-search-count').text('');
            $('#bv-th-search-prev, #bv-th-search-next').prop('disabled', true);
        }

        function highlightSearchInThread(query) {
            clearSearchHighlights();
            const q = (query || '').trim();
            if (q.length < 2) return;

            const re = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
            const $bubbles = $('.bv-th-inner .bv-bubble');
            const hits = [];

            $bubbles.each(function () {
                // Sólo procesar nodos de texto (sin tocar HTML interno)
                const walker = document.createTreeWalker(this, NodeFilter.SHOW_TEXT, {
                    acceptNode(node) {
                        const p = node.parentElement;
                        if (!p) return NodeFilter.FILTER_REJECT;
                        if (p.closest('.meta, .note-badge, mark, script, style')) return NodeFilter.FILTER_REJECT;
                        return node.nodeValue && re.test(node.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
                    },
                });
                const texts = [];
                let n;
                while ((n = walker.nextNode())) texts.push(n);
                texts.forEach(t => {
                    re.lastIndex = 0;
                    const frag = document.createDocumentFragment();
                    let last = 0;
                    let m;
                    const v = t.nodeValue;
                    while ((m = re.exec(v))) {
                        if (m.index > last) frag.appendChild(document.createTextNode(v.slice(last, m.index)));
                        const mark = document.createElement('mark');
                        mark.className = 'bv-search-hit';
                        mark.textContent = m[0];
                        frag.appendChild(mark);
                        hits.push(mark);
                        last = m.index + m[0].length;
                        if (m.index === re.lastIndex) re.lastIndex++;
                    }
                    if (last < v.length) frag.appendChild(document.createTextNode(v.slice(last)));
                    t.parentNode.replaceChild(frag, t);
                });
            });

            searchHits = hits;
            searchIndex = hits.length ? 0 : -1;
            $('#bv-th-search-count').text(hits.length ? `${searchIndex + 1}/${hits.length}` : 'Sin resultados');
            $('#bv-th-search-prev, #bv-th-search-next').prop('disabled', hits.length < 2);
            if (hits.length) focusSearchHit(0);
        }

        function focusSearchHit(idx) {
            $('mark.bv-search-current').removeClass('bv-search-current');
            const hit = searchHits[idx];
            if (!hit) return;
            hit.classList.add('bv-search-current');
            hit.scrollIntoView({ behavior: 'smooth', block: 'center' });
            $('#bv-th-search-count').text(`${idx + 1}/${searchHits.length}`);
        }

        $(document).on('click', '#bv-th-search-btn', function () {
            $('#bv-th-search').toggleClass('bv-hidden');
            if (!$('#bv-th-search').hasClass('bv-hidden')) {
                $('#bv-th-search-input').trigger('focus');
            } else {
                clearSearchHighlights();
            }
        });

        $(document).on('click', '#bv-th-search-close', function () {
            $('#bv-th-search').addClass('bv-hidden');
            $('#bv-th-search-input').val('');
            clearSearchHighlights();
        });

        $(document).on('input', '#bv-th-search-input', function () {
            highlightSearchInThread($(this).val());
        });

        $(document).on('click', '#bv-th-search-next', function () {
            if (!searchHits.length) return;
            searchIndex = (searchIndex + 1) % searchHits.length;
            focusSearchHit(searchIndex);
        });

        $(document).on('click', '#bv-th-search-prev', function () {
            if (!searchHits.length) return;
            searchIndex = (searchIndex - 1 + searchHits.length) % searchHits.length;
            focusSearchHit(searchIndex);
        });

        $(document).on('keydown', '#bv-th-search-input', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(e.shiftKey ? '#bv-th-search-prev' : '#bv-th-search-next').click();
            } else if (e.key === 'Escape') {
                $('#bv-th-search-close').click();
            }
        });


        // ─── Tabs composer ───────────────────────────────────────────
        $(document).on('click', '.bv-composer-tab', function () {
            const $tab = $(this);
            const target = $tab.data('bv-tab');
            $tab.siblings().removeClass('on');
            $tab.addClass('on');
            $('#bv-composer-box').toggleClass('note', target === 'note');

            // Placeholder de nota interna en vez del de respuesta: "/ para
            // respuestas rápidas, @ para mencionar" no aplica y conviene
            // recordar que una nota no llega al cliente. El original se
            // guarda en data() la primera vez para no duplicar la traducción
            // aquí (viene del atributo data-bv-note-placeholder en el blade).
            const $ta = $('.bv-composer-input');
            if ($ta.length) {
                if ($ta.data('bv-default-placeholder') === undefined) {
                    $ta.data('bv-default-placeholder', $ta.attr('placeholder'));
                }
                const notePlaceholder = $ta.data('bv-note-placeholder');
                $ta.attr('placeholder', (target === 'note' && notePlaceholder) ? notePlaceholder : $ta.data('bv-default-placeholder'));
            }
            // El textarea de texto libre no aporta nada mientras se elige una
            // plantilla HSM o se traduce — mostrarlo a la vez solo comprimia el
            // area de mensajes de arriba a una franja minima (parecia que el
            // panel "chocaba" con los tabs y el composer).
            $('#bv-composer-box').toggle(target !== 'hsm' && target !== 'translate');

            if (target === 'hsm') {
                $('#bv-translate-panel').removeClass('on');
                $('#bv-hsm-picker').addClass('on');
            } else if (target === 'translate') {
                $('#bv-hsm-picker').removeClass('on');
                $('#bv-translate-panel').addClass('on');
            } else {
                $('#bv-hsm-picker').removeClass('on');
                $('#bv-translate-panel').removeClass('on');
            }
        });

        // ─── Cerrar paneles HSM y Traducción ─────────────────────────
        function activateReplyTab() {
            const $reply = $('.bv-composer-tab[data-bv-tab="reply"]');
            $reply.siblings().removeClass('on');
            $reply.addClass('on');
            $('#bv-composer-box').removeClass('note').show();
        }

        $(document).on('click', '#bv-hsm-close, #bv-hsm-close-2', function () {
            $('#bv-hsm-picker').removeClass('on');
            activateReplyTab();
        });

        // ─── HSM Templates ────────────────────────────────────────────
        var hsmTemplates = [];
        var hsmSelectedId = null;
        var hsmPreviewBody = '';
        // Preferencia de "ver todos los idiomas" (ver applyHsmFilters). Se
        // resetea en cada cambio de conversación (evento pane:loaded) para que
        // no se arrastre de un cliente a otro con idioma distinto.
        var hsmShowAllLangs = false;

        function hsmListStatus(cls, icon, text) {
            var $list = $('#bv-hsm-list');
            $list.find('.bv-hsm-row, .bv-hsm-list-status').remove();
            $list.append('<div class="bv-hsm-list-status' + (cls ? ' ' + cls : '') + '"><i class="' + icon + '"></i>' + text + '</div>');
        }

        function loadHsmTemplates() {
            if (hsmTemplates.length) { applyHsmFilters($('#bv-hsm-search').val()); return; }
            hsmListStatus('', 'fas fa-spinner fa-spin', 'Cargando plantillas…');
            $.ajax({
                url: '/panel/helpdesk/hsm-templates',
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (resp) {
                hsmTemplates = resp.templates || [];
                applyHsmFilters($('#bv-hsm-search').val());
            }).fail(function () {
                hsmListStatus('is-error', 'fas fa-triangle-exclamation', 'Error al cargar plantillas');
            });
        }

        $(document).on('pane:loaded', function () {
            hsmShowAllLangs = false;
            hsmSelectedId = null;
        });

        var HSM_CATEGORY_LABELS = { marketing: 'Marketing', utility: 'Utility', authentication: 'Auth' };
        var HSM_HEADER_ICONS = { image: 'fa-image', video: 'fa-video', document: 'fa-file' };

        // Normaliza "en_US"/"pt_BR"/"ES" -> "en"/"pt"/"es" para comparar con
        // customer.language (que solo guarda es/en/fr/de/pt/it, sin región).
        function hsmLangPrefix(lang) {
            return (lang || '').toString().split(/[_-]/)[0].toLowerCase();
        }

        // Reordena: primero las plantillas cuyo idioma registrado en Meta
        // coincide con el idioma del contacto (evita elegir por error una
        // plantilla en otro idioma — Meta rechaza el envio con error 132001
        // si el idioma no coincide con el registrado para esa plantilla).
        // Si ninguna coincide, se deja el orden original (no hay nada mejor
        // que ofrecer) y el badge ambar avisa igual en cada fila.
        function sortHsmByCustomerLanguage(list, customerLang) {
            if (!customerLang) return list;
            var matched = [];
            var rest = [];
            list.forEach(function (t) {
                (hsmLangPrefix(t.language) === customerLang ? matched : rest).push(t);
            });
            return matched.length ? matched.concat(rest) : list;
        }

        function renderHsmList(list, query, customerLang) {
            var $list = $('#bv-hsm-list');
            $list.find('.bv-hsm-row, .bv-hsm-list-status').remove();
            if (!list.length) {
                // Distinguimos "no hay ninguna plantilla" de "tu busqueda no
                // encontro nada" — antes ambos casos mostraban el mismo texto
                // y un agente buscando algo mal escrito podia creer que el
                // canal no tiene ninguna plantilla aprobada.
                if (query) {
                    hsmListStatus('', 'fas fa-magnifying-glass', 'Sin resultados para "' + escapeHtml(query) + '"');
                } else {
                    hsmListStatus('', 'fas fa-inbox', 'Sin plantillas aprobadas');
                }
                return;
            }
            var sorted = sortHsmByCustomerLanguage(list, customerLang);
            sorted.forEach(function (t, i) {
                var catLabel = HSM_CATEGORY_LABELS[t.category] || '';
                var catBadge = catLabel
                    ? '<span class="bv-hsm-badge-category bv-hsm-badge-category--' + t.category + '">' + catLabel + '</span>'
                    : '';
                var tLang = hsmLangPrefix(t.language);
                var isMismatch = customerLang && tLang && tLang !== customerLang;
                var langBadge = tLang
                    ? '<span class="bv-hsm-badge-lang' + (isMismatch ? ' bv-hsm-badge-lang--mismatch' : '') + '"' +
                        (isMismatch ? ' title="El contacto usa \'' + escapeHtml(customerLang) + '\', esta plantilla esta registrada en \'' + escapeHtml(tLang) + '\'">' : '>') +
                        tLang.toUpperCase() + '</span>'
                    : '';
                // Fragmento del cuerpo en la fila: el agente ya intuye de que va la
                // plantilla sin tener que abrirlas una a una para leer la vista previa.
                var excerpt = (t.body || '').replace(/\\n/g, ' ').replace(/\s+/g, ' ').trim();
                if (excerpt.length > 90) { excerpt = excerpt.slice(0, 90) + '…'; }
                var excerptHtml = excerpt ? '<div class="excerpt">' + escapeHtml(excerpt) + '</div>' : '';
                var html = '<div class="bv-hsm-row' + (i === 0 ? ' on' : '') + (isMismatch ? ' bv-hsm-row--lang-mismatch' : '') + '" data-hsm-id="' + t.id + '">' +
                    '<div class="nm">' + escapeHtml(t.name) + '</div>' +
                    excerptHtml +
                    '<div class="meta">' + langBadge + catBadge + '<span class="bv-hsm-badge-approved">APPROVED</span></div></div>';
                $list.append(html);
            });
            if (sorted.length) selectHsmTemplate(sorted[0].id);
        }

        function getCustomerLang() {
            return hsmLangPrefix(($('.bv-right').data('customer-language') || '').toString());
        }

        // Busca tanto en el nombre como en el cuerpo de la plantilla — un agente
        // que recuerda "reembolso" o "envío" pero no el nombre técnico interno
        // (ej. "postventa_02_es") antes no encontraba nada.
        function hsmMatchesQuery(t, q) {
            if (!q) return true;
            var name = (t.name || '').toLowerCase();
            var body = (t.body || '').toLowerCase();
            return name.includes(q) || body.includes(q);
        }

        function renderHsmLangToggle(hasLangMatch, otherLangCount) {
            var $toggle = $('#bv-hsm-lang-toggle');
            $toggle.toggleClass('d-none', !hasLangMatch);
            if (!hasLangMatch) return;
            $toggle.find('#bv-hsm-lang-toggle-input').prop('checked', hsmShowAllLangs);
            $toggle.find('.bv-hsm-lang-toggle-label').text('Mostrar todos los idiomas (' + otherLangCount + ')');
        }

        // Filtro por idioma + texto combinados. Por defecto solo se listan las
        // plantillas en el idioma del cliente (evita elegir sin querer una en
        // otro idioma — ver nota de error 132001 en sortHsmByCustomerLanguage).
        // Si el cliente no tiene ninguna plantilla en su idioma no hay nada que
        // filtrar y se muestran todas (mismo fallback que ya usaba el orden).
        function applyHsmFilters(query) {
            var q = (query || '').toLowerCase();
            var customerLang = getCustomerLang();
            var textFiltered = hsmTemplates.filter(function (t) { return hsmMatchesQuery(t, q); });
            var hasLangMatch = !!customerLang && hsmTemplates.some(function (t) {
                return hsmLangPrefix(t.language) === customerLang;
            });
            var otherLangCount = hasLangMatch
                ? hsmTemplates.filter(function (t) { return hsmLangPrefix(t.language) !== customerLang; }).length
                : 0;

            renderHsmLangToggle(hasLangMatch, otherLangCount);

            var shown = (hasLangMatch && !hsmShowAllLangs)
                ? textFiltered.filter(function (t) { return hsmLangPrefix(t.language) === customerLang; })
                : textFiltered;

            renderHsmList(shown, q, customerLang);
        }

        var HSM_GREETING_RE = /\b(hola|hi|hello)\s*\{\{(\d+)\}\}/i;
        // El hueco entre "caso" y "{{n}}" solo puede ser espacios/":#*" (ej.
        // "caso *#{{2}}*", "caso es: *{{2}}*") — NO letras. Con [^{}]{0,20}
        // (sin restringir a puntuacion) "✅ Caso resuelto\nHola {{1}}" hacia
        // falso positivo: "resuelto" tambien caia dentro de la ventana de 20
        // caracteres y el {{1}} del saludo (nombre del cliente) se marcaba
        // por error como "numero de caso", pisando el hint correcto.
        var HSM_CASE_RE = /\bcaso\b(?:\s+es)?[\s:#*]{0,10}\{\{(\d+)\}\}/i;

        // Best-effort: detecta que variable es "el nombre del cliente" o "el
        // numero de caso" mirando el texto que las rodea en la propia
        // plantilla (no hay metadata de tipo por variable, solo {{n}}
        // posicionales) y las prellena con datos que ya tenemos en pantalla
        // (nombre del cliente del panel derecho, id de la conversacion). El
        // agente sigue pudiendo editarlas — es un punto de partida, no un
        // valor confirmado (por eso se resaltan y llevan tooltip).
        function hsmAutoFillHints(t) {
            var hints = {};
            var fullText = ((t.header_type === 'text' ? t.header_value : '') || '') + '\n' + (t.body || '');

            var customerName = ($('.bv-right').data('customer-name') || '').toString().trim();
            var greetingMatch = fullText.match(HSM_GREETING_RE);
            if (customerName && greetingMatch) { hints[greetingMatch[2]] = customerName; }

            var conversationId = $('.bv-composer').data('bv-conversation-id');
            var caseMatch = fullText.match(HSM_CASE_RE);
            if (conversationId && caseMatch && !hints[caseMatch[1]]) { hints[caseMatch[1]] = '#' + conversationId; }

            return hints;
        }

        function selectHsmTemplate(id) {
            var t = hsmTemplates.find(function (x) { return x.id == id; });
            if (!t) return;
            hsmSelectedId = id;
            // Algunas plantillas quedaron guardadas con la secuencia literal "\n"
            // (backslash + n) en vez de un salto de linea real — se veian tal
            // cual en la vista previa. Normalizamos ambos casos antes de pintar.
            hsmPreviewBody = (t.body || 'Sin contenido').replace(/\\n/g, '\n');
            var varsHtml = '';
            for (var i = 1; i <= (t.param_count || 0); i++) {
                varsHtml += '<div class="bv-hsm-var-row">' +
                    '<span class="bv-hsm-var-lbl">{{' + i + '}}</span>' +
                    '<input type="text" class="bv-hsm-var-input" data-hsm-var-idx="' + i + '" placeholder="Variable ' + i + '">' +
                '</div>';
            }
            $('#bv-hsm-vars-list').html(varsHtml);
            // Plantilla sin variables: no tiene sentido mostrar el bloque
            // "VARIABLES / Sin variables" vacío, solo ocupa espacio.
            $('#bv-hsm-vars').toggleClass('d-none', !varsHtml);

            // .val() en vez de meter el valor en el HTML de arriba: el nombre
            // del cliente es dato de usuario y podria traer comillas u otros
            // caracteres que rompan el atributo value="..." si se concatenan
            // como string (escapeHtml no escapa comillas, solo &<>).
            var hints = hsmAutoFillHints(t);
            Object.keys(hints).forEach(function (idx) {
                $('.bv-hsm-var-input[data-hsm-var-idx="' + idx + '"]')
                    .val(hints[idx])
                    .addClass('bv-hsm-var-input--auto')
                    .attr('title', 'Prellenado automáticamente — revisa antes de enviar');
            });

            renderHsmPreview();
        }

        // Sustituye {{n}} por el valor tecleado en cada variable (o lo deja tal
        // cual si aun esta vacia) para que la "VISTA PREVIA" muestre exactamente
        // lo que va a recibir el cliente, no placeholders genericos. El header
        // de la plantilla puede traer su propia variable (ej. "Hola {{1}} 👋")
        // — antes no se pintaba en absoluto y esos {{n}} quedaban invisibles.
        function renderHsmPreview() {
            var t = hsmTemplates.find(function (x) { return x.id == hsmSelectedId; });
            if (!t) return;

            var values = {};
            $('.bv-hsm-var-input').each(function () {
                var val = ($(this).val() || '').trim();
                if (val) { values[$(this).data('hsm-var-idx')] = val; }
            });
            function substitute(raw) {
                var text = raw || '';
                Object.keys(values).forEach(function (idx) {
                    text = text.split('{{' + idx + '}}').join(values[idx]);
                });
                return text;
            }

            var $header = $('#bv-hsm-preview-header');
            if (t.header_type === 'text' && t.header_value) {
                $header.html(escapeHtml(substitute(t.header_value))).show();
            } else if (t.header_type && HSM_HEADER_ICONS[t.header_type]) {
                var label = t.header_type.charAt(0).toUpperCase() + t.header_type.slice(1);
                $header.html('<i class="fas ' + HSM_HEADER_ICONS[t.header_type] + '"></i> ' + label).show();
            } else {
                $header.hide().empty();
            }

            $('#bv-hsm-preview-text').html(renderWhatsAppMarkup(escapeHtml(substitute(hsmPreviewBody))).replace(/\n/g, '<br>'));

            var $footer = $('#bv-hsm-preview-footer');
            if (t.footer_text) { $footer.text(t.footer_text).show(); } else { $footer.hide().empty(); }
        }

        $(document).on('input', '.bv-hsm-var-input', function () {
            // El agente edito manualmente un valor prellenado — deja de ser
            // una sugerencia sin revisar, ya la reviso (o la corrigio) el.
            $(this).removeClass('is-invalid bv-hsm-var-input--auto').removeAttr('title');
            renderHsmPreview();
        });

        $(document).on('click', '.bv-composer-tab[data-bv-tab="hsm"]', function () {
            loadHsmTemplates();
        });

        $(document).on('click', '.bv-hsm-row', function () {
            $(this).siblings('.bv-hsm-row').removeClass('on');
            $(this).addClass('on');
            selectHsmTemplate($(this).data('hsm-id'));
        });

        $(document).on('input', '#bv-hsm-search', function () {
            applyHsmFilters($(this).val());
        });

        $(document).on('change', '#bv-hsm-lang-toggle-input', function () {
            hsmShowAllLangs = $(this).is(':checked');
            applyHsmFilters($('#bv-hsm-search').val());
        });

        // Navegacion por teclado: flechas para moverse por la lista filtrada,
        // Enter para saltar directo al primer campo de variable (o al boton
        // de insertar si la plantilla no tiene variables) sin soltar el teclado.
        $(document).on('keydown', '#bv-hsm-search', function (e) {
            var $rows = $('#bv-hsm-list .bv-hsm-row');
            if (!$rows.length) return;

            if (e.key === 'Enter') {
                e.preventDefault();
                var $firstVar = $('.bv-hsm-var-input').first();
                ($firstVar.length ? $firstVar : $('#bv-hsm-insert')).trigger('focus');
                return;
            }
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
            e.preventDefault();

            var idx = $rows.index($rows.filter('.on'));
            idx = e.key === 'ArrowDown' ? (idx + 1) % $rows.length : (idx - 1 + $rows.length) % $rows.length;

            var $next = $rows.eq(idx);
            $rows.removeClass('on');
            $next.addClass('on');
            selectHsmTemplate($next.data('hsm-id'));
            $next[0].scrollIntoView({ block: 'nearest' });
        });

        $(document).on('click', '#bv-hsm-insert', function () {
            var t = hsmTemplates.find(function (x) { return x.id == hsmSelectedId; });
            if (!t) { if (window.toastr) toastr.warning('Selecciona una plantilla'); return; }
            var sendUrl = $('.bv-composer').data('bv-send-hsm-url');
            if (!sendUrl) { if (window.toastr) toastr.error('No hay conversación activa'); return; }

            var variables = [];
            var $firstInvalid = null;
            $('.bv-hsm-var-input').each(function () {
                var val = ($(this).val() || '').trim();
                $(this).toggleClass('is-invalid', !val);
                if (!val && !$firstInvalid) { $firstInvalid = $(this); }
                variables.push(val);
            });
            if ($firstInvalid) {
                if (window.toastr) toastr.warning('Completa todas las variables de la plantilla');
                $firstInvalid.trigger('focus');
                return;
            }

            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: sendUrl,
                method: 'POST',
                dataType: 'json',
                // external_id es el nombre tecnico registrado en Meta; t.name es solo la
                // etiqueta amigable — mandarla rompe el envio real (132001 en Meta).
                data: { template_name: t.external_id, variables: variables },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            }).done(function (resp) {
                if (window.toastr) toastr.success('Plantilla enviada.');
                $('#bv-hsm-picker').removeClass('on');
                activateReplyTab();
            }).fail(function (xhr) {
                var msg = xhr?.responseJSON?.message || 'Error al enviar plantilla';
                if (window.toastr) toastr.error(msg);
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        // La "x" del header solo cierra el panel sin decidir nada (como
        // cancelar). "#bv-translate-close-2" es el boton "Desactivar" del
        // footer — antes hacia exactamente lo mismo que la "x" (solo cerrar),
        // sin tocar sessionStorage, asi que la traduccion seguia activa para
        // los siguientes mensajes aunque el agente creyera haberla apagado.
        $(document).on('click', '#bv-translate-close', function () {
            $('#bv-translate-panel').removeClass('on');
            activateReplyTab();
        });

        $(document).on('click', '#bv-translate-close-2', function () {
            // mode:'off' explicito (no solo "sin preferencia") para que
            // tambien anule el ajuste global "Traducir mensajes salientes"
            // de Settings mientras dure esta sesion de navegador — quitar la
            // key directamente habria vuelto a caer en ese default global.
            sessionStorage.setItem('inbox_translation_settings', JSON.stringify({ mode: 'off' }));
            $('.bv-tp-mode').removeClass('on');
            $('#bv-translate-panel').removeClass('on');
            activateReplyTab();
            if (window.toastr) toastr.info('Traducción desactivada para esta conversación.');
        });


        // ─── Helpers de navegación de inbox ──────────────────────────
        function navigateInbox(params) {
            const url = new URL(window.location.href);
            // Limpiar params de navegación conocidos
            ['unread', 'mine', 'priority'].forEach(function (p) {
                url.searchParams.delete(p);
            });
            Object.keys(params).forEach(function (key) {
                url.searchParams.set(key, params[key]);
            });
            window.location.href = url.toString();
        }

        function archiveCurrentConversation() {
            const $btn = $('[data-bv-action="archive"][data-bv-url]').first();
            if ($btn.length) {
                $btn.click();
                return;
            }
            // Fallback: derive archive URL from update URL
            const urls = getConvUrls();
            if (!urls.updateUrl) {
                toastr && toastr.warning('No hay conversacion activa');
                return;
            }
            const archiveUrl = urls.updateUrl.replace(/\/?$/, '/archive');
            $.ajax({
                url: archiveUrl,
                method: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function (resp) {
                    toastr && toastr.success((resp && resp.message) || 'Conversación archivada');
                    $('.bv-conv.on').fadeOut(180, function () { $(this).remove(); });
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudo archivar';
                    toastr && toastr.error(msg);
                });
        }

        // ─── Atajos de teclado ───────────────────────────────────────
        // State machine para secuencia G → X
        let gPressed = false;
        let gTimer = null;

        $(document).on('keydown', function (e) {
            // No interferir si el usuario está escribiendo
            if ($(e.target).is('input, textarea, [contenteditable]')) return;

            // ⌘/ o Ctrl+/ — toggle shortcuts modal
            if ((e.metaKey || e.ctrlKey) && e.key === '/') {
                e.preventDefault();
                const $shortcuts = $('[data-bv-modal-name="shortcuts"]');
                if ($shortcuts.hasClass('on')) {
                    closeModal($shortcuts);
                } else {
                    openModal('shortcuts');
                }
                return;
            }

            // ⌘E — archivar conversacion actual
            if ((e.metaKey || e.ctrlKey) && !e.shiftKey && e.key.toLowerCase() === 'e') {
                e.preventDefault();
                archiveCurrentConversation();
                return;
            }

            // ⌘+Shift+D — cerrar conversacion
            if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key.toLowerCase() === 'd') {
                e.preventDefault();
                openModal('close-conv');
                return;
            }


            // Ignorar otros modificadores
            if (e.metaKey || e.ctrlKey) return;

            // Tab — saltar al hilo cuando focus en lista
            if (e.key === 'Tab' && !e.shiftKey) {
                const $list = $('.bv-list');
                if ($list.length && ($list.is(':focus') || $list.find(':focus').length)) {
                    e.preventDefault();
                    $('.bv-th-inner').focus();
                }
                return;
            }

            // Shift+Tab — saltar al panel derecho cuando focus en hilo
            if (e.key === 'Tab' && e.shiftKey) {
                const $thread = $('.bv-thread');
                if ($thread.length && ($thread.is(':focus') || $thread.find(':focus').length)) {
                    e.preventDefault();
                    $('.bv-right').first().focus();
                }
                return;
            }

            // Secuencia G → X (state machine)
            if (e.key.toLowerCase() === 'g' && !gPressed) {
                gPressed = true;
                clearTimeout(gTimer);
                gTimer = setTimeout(function () { gPressed = false; }, 1500);
                return;
            }

            if (gPressed) {
                gPressed = false;
                clearTimeout(gTimer);
                switch (e.key.toLowerCase()) {
                    case 'u': navigateInbox({ unread: 1 }); break;
                    case 'm': navigateInbox({ mine: 1 }); break;
                    case 'a': navigateInbox({}); break;
                    case 'r': navigateInbox({ priority: 'urgent' }); break;
                }
                return;
            }

            const key = e.key.toLowerCase();
            switch (e.key) {
                case '?':
                    e.preventDefault();
                    openModal('shortcuts');
                    break;
                case '#':
                    e.preventDefault();
                    openModal('close-conv');
                    break;
                case 'ArrowDown':
                case 'j':
                    e.preventDefault();
                    navigateConv(1);
                    break;
                case 'ArrowUp':
                case 'k':
                    e.preventDefault();
                    navigateConv(-1);
                    break;
                default:
                    if (key === 'a') openModal('assign');
                    else if (key === 't') openModal('tags');
                    else if (key === 's') openModal('status');
                    else if (key === 'p') openModal('priority');
                    else if (key === 'f') openModal('filter');
                    else if (key === 'm') openModal('macro');
                    else if (key === 'n') $('.bv-composer-tab[data-bv-tab="note"]').click();
                    else if (key === 'r') $('.bv-composer-tab[data-bv-tab="reply"]').click();
            }
        });

        function navigateConv(direction) {
            const $items = $('.bv-conv:visible');
            if ($items.length === 0) return;
            const $current = $items.filter('.on');
            let nextIndex;
            if ($current.length === 0) {
                nextIndex = 0;
            } else {
                const currentIndex = $items.index($current);
                nextIndex = Math.max(0, Math.min($items.length - 1, currentIndex + direction));
            }
            $items.eq(nextIndex).click();
        }

        // ─── Composer: atajo de envío configurable ────────────────────
        // Preferencia persistente: 'ctrl-enter' (default) o 'enter'
        const SEND_SHORTCUT_KEY = 'bv:composer:send-shortcut';
        function getSendShortcut() {
            const v = localStorage.getItem(SEND_SHORTCUT_KEY);
            return v === 'enter' ? 'enter' : 'ctrl-enter';
        }
        function applySendShortcutUI() {
            const cur = getSendShortcut();
            const isMac = /Mac/.test(navigator.platform);
            $('#bv-kbd-send').text(cur === 'enter' ? '↵' : (isMac ? '⌘↵' : 'Ctrl+↵'));
            $('.bv-send-menu-opt').each(function () {
                $(this).toggleClass('on', $(this).data('bv-send-shortcut') === cur);
            });
        }
        applySendShortcutUI();

        $(document).on('keydown', '.bv-composer-input', function (e) {
            if (e.key !== 'Enter') return;
            // Si el menú de mención está abierto, dejar que su handler maneje Enter
            const $mentionMenu = $('#bv-mention-menu');
            if ($mentionMenu.length && !$mentionMenu.hasClass('bv-hidden')) return;
            // Si el slash-menu está abierto, dejar que su handler maneje Enter
            const $slashMenu = $('#bv-slash-menu');
            if ($slashMenu.length && $slashMenu.is(':visible')) return;
            const shortcut = getSendShortcut();
            const hasMod = e.metaKey || e.ctrlKey;
            if (shortcut === 'enter') {
                // Enter envía. Shift+Enter / Ctrl+Enter / Cmd+Enter = salto de línea
                if (e.shiftKey || hasMod) return;
                e.preventDefault();
                $(this).closest('.bv-composer').find('.btn-send').click();
            } else {
                // Ctrl/Cmd+Enter envía. Enter solo = salto de línea
                if (!hasMod) return;
                e.preventDefault();
                $(this).closest('.bv-composer').find('.btn-send').click();
            }
        });

        // Toggle del menú de atajo
        $(document).on('click', '#bv-send-config', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $menu = $('#bv-send-menu');
            const willOpen = $menu.hasClass('bv-hidden');
            $menu.toggleClass('bv-hidden');
            $(this).attr('aria-expanded', willOpen ? 'true' : 'false');
        });

        // Selección de opción
        $(document).on('click', '.bv-send-menu-opt', function (e) {
            e.preventDefault();
            const value = $(this).data('bv-send-shortcut');
            localStorage.setItem(SEND_SHORTCUT_KEY, value);
            applySendShortcutUI();
            $('#bv-send-menu').addClass('bv-hidden');
            $('#bv-send-config').attr('aria-expanded', 'false');
            // Sin toast: el kbd del botón Enviar ya muestra el atajo activo
            $('.bv-composer-input').focus();
        });

        // Click fuera cierra el menú
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-send-menu, #bv-send-config').length) {
                $('#bv-send-menu').addClass('bv-hidden');
                $('#bv-send-config').attr('aria-expanded', 'false');
            }
        });

        // ─── Btn enviar (AJAX al endpoint storeMessage) ──────────────
        // UX-02: envío optimista. Pinta la burbuja al instante (bv-bubble--pending),
        // mantiene el textarea habilitado/enfocado, reconcilia en .done y ofrece
        // "Reintentar" en .fail. El eco por WebSocket de los mensajes propios ya se
        // descarta en index.blade.php (user_id === myId), por lo que la burbuja
        // optimista no se duplica; aun así reconcilePendingBubble protege por id.
        function sendMessage(opts) {
            const { text, isInternal, url, $btn, $pending } = opts;
            const $icon = $btn.find('i').first();
            const iconCls = $icon.attr('class');
            $btn.prop('disabled', true);
            if (iconCls) { $icon.attr('class', 'fas fa-spinner fa-spin'); }

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: {
                    body: text,
                    is_internal: isInternal ? 1 : 0,
                    action: 'send',
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function (resp) {
                    reconcilePendingBubble($pending, resp?.item, isInternal);
                    $(document).trigger('bv:message:sent', resp?.item);
                    // Sin toast: el bubble que aparece en el thread es la confirmación visual
                })
                .fail(function (xhr) {
                    const msg = xhr?.responseJSON?.errors?.body?.[0]
                        || xhr?.responseJSON?.message
                        || 'No se pudo enviar el mensaje';
                    markPendingBubbleFailed($pending, { text, isInternal, url });
                    if (window.toastr) {
                        toastr.error(msg);
                    } else {
                        alert(msg);
                    }
                })
                .always(function () {
                    $btn.prop('disabled', false);
                    if (iconCls) { $icon.attr('class', iconCls); }
                });
        }

        $(document).on('click', '.btn-send', function () {
            const $btn = $(this);
            const $composer = $btn.closest('.bv-composer');
            const $textarea = $composer.find('.bv-composer-input');
            const text = $textarea.val().trim();
            const url = $composer.data('bv-send-url');
            if (!text || !url || $btn.prop('disabled')) return;

            const isInternal = $composer.find('.bv-composer-tab.on').data('bv-tab') === 'note';

            // Panel "Traducción de conversación", modo Salientes/Ambos: lo que
            // se envía al cliente es la traducción de lo que escribió el
            // agente, no el texto original. Notas internas nunca se traducen.
            //
            // Si el agente activó el panel manualmente en esta sesión de
            // navegador, esa preferencia manda. Si no lo ha tocado, cae al
            // ajuste global "Traducir mensajes salientes" de
            // /panel/settings/helpdesk-translate (data-bv-auto-translate-outgoing)
            // — así el agente no tiene que activar nada por conversación.
            const storedSettings = sessionStorage.getItem('inbox_translation_settings');
            const tSettings = storedSettings ? JSON.parse(storedSettings) : null;
            const globalAutoOutgoing = $composer.data('bv-auto-translate-outgoing') === 1 || $composer.data('bv-auto-translate-outgoing') === '1';
            const translateBeforeSend = !isInternal && (
                tSettings ? (tSettings.mode === 'outgoing' || tSettings.mode === 'both') : globalAutoOutgoing
            );

            if (!translateBeforeSend) {
                // UX-02: burbuja optimista + textarea vacío pero activo y enfocado.
                const $pending = appendPendingBubble(text, isInternal);
                $textarea.val('').css('height', 'auto').focus();

                sendMessage({ text, isInternal, url, $btn, $pending });
                return;
            }

            const $icon = $btn.find('i').first();
            const iconCls = $icon.attr('class');
            $btn.prop('disabled', true);
            if (iconCls) { $icon.attr('class', 'fas fa-spinner fa-spin'); }

            // "IDIOMA DESTINO" del panel sirve para la dirección Entrantes
            // (traducir al agente). Para Salientes el destino correcto es el
            // idioma real del cliente, no ese mismo select — si no, en modo
            // "Ambos" ambas direcciones apuntarían al mismo idioma y una de
            // las dos quedaría mal traducida (o sin traducir).
            const customerLang = ($('.bv-right').data('customer-language') || '').toString().trim();
            const outgoingTo = customerLang || tSettings?.to || 'es';

            $.ajax({
                url: '/panel/helpdesk/translate',
                method: 'POST',
                dataType: 'json',
                data: { text: text, from: tSettings?.from || 'auto', to: outgoingTo },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function (resp) {
                    const translated = (resp && resp.translated) ? resp.translated : text;
                    const $pending = appendPendingBubble(translated, isInternal);
                    if (translated !== text) {
                        $pending.find('.bv-bubble').append(
                            '<div class="bv-bubble-translation"><span class="bv-bubble-translation-lbl">&#8627; escrito como: </span>'
                            + escape(text) + '</div>'
                        );
                    }
                    $textarea.val('').css('height', 'auto').focus();
                    sendMessage({ text: translated, isInternal, url, $btn, $pending });
                })
                .fail(function () {
                    if (window.toastr) toastr.error('No se pudo traducir el mensaje antes de enviar');
                    $btn.prop('disabled', false);
                    if (iconCls) { $icon.attr('class', iconCls); }
                });
        });

        // UX-02: reintentar el envío de una burbuja fallida.
        $(document).on('click', '.bv-bubble-retry', function (e) {
            e.preventDefault();
            const $retry = $(this);
            const opts = $retry.data('retry');
            if (!opts) return;
            const $pending = $retry.closest('.bv-msg');
            const $bubble = $pending.find('.bv-bubble');
            $bubble.removeClass('bv-bubble--failed').addClass('bv-bubble--pending opacity-50');
            $retry.remove();
            $bubble.find('.meta span').first().text('Tú · Enviando…');
            if (!$bubble.find('.bv-pending-spin').length) {
                $bubble.find('.meta').append('<i class="fas fa-circle-notch fa-spin bv-pending-spin ms-1"></i>');
            }
            sendMessage({
                text: opts.text,
                isInternal: opts.isInternal,
                url: opts.url,
                $btn: $('.bv-composer .btn-send').first(),
                $pending,
            });
        });

        const escape = function (s) {
            return $('<div>').text(s ?? '').html();
        };

        function fileExtFromUrl(url) {
            const path = (url || '').split('?')[0].split('#')[0];
            const ext = path.split('.').pop() || '';
            return ext.toLowerCase();
        }

        function inferAttachType(meta, url) {
            const ext = fileExtFromUrl(url);
            const name = (meta?.name || url || '').split('/').pop().split('?')[0];
            // Facebook/Instagram widget voice recorder: voz-*.webm is always audio
            if (ext === 'webm' && name.startsWith('voz-')) return 'audio';
            if (meta?.type && meta.type !== 'video') return meta.type;
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'image';
            if (['mp4', 'mov'].includes(ext)) return 'video';
            if (['mp3', 'ogg', 'wav', 'oga', 'm4a', 'opus'].includes(ext)) return 'audio';
            // webm: prefer audio/webm mime over generic video classification
            if (ext === 'webm' && (meta?.mime || meta?.mime_type || '').startsWith('audio/')) return 'audio';
            if (ext === 'webm') return 'video';
            return 'document';
        }

        function buildWaveformHtml(url) {
            // Pseudo-aleatorio determinista para que las barras sean estables
            let seed = 0;
            for (let i = 0; i < (url || '').length; i++) seed = (seed * 31 + url.charCodeAt(i)) >>> 0;
            const rand = () => { seed = (seed * 1664525 + 1013904223) >>> 0; return seed / 0xFFFFFFFF; };
            let html = '';
            for (let b = 0; b < 32; b++) {
                const h = 25 + Math.round(rand() * 75);
                html += '<span class="bv-audio-bar" style="height:' + h + '%"></span>';
            }
            return html;
        }

        const docIconMap = {
            pdf: 'fa-file-pdf', doc: 'fa-file-word', docx: 'fa-file-word',
            xls: 'fa-file-excel', xlsx: 'fa-file-excel',
            ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint',
            zip: 'fa-file-zipper', csv: 'fa-file-csv', txt: 'fa-file-lines',
        };

        function buildAttachmentHtml(url, meta, ctx) {
            const type = inferAttachType(meta, url);
            const ext = fileExtFromUrl(url);
            const fileName = meta?.name || decodeURIComponent((url || '').split('/').pop() || 'archivo');
            const fileSize = meta?.size ? Math.round(meta.size / 1024) + ' KB' : '';
            // Use escapeHtml for URLs in HTML attributes (NOT escape() which mangles https: → https%3A)
            const safeUrl = escapeHtml(url);

            if (type === 'image') {
                return '<a href="' + safeUrl + '" class="bv-attach-thumb" data-bv-modal="file-preview" data-bv-preview-src="' + safeUrl + '" data-bv-preview-type="image">' +
                    '<img src="' + safeUrl + '" alt="' + escapeHtml(fileName) + '" loading="lazy" width="200" height="200">' +
                '</a>';
            }
            if (type === 'video') {
                return '<div class="bv-video-bubble">' +
                    '<video controls preload="metadata" class="bv-video-player">' +
                        '<source src="' + safeUrl + '">' +
                    '</video>' +
                '</div>';
            }
            if (type === 'audio') {
                const authorLabel = ctx?.authorLabel || 'Tú';
                const colorIdx = ctx?.colorIdx !== undefined ? ctx.colorIdx : 1;
                const initials = authorLabel.split(' ').slice(0, 2).map(w => (w || '').charAt(0).toUpperCase()).join('');
                return '<div class="bv-audio-msg" data-bv-audio-src="' + safeUrl + '">' +
                    '<div class="bv-audio-avatar bv-th-av-c' + colorIdx + '">' + escapeHtml(initials) + '<span class="bv-audio-mic"><i class="fas fa-microphone"></i></span></div>' +
                    '<button type="button" class="bv-audio-play" aria-label="Reproducir"><i class="fas fa-play"></i></button>' +
                    '<div class="bv-audio-wave" role="slider" tabindex="0" aria-label="Progreso del audio">' +
                        buildWaveformHtml(url) +
                        '<span class="bv-audio-progress-dot"></span>' +
                    '</div>' +
                    '<span class="bv-audio-time">0:00</span>' +
                    '<button type="button" class="bv-audio-speed" data-bv-speed="1" title="Velocidad">1x</button>' +
                    '<audio preload="metadata" class="bv-audio-el"><source src="' + safeUrl + '"></audio>' +
                '</div>';
            }
            const docIcon = docIconMap[ext] || 'fa-file';
            return '<a href="' + safeUrl + '" target="_blank" rel="noopener" class="bv-attach-file">' +
                '<i class="far ' + docIcon + '"></i>' +
                '<div class="bv-attach-file-info">' +
                    '<span class="bv-attach-file-name">' + escapeHtml(fileName) + '</span>' +
                    (fileSize ? '<span class="bv-attach-file-size">' + fileSize + '</span>' : '') +
                '</div>' +
            '</a>';
        }

        function renderBubbleEl(item, isInternal) {
            if (!item) return null;
            if (item.type === 'email_sent') return null;

            const isIncoming = !!item.is_incoming;
            const noteBadge = isInternal
                ? '<div class="note-badge"><i class="fas fa-lock"></i> Nota interna</div>'
                : '';
            const checkmark = !isInternal && !isIncoming
                ? '<span class="chk read bv-chk-read">✓✓</span>'
                : '';
            const bubbleClass = isInternal ? 'bv-bubble note' : 'bv-bubble';
            const msgClass = isInternal ? 'bv-msg in' : (isIncoming ? 'bv-msg in' : 'bv-msg out');

            let quotedHtml = '';
            if (item.reply_to) {
                quotedHtml = '<div class="bv-quoted-msg" data-bv-jump-to="' + (item.reply_to.id || '') + '">' +
                    '<div class="bv-quoted-author">' + escape(item.reply_to.author || '') + '</div>' +
                    '<div class="bv-quoted-body">' + escape(item.reply_to.body || '') + '</div>' +
                '</div>';
            }

            // Render attachments si los hay
            // attachment_urls puede ser:
            //   - array de strings (URL directos, formato legacy)
            //   - array de objetos {url, name, size, mime, mime_type, type, path}
            let attachmentsHtml = '';
            const urls = item.attachment_urls || [];
            const metas = item.attachments || (item.metadata?.attachments) || [];
            if (urls.length) {
                const authorStr = item.author || (isIncoming ? 'Cliente' : 'Tú');
                // Generate a stable colour index from author name (0–9) matching server-side logic
                let cIdx = 0;
                for (let ci = 0; ci < authorStr.length; ci++) cIdx += authorStr.charCodeAt(ci);
                cIdx = cIdx % 10;
                const attachCtx = { authorLabel: authorStr, colorIdx: cIdx };
                attachmentsHtml = '<div class="bv-attachment-gallery">';
                urls.forEach((u, i) => {
                    const url = (u && typeof u === 'object') ? (u.url || '') : u;
                    const inlineMeta = (u && typeof u === 'object') ? u : null;
                    attachmentsHtml += buildAttachmentHtml(url, inlineMeta || metas[i] || {}, attachCtx);
                });
                attachmentsHtml += '</div>';
            }

            // Link preview (OG metadata) — shown when the body contains a URL.
            const linkPreview = item.metadata?.link_preview || item.link_preview || null;
            let linkPreviewHtml = '';
            if (linkPreview && (linkPreview.title || linkPreview.description || linkPreview.image)) {
                const lpUrl = linkPreview.url || '';
                const lpImg = linkPreview.image
                    ? '<img src="' + escape(linkPreview.image) + '" alt="' + escape(linkPreview.title || '') + '" loading="lazy" class="bv-lp-img">'
                    : '';
                const lpTitle = linkPreview.title
                    ? '<p class="bv-lp-title">' + escape(linkPreview.title) + '</p>'
                    : '';
                const lpDesc = linkPreview.description
                    ? '<p class="bv-lp-desc">' + escape(linkPreview.description) + '</p>'
                    : '';
                const lpSite = linkPreview.site || (lpUrl ? (new URL(lpUrl).hostname || '') : '');
                const lpFavicon = linkPreview.favicon
                    ? '<img src="' + escape(linkPreview.favicon) + '" alt="" class="bv-lp-favicon" loading="lazy" onerror="this.remove()">'
                    : '<i class="fas fa-link"></i>';
                linkPreviewHtml =
                    '<a href="' + escape(lpUrl) + '" target="_blank" rel="noopener noreferrer" class="bv-link-preview">' +
                        lpImg +
                        '<div class="bv-lp-body">' +
                            lpTitle +
                            lpDesc +
                            '<div class="bv-lp-meta">' +
                                lpFavicon +
                                '<span>' + escape(lpSite) + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</a>';
            }

            // Hide the body when it is *only* the URL that has already been
            // unfurled into a preview card — clicking the card already opens it.
            const trimmedBody = (item.body || '').trim();
            const previewUrl = (linkPreview?.url || '').trim();
            const isJustTheUrl = previewUrl && (
                trimmedBody === previewUrl ||
                trimmedBody.replace(/\/$/, '') === previewUrl.replace(/\/$/, '')
            );
            const bodyHtml = (item.body && !isJustTheUrl)
                ? escape(item.body)
                    .replace(/\n/g, '<br>')
                    // Auto-linkify URLs so visitor messages with raw URLs become clickable.
                    .replace(/(https?:\/\/[^\s<>"']+)/gi, (full) => {
                        let url = full;
                        let trail = '';
                        while (/[.,;:!?)\]]$/.test(url)) {
                            trail = url.slice(-1) + trail;
                            url = url.slice(0, -1);
                        }
                        return '<a href="' + url + '" target="_blank" rel="noopener noreferrer" class="bv-msg-link">' + url + '</a>' + trail;
                    })
                    .replace(/(^|\s|>)@([\p{L}0-9._-]+)/gu, (full, prefix, handle) => {
                        return prefix + '<span class="bv-mention-chip" data-bv-mention-handle="' + escape(handle) + '">@' + escape(handle) + '</span>';
                    })
                : '';

            // Data attributes mirror the server-rendered bubbles so the
            // context-menu (right-click) can discover the body, author,
            // internal/out flags etc. — without these, the "Traducir" entry
            // disappears for messages that arrived via WebSocket.
            const rawBody = (item.body || '').toString();
            const dataAttrs =
                ' data-bv-item-id="' + escape(item.id || '') + '"' +
                ' data-bv-author="' + escape(item.author || '') + '"' +
                ' data-bv-is-internal="' + (isInternal ? '1' : '0') + '"' +
                ' data-bv-is-out="' + (isIncoming ? '0' : '1') + '"' +
                ' data-bv-body="' + escape(rawBody) + '"' +
                ' data-bv-body-preview="' + escape(rawBody.slice(0, 80)) + '"';

            const outgoing = (item.outgoing_translated_body || '').toString();
            const outTrHtml = (!isInternal && !isIncoming && outgoing)
                ? '<div class="bv-bubble-translation bv-outgoing-translation"' +
                  ' data-bv-original="' + escape(rawBody) + '"' +
                  ' data-bv-translated="' + escape(outgoing) + '">' +
                  '<span class="bv-bubble-translation-lbl">&#8627; </span>' +
                  escape(outgoing) +
                  ' <button type="button" class="bv-bubble-translation-toggle" title="Ver original"><i class="fas fa-arrows-rotate"></i></button>' +
                  '</div>'
                : '';

            // WhatsApp: media received but not yet downloaded (media_id present, no attachment_urls)
            const meta = item.metadata || {};
            let pendingHtml = '';
            if (!attachmentsHtml && meta.media_id && meta.message_type) {
                const pendingIcons = { image: 'fa-image', audio: 'fa-microphone', video: 'fa-video', document: 'fa-file', sticker: 'fa-face-smile', voice: 'fa-microphone' };
                const pendingLabels = { image: 'Imagen', audio: 'Audio', video: 'Video', document: 'Documento', sticker: 'Sticker', voice: 'Audio' };
                pendingHtml = '<div class="bv-media-pending text-muted"><i class="fas ' + (pendingIcons[meta.message_type] || 'fa-paperclip') + ' me-1"></i>' +
                    escapeHtml(pendingLabels[meta.message_type] || meta.message_type) + '</div>';
            }

            // WhatsApp contact card
            let contactHtml = '';
            if (item.type === 'contact' && meta.name) {
                contactHtml = '<div class="bv-contact-card">' +
                    '<div class="bv-contact-card-icon"><i class="far fa-address-card"></i></div>' +
                    '<div class="bv-contact-card-info">' +
                    '<div class="bv-contact-card-name">' + escapeHtml(meta.name) + '</div>' +
                    (meta.phone ? '<div class="bv-contact-card-detail"><i class="fas fa-phone"></i> ' + escapeHtml(meta.phone) + '</div>' : '') +
                    (meta.email ? '<div class="bv-contact-card-detail"><i class="far fa-envelope"></i> ' + escapeHtml(meta.email) + '</div>' : '') +
                    '</div></div>';
            }

            // WhatsApp location
            let locationHtml = '';
            if (item.type === 'location' && meta.lat) {
                const lat = meta.lat, lng = meta.lng;
                const mapUrl = 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '&zoom=15';
                const mapImg = 'https://staticmap.openstreetmap.de/staticmap.php?center=' + lat + ',' + lng + '&zoom=14&size=280x140&markers=' + lat + ',' + lng + ',red';
                locationHtml = '<div class="bv-location-bubble">' +
                    '<a href="' + escapeHtml(mapUrl) + '" target="_blank" rel="noopener" class="bv-location-map-link">' +
                    '<img src="' + escapeHtml(mapImg) + '" alt="Mapa" loading="lazy" class="bv-location-map-img" width="280" height="140"></a>' +
                    '<div class="bv-location-address"><i class="fas fa-location-dot"></i><span>' + escapeHtml(meta.address || lat + ', ' + lng) + '</span></div>' +
                    '</div>';
            }

            // Server only renders an avatar for incoming messages / internal notes
            // (outgoing bubbles don't need one — the "out" alignment identifies
            // them), and only labels the "meta" line with the author name for
            // outgoing messages (the avatar already identifies the customer).
            const showAvatar = isIncoming || isInternal;
            const colorIdx = Number.isFinite(item.colorIdx) ? item.colorIdx : 1;
            const initialsSource = (item.author || '').trim();
            const nameParts = initialsSource.split(/\s+/);
            const initials = ((nameParts[0]?.[0] || '') + (nameParts[1]?.[0] || '')).toUpperCase();
            const avatarHtml = showAvatar
                ? '<div class="av-sm bv-th-av-c' + colorIdx + '">' + escape(initials) + '</div>'
                : '';
            const metaLabel = (!isIncoming && !isInternal)
                ? escape(item.author || 'Tú') + ' · ' + escape(item.time || '')
                : escape(item.time || '');

            const $bubble = $(
                '<div class="' + msgClass + '">' +
                    avatarHtml +
                    '<div class="' + bubbleClass + '"' + dataAttrs + '>' +
                        noteBadge +
                        quotedHtml +
                        bodyHtml +
                        linkPreviewHtml +
                        attachmentsHtml +
                        pendingHtml +
                        contactHtml +
                        locationHtml +
                        outTrHtml +
                        '<div class="meta">' +
                            '<span>' + metaLabel + '</span>' +
                            checkmark +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            return $bubble;
        }

        function appendBubbleToThread(item, isInternal) {
            const $inner = $('.bv-th-inner');
            if (!$inner.length) return;
            const $bubble = renderBubbleEl(item, isInternal);
            if (!$bubble) return;
            // If bubble already exists (re-broadcast after media download), replace it
            const $existing = $inner.find('.bv-bubble[data-bv-item-id="' + escape(item.id || '') + '"]').closest('.bv-msg');
            if ($existing.length) {
                $existing.replaceWith($bubble);
            } else {
                $inner.append($bubble);
            }
            scrollThreadToBottom(true);
        }
        window.appendBubbleToThread = appendBubbleToThread;

        // Mensajes de actividad (etiqueta añadida/quitada, asignación, cambio de
        // estado, etc.) — se pintan como píldora centrada, no como burbuja.
        function appendActivityPillToThread(body) {
            const $inner = $('.bv-th-inner');
            if (!$inner.length) return;
            const $pill = $('<div class="bv-event-pill"><span></span></div>');
            $pill.find('span').text(body || '');
            $inner.append($pill);
            scrollThreadToBottom(true);
        }
        window.appendActivityPillToThread = appendActivityPillToThread;

        // ─── UX-02: burbujas optimistas (pending / reconciliación / fallo) ───
        function appendPendingBubble(text, isInternal) {
            const $inner = $('.bv-th-inner');
            if (!$inner.length) return $();
            const tempId = 'bv-pending-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
            const $msg = renderBubbleEl({
                id: tempId,
                body: text,
                author: 'Tú',
                time: 'Enviando…',
                is_incoming: false,
            }, isInternal);
            if (!$msg || !$msg.length) return $();
            $msg.find('.bv-bubble').addClass('bv-bubble--pending opacity-50').attr('data-bv-pending', '1');
            $msg.find('.bv-chk-read, .chk').remove();
            $msg.find('.meta').append('<i class="fas fa-circle-notch fa-spin bv-pending-spin ms-1"></i>');
            $inner.append($msg);
            scrollThreadToBottom(true);
            return $msg;
        }

        function reconcilePendingBubble($pending, item, isInternal) {
            if (!$pending || !$pending.length) {
                if (item) appendBubbleToThread(item, isInternal);
                return;
            }
            if (!item) { $pending.remove(); return; }
            const realId = (item.id == null ? '' : String(item.id));
            // Si el mensaje real ya llegó (p.ej. por WebSocket), descartar el placeholder.
            const $already = $('.bv-th-inner .bv-bubble[data-bv-item-id="' + escape(realId) + '"]').not('[data-bv-pending]');
            if (realId && $already.length) {
                $pending.remove();
                return;
            }
            const $real = renderBubbleEl(item, isInternal);
            if ($real && $real.length) { $pending.replaceWith($real); }
            else { $pending.remove(); }
        }

        function markPendingBubbleFailed($pending, retryOpts) {
            if (!$pending || !$pending.length) return;
            const $bubble = $pending.find('.bv-bubble');
            $bubble.removeClass('opacity-50 bv-bubble--pending').addClass('bv-bubble--failed');
            $bubble.find('.bv-pending-spin').remove();
            const $meta = $bubble.find('.meta');
            $meta.find('span').first().text('No se pudo enviar');
            if (!$meta.find('.bv-bubble-retry').length) {
                const $retry = $('<button type="button" class="bv-bubble-retry btn btn-sm btn-link p-0 ms-1 text-danger"><i class="fas fa-rotate-right me-1"></i>Reintentar</button>');
                $retry.data('retry', retryOpts);
                $meta.append($retry);
            }
        }

        // ─── perf-04: cargar mensajes anteriores (paginación hacia arriba) ───
        function loadOlderItems($btn) {
            if ($btn.data('loading')) { return; }
            const url = $btn.data('url');
            const before = $btn.data('oldest-id');
            if (!url || !before) { return; }

            $btn.data('loading', true);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cargando…');

            const $body = $('.bv-th-body');
            const prevHeight = $body.length ? $body[0].scrollHeight : 0;
            const prevTop = $body.length ? $body[0].scrollTop : 0;

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                data: { before: before },
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done(function (resp) {
                const items = (resp && resp.items) || [];
                const $wrap = $btn.closest('.bv-load-older');

                if (!items.length) {
                    $wrap.remove();
                    return;
                }

                // Los items llegan en orden ascendente (el primero es el más antiguo).
                const els = [];
                items.forEach(function (it) {
                    const $b = renderBubbleEl(it, !!it.is_internal);
                    if ($b) { els.push($b[0]); }
                });
                if (items[0] && items[0].id) { $btn.data('oldest-id', items[0].id); }
                $wrap.after(els);

                // Mantener la posición de scroll tras el prepend.
                if ($body.length) {
                    $body[0].scrollTop = prevTop + ($body[0].scrollHeight - prevHeight);
                }

                if (!resp.has_more) { $wrap.remove(); }
            }).fail(function (xhr) {
                // Endpoint aún no disponible o sin acceso → degradar sin romper.
                if (xhr.status === 404 || xhr.status === 405) {
                    $btn.closest('.bv-load-older').remove();
                    return;
                }
                if (window.toastr) { toastr.error('No se pudieron cargar los mensajes anteriores'); }
            }).always(function () {
                $btn.data('loading', false).prop('disabled', false).html(originalHtml);
            });
        }

        $(document).on('click', '#bv-load-older .bv-load-older-btn', function () {
            loadOlderItems($(this));
        });

        // Click en bubble citado → scroll al original con flash highlight
        $(document).on('click', '.bv-quoted-msg', function () {
            const id = $(this).data('bv-jump-to');
            if (!id) return;
            const $target = $('.bv-bubble[data-bv-item-id="' + id + '"]');
            if (!$target.length) return;
            $target.closest('.bv-msg')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $target.addClass('bv-bubble-flash');
            setTimeout(() => $target.removeClass('bv-bubble-flash'), 1500);
        });

        // ─── Auto-resize textarea ────────────────────────────────────
        $(document).on('input', '.bv-composer-input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(160, this.scrollHeight) + 'px';
        });


        // ─── Event: message added from external modal (e.g. note modal) ─
        $(document).on('bv:message:added', function (e, item, isInternal) {
            appendBubbleToThread(item, isInternal);
        });


        // ─── Slash menu: canned replies ───────────────────────────────
        const slashMenu = (function () {
            let $menu = null;
            let $textarea = null;
            let selectedIndex = -1;
            let items = [];
            let debounceTimer = null;
            const searchUrl = (window.bvCannedRepliesUrl || '/panel/helpdesk/canned-replies/search');

            function buildMenu() {
                if ($('#bv-slash-menu').length) {
                    $menu = $('#bv-slash-menu');
                    return;
                }
                $menu = $('<div id="bv-slash-menu" class="bv-slash-menu" style="display:none"></div>');
                $('body').append($menu);
            }

            function getSlashQuery(ta) {
                const val = ta.value;
                const pos = ta.selectionStart;
                const lineStart = val.lastIndexOf('\n', pos - 1) + 1;
                const lineText = val.substring(lineStart, pos);
                if (!lineText.startsWith('/')) return null;
                return lineText.substring(1);
            }

            function positionMenu(ta) {
                const rect = ta.getBoundingClientRect();
                $menu.css({
                    top: rect.top + window.scrollY - $menu.outerHeight() - 4,
                    left: rect.left + window.scrollX,
                    width: Math.min(420, rect.width),
                });
            }

            function renderItems() {
                $menu.empty();
                if (!items.length) {
                    $menu.hide();
                    return;
                }
                items.forEach(function (item, idx) {
                    const preview = (item.body || '').substring(0, 60).replace(/\n/g, ' ');
                    const $row = $(
                        '<div class="bv-slash-item" data-idx="' + idx + '">' +
                            '<div class="bv-slash-meta">' +
                                (item.shortcut ? '<span class="bv-slash-shortcut">/' + $('<div>').text(item.shortcut).html() + '</span>' : '') +
                                '<span class="bv-slash-name">' + $('<div>').text(item.name).html() + '</span>' +
                            '</div>' +
                            '<div class="bv-slash-preview">' + $('<div>').text(preview).html() + '</div>' +
                        '</div>'
                    );
                    $menu.append($row);
                });
                setSelected(0);
                positionMenu($textarea[0]);
                $menu.show();
            }

            function setSelected(idx) {
                selectedIndex = Math.max(0, Math.min(items.length - 1, idx));
                $menu.find('.bv-slash-item').removeClass('on').eq(selectedIndex).addClass('on');
            }

            function insertReply(item) {
                if (!$textarea || !$textarea.length) return;
                const ta = $textarea[0];
                const val = ta.value;
                const pos = ta.selectionStart;
                const lineStart = val.lastIndexOf('\n', pos - 1) + 1;
                const before = val.substring(0, lineStart);
                const after = val.substring(pos);
                const body = item.body || '';
                ta.value = before + body + after;
                const newPos = before.length + body.length;
                ta.setSelectionRange(newPos, newPos);
                $textarea.trigger('input');
                close();
                $textarea.focus();
            }

            function close() {
                if ($menu) $menu.hide();
                items = [];
                selectedIndex = -1;
            }

            function search(q) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    $.ajax({
                        url: searchUrl,
                        method: 'GET',
                        dataType: 'json',
                        data: { q: q },
                        headers: { 'Accept': 'application/json' },
                    })
                        .done(function (data) {
                            items = Array.isArray(data) ? data : [];
                            renderItems();
                        })
                        .fail(function () {
                            close();
                        });
                }, 200);
            }

            function init() {
                buildMenu();

                $(document).on('input', '.bv-composer-input', function () {
                    $textarea = $(this);
                    const q = getSlashQuery(this);
                    if (q === null) {
                        close();
                        return;
                    }
                    search(q);
                });

                $(document).on('keydown', '.bv-composer-input', function (e) {
                    if (!$menu || !$menu.is(':visible')) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        setSelected(selectedIndex + 1);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        setSelected(selectedIndex - 1);
                    } else if (e.key === 'Enter') {
                        if (items[selectedIndex]) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            insertReply(items[selectedIndex]);
                        }
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        close();
                    }
                });

                $(document).on('click', '.bv-slash-item', function () {
                    const idx = parseInt($(this).data('idx'), 10);
                    if (items[idx]) {
                        insertReply(items[idx]);
                    }
                });

                $(document).on('click', function (e) {
                    if ($menu && !$(e.target).closest('.bv-slash-menu, .bv-composer-input').length) {
                        close();
                    }
                });
            }

            return { init: init };
        })();

        slashMenu.init();


        function applyTranslationSettings(mode, from, to) {
            mode = mode || 'incoming';
            from = from || 'auto';
            to = to || 'es';
            sessionStorage.setItem('inbox_translation_settings', JSON.stringify({ mode: mode, from: from, to: to }));
            if (mode === 'incoming' || mode === 'both') {
                translateAllIncomingBubbles(to);
            }
        }
        window.bvApplyTranslationSettings = applyTranslationSettings;

        // ─── Panel Traducir: "Activar traducción" ────────────────────
        $(document).on('click', '#bv-translate-panel .bv-panel-btn-confirm', function () {
            const mode = $('.bv-tp-mode.on').data('mode') || 'incoming';
            const from = $('#bv-tp-from').val() || 'auto';
            const to = $('#bv-tp-to').val() || 'es';

            applyTranslationSettings(mode, from, to);

            $('#bv-translate-panel').removeClass('on');
            activateReplyTab();
        });

        // ─── Traducción de burbuja (reusable desde context menu) ────────
        function translateBubble($bubble, text, overrideTo) {
            if (!$bubble || !$bubble.length || !text) return;
            if ($bubble.find('.bv-bubble-translation').length) return; // ya traducida
            const settings = JSON.parse(sessionStorage.getItem('inbox_translation_settings') || '{}');
            const to = overrideTo || settings.to || 'es';
            const from = settings.from || 'auto';

            $bubble.append('<div class="bv-bubble-translation bv-bubble-translation--loading"><span>traduciendo…</span></div>');

            // Prefer the per-item endpoint when the bubble carries an item-id
            // — that endpoint persists translated_body on the conversation
            // item, so the translation survives reloads and any other agent
            // opening the conversation sees it.
            const itemId = $bubble.data('bv-item-id');
            const usePersistent = !!itemId;
            const url = usePersistent
                ? '/panel/helpdesk/conversations/items/' + itemId + '/translate'
                : '/panel/helpdesk/translate';
            const data = usePersistent ? { target: to } : { text: text, from: from, to: to };

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
            })
                .done(function (resp) {
                    $bubble.find('.bv-bubble-translation--loading').remove();
                    const $tr = $('<div class="bv-bubble-translation"></div>');
                    $tr.attr('data-bv-original', text);
                    $tr.attr('data-bv-translated', resp.translated);
                    $tr.append('<span class="bv-bubble-translation-lbl">&#8627; </span>');
                    $tr.append(document.createTextNode(resp.translated));
                    $tr.append(' <button type="button" class="bv-bubble-translation-toggle" title="Ver original"><i class="fas fa-arrows-rotate"></i></button>');
                    $bubble.append($tr);
                })
                .fail(function () {
                    $bubble.find('.bv-bubble-translation--loading').remove();
                    if (window.toastr) {
                        toastr.error('No se pudo traducir');
                    }
                });
        }

        // Toggle "Ver original" en la traducción de un mensaje.
        $(document).on('click', '.bv-bubble-translation-toggle', function (e) {
            e.stopPropagation();
            const $tr = $(this).closest('.bv-bubble-translation');
            const original = $tr.data('bv-original') || '';
            const translated = $tr.data('bv-translated') || '';
            const showingOriginal = $tr.hasClass('showing-original');
            const $lbl = $tr.find('.bv-bubble-translation-lbl');
            // Rebuild the visible text node — keep label and toggle button.
            $tr.contents().filter(function () { return this.nodeType === 3; }).remove();
            $lbl.after(document.createTextNode(showingOriginal ? translated : original));
            $tr.toggleClass('showing-original');
            $(this).attr('title', showingOriginal ? 'Ver original' : 'Ver traducción');
        });

        // Traducir todas las burbujas entrantes (botón "Traducir todas" del panel)
        function translateAllIncomingBubbles(to) {
            $('.bv-msg.in .bv-bubble').each(function () {
                const $bubble = $(this);
                if ($bubble.find('.bv-bubble-translation').length) return;
                const text = ($bubble.data('bv-body') || '').toString();
                if (text) translateBubble($bubble, text, to);
            });
        }

        // ─── Context menu de burbujas (click derecho, estilo WhatsApp) ──
        function buildBubbleMenuItems($bubble) {
            const isInternal = String($bubble.data('bv-is-internal') || '0') === '1';
            const isOut = String($bubble.data('bv-is-out') || '0') === '1';
            const body = ($bubble.data('bv-body') || '').toString();
            const hasBody = body.length > 0;
            const alreadyTranslated = $bubble.find('.bv-bubble-translation').length > 0;

            const items = [];
            if (hasBody) {
                items.push({ icon: 'fas fa-reply', label: 'Responder', action: 'reply' });
            }
            items.push({ icon: 'far fa-face-smile', label: 'Reaccionar', action: 'react' });
            if (hasBody) {
                items.push({ icon: 'far fa-copy', label: 'Copiar texto', action: 'copy' });
            }
            if (hasBody && !isInternal && !alreadyTranslated) {
                items.push({ icon: 'fas fa-language', label: 'Traducir', action: 'translate' });
            }
            items.push({ icon: 'fas fa-share', label: 'Reenviar', action: 'forward' });
            items.push({ icon: 'fas fa-circle-info', label: 'Info del mensaje', action: 'info' });
            if (isOut) {
                items.push({ icon: 'far fa-trash-can', label: 'Eliminar', action: 'delete', danger: true });
            }
            return items;
        }

        function openBubbleMenu($bubble, x, y) {
            $('#bv-bubble-menu').remove();
            const items = buildBubbleMenuItems($bubble);
            if (!items.length) return;

            const itemsHtml = items.map((it, i) => (
                '<button type="button" class="bv-bubble-menu-item' + (it.danger ? ' is-danger' : '') + '" data-bv-action="' + it.action + '" data-bv-idx="' + i + '">' +
                    '<i class="' + it.icon + '"></i><span>' + it.label + '</span>' +
                '</button>'
            )).join('');

            const $menu = $('<div id="bv-bubble-menu" class="bv-bubble-menu" role="menu">' + itemsHtml + '</div>');
            $('body').append($menu);
            $menu.data('bubble', $bubble);

            // Posicionar con clamp al viewport
            const w = $menu.outerWidth();
            const h = $menu.outerHeight();
            let left = x;
            let top = y;
            if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;
            if (top + h > window.innerHeight - 8) top = window.innerHeight - h - 8;
            if (left < 8) left = 8;
            if (top < 8) top = 8;
            $menu.css({ left: left + 'px', top: top + 'px' });
        }

        function closeBubbleMenu() {
            $('#bv-bubble-menu').remove();
        }

        // Abre con click derecho sobre el bubble
        $(document).on('contextmenu', '.bv-bubble', function (e) {
            // Si el click derecho fue sobre un link/botón interno, dejar el menú nativo
            if ($(e.target).closest('a, button, .bv-mention-chip').length) return;
            e.preventDefault();
            openBubbleMenu($(this), e.clientX, e.clientY);
        });

        // En desktop también accesible con long-press (no estorba)
        let bubbleLongPressTimer = null;
        $(document).on('touchstart', '.bv-bubble', function (e) {
            const $bubble = $(this);
            const t = e.originalEvent.touches[0];
            bubbleLongPressTimer = setTimeout(() => openBubbleMenu($bubble, t.clientX, t.clientY), 500);
        });
        $(document).on('touchend touchmove', '.bv-bubble', function () {
            clearTimeout(bubbleLongPressTimer);
        });

        // Click fuera o ESC cierra
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-bubble-menu').length) {
                closeBubbleMenu();
            }
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closeBubbleMenu();
        });

        // Acción de un item
        $(document).on('click', '.bv-bubble-menu-item', function () {
            const action = $(this).data('bv-action');
            const $menu = $(this).closest('#bv-bubble-menu');
            const $bubble = $menu.data('bubble');
            closeBubbleMenu();
            if (!$bubble || !$bubble.length) return;

            const body = ($bubble.data('bv-body') || '').toString();
            const id = $bubble.data('bv-item-id');
            const author = $bubble.data('bv-author') || '';
            const preview = ($bubble.data('bv-body-preview') || body.slice(0, 80)).toString();

            switch (action) {
                case 'reply':
                    $(document).trigger('bv:set-reply', { id, author, body: preview });
                    break;
                case 'translate':
                    translateBubble($bubble, body);
                    break;
                case 'copy':
                    if (navigator.clipboard && body) {
                        navigator.clipboard.writeText(body).catch(() => {});
                        if (window.toastr) toastr.success('Texto copiado');
                    }
                    break;
                case 'react':
                    openReactionPicker($bubble, id);
                    break;
                case 'forward':
                    openMessageForwardModal($bubble, id, body, preview);
                    break;
                case 'info':
                    openMessageInfoModal($bubble, id);
                    break;
                case 'delete':
                    if (window.toastr) toastr.warning('La eliminación de mensajes no está habilitada');
                    break;
            }
        });

        // ─── Reaction picker ─────────────────────────────────────────────
        const REACTION_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '🙏', '🔥', '👏'];

        function openReactionPicker($bubble, itemId) {
            $('#bv-reaction-picker').remove();
            const buttons = REACTION_EMOJIS.map(e =>
                '<button type="button" class="bv-reaction-btn" data-bv-emoji="' + e + '">' + e + '</button>'
            ).join('');
            const $picker = $('<div id="bv-reaction-picker" class="bv-reaction-picker">' + buttons + '</div>');
            $('body').append($picker);

            const r = $bubble[0].getBoundingClientRect();
            const w = $picker.outerWidth();
            const h = $picker.outerHeight();
            let left = r.left + (r.width - w) / 2;
            let top = r.top - h - 8;
            if (top < 8) top = r.bottom + 8;
            if (left < 8) left = 8;
            if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;
            $picker.css({ left: left + 'px', top: top + 'px' });

            const closer = (ev) => {
                if (!$(ev.target).closest('#bv-reaction-picker').length) {
                    $picker.remove();
                    document.removeEventListener('click', closer, true);
                }
            };
            setTimeout(() => document.addEventListener('click', closer, true), 0);

            $picker.on('click', '.bv-reaction-btn', function () {
                const emoji = $(this).data('bv-emoji');
                $picker.remove();
                document.removeEventListener('click', closer, true);

                $.ajax({
                    url: '/panel/helpdesk/messages/' + itemId + '/react',
                    method: 'POST',
                    dataType: 'json',
                    data: { emoji },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                }).done(function (resp) {
                    // Sin esto la reacción se guardaba bien (200 confirmado) pero
                    // no se veía en la burbuja hasta recargar — no había ningún
                    // feedback de que la acción hizo algo.
                    //
                    // Reconstruye el mismo contenedor que el blade pinta al cargar
                    // la página (`.bv-bubble-reactions > .bv-reaction[data-bv-react]`,
                    // agrupado por emoji con contador, desde metadata['reactions']) —
                    // usar una clase distinta duplicaba visualmente la reacción ya
                    // renderizada por el servidor en vez de sincronizarse con ella.
                    const counts = {};
                    (resp.reactions || []).forEach(function (r) {
                        if (r && r.emoji) counts[r.emoji] = (counts[r.emoji] || 0) + 1;
                    });
                    let $wrap = $bubble.find('.bv-bubble-reactions');
                    const emojis = Object.keys(counts);
                    if (!emojis.length) {
                        $wrap.remove();
                        return;
                    }
                    if (!$wrap.length) {
                        $wrap = $('<div class="bv-bubble-reactions"></div>');
                        const $meta = $bubble.find('.meta').first();
                        if ($meta.length) $meta.before($wrap); else $bubble.append($wrap);
                    }
                    $wrap.empty();
                    emojis.forEach(function (e) {
                        $('<button type="button" class="bv-reaction"></button>')
                            .attr('data-bv-react', e)
                            .attr('data-bv-item', itemId)
                            .append(document.createTextNode(e + ' '))
                            .append($('<span class="c"></span>').text(counts[e]))
                            .appendTo($wrap);
                    });
                }).fail(function (xhr) {
                    if (window.toastr) {
                        const msg = xhr?.responseJSON?.message || 'No se pudo registrar la reacción';
                        toastr.error(msg);
                    }
                });
            });
        }

        // ─── Hover toolbar en burbujas (responder / reaccionar) ───────────
        // "reply" y "react" ya existían completos (backend + frontend) pero
        // solo se disparaban por clic derecho / long-press — nada en la UI
        // insinuaba que existían, así que en la práctica nadie los usaba. Un
        // botón visible al pasar el ratón reutiliza exactamente la misma
        // lógica (bv:set-reply / openReactionPicker) sin duplicarla.
        function bubbleActionData($bubble) {
            const body = ($bubble.data('bv-body') || '').toString();
            return {
                id: $bubble.data('bv-item-id'),
                author: $bubble.data('bv-author') || '',
                preview: ($bubble.data('bv-body-preview') || body.slice(0, 80)).toString(),
            };
        }

        $(document).on('mouseenter', '.bv-bubble', function () {
            const $bubble = $(this);
            if ($bubble.find('.bv-bubble-hover-actions').length) return;
            $bubble.append(
                '<div class="bv-bubble-hover-actions">' +
                    '<button type="button" class="bv-bubble-hover-btn" data-bv-hover-action="reply" data-bv-tip="Responder citando" aria-label="Responder citando"><i class="fas fa-reply"></i></button>' +
                    '<button type="button" class="bv-bubble-hover-btn" data-bv-hover-action="react" data-bv-tip="Reaccionar" aria-label="Reaccionar"><i class="far fa-face-smile"></i></button>' +
                '</div>'
            );
        });

        $(document).on('mouseleave', '.bv-bubble', function () {
            $(this).find('.bv-bubble-hover-actions').remove();
        });

        $(document).on('click', '.bv-bubble-hover-btn', function (e) {
            e.stopPropagation();
            const $bubble = $(this).closest('.bv-bubble');
            const data = bubbleActionData($bubble);
            if ($(this).data('bv-hover-action') === 'reply') {
                $(document).trigger('bv:set-reply', { id: data.id, author: data.author, body: data.preview });
            } else {
                openReactionPicker($bubble, data.id);
            }
        });

        // ─── Forward modal (reenviar mensaje a otro cliente) ─────────────
        // Nota: existe otro `openForwardModal` más abajo para attachments —
        // este se llama distinto para no chocar con el otro en el closure.
        function openMessageForwardModal($bubble, itemId, body, preview) {
            $('#bv-msg-forward-modal').remove();
            $('#bv-forward-modal').remove();
            const previewText = preview || (body || '').slice(0, 120) || '(sin texto)';
            const $modal = $(
                '<div id="bv-msg-forward-modal" class="bv-modal on" role="dialog" aria-modal="true">' +
                    '<div class="bv-modal-dialog">' +
                        '<div class="bv-modal-head">' +
                            '<div class="bv-modal-title"><i class="fas fa-share"></i> Reenviar mensaje</div>' +
                            '<button type="button" class="bv-modal-close" aria-label="Cerrar">' +
                                '<i class="fas fa-xmark"></i>' +
                            '</button>' +
                        '</div>' +
                        '<div class="bv-modal-body">' +
                            '<div class="bv-fwd-preview">' + escape(previewText) + '</div>' +
                            '<label class="bv-modal-label">Buscar conversación o cliente</label>' +
                            '<input type="text" id="bv-fwd-search" class="bv-modal-input" placeholder="Nombre, email o asunto…" autocomplete="off">' +
                            '<div id="bv-fwd-results" class="bv-fwd-results"></div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            applyModalA11y($modal, 'msg-forward');
            $('body').append($modal);
            setTimeout(() => $modal.find('#bv-fwd-search').trigger('focus'), 50);

            const close = () => $modal.remove();
            $modal.on('click', '.bv-modal-close, .bv-modal', function (ev) {
                if (ev.target === this) close();
            });

            let searchTimer = null;
            $modal.on('input', '#bv-fwd-search', function () {
                const q = $(this).val();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    if (!q || q.length < 2) {
                        $('#bv-fwd-results').html('<div class="bv-fwd-hint">Escribe al menos 2 caracteres…</div>');
                        return;
                    }
                    $('#bv-fwd-results').html('<div class="bv-fwd-hint">Buscando…</div>');
                    $.ajax({
                        url: '/panel/helpdesk/customers/search',
                        method: 'GET',
                        data: { q, limit: 8 },
                        dataType: 'json',
                        headers: { 'Accept': 'application/json' },
                    }).done(function (resp) {
                        const list = resp?.data || resp?.customers || resp || [];
                        if (!Array.isArray(list) || !list.length) {
                            $('#bv-fwd-results').html('<div class="bv-fwd-hint">Sin resultados</div>');
                            return;
                        }
                        const html = list.map(c => (
                            '<button type="button" class="bv-fwd-result" data-bv-customer-id="' + (c.id || '') + '">' +
                                '<div class="bv-fwd-name">' + escape(c.name || c.firstname || c.email || 'Sin nombre') + '</div>' +
                                '<div class="bv-fwd-email">' + escape(c.email || c.phone || '') + '</div>' +
                            '</button>'
                        )).join('');
                        $('#bv-fwd-results').html(html);
                    }).fail(function () {
                        $('#bv-fwd-results').html('<div class="bv-fwd-hint is-error">Error al buscar</div>');
                    });
                }, 250);
            });

            $modal.on('click', '.bv-fwd-result', function () {
                const customerId = $(this).data('bv-customer-id');
                if (!customerId) return;
                $.ajax({
                    url: '/panel/helpdesk/messages/' + itemId + '/forward',
                    method: 'POST',
                    dataType: 'json',
                    data: { customer_id: customerId },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                }).done(function (resp) {
                    close();
                    if (window.toastr) toastr.success(resp?.message || 'Mensaje reenviado');
                }).fail(function (xhr) {
                    const msg = xhr?.responseJSON?.message || 'No se pudo reenviar';
                    if (window.toastr) toastr.error(msg);
                });
            });
        }

        // ─── Message info modal ──────────────────────────────────────────
        function openMessageInfoModal($bubble, itemId) {
            $('#bv-msg-info-modal').remove();
            const $modal = $(
                '<div id="bv-msg-info-modal" class="bv-modal on" role="dialog" aria-modal="true">' +
                    '<div class="bv-modal-dialog">' +
                        '<div class="bv-modal-head">' +
                            '<div class="bv-modal-title"><i class="fas fa-circle-info"></i> Información del mensaje</div>' +
                            '<button type="button" class="bv-modal-close" aria-label="Cerrar">' +
                                '<i class="fas fa-xmark"></i>' +
                            '</button>' +
                        '</div>' +
                        '<div class="bv-modal-body" id="bv-msg-info-body">' +
                            '<div class="bv-fwd-hint">Cargando…</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            applyModalA11y($modal, 'msg-info');
            $('body').append($modal);
            $modal.on('click', '.bv-modal-close, .bv-modal', function (ev) {
                if (ev.target === this) $modal.remove();
            });

            $.ajax({
                url: '/panel/helpdesk/messages/' + itemId + '/info',
                method: 'GET',
                dataType: 'json',
                headers: { 'Accept': 'application/json' },
            }).done(function (resp) {
                const d = resp?.data || resp || {};
                const fmt = (s) => s ? new Date(s).toLocaleString() : '—';
                const rows = [
                    ['Enviado', fmt(d.sent_at || d.created_at)],
                    ['Entregado al cliente', fmt(d.delivered_at || d.customer_delivered_at)],
                    ['Leído por el cliente', fmt(d.read_at || d.customer_read_at)],
                    ['Autor', d.author_name || d.sender_name || '—'],
                    ['Canal', d.channel || '—'],
                    ['ID externo', d.external_id || '—'],
                ];
                const html = rows.map(([k, v]) => (
                    '<div class="bv-msg-info-row">' +
                        '<div class="bv-msg-info-label">' + escape(k) + '</div>' +
                        '<div class="bv-msg-info-value">' + escape(String(v)) + '</div>' +
                    '</div>'
                )).join('');
                $('#bv-msg-info-body').html(html);
            }).fail(function (xhr) {
                const msg = xhr?.responseJSON?.message || 'No se pudo cargar la información';
                $('#bv-msg-info-body').html('<div class="bv-fwd-hint is-error">' + escape(msg) + '</div>');
            });
        }

        // ─── Attach menu actions (document/image/audio/video/contact/location)
        const attachAcceptMap = {
            document: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip',
            image: 'image/*',
            audio: 'audio/*',
            video: 'video/*',
        };

        function ensureFilePicker() {
            if (!document.getElementById('bv-file-picker')) {
                $('body').append('<input type="file" id="bv-file-picker" hidden multiple>');
            }
            return document.getElementById('bv-file-picker');
        }

        function appendOptimisticUploadBubble(files) {
            const $inner = $('.bv-th-inner');
            if (!$inner.length) return null;
            const placeholders = Array.from(files).map(f => {
                const isImg = (f.type || '').startsWith('image/');
                const preview = isImg ? URL.createObjectURL(f) : null;
                const fname = f.name || 'archivo';
                const fsize = f.size ? Math.round(f.size / 1024) + ' KB' : '';
                if (preview) {
                    return '<div class="bv-attach-placeholder" style="background:transparent;padding:0;min-height:auto">' +
                        '<img src="' + preview + '" alt="' + escape(fname) + '" style="width:200px;border-radius:8px;opacity:0.6">' +
                        '<div class="bv-attach-placeholder-spinner"></div>' +
                    '</div>';
                }
                return '<div class="bv-attach-placeholder">' +
                    '<div class="bv-attach-placeholder-spinner"></div>' +
                    '<div>' + escape(fname) + (fsize ? ' · ' + fsize : '') + '</div>' +
                '</div>';
            }).join('');

            const $bubble = $(
                '<div class="bv-msg out" data-bv-optimistic="1">' +
                    '<div class="bv-bubble">' +
                        '<div class="bv-attachment-gallery">' + placeholders + '</div>' +
                        '<div class="meta">' +
                            '<span>Tú · subiendo…</span>' +
                            '<span class="chk read bv-chk-read" style="opacity:0.4">⏳</span>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $inner.append($bubble);
            scrollThreadToBottom(true);
            return $bubble;
        }

        async function uploadFiles(files) {
            const convId = $('.bv-composer').data('bv-conversation-id');
            if (!convId || !files || !files.length) return;

            const fd = new FormData();
            for (const f of files) fd.append('files[]', f);

            $('#bv-upload-progress').removeClass('bv-hidden');
            $('#bv-upload-bar').css('width', '20%');
            const $optimistic = appendOptimisticUploadBubble(files);

            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/conversations/' + convId + '/attachments',
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                    xhr: function () {
                        const x = new window.XMLHttpRequest();
                        x.upload.addEventListener('progress', function (e) {
                            if (e.lengthComputable) {
                                const pct = Math.round((e.loaded / e.total) * 90);
                                $('#bv-upload-bar').css('width', pct + '%');
                            }
                        }, false);
                        return x;
                    },
                });
                $('#bv-upload-bar').css('width', '100%');
                if ($optimistic) $optimistic.remove();
                if (resp?.item && typeof window.appendBubbleToThread === 'function') {
                    window.appendBubbleToThread(resp.item, false);
                }
                setTimeout(() => {
                    $('#bv-upload-progress').addClass('bv-hidden');
                    $('#bv-upload-bar').css('width', '0%');
                }, 600);
            } catch (xhr) {
                $('#bv-upload-progress').addClass('bv-hidden');
                if ($optimistic) $optimistic.remove();
                const msg = xhr?.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors)[0]?.[0]
                    : (xhr?.responseJSON?.message || 'No se pudo subir el archivo');
                if (window.toastr) toastr.error(msg);
            }
        }

        $(document).on('click', '[data-bv-attach-type]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const type = $(this).data('bv-attach-type');
            $('#bv-attach-menu').removeClass('on');

            if (type === 'store') {
                // El click ya abre el modal store-picker via data-bv-modal — no hacer nada extra
                return;
            }

            if (type === 'contact' || type === 'location') {
                // Legacy types — ahora ocultos por la opción Tienda
                return;
            }

            if (type === 'record') {
                startVoiceRecording();
                return;
            }

            const accept = attachAcceptMap[type] || '';
            const picker = ensureFilePicker();
            picker.setAttribute('accept', accept);
            picker.value = '';
            picker.click();
        });

        // ─── Voice recorder ───────────────────────────────────────────
        let mediaRecorder = null;
        let audioChunks = [];
        let recorderCancelled = false;
        let recordTimer = null;
        let recordSeconds = 0;

        function showRecorderUI() {
            if (document.getElementById('bv-recorder-bar')) return;
            const $bar = $(
                '<div id="bv-recorder-bar" class="bv-recorder-bar">' +
                    '<span class="bv-recorder-pulse"></span>' +
                    '<span class="bv-recorder-time" id="bv-recorder-time">0:00</span>' +
                    '<button class="bv-recorder-stop" id="bv-recorder-stop"><i class="fas fa-stop"></i> Enviar</button>' +
                    '<button class="bv-recorder-cancel" id="bv-recorder-cancel"><i class="fas fa-xmark"></i> Cancelar</button>' +
                '</div>'
            );
            $('.bv-composer').prepend($bar);
        }

        function hideRecorderUI() {
            $('#bv-recorder-bar').remove();
            clearInterval(recordTimer);
            recordTimer = null;
            recordSeconds = 0;
        }

        function tickRecorderTime() {
            recordSeconds++;
            const m = Math.floor(recordSeconds / 60);
            const s = String(recordSeconds % 60).padStart(2, '0');
            $('#bv-recorder-time').text(m + ':' + s);
        }

        async function startVoiceRecording() {
            if (!navigator.mediaDevices?.getUserMedia) {
                if (window.toastr) toastr.error('Tu navegador no soporta grabación de audio. Usa "Subir audio" para enviar un archivo.');
                return;
            }

            // No pre-check con permissions.query: en Chrome puede estar desincronizado.
            // getUserMedia es la fuente real de verdad: si hay permiso, abre el stream;
            // si no, lanza NotAllowedError y mostramos el helper.
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioChunks = [];
                recorderCancelled = false;
                mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
                mediaRecorder.ondataavailable = e => {
                    if (recorderCancelled) return;
                    audioChunks.push(e.data);
                };
                mediaRecorder.onstop = async () => {
                    stream.getTracks().forEach(t => t.stop());
                    if (!recorderCancelled && audioChunks.length) {
                        const blob = new Blob(audioChunks, { type: 'audio/webm' });
                        const file = new File([blob], 'voz-' + Date.now() + '.webm', { type: 'audio/webm' });
                        await uploadFiles([file]);
                    }
                    audioChunks = [];
                    hideRecorderUI();
                };
                showRecorderUI();
                recordSeconds = 0;
                $('#bv-recorder-time').text('0:00');
                recordTimer = setInterval(tickRecorderTime, 1000);
                mediaRecorder.start();
            } catch (err) {
                console.error('[bv-mic] getUserMedia error:', err.name, err.message, err);
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    showMicDeniedHelp(err);
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    if (window.toastr) toastr.error('No se detectó ningún micrófono conectado');
                } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                    if (window.toastr) toastr.error('El micrófono está siendo usado por otra aplicación. Cierra Zoom/Meet/etc. y reintenta.', '', { timeOut: 8000 });
                } else if (err.name === 'SecurityError') {
                    if (window.toastr) toastr.error('Error de seguridad: el micrófono solo funciona en HTTPS o localhost.', '', { timeOut: 8000 });
                } else {
                    if (window.toastr) toastr.error('Error al acceder al micrófono (' + err.name + '): ' + err.message, '', { timeOut: 8000 });
                }
            }
        }

        async function showMicDeniedHelp(err) {
            const origin = window.location.origin;
            const isSecure = window.isSecureContext;

            // Resolve real permission state up front so the modal renders the right guidance
            let permState = 'unknown';
            try {
                if (navigator.permissions) {
                    const p = await navigator.permissions.query({ name: 'microphone' });
                    permState = p.state; // 'granted' | 'denied' | 'prompt'
                }
            } catch (e) { /* unsupported */ }

            // Detect "labels empty" — confirms the OS/browser is hiding device identity
            let labelsHidden = false;
            try {
                const list = await navigator.mediaDevices.enumerateDevices();
                labelsHidden = list.some(d => d.kind === 'audioinput' && !d.label);
            } catch (e) {}

            const isMac = /Mac|iPhone|iPad/.test(navigator.platform);
            const browserName = (() => {
                const ua = navigator.userAgent;
                if (/Edg\//.test(ua)) return 'Microsoft Edge';
                if (/Chrome\//.test(ua)) return 'Google Chrome';
                if (/Firefox\//.test(ua)) return 'Firefox';
                if (/Safari\//.test(ua)) return 'Safari';
                return 'tu navegador';
            })();

            // When state==='denied' but the site UI shows permitted, this is the classic
            // sticky-deny + macOS override scenario. Make it explicit.
            const stickyDenyNotice = permState === 'denied' ? (
                '<div class=”perm-notice-denied”>' +
                    '<strong>⚠️ Activar el switch del sitio NO es suficiente</strong><br>' +
                    'El navegador reporta <code>permission: denied</code>. El switch per-site guarda preferencias ' +
                    'pero <strong>no resetea el estado runtime</strong> que el navegador cachea tras un bloqueo previo. ' +
                    'Sigue los pasos de abajo en orden.' +
                '</div>'
            ) : '';

            const macInstructions = isMac ? (
                '<div class=”perm-step”>' +
                    '<div class=”h”><span class=”num”>1</span> Permitir el micrófono en macOS</div>' +
                    '<ul>' +
                        '<li>Abre <b>Ajustes del sistema</b> → Privacidad y seguridad → Micrófono</li>' +
                        '<li>Activa el switch para <b>' + browserName + '</b></li>' +
                        '<li>Si ya estaba activo, <b>desactívalo y vuelve a activarlo</b></li>' +
                        '<li>Cierra completamente ' + browserName + ' (<b>Cmd+Q</b>) y reábrelo</li>' +
                    '</ul>' +
                    '<button type=”button” class=”btn btn-secondary btn-sm” id=”bv-mic-open-mac-prefs” style=”align-self:flex-start”>Abrir Ajustes del sistema</button>' +
                '</div>'
            ) : '';

            const browserResetSection =
                '<div class=”perm-step”>' +
                    '<div class=”h”><span class=”num”>' + (isMac ? '2' : '1') + '</span> Resetear el permiso del sitio</div>' +
                    '<div class=”hint”>Esta es la forma <b>rápida</b> que sí funciona: usa “Restablecer permisos”, no toques el switch del micrófono.</div>' +
                    '<ul>' +
                        '<li>Click en el icono <i class=”fas fa-lock” style=”font-size:9px”></i> a la izquierda de <code style=”background:#e5e7eb;padding:1px 4px;border-radius:3px;font-family:monospace;font-size:10.5px”>' + origin + '</code> en la barra de direcciones</li>' +
                        '<li>Click en <b>”Restablecer permisos”</b> (al final del popup)</li>' +
                        '<li><b>Recarga la página</b> (Cmd+R)</li>' +
                        '<li>Vuelve a hacer click en el botón del micrófono → ahora saldrá el prompt nativo</li>' +
                        '<li>Click en <b>Permitir</b></li>' +
                    '</ul>' +
                '</div>' +
                '<div class=”perm-step”>' +
                    '<div class=”h”><span class=”num”>' + (isMac ? '3' : '2') + '</span> Si Chrome tiene bloqueado el micrófono globalmente</div>' +
                    '<div style=”font-size:11.5px;color:#6b7280;line-height:1.55”>Pega en una pestaña nueva:</div>' +
                    '<div class=”perm-url”>' +
                        '<span>chrome://settings/content/microphone</span>' +
                        '<button class=”copy” data-bv-copy=”chrome://settings/content/microphone” title=”Copiar”><i class=”far fa-copy”></i></button>' +
                    '</div>' +
                    '<ul>' +
                        '<li>En “<b>Comportamiento predeterminado</b>” verifica que esté “Los sitios pueden pedirte usar tu micrófono”</li>' +
                        '<li>En “<b>No permitir</b>” busca <code style=”background:#e5e7eb;padding:1px 4px;border-radius:3px;font-family:monospace;font-size:10.5px”>' + origin + '</code> y elimínalo si aparece</li>' +
                    '</ul>' +
                '</div>';

            const diag = '<div class=”perm-diag”>' +
                '<div class=”h”>Diagnóstico</div>' +
                '<div>Origen: <code>' + origin + '</code></div>' +
                '<div>Contexto seguro: <code>' + (isSecure ? 'sí' : 'NO ❌') + '</code></div>' +
                '<div>Permiso (Permissions API): <code>' + permState + '</code></div>' +
                '<div>Etiquetas de dispositivo ocultas: <code>' + (labelsHidden ? 'sí (sin acceso real)' : 'no') + '</code></div>' +
                (err ? '<div>Error: <code>' + (err.name || 'Error') + ': ' + (err.message || '') + '</code></div>' : '') +
                '</div>';

            // El botón “Reintentar sin recargar” sólo funciona si el estado fue resetado a “prompt”.
            // Cuando es “denied” estable, NO produce re-prompt — lo ocultamos para no confundir.
            const retryBtn = permState !== 'denied'
                ? '<button class=”btn btn-secondary w-100” id=”bv-mic-denied-retry”>Reintentar sin recargar</button>'
                : '';

            const $modal = $(
                '<div id=”bv-mic-denied” class=”hd-overlay” style=”z-index:99999;padding-top:5vh”>' +
                    '<div class=”hd-modal w-md” style=”max-height:90vh;display:flex;flex-direction:column”>' +
                        '<div class=”modal-head”>' +
                            '<div class=”modal-icon” style=”background:#f3f4f6;color:#374151”><i class=”fas fa-microphone-slash”></i></div>' +
                            '<div class=”modal-title-wrap”>' +
                                '<span class=”modal-label”>PERMISO · NAVEGADOR</span>' +
                                '<span class=”modal-title”>No se pudo acceder al micrófono</span>' +
                            '</div>' +
                            '<button class=”modal-close” id=”bv-mic-denied-close”><i class=”fas fa-xmark”></i></button>' +
                        '</div>' +
                        '<div class=”modal-body” style=”overflow-y:auto;flex:1”>' +
                            '<div class=”perm-hero”>' +
                                '<div class=”ic-circle”><i class=”fas fa-microphone-slash”></i></div>' +
                                '<div class=”t”>No se pudo acceder al micrófono</div>' +
                            '</div>' +
                            stickyDenyNotice +
                            macInstructions +
                            browserResetSection +
                            diag +
                        '</div>' +
                        '<div class=”modal-foot modal-foot--stack”>' +
                            '<button class=”btn btn-primary w-100” id=”bv-mic-denied-reload”>Recargar página</button>' +
                            retryBtn +
                            '<button class=”btn btn-secondary w-100” id=”bv-mic-denied-close2”>Salir sin micrófono</button>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($modal);
        }

        // Copy-to-clipboard helper para el chrome://settings/...
        $(document).on('click', '.bv-mic-denied-copy, .bv-mic-denied-url, .perm-url .copy, .perm-url span', async function () {
            const text = $(this).data('bv-copy');
            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
            }
        });

        $(document).on('click', '#bv-mic-denied-reload', function () {
            window.location.reload();
        });

        $(document).on('click', '#bv-mic-open-mac-prefs', function () {
            // El esquema x-apple.systempreferences solo funciona desde Safari; igualmente lo intentamos.
            window.location.href = 'x-apple.systempreferences:com.apple.preference.security?Privacy_Microphone';
        });

        $(document).on('click', '#bv-mic-denied-close, #bv-mic-denied-close2, .hd-overlay#bv-mic-denied', function (e) {
            if (e.target.id === 'bv-mic-denied-close' || e.target.id === 'bv-mic-denied-close2' || e.target.id === 'bv-mic-denied') {
                $('#bv-mic-denied').remove();
            }
        });

        $(document).on('click', '#bv-mic-denied-upload', function () {
            $('#bv-mic-denied').remove();
            const picker = ensureFilePicker();
            picker.setAttribute('accept', 'audio/*');
            picker.value = '';
            picker.click();
        });

        // Retry mic permission — fuerza re-prompt del navegador (solo funciona si el user reseteó el block)
        $(document).on('click', '#bv-mic-denied-retry', async function () {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                stream.getTracks().forEach(t => t.stop());
                $('#bv-mic-denied').remove();
            } catch (e) {
                if (window.toastr) {
                    toastr.error('Sigue bloqueado. Usa el icono 🔒 en la URL para permitirlo manualmente.', '', { timeOut: 8000 });
                }
            }
        });

        // ─── Store picker: enviar tienda como mensaje ────────────────
        $(document).on('click', '.bv-store-item', async function () {
            const $btn = $(this);
            const data = {
                name: $btn.data('bv-store-name'),
                address: $btn.data('bv-store-address'),
                phone: $btn.data('bv-store-phone'),
                email: $btn.data('bv-store-email'),
            };
            const lines = ['🏪 *' + data.name + '*'];
            if (data.address) lines.push('📍 ' + data.address);
            if (data.phone) lines.push('📞 ' + data.phone);
            if (data.email) lines.push('✉️ ' + data.email);
            const body = lines.join('\n');

            const convId = $('.bv-composer').data('bv-conversation-id');
            if (!convId) return;

            $btn.prop('disabled', true);
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/conversations/' + convId + '/messages',
                    method: 'POST',
                    dataType: 'json',
                    data: { body, action: 'send' },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                });
                // Sin toast: el bubble aparece en el thread como confirmación visual
                $('[data-bv-modal-name="store-picker"]').removeClass('on');
                $('body').css('overflow', '');
                if (resp?.item && typeof window.appendBubbleToThread === 'function') {
                    window.appendBubbleToThread(resp.item, false);
                }
            } catch (e) {
                if (window.toastr) toastr.error('No se pudo compartir la tienda');
            } finally {
                $btn.prop('disabled', false);
            }
        });

        // Search dentro del modal store-picker
        $(document).on('input', '#bv-store-search-input', function () {
            const q = $(this).val().toLowerCase().trim();
            $('.bv-store-item').each(function () {
                const text = ($(this).text() || '').toLowerCase();
                $(this).toggle(!q || text.includes(q));
            });
        });

        $(document).on('click', '#bv-recorder-stop', function () {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
        });

        $(document).on('click', '#bv-recorder-cancel', function () {
            recorderCancelled = true;
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            } else {
                hideRecorderUI();
            }
        });

        $(document).on('change', '#bv-file-picker', function (e) {
            const files = e.target.files;
            if (files && files.length) uploadFiles(files);
        });

        // Drag & drop files into composer
        $(document).on('dragover', '.bv-composer-input, .bv-composer-box', function (e) {
            e.preventDefault();
            $(this).addClass('bv-dragover');
        });
        $(document).on('dragleave drop', '.bv-composer-input, .bv-composer-box', function (e) {
            $(this).removeClass('bv-dragover');
        });
        $(document).on('drop', '.bv-composer-input, .bv-composer-box', function (e) {
            e.preventDefault();
            const files = e.originalEvent.dataTransfer?.files;
            if (files && files.length) uploadFiles(files);
        });

        // ─── Emoji picker ─────────────────────────────────────────────
        const emojiCategories = {
            smileys: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇','🥰','😍','🤩','😘','🤗','🤔','😎'],
            reactions: ['👍','👎','❤️','🔥','🎉','💯','👏','🙏','🤝','💪','✅','❌','⚠️','⭐','💡','📌','✨','💬','📞','🚀'],
            objects: ['📞','📱','💻','📧','📅','📎','📂','📦','🛍️','🏷️','💳','💰','🎁','📈','📊','🗓️','⏰','⏳','📍','🌍'],
        };

        function buildEmojiPicker() {
            if (document.getElementById('bv-emoji-picker')) return;
            const $p = $('<div id="bv-emoji-picker" class="bv-emoji-picker bv-hidden"></div>');
            const $tabs = $('<div class="bv-emoji-tabs"></div>');
            const $grid = $('<div class="bv-emoji-grid"></div>');
            ['smileys', 'reactions', 'objects'].forEach((cat, i) => {
                const labelMap = { smileys: '😀', reactions: '👍', objects: '📦' };
                $tabs.append(
                    $('<button class="bv-emoji-tab"></button>')
                        .text(labelMap[cat])
                        .attr('data-bv-emoji-cat', cat)
                        .toggleClass('on', i === 0)
                );
            });
            renderEmojiGrid($grid, 'smileys');
            $p.append($tabs).append($grid);
            $('body').append($p);
        }

        function renderEmojiGrid($grid, cat) {
            $grid.empty();
            (emojiCategories[cat] || []).forEach(em => {
                $grid.append($('<button class="bv-emoji-cell"></button>').text(em).attr('data-bv-emoji', em));
            });
        }

        function positionEmojiPicker($trigger) {
            const $p = $('#bv-emoji-picker');
            const rect = $trigger[0].getBoundingClientRect();
            const pickerW = 280;
            const pickerH = $p.outerHeight() || 280;
            // Render encima del botón; si no cabe arriba, debajo
            const fitsAbove = rect.top - 8 >= pickerH;
            const top = fitsAbove ? rect.top - pickerH - 6 : rect.bottom + 6;
            // Alinea por la derecha del botón pero clamp al viewport
            let left = rect.right - pickerW;
            if (left < 8) left = 8;
            if (left + pickerW > window.innerWidth - 8) left = window.innerWidth - pickerW - 8;
            $p.css({
                position: 'fixed',
                top: top + 'px',
                left: left + 'px',
                zIndex: 9999,
            });
        }

        $(document).on('click', '#bv-btn-emoji', function (e) {
            e.preventDefault();
            e.stopPropagation();
            buildEmojiPicker();
            const $p = $('#bv-emoji-picker');
            if ($p.hasClass('bv-hidden')) {
                positionEmojiPicker($(this));
                $p.removeClass('bv-hidden');
            } else {
                $p.addClass('bv-hidden');
            }
        });

        $(document).on('click', '.bv-emoji-tab', function () {
            const cat = $(this).data('bv-emoji-cat');
            $('.bv-emoji-tab').removeClass('on');
            $(this).addClass('on');
            renderEmojiGrid($('.bv-emoji-grid'), cat);
        });

        $(document).on('click', '.bv-emoji-cell', function () {
            const em = $(this).data('bv-emoji');
            const $ta = $('.bv-composer-input').first();
            const ta = $ta[0];
            const start = ta.selectionStart || 0;
            const end = ta.selectionEnd || 0;
            const v = $ta.val();
            $ta.val(v.slice(0, start) + em + v.slice(end));
            ta.selectionStart = ta.selectionEnd = start + em.length;
            ta.focus();
            $('#bv-emoji-picker').addClass('bv-hidden');
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-emoji-picker, #bv-btn-emoji').length) {
                $('#bv-emoji-picker').addClass('bv-hidden');
            }
        });

        // ─── Mention picker (@) ──────────────────────────────────────
        // Trigger: click en bv-btn-mention (inserta '@' y abre menú) o tipear '@' en el textarea.
        let mentionAnchor = null; // posición del '@' que disparó el menú (selectionStart al momento)
        let mentionItems = []; // unificado: [{type:'special'|'agent'|'team', handle, label, sub, ...}]
        let mentionSelectedIdx = 0;
        let mentionFetchTimer = null;
        let mentionFetchSeq = 0;

        const MENTION_SPECIALS = [
            { type: 'special', handle: 'all',  label: '@all',  sub: 'Notifica a todos los agentes',                  icon: 'fas fa-globe' },
            { type: 'special', handle: 'here', label: '@here', sub: 'Solo a quien está conectado ahora',             icon: 'fas fa-bolt' },
            { type: 'special', handle: 'team', label: '@team', sub: 'Al equipo asignado a esta conversación',        icon: 'fas fa-users-rectangle' },
        ];

        function buildMentionMenu() {
            if (document.getElementById('bv-mention-menu')) return;
            const $m = $(
                '<div id="bv-mention-menu" class="bv-mention-menu bv-hidden" role="listbox">' +
                    '<div class="bv-mention-list"></div>' +
                    '<div class="bv-mention-foot">' +
                        '<span><kbd>↑</kbd><kbd>↓</kbd> navegar</span>' +
                        '<span><kbd>↵</kbd> insertar</span>' +
                        '<span><kbd>Esc</kbd> cerrar</span>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($m);
        }

        function positionMentionMenu($trigger) {
            const $m = $('#bv-mention-menu');
            const rect = $trigger[0].getBoundingClientRect();
            const menuH = $m.outerHeight() || 280;
            const menuW = 320;
            const fitsAbove = rect.top - 8 >= menuH;
            // Posiciona arriba o abajo del textarea, alineado al borde izquierdo
            const top = fitsAbove ? rect.top - menuH - 6 : rect.bottom + 6;
            let left = rect.left;
            if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
            if (left < 8) left = 8;
            $m.css({
                position: 'fixed',
                top: top + 'px',
                left: left + 'px',
                width: menuW + 'px',
                zIndex: 9999,
            });
        }

        function renderMentionItemHtml(item, idx, selected) {
            const esc = (s) => $('<i>').text(s == null ? '' : String(s)).html();
            const cls = 'bv-mention-row' + (selected ? ' on' : '');

            if (item.type === 'special') {
                return '<button type="button" class="' + cls + '" role="option" data-bv-mention-idx="' + idx + '">' +
                    '<span class="bv-mention-av is-special"><i class="' + item.icon + '"></i></span>' +
                    '<span class="bv-mention-body">' +
                        '<span class="bv-mention-name">' + esc(item.label) + '</span>' +
                        '<span class="bv-mention-meta">' + esc(item.sub) + '</span>' +
                    '</span>' +
                    '<span class="bv-mention-tag">Especial</span>' +
                '</button>';
            }

            if (item.type === 'team') {
                const t = item.data;
                const members = parseInt(t.members_count || 0, 10);
                return '<button type="button" class="' + cls + '" role="option" data-bv-mention-idx="' + idx + '">' +
                    '<span class="bv-mention-av is-team"><i class="fas fa-users"></i></span>' +
                    '<span class="bv-mention-body">' +
                        '<span class="bv-mention-name">' + esc(t.name) + '</span>' +
                        '<span class="bv-mention-meta">@' + esc(t.key) + ' · ' + members + (members === 1 ? ' miembro' : ' miembros') + '</span>' +
                    '</span>' +
                    '<span class="bv-mention-tag">Equipo</span>' +
                '</button>';
            }

            // Agent
            const a = item.data;
            const colorIdx = ((a.id || 0) % 6) + 1;
            const statusClass = a.status || (a.online ? 'online' : 'offline');
            return '<button type="button" class="' + cls + '" role="option" data-bv-mention-idx="' + idx + '">' +
                '<span class="bv-av c' + colorIdx + ' bv-mention-av-bv">' + esc(a.initials || '?') +
                    '<span class="bv-av-dot ' + statusClass + '"></span></span>' +
                '<span class="bv-mention-body">' +
                    '<span class="bv-mention-name">' + esc(a.name) +
                        (a.role ? '<span class="bv-mention-role-badge">' + esc(a.role) + '</span>' : '') +
                    '</span>' +
                    '<span class="bv-mention-meta">@' + esc(a.username) + ' · ' + esc(a.status_label || (a.online ? 'En línea' : 'Offline')) + '</span>' +
                '</span>' +
            '</button>';
        }

        function renderMentionList(payload, term) {
            const $list = $('#bv-mention-menu .bv-mention-list').empty();
            mentionItems = [];
            mentionSelectedIdx = 0;
            const t = (term || '').toLowerCase();

            // Especiales (filtrar por handle)
            const specials = MENTION_SPECIALS.filter(s => !t || s.handle.startsWith(t));

            // Agentes (vienen ya filtrados del server)
            const agents = (payload?.agents || []).map(a => ({ type: 'agent', handle: a.username, data: a }));

            // Equipos (vienen ya filtrados del server)
            const teams = (payload?.teams || []).map(g => ({ type: 'team', handle: g.key, data: g }));

            const sections = [
                { title: 'Especiales', items: specials.map(s => ({ ...s })) },
                { title: 'Agentes',    items: agents },
                { title: 'Equipos',    items: teams },
            ].filter(s => s.items.length > 0);

            if (sections.length === 0) {
                $list.append('<div class="bv-mention-empty">Sin resultados para "' + $('<i>').text(t).html() + '"</div>');
                return;
            }

            let idx = 0;
            sections.forEach(section => {
                $list.append('<div class="bv-mention-section">' + section.title + '</div>');
                section.items.forEach(item => {
                    mentionItems.push(item);
                    $list.append(renderMentionItemHtml(item, idx, idx === 0));
                    idx++;
                });
            });
        }

        async function fetchAgentsForMention(term) {
            const seq = ++mentionFetchSeq;
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/api/agents-autocomplete',
                    method: 'GET',
                    data: { q: term || '' },
                    dataType: 'json',
                });
                if (seq !== mentionFetchSeq) return; // outdated response
                renderMentionList(resp || {}, term);
            } catch (e) {
                if (seq !== mentionFetchSeq) return;
                renderMentionList({}, term);
            }
        }

        function openMentionMenu($trigger, term) {
            buildMentionMenu();
            const $m = $('#bv-mention-menu');
            $m.removeClass('bv-hidden');
            positionMentionMenu($trigger);
            clearTimeout(mentionFetchTimer);
            mentionFetchTimer = setTimeout(() => fetchAgentsForMention(term), 120);
        }

        function closeMentionMenu() {
            $('#bv-mention-menu').addClass('bv-hidden');
            mentionAnchor = null;
            mentionItems = [];
        }

        function insertMentionAt($ta, anchorPos, item) {
            const ta = $ta[0];
            const v = $ta.val();
            // Reemplaza desde el '@' hasta la posición actual del cursor por '@handle '
            const cursor = ta.selectionStart || v.length;
            const before = v.slice(0, anchorPos);
            const after = v.slice(cursor);
            const handle = item.type === 'agent' ? item.data.username
                          : item.type === 'team' ? item.data.key
                          : item.handle; // special
            const replacement = '@' + handle + ' ';
            $ta.val(before + replacement + after);
            const newPos = before.length + replacement.length;
            ta.selectionStart = ta.selectionEnd = newPos;
            ta.focus();
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // ─── Popover info al click en .bv-mention-chip ─────────────────
        // Cachea la respuesta del agentes-autocomplete para resolver el handle
        // y muestra una mini-card con avatar, nombre, status y acciones rápidas.
        let mentionPopoverCache = null;
        let mentionPopoverFetchPromise = null;

        function ensureMentionPopoverData() {
            if (mentionPopoverCache !== null) return Promise.resolve(mentionPopoverCache);
            if (mentionPopoverFetchPromise) return mentionPopoverFetchPromise;
            mentionPopoverFetchPromise = $.ajax({
                url: '/panel/helpdesk/api/agents-autocomplete',
                method: 'GET',
                data: { q: '' },
                dataType: 'json',
            }).then(resp => {
                mentionPopoverCache = resp || { agents: [], teams: [] };
                mentionPopoverFetchPromise = null;
                return mentionPopoverCache;
            }).catch(() => {
                mentionPopoverFetchPromise = null;
                return { agents: [], teams: [] };
            });
            return mentionPopoverFetchPromise;
        }

        function buildMentionPopoverHtml(handle, data) {
            const esc = (s) => $('<i>').text(s == null ? '' : String(s)).html();
            const lower = handle.toLowerCase();

            // Especiales
            const specialMap = {
                all: { title: 'Todos los agentes', sub: 'Notifica a todos los miembros del workspace', icon: 'fas fa-globe' },
                here: { title: 'Agentes en línea', sub: 'Solo notifica a los que están conectados ahora', icon: 'fas fa-bolt' },
                team: { title: 'Equipo de la conversación', sub: 'Notifica al grupo asignado a esta conversación', icon: 'fas fa-users-rectangle' },
            };
            if (specialMap[lower]) {
                const s = specialMap[lower];
                return '<div class="bv-mention-pop-row">' +
                    '<div class="bv-mention-pop-av special"><i class="' + s.icon + '"></i></div>' +
                    '<div class="bv-mention-pop-body">' +
                        '<div class="bv-mention-pop-name">@' + esc(lower) + '</div>' +
                        '<div class="bv-mention-pop-sub">' + esc(s.title) + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="bv-mention-pop-desc">' + esc(s.sub) + '</div>';
            }

            // Equipo (key)
            const team = (data?.teams || []).find(t => (t.key || '').toLowerCase() === lower);
            if (team) {
                return '<div class="bv-mention-pop-row">' +
                    '<div class="bv-mention-pop-av team"><i class="fas fa-users"></i></div>' +
                    '<div class="bv-mention-pop-body">' +
                        '<div class="bv-mention-pop-name">' + esc(team.name) + '</div>' +
                        '<div class="bv-mention-pop-sub">@' + esc(team.key) + ' · ' + (team.members_count || 0) + ' miembros</div>' +
                    '</div>' +
                '</div>' +
                (team.description ? '<div class="bv-mention-pop-desc">' + esc(team.description) + '</div>' : '');
            }

            // Agente (username)
            const agent = (data?.agents || []).find(a => (a.username || '').toLowerCase() === lower);
            if (agent) {
                const colorIdx = ((agent.id || 0) % 6) + 1;
                const statusClass = agent.status || (agent.online ? 'online' : 'offline');
                return '<div class="bv-mention-pop-row">' +
                    '<div class="bv-av c' + colorIdx + ' bv-mention-pop-av-bv">' + esc(agent.initials || '?') +
                        '<span class="bv-av-dot ' + statusClass + '"></span></div>' +
                    '<div class="bv-mention-pop-body">' +
                        '<div class="bv-mention-pop-name">' + esc(agent.name) +
                            (agent.role ? '<span class="bv-mention-role-badge">' + esc(agent.role) + '</span>' : '') +
                        '</div>' +
                        '<div class="bv-mention-pop-sub">' + esc(agent.status_label || (agent.online ? 'En línea' : 'Offline')) +
                            (agent.email ? ' · ' + esc(agent.email) : '') + '</div>' +
                    '</div>' +
                '</div>';
            }

            // Sin resolver
            return '<div class="bv-mention-pop-row">' +
                '<div class="bv-mention-pop-av special"><i class="fas fa-question"></i></div>' +
                '<div class="bv-mention-pop-body">' +
                    '<div class="bv-mention-pop-name">@' + esc(handle) + '</div>' +
                    '<div class="bv-mention-pop-sub">Mención sin resolver</div>' +
                '</div>' +
            '</div>';
        }

        function positionMentionPopover($trigger) {
            const $p = $('#bv-mention-popover');
            const rect = $trigger[0].getBoundingClientRect();
            const w = $p.outerWidth() || 280;
            const h = $p.outerHeight() || 140;
            const fitsAbove = rect.top - 8 >= h;
            const top = fitsAbove ? rect.top - h - 8 : rect.bottom + 8;
            let left = rect.left + (rect.width / 2) - (w / 2);
            if (left < 8) left = 8;
            if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;
            $p.css({ position: 'fixed', top: top + 'px', left: left + 'px', zIndex: 9999 });
        }

        $(document).on('click', '.bv-mention-chip', async function (e) {
            e.preventDefault();
            e.stopPropagation();
            const handle = $(this).data('bv-mention-handle');
            if (!handle) return;
            const $trigger = $(this);

            $('#bv-mention-popover').remove();
            const $pop = $('<div id="bv-mention-popover" class="bv-mention-popover"><div class="bv-mention-pop-loading">Cargando…</div></div>');
            $('body').append($pop);
            positionMentionPopover($trigger);

            const data = await ensureMentionPopoverData();
            $pop.html(buildMentionPopoverHtml(String(handle), data));
            positionMentionPopover($trigger);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-mention-popover, .bv-mention-chip').length) {
                $('#bv-mention-popover').remove();
            }
        });

        // ─── Modal de mención (abre por data-bv-modal="mention") ──────
        // Soporta dos tabs: Agentes y Equipos. El dropdown inline al tipear @
        // sigue usándose para flujo rápido (solo agentes).
        let mentionModalSelected = null; // { type: 'agent'|'team', data: {...} }
        let mentionModalAgents = [];
        let mentionModalTeams = [];
        let mentionModalFetchSeq = 0;
        let mentionActiveTab = 'agents';

        function renderMentionAgentRow(a, i) {
            const esc = (s) => $('<i>').text(s == null ? '' : String(s)).html();
            const colorIdx = ((a.id || 0) % 6) + 1;
            const cur = parseInt(a.workload_current || 0, 10);
            const max = parseInt(a.workload_max || 15, 10);
            const ratio = max > 0 ? Math.min(1, cur / max) : 0;
            const barColor = ratio >= 1 ? 'danger' : (ratio >= 0.7 ? 'warning' : 'success');
            const statusClass = a.status || (a.online ? 'online' : 'offline');
            const skills = (a.skills || []).filter(Boolean);
            const subParts = [esc(a.status_label || (a.online ? 'En línea' : 'Offline'))];
            if (skills.length) subParts.push(esc(skills.join(', ')));
            return $(
                '<button type="button" class="bv-opt bv-mention-opt" role="option" data-bv-mention-type="agent" data-bv-mention-idx="' + i + '">' +
                    '<div class="bv-av c' + colorIdx + '">' + esc(a.initials) +
                        '<span class="bv-av-dot ' + statusClass + '"></span>' +
                    '</div>' +
                    '<div class="body">' +
                        '<div class="bv-mention-row-title">' +
                            '<span class="name">' + esc(a.name) + '</span>' +
                            (a.role ? '<span class="bv-mention-role-badge">' + esc(a.role) + '</span>' : '') +
                        '</div>' +
                        '<div class="sub">' + subParts.join(' · ') + '</div>' +
                    '</div>' +
                    '<div class="bv-mention-load">' +
                        '<div class="bv-mention-load-num">' + cur + '/' + max + '</div>' +
                        '<div class="bv-mention-load-bar"><span class="bv-mention-load-fill ' + barColor + '" style="width:' + Math.round(ratio * 100) + '%"></span></div>' +
                    '</div>' +
                    '<i class="fas fa-check check"></i>' +
                '</button>'
            );
        }

        function renderMentionTeamRow(t, i) {
            const esc = (s) => $('<i>').text(s == null ? '' : String(s)).html();
            const colorIdx = ((t.id || 0) % 6) + 1;
            const cur = parseInt(t.workload_current || 0, 10);
            const max = parseInt(t.workload_max || 10, 10);
            const ratio = max > 0 ? Math.min(1, cur / max) : 0;
            const barColor = ratio >= 1 ? 'danger' : (ratio >= 0.7 ? 'warning' : 'success');
            const members = parseInt(t.members_count || 0, 10);
            const subParts = [members + ' ' + (members === 1 ? 'miembro' : 'miembros')];
            if (t.description) subParts.push(esc(t.description));
            return $(
                '<button type="button" class="bv-opt bv-mention-opt bv-mention-team-opt" role="option" data-bv-mention-type="team" data-bv-mention-idx="' + i + '">' +
                    '<div class="bv-av c' + colorIdx + '"><i class="fas fa-users bv-icon-sm"></i></div>' +
                    '<div class="body">' +
                        '<div class="bv-mention-row-title">' +
                            '<span class="name">' + esc(t.name) + '</span>' +
                            '<span class="bv-mention-team-badge">@' + esc(t.key) + '</span>' +
                        '</div>' +
                        '<div class="sub">' + subParts.join(' · ') + '</div>' +
                    '</div>' +
                    '<div class="bv-mention-load">' +
                        '<div class="bv-mention-load-num">' + cur + '/' + max + '</div>' +
                        '<div class="bv-mention-load-bar"><span class="bv-mention-load-fill ' + barColor + '" style="width:' + Math.round(ratio * 100) + '%"></span></div>' +
                    '</div>' +
                    '<i class="fas fa-check check"></i>' +
                '</button>'
            );
        }

        function renderMentionModalLists(payload) {
            mentionModalAgents = payload.agents || [];
            mentionModalTeams = payload.teams || [];
            mentionModalSelected = null;
            $('#bv-mention-modal-insert').prop('disabled', true);

            $('#bv-mention-tab-count-agents').text(mentionModalAgents.length);
            $('#bv-mention-tab-count-teams').text(mentionModalTeams.length);

            const $agents = $('#bv-mention-modal-list-agents').empty();
            if (!mentionModalAgents.length) {
                $agents.append('<div class="bv-mention-modal-empty">Sin agentes</div>');
            } else {
                mentionModalAgents.forEach((a, i) => $agents.append(renderMentionAgentRow(a, i)));
            }

            const $teams = $('#bv-mention-modal-list-teams').empty();
            if (!mentionModalTeams.length) {
                $teams.append('<div class="bv-mention-modal-empty">Sin equipos</div>');
            } else {
                mentionModalTeams.forEach((t, i) => $teams.append(renderMentionTeamRow(t, i)));
            }
        }

        async function fetchAgentsForModal(term) {
            const seq = ++mentionModalFetchSeq;
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/api/agents-autocomplete',
                    method: 'GET',
                    data: { q: term || '' },
                    dataType: 'json',
                });
                if (seq !== mentionModalFetchSeq) return;
                renderMentionModalLists(resp || {});
            } catch (e) {
                if (seq !== mentionModalFetchSeq) return;
                renderMentionModalLists({});
            }
        }

        function switchMentionTab(tab) {
            mentionActiveTab = tab;
            $('[data-bv-modal-name="mention"] .bv-modal-tab').removeClass('on')
                .filter('[data-tab="' + tab + '"]').addClass('on');
            $('[data-bv-modal-name="mention"] .bv-mention-tab').addClass('bv-tab-hidden')
                .filter('[data-panel="' + tab + '"]').removeClass('bv-tab-hidden');
            // Reset selección al cambiar de tab
            $('[data-bv-modal-name="mention"] .bv-opt').removeClass('on');
            mentionModalSelected = null;
            $('#bv-mention-modal-insert').prop('disabled', true);
        }

        // Al abrir el modal de mención
        $(document).on('click', '[data-bv-modal="mention"]', function () {
            $('#bv-mention-search').val('');
            switchMentionTab('agents');
            $('#bv-mention-modal-list-agents').html('<div class="bv-mention-modal-empty">Cargando…</div>');
            $('#bv-mention-modal-list-teams').html('<div class="bv-mention-modal-empty">Cargando…</div>');
            fetchAgentsForModal('');
            setTimeout(() => $('#bv-mention-search').trigger('focus'), 80);
        });

        // Cambio de tab
        $(document).on('click', '[data-bv-modal-name="mention"] .bv-modal-tab', function () {
            switchMentionTab($(this).data('tab'));
        });

        // Búsqueda live con debounce
        let mentionModalSearchTimer = null;
        $(document).on('input', '#bv-mention-search', function () {
            const term = $(this).val();
            clearTimeout(mentionModalSearchTimer);
            mentionModalSearchTimer = setTimeout(() => fetchAgentsForModal(term), 180);
        });

        // Selección (agente o equipo)
        $(document).on('click', '[data-bv-modal-name="mention"] .bv-opt', function () {
            const idx = parseInt($(this).data('bv-mention-idx'), 10);
            const type = $(this).data('bv-mention-type');
            if (isNaN(idx) || !type) return;
            $('[data-bv-modal-name="mention"] .bv-opt').removeClass('on');
            $(this).addClass('on');
            const data = type === 'team' ? mentionModalTeams[idx] : mentionModalAgents[idx];
            mentionModalSelected = data ? { type, data } : null;
            $('#bv-mention-modal-insert').prop('disabled', !mentionModalSelected);
        });

        // Doble click inserta directo
        $(document).on('dblclick', '[data-bv-modal-name="mention"] .bv-opt', function () {
            $(this).trigger('click');
            $('#bv-mention-modal-insert').trigger('click');
        });

        // Insertar la mención
        $(document).on('click', '#bv-mention-modal-insert', function () {
            if (!mentionModalSelected) return;
            const $ta = $('.bv-composer-input').first();
            if (!$ta.length) return;
            const ta = $ta[0];
            const start = ta.selectionStart || ta.value.length;
            const end = ta.selectionEnd || start;
            const v = $ta.val();
            const handle = mentionModalSelected.type === 'team'
                ? mentionModalSelected.data.key
                : mentionModalSelected.data.username;
            const needsSpaceBefore = start > 0 && !/\s/.test(v[start - 1] || '');
            const insert = (needsSpaceBefore ? ' @' : '@') + handle + ' ';
            $ta.val(v.slice(0, start) + insert + v.slice(end));
            ta.selectionStart = ta.selectionEnd = start + insert.length;
            ta.dispatchEvent(new Event('input', { bubbles: true }));
            $('[data-bv-modal-name="mention"]').removeClass('on');
            if ($('.bv-modal.on').length === 0) $('body').css('overflow', '');
            ta.focus();
        });

        // Enter dentro del input de búsqueda inserta el primer resultado del tab activo
        $(document).on('keydown', '#bv-mention-search', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const $first = $('[data-bv-modal-name="mention"] [data-panel="' + mentionActiveTab + '"] .bv-opt').first();
                if ($first.length) {
                    $first.trigger('click');
                    $('#bv-mention-modal-insert').trigger('click');
                }
            }
        });

        // Detectar '@' tipeado / actualizar filtro
        $(document).on('input', '.bv-composer-input', function () {
            const ta = this;
            const cursor = ta.selectionStart || 0;
            const v = ta.value;
            // Si ya hay un anchor activo, comprueba si seguimos dentro del rango '@xxx'
            if (mentionAnchor !== null) {
                if (cursor < mentionAnchor + 1 || v[mentionAnchor] !== '@') {
                    closeMentionMenu();
                    return;
                }
                const fragment = v.slice(mentionAnchor + 1, cursor);
                if (/\s/.test(fragment)) {
                    closeMentionMenu();
                    return;
                }
                openMentionMenu($(this), fragment);
                return;
            }
            // Sin anchor: detecta '@' nuevo precedido por inicio o whitespace
            if (cursor > 0 && v[cursor - 1] === '@' && (cursor === 1 || /\s/.test(v[cursor - 2]))) {
                mentionAnchor = cursor - 1;
                openMentionMenu($(this), '');
            }
        });

        function highlightMentionRow(idx) {
            const $rows = $('.bv-mention-row');
            $rows.removeClass('on');
            const $row = $rows.eq(idx);
            $row.addClass('on');
            // Scroll into view dentro del menú
            const row = $row[0];
            if (row && row.scrollIntoView) {
                row.scrollIntoView({ block: 'nearest' });
            }
        }

        // Navegación con teclado dentro del menú
        $(document).on('keydown', '.bv-composer-input', function (e) {
            if (mentionAnchor === null) return;
            const total = mentionItems.length;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                mentionSelectedIdx = (mentionSelectedIdx + 1) % Math.max(total, 1);
                highlightMentionRow(mentionSelectedIdx);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                mentionSelectedIdx = (mentionSelectedIdx - 1 + total) % Math.max(total, 1);
                highlightMentionRow(mentionSelectedIdx);
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                if (!total) {
                    closeMentionMenu();
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                const item = mentionItems[mentionSelectedIdx];
                insertMentionAt($(this), mentionAnchor, item);
                closeMentionMenu();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeMentionMenu();
            }
        });

        // Click en una fila del menú
        $(document).on('click', '.bv-mention-row', function (e) {
            e.preventDefault();
            const idx = parseInt($(this).data('bv-mention-idx'), 10) || 0;
            const item = mentionItems[idx];
            if (!item) return;
            const $ta = $('.bv-composer-input').first();
            insertMentionAt($ta, mentionAnchor, item);
            closeMentionMenu();
        });

        // Hover destaca la fila
        $(document).on('mouseenter', '.bv-mention-row', function () {
            const idx = parseInt($(this).data('bv-mention-idx'), 10);
            if (!isNaN(idx)) {
                mentionSelectedIdx = idx;
                $('.bv-mention-row').removeClass('on');
                $(this).addClass('on');
            }
        });

        // Click fuera cierra el menú
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-mention-menu, .bv-composer-input, #bv-btn-mention').length) {
                closeMentionMenu();
            }
        });

        // ─── Quick replies dropdown (botón rayo) ─────────────────────
        let cannedReplies = null;

        async function loadCannedReplies() {
            if (cannedReplies !== null) return cannedReplies;
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/canned-replies/search',
                    method: 'GET',
                    dataType: 'json',
                });
                cannedReplies = Array.isArray(resp) ? resp : (resp.data || resp.items || []);
            } catch (e) {
                cannedReplies = [];
            }
            return cannedReplies;
        }

        function buildQuickRepliesDropdown(items) {
            if (document.getElementById('bv-quick-replies')) {
                $('#bv-quick-replies').remove();
            }
            const $d = $('<div id="bv-quick-replies" class="bv-quick-replies"></div>');
            if (!items.length) {
                $d.append('<div class="bv-quick-empty">Sin respuestas rápidas</div>');
            } else {
                items.forEach(it => {
                    const $row = $('<button class="bv-quick-item"></button>');
                    $row.append('<span class="bv-quick-shortcut">' + (it.shortcut || it.name) + '</span>');
                    $row.append('<span class="bv-quick-name">' + (it.name || '') + '</span>');
                    $row.append('<span class="bv-quick-body">' + (it.body || '').slice(0, 80) + '</span>');
                    $row.attr('data-bv-quick-body', it.body || '');
                    $d.append($row);
                });
            }
            $('body').append($d);
        }

        function positionQuickReplies($trigger) {
            const offset = $trigger.offset();
            $('#bv-quick-replies').css({
                position: 'absolute',
                top: (offset.top - 320) + 'px',
                left: offset.left + 'px',
                zIndex: 9999,
            });
        }

        $(document).on('click', '.bv-composer button[title="Respuesta rápida"]', async function (e) {
            e.stopPropagation();
            const items = await loadCannedReplies();
            buildQuickRepliesDropdown(items);
            positionQuickReplies($(this));
        });

        $(document).on('click', '.bv-quick-item', function () {
            const body = $(this).data('bv-quick-body');
            const $ta = $('.bv-composer-input').first();
            $ta.val(body).focus();
            $('#bv-quick-replies').remove();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-quick-replies, .bv-composer button[title="Respuesta rápida"]').length) {
                $('#bv-quick-replies').remove();
            }
        });

        // ─── AI suggestions (botón sparkles) ──────────────────────────
        function buildAiDropdown(items) {
            if (document.getElementById('bv-ai-suggestions')) {
                $('#bv-ai-suggestions').remove();
            }
            const $d = $('<div id="bv-ai-suggestions" class="bv-ai-suggestions"></div>');
            $d.append('<div class="bv-ai-head"><i class="fas fa-sparkles"></i> Sugerencias IA</div>');
            (items || []).forEach(it => {
                const $row = $('<button class="bv-ai-item"></button>')
                    .text(it.text || it)
                    .attr('data-bv-ai-text', it.text || it);
                $d.append($row);
            });
            $('body').append($d);
        }

        function positionAiDropdown($trigger) {
            const offset = $trigger.offset();
            $('#bv-ai-suggestions').css({
                position: 'absolute',
                top: (offset.top - 220) + 'px',
                left: Math.max(8, offset.left - 200) + 'px',
                zIndex: 9999,
            });
        }

        $(document).on('click', '.bv-composer button[data-bv-tip="Sugerencia IA"]', async function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const convId = $('.bv-composer').data('bv-conversation-id');
            if (!convId) return;
            $btn.prop('disabled', true);
            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/conversations/' + convId + '/ai/suggestions',
                    method: 'POST',
                    dataType: 'json',
                    data: { context: $('.bv-composer-input').val() || '' },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                });
                const suggestions = (resp.data && resp.data.suggestions) || [];
                if (!suggestions.length) {
                    if (window.toastr) toastr.info(resp.message || 'No hay sugerencias disponibles.');
                    return;
                }
                buildAiDropdown(suggestions);
                positionAiDropdown($btn);
            } catch (e) {
                if (window.toastr) toastr.error('No se pudo obtener sugerencias');
            } finally {
                $btn.prop('disabled', false);
            }
        });

        $(document).on('click', '.bv-ai-item', function () {
            const text = $(this).data('bv-ai-text');
            const $ta = $('.bv-composer-input').first();
            $ta.val(text).focus();
            $('#bv-ai-suggestions').remove();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bv-ai-suggestions, .bv-composer button[data-bv-tip="Sugerencia IA"]').length) {
                $('#bv-ai-suggestions').remove();
            }
        });

        // ─── Reply quoted (estilo WhatsApp) ──────────────────────────
        let activeReply = null;

        // Trigger desde context menu o cualquier UI que tenga los datos del bubble
        $(document).on('bv:set-reply', function (e, payload) {
            if (!payload || !payload.id) return;
            activeReply = {
                id: payload.id,
                author: payload.author || '',
                body: payload.body || '',
            };
            renderQuotePreview();
            $('.bv-composer-input').focus();
        });

        function renderQuotePreview() {
            $('#bv-quote-preview').remove();
            if (!activeReply) return;
            const escapeHtml = s => $('<div>').text(s == null ? '' : s).html();
            const $q = $(
                '<div class="bv-quote-preview" id="bv-quote-preview">' +
                    '<div class="bv-quote-line"></div>' +
                    '<div class="bv-quote-content">' +
                        '<div class="bv-quote-author"><i class="fas fa-reply"></i> ' + escapeHtml(activeReply.author) + '</div>' +
                        '<div class="bv-quote-body">' + escapeHtml(activeReply.body) + '</div>' +
                    '</div>' +
                    '<button class="bv-quote-cancel" id="bv-quote-cancel"><i class="fas fa-xmark"></i></button>' +
                '</div>'
            );
            $('.bv-composer-box').before($q);
        }

        $(document).on('click', '#bv-quote-cancel', function () {
            activeReply = null;
            $('#bv-quote-preview').remove();
        });

        // Override $.ajax para añadir reply_to_id en messages
        const _origAjax = $.ajax;
        $.ajax = function (opts) {
            if (opts && typeof opts.url === 'string'
                && /\/conversations\/\d+\/messages$/.test(opts.url)
                && (opts.method || '').toUpperCase() === 'POST'
                && activeReply) {
                if (opts.data && typeof opts.data === 'object' && !(opts.data instanceof FormData)) {
                    opts.data.reply_to_id = activeReply.id;
                }
                activeReply = null;
                $('#bv-quote-preview').remove();
            }
            return _origAjax.apply(this, arguments);
        };

        // ─── Audio message player (estilo WhatsApp) ──────────────────
        function fmtAudioTime(secs) {
            if (!isFinite(secs) || secs < 0) return '0:00';
            const m = Math.floor(secs / 60);
            const s = Math.floor(secs % 60);
            return m + ':' + String(s).padStart(2, '0');
        }

        function syncAudioUI($msg, audio) {
            const dur = isFinite(audio.duration) ? audio.duration : 0;
            const cur = audio.currentTime || 0;
            const pct = dur > 0 ? cur / dur : 0;
            const $bars = $msg.find('.bv-audio-bar');
            const total = $bars.length;
            const playedCount = Math.round(pct * total);
            $bars.each(function (i) {
                $(this).toggleClass('played', i < playedCount);
            });
            // Dot indicador que se mueve sobre la waveform
            $msg.find('.bv-audio-progress-dot').css('left', (pct * 100) + '%');
            const display = audio.paused && cur === 0 ? dur : cur;
            $msg.find('.bv-audio-time').text(fmtAudioTime(display));
        }

        $(document).on('click', '.bv-audio-play', function () {
            const $msg = $(this).closest('.bv-audio-msg');
            const audio = $msg.find('.bv-audio-el')[0];
            if (!audio) return;

            // Pausa cualquier otro audio que esté sonando
            $('.bv-audio-msg').not($msg).each(function () {
                const other = $(this).find('.bv-audio-el')[0];
                if (other && !other.paused) {
                    other.pause();
                    $(this).find('.bv-audio-play').removeClass('playing').html('<i class="fas fa-play"></i>');
                }
            });

            if (audio.paused) {
                audio.play().catch(err => {
                    console.error('[bv-audio] play error:', err);
                    if (window.toastr) toastr.error('No se puede reproducir el audio: ' + err.message);
                });
            } else {
                audio.pause();
            }
        });

        // Native capture phase: media events (timeupdate, play, pause, ended,
        // loadedmetadata, durationchange, error) don't bubble through jQuery
        // delegation reliably on <audio> elements. Capture works in all browsers.
        function onMediaEvent(eventName, handler) {
            document.addEventListener(eventName, function (ev) {
                const el = ev.target;
                if (el && el.classList && el.classList.contains('bv-audio-el')) {
                    handler(el, ev);
                }
            }, true);
        }

        onMediaEvent('play', function (el) {
            const $msg = $(el).closest('.bv-audio-msg');
            $msg.find('.bv-audio-play').addClass('playing').html('<i class="fas fa-pause"></i>');
        });

        onMediaEvent('pause', function (el) {
            const $msg = $(el).closest('.bv-audio-msg');
            $msg.find('.bv-audio-play').removeClass('playing').html('<i class="fas fa-play"></i>');
        });

        onMediaEvent('ended', function (el) {
            const $msg = $(el).closest('.bv-audio-msg');
            $msg.find('.bv-audio-play').removeClass('playing').html('<i class="fas fa-play"></i>');
            el.currentTime = 0;
            syncAudioUI($msg, el);
        });

        onMediaEvent('timeupdate', function (el) {
            syncAudioUI($(el).closest('.bv-audio-msg'), el);
        });

        onMediaEvent('loadedmetadata', function (el) {
            syncAudioUI($(el).closest('.bv-audio-msg'), el);
        });

        onMediaEvent('durationchange', function (el) {
            syncAudioUI($(el).closest('.bv-audio-msg'), el);
        });

        onMediaEvent('error', function (el) {
            const $msg = $(el).closest('.bv-audio-msg');
            $msg.find('.bv-audio-time').text('Error');
            console.error('[bv-audio] media error:', el.error);
        });

        // Seek por clic en el waveform
        $(document).on('click', '.bv-audio-wave', function (e) {
            const $msg = $(this).closest('.bv-audio-msg');
            const audio = $msg.find('.bv-audio-el')[0];
            if (!audio || !isFinite(audio.duration)) return;
            const rect = this.getBoundingClientRect();
            const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            audio.currentTime = pct * audio.duration;
        });

        // Cambio de velocidad: 1x → 1.5x → 2x → 1x
        $(document).on('click', '.bv-audio-speed', function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const cur = parseFloat($btn.data('bv-speed')) || 1;
            const next = cur === 1 ? 1.5 : (cur === 1.5 ? 2 : 1);
            $btn.data('bv-speed', next).text(next + 'x');
            const audio = $btn.closest('.bv-audio-msg').find('.bv-audio-el')[0];
            if (audio) audio.playbackRate = next;
        });

        // ─── File actions popup (ver / descargar / reenviar) ─────────
        // Nota: las imágenes (.bv-attach-thumb) abren el lightbox directamente
        // vía openLightboxFromLink. Aquí solo se enganchan los documentos.
        $(document).on('click', '.bv-attach-file', function (e) {
            if ($(e.target).closest('.bv-file-actions').length) return;
            e.preventDefault();
            e.stopPropagation();

            const $a = $(this);
            const url = $a.attr('href');
            const isImage = $a.hasClass('bv-attach-thumb');
            const fileName = decodeURIComponent((url || '').split('/').pop() || 'archivo');

            $('.bv-file-actions').remove();

            const $popup = $(
                '<div class="bv-file-actions">' +
                    '<button class="bv-file-action" data-bv-fa="view"><i class="far fa-eye"></i> Ver</button>' +
                    '<button class="bv-file-action" data-bv-fa="download"><i class="fas fa-download"></i> Descargar</button>' +
                    '<button class="bv-file-action" data-bv-fa="forward"><i class="fas fa-share"></i> Reenviar</button>' +
                '</div>'
            );
            $popup.attr('data-bv-fa-url', url);
            $popup.attr('data-bv-fa-name', fileName);
            $popup.attr('data-bv-fa-type', isImage ? 'image' : 'file');

            const offset = $a.offset();
            $popup.css({
                position: 'absolute',
                top: (offset.top + $a.outerHeight() + 4) + 'px',
                left: offset.left + 'px',
                zIndex: 9999,
            });
            $('body').append($popup);
        });

        $(document).on('click', '.bv-file-action', function (e) {
            e.stopPropagation();
            const $btn = $(this);
            const $popup = $btn.closest('.bv-file-actions');
            const action = $btn.data('bv-fa');
            const url = $popup.attr('data-bv-fa-url');
            const name = $popup.attr('data-bv-fa-name');
            const type = $popup.attr('data-bv-fa-type');
            $popup.remove();

            if (action === 'view') {
                if (type === 'image') {
                    const $modal = $('[data-bv-modal-name="file-preview"]');
                    if ($modal.length) {
                        $modal.find('.bv-file-preview-content, #bv-file-preview-content')
                            .html('<img src="' + url + '" alt="" style="max-width:100%;max-height:80vh">');
                        $modal.addClass('on');
                        $('body').css('overflow', 'hidden');
                    } else {
                        window.open(url, '_blank');
                    }
                } else {
                    window.open(url, '_blank');
                }
            } else if (action === 'download') {
                downloadBlob(url, name);
            } else if (action === 'forward') {
                openForwardModal(url, name);
            }
        });

        $(document).on('click', function () {
            $('.bv-file-actions').remove();
        });

        async function openForwardModal(sourceUrl, originalName) {
            $('#bv-forward-modal').remove();
            const $modal = $(
                '<div class="bv-modal on" id="bv-forward-modal" data-bv-modal-name="forward-attachment">' +
                    '<div class="bv-modal-dialog">' +
                        '<div class="bv-modal-head">' +
                            '<div class="bv-modal-title"><i class="fas fa-share"></i> Reenviar archivo</div>' +
                            '<button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>' +
                        '</div>' +
                        '<div class="bv-modal-body">' +
                            '<div class="bv-store-search">' +
                                '<i class="fas fa-magnifying-glass"></i>' +
                                '<input type="text" id="bv-forward-search" placeholder="Buscar conversación...">' +
                            '</div>' +
                            '<div class="bv-store-list" id="bv-forward-targets">' +
                                '<div class="bv-tab-empty"><i class="fas fa-spinner fa-spin"></i><div class="bv-tab-empty-title">Cargando...</div></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            window.bvSetLastFocusedBeforeModal(document.activeElement);
            applyModalA11y($modal, 'forward-attachment');
            $('body').append($modal);
            $('body').css('overflow', 'hidden');
            $modal.attr('data-bv-fa-url', sourceUrl);
            $modal.attr('data-bv-fa-name', originalName);

            try {
                const resp = await $.ajax({
                    url: '/panel/helpdesk/search/global',
                    method: 'GET',
                    data: { q: '' },
                    dataType: 'json',
                });
                const convs = (resp && resp.conversations) || [];
                const $list = $('#bv-forward-targets').empty();
                const escapeHtml = s => $('<div>').text(s == null ? '' : s).html();
                if (!convs.length) {
                    $list.html('<div class="bv-tab-empty"><div class="bv-tab-empty-title">Sin conversaciones</div></div>');
                } else {
                    convs.forEach(c => {
                        const $row = $(
                            '<button class="bv-store-item bv-forward-target" data-bv-target-id="' + c.id + '">' +
                                '<div class="bv-store-icon" style="background:#6366f1"><i class="fas fa-comment-dots"></i></div>' +
                                '<div class="bv-store-body">' +
                                    '<div class="bv-store-name">' + escapeHtml(c.customer_name || c.subject || 'Conv #' + c.id) + '</div>' +
                                    '<div class="bv-store-meta">' + escapeHtml(c.subject || '') + '</div>' +
                                '</div>' +
                                '<i class="fas fa-paper-plane bv-store-send-icon"></i>' +
                            '</button>'
                        );
                        $list.append($row);
                    });
                }
            } catch (e) {
                $('#bv-forward-targets').html('<div class="bv-tab-empty">Error cargando</div>');
            }
        }

        $(document).on('click', '.bv-forward-target', async function () {
            const $btn = $(this);
            const targetId = $btn.data('bv-target-id');
            const $modal = $('#bv-forward-modal');
            const sourceUrl = $modal.attr('data-bv-fa-url');
            const originalName = $modal.attr('data-bv-fa-name');

            $btn.prop('disabled', true);
            try {
                await $.ajax({
                    url: '/panel/helpdesk/conversations/' + targetId + '/attachments/forward',
                    method: 'POST',
                    dataType: 'json',
                    data: { source_url: sourceUrl, original_name: originalName },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                    },
                });
                $modal.remove();
                $('body').css('overflow', '');
            } catch (e) {
                if (window.toastr) toastr.error('No se pudo reenviar');
                $btn.prop('disabled', false);
            }
        });

        $(document).on('click', '#bv-forward-modal [data-bv-close]', function () {
            $('#bv-forward-modal').remove();
            $('body').css('overflow', '');
        });

        $(document).on('input', '#bv-forward-search', function () {
            const q = $(this).val().toLowerCase().trim();
            $('.bv-forward-target').each(function () {
                const text = $(this).text().toLowerCase();
                $(this).toggle(!q || text.includes(q));
            });
        });


        // Exponer helpers compartidos con conversations-extras.js / conversations-list.js
        window.uploadFiles = uploadFiles;
        window.openMessageForwardModal = openMessageForwardModal;

    });
})(jQuery);

// ═══════════════════════════════════════════════════════════════════
// Suscripción Reverb + typing + borrador por conversación. Extraído de
// inbox/index.blade.php. Antes vivía acoplada a la conversación inicial;
// ahora se expone como window.bvBindConversation(convId) para que
// conversations-list.js la vuelva a enlazar tras cada cambio de conversación
// SPA (sin recargar la página). Los handlers de document van namespaced con
// .bvconv y se reenganchan en cada bind para no duplicarse; los canales Echo
// anteriores se abandonan. Vive en conversations-thread.js porque usa
// appendBubbleToThread / appendActivityPillToThread, definidas más arriba en
// este mismo archivo. window.BvSelectedConversationId es el dato server-side
// que index.blade.php expone justo antes de cargar estos scripts.
// ═══════════════════════════════════════════════════════════════════
(function () {
    var currentConvId = null;
    var convChannel = null;
    var typingTimeout = null;
    var typingHideTimer = null;
    var lastTypingPing = 0;
    var lastTypingState = false;

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }
    function myId() {
        return parseInt(document.querySelector('meta[name="user-id"]')?.content || '0', 10);
    }

    function showTypingIndicator() {
        var $ind = $('#bv-typing-ind');
        if (!$ind.length) {
            // UI-05: role=status + aria-live anuncian "escribiendo…" a lectores de pantalla.
            $ind = $('<div id="bv-typing-ind" class="bv-typing-ind" role="status" aria-live="polite"><span class="bv-typing-dots" aria-hidden="true"><span></span><span></span><span></span></span><span class="bv-typing-text">Escribiendo…</span></div>');
            $('.bv-th-body').append($ind);
        }
        $ind.show();
        clearTimeout(typingHideTimer);
        typingHideTimer = setTimeout(function () { $ind.hide(); }, 4000);
    }

    function postTypingState(isTyping) {
        if (!currentConvId || lastTypingState === isTyping) return;
        lastTypingState = isTyping;
        $.ajax({
            url: '/panel/helpdesk/conversations/' + currentConvId + '/typing',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf() },
            data: { is_typing: isTyping ? 1 : 0 },
        });
    }

    // Abandona los canales y handlers de la conversación previa.
    window.bvUnbindConversation = function () {
        if (typeof window.Echo !== 'undefined' && window.Echo && currentConvId) {
            try { window.Echo.leave('helpdesk.conversation.' + currentConvId); } catch (_) {}
            try { window.Echo.leave('helpdesk.conversation.' + currentConvId + '.typing'); } catch (_) {}
        }
        $(document).off('.bvconv');
        clearTimeout(typingTimeout);
        clearTimeout(typingHideTimer);
        $('#bv-typing-ind').hide();
        lastTypingState = false;
        lastTypingPing = 0;
        convChannel = null;
        currentConvId = null;
    };

    // ─── Sugerencia "Detectar idioma" (modal detect-lang.blade.php) ──
    // El idioma del contacto (helpdesk_customers.language) y el idioma de
    // trabajo del agente (select #bv-tp-to del panel Traducir, precargado
    // server-side desde helpdesktranslate.default_target) vivían sin
    // conectar entre sí: el agente nunca se enteraba de que estaba
    // respondiendo en un idioma distinto al del cliente salvo que se
    // fijara él mismo. No depende de Echo/Reverb (no hay tiempo real
    // fiable en este entorno) — se revisa con lo que ya llegó en el pane.
    var HD_LANG_LABELS = { es: 'Español', en: 'Inglés', fr: 'Francés', de: 'Alemán', pt: 'Portugués', it: 'Italiano' };

    function maybeSuggestLanguageMismatch(convId) {
        var seenKey = 'bv_lang_prompt_seen_' + convId;
        if (sessionStorage.getItem(seenKey)) return;

        var settings = {};
        try { settings = JSON.parse(sessionStorage.getItem('inbox_translation_settings') || '{}'); } catch (_e) {}
        if (settings.mode && settings.mode !== 'off') return; // ya hay traducción activa en esta pestaña

        var customerLang = ($('.bv-right').data('customer-language') || '').toString().trim().toLowerCase();
        var workingLang = ($('#bv-tp-to').val() || '').toString().trim().toLowerCase();
        if (!customerLang || !workingLang || customerLang === workingLang) return;
        if (!HD_LANG_LABELS[customerLang] || !HD_LANG_LABELS[workingLang]) return;

        var sample = ($('.bv-msg.in .bv-bubble').last().data('bv-body') || '').toString().trim();
        if (!sample) return; // sin mensajes entrantes todavía, nada que mostrar como ejemplo

        var $modal = $('[data-bv-modal-name="detect-lang"]');
        if (!$modal.length) return;

        // No se repite en esta conversación aunque el agente cierre el
        // modal sin elegir nada — evita que reaparezca en cada mensaje.
        sessionStorage.setItem(seenKey, '1');

        $modal.addClass('on');
        $('body').css('overflow', 'hidden');
        $(document).trigger('bv:modal:open', ['detect-lang', {
            detected: HD_LANG_LABELS[customerLang],
            working: HD_LANG_LABELS[workingLang],
            sample: sample.length > 140 ? sample.slice(0, 140) + '…' : sample,
            fromCode: customerLang,
            toCode: workingLang,
        }]);
    }

    window.bvBindConversation = function (convId) {
        convId = parseInt(convId, 10);
        if (!convId) return;

        maybeSuggestLanguageMismatch(convId);

        window.bvUnbindConversation();
        currentConvId = convId;

        if (typeof window.Echo === 'undefined' || !window.Echo) return;

        // Marcar como leída + limpiar el badge en la lista.
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/mark-read',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf() },
        }).done(function () {
            var $item = $('.bv-conv[data-bv-conv-id="' + convId + '"]');
            var wasUnread = $item.hasClass('unread');
            $item.removeClass('unread');
            $item.find('.bv-ucount').remove();
            if (wasUnread) {
                var $counter = $('[data-counter="unread"]');
                var cur = parseInt($counter.text(), 10) || 0;
                $counter.text(Math.max(0, cur - 1));
            }
        }).fail(function (xhr) {
            console.warn('[Inbox] mark-read failed:', xhr.status);
        });

        convChannel = window.Echo.private('helpdesk.conversation.' + convId);

        convChannel.listen('.item.created', function (e) {
            // El payload viene envuelto en { message: {...} } desde broadcastWith()
            const msg = e.message || e;

            // Mensajes de actividad (etiqueta añadida, cambio de estado, asignación,
            // etc.): se pintan como píldora centrada, no como burbuja de chat.
            if (msg.type === 'activity') {
                if (typeof window.appendActivityPillToThread === 'function') {
                    window.appendActivityPillToThread(msg.body);
                }
                return;
            }

            // Si el mensaje lo envió el propio agente, ya está pintado por la UI optimista
            if (msg.user_id && parseInt(msg.user_id, 10) === myId()) return;

            const isCustomerMessage = !msg.user_id && msg.author_id;
            const custId = (e.conversation && e.conversation.customer && e.conversation.customer.id) || convId;
            const item = {
                id: msg.id,
                body: msg.body,
                attachment_urls: msg.attachment_urls || [],
                attachments: msg.attachments || [],
                metadata: msg.metadata || {},
                is_internal: !!msg.is_internal,
                is_incoming: !!isCustomerMessage,
                author: msg.sender_name || (isCustomerMessage ? 'Cliente' : 'Tú'),
                time: new Date(msg.created_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                avatar: msg.sender_avatar,
                // Matches the server-side `(customer->id ?? conversation->id ?? 1) - 1) % 8 + 1`
                // so the avatar colour is identical whether the bubble came from the
                // initial page render or a live WebSocket append.
                colorIdx: ((custId - 1) % 8 + 8) % 8 + 1,
            };

            if (typeof window.appendBubbleToThread === 'function') {
                window.appendBubbleToThread(item, !!msg.is_internal);
            }

            // UI-05: anunciar el mensaje entrante en la live-region oculta.
            if (typeof window.bvAnnounce === 'function') {
                window.bvAnnounce('Nuevo mensaje de ' + (item.author || 'cliente'));
            }

            // PERF-05: delivery/read receipts ya NO viajan en este evento (ver
            // '.receipts_updated' más abajo) — antes cada recibo generaba un
            // broadcast .item.created completo por mensaje marcado.
            const meta = msg.metadata || {};

            // Render customer reactions (e.g. ❤️) on agent-sent bubbles.
            if (msg.user_id && Array.isArray(meta.customer_reactions) && meta.customer_reactions.length) {
                const $bubble = $('.bv-bubble[data-bv-item-id="' + msg.id + '"]');
                if ($bubble.length) {
                    const emoji = meta.customer_reactions[0].emoji || '❤️';
                    let $r = $bubble.find('.bv-bubble-reaction');
                    if (!$r.length) {
                        $r = $('<span class="bv-bubble-reaction"></span>');
                        $bubble.append($r);
                    }
                    $r.text(emoji);
                }
            }

            window.dispatchEvent(new CustomEvent('inbox:incoming-message', { detail: msg }));

            // Push notification for per-conversation listener (agent on page, tab hidden)
            if (isCustomerMessage && document.visibilityState === 'hidden') {
                const conv = e.conversation || {};
                const customerName = conv.customer_name || 'Nuevo mensaje';
                const preview = (msg.body || '').slice(0, 100);
                if (typeof window.showInboxPushNotif === 'function') {
                    window.showInboxPushNotif(convId, customerName, preview, msg.sender_avatar || null);
                }
            }
        });

        // PERF-05: recibos de entrega/lectura de Messenger/Instagram llegan
        // agregados — un solo evento con los ids de los ítems marcados, en vez
        // de un '.item.created' completo por cada mensaje que cambia de estado.
        convChannel.listen('.receipts_updated', function (e) {
            if (!e || !Array.isArray(e.item_ids) || !e.item_ids.length) return;

            const isRead = e.field === 'customer_read_at';

            e.item_ids.forEach(function (itemId) {
                const $bubble = $('.bv-bubble[data-bv-item-id="' + itemId + '"]');
                if (!$bubble.length) return;

                const $chk = $bubble.find('.bv-chk-read, .chk');
                if (isRead) {
                    $chk.removeClass('chk-delivered').addClass('chk-read').addClass('text-primary');
                } else {
                    $chk.addClass('chk-delivered');
                }
            });
        });

        // ─── Typing indicator: peer (Echo whisper) + customer (Meta API) ────
        $(document).on('input.bvconv', '.bv-composer-input', function () {
            var now = Date.now();
            // Throttle network/whisper traffic: max 1 ping every 2s while typing.
            if (now - lastTypingPing >= 2000) {
                lastTypingPing = now;
                if (convChannel && convChannel.whisper) {
                    convChannel.whisper('typing', { user_id: myId(), is_typing: true });
                }
                postTypingState(true);
            }

            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function () {
                if (convChannel && convChannel.whisper) {
                    convChannel.whisper('typing', { user_id: myId(), is_typing: false });
                }
                postTypingState(false);
            }, 3000);
        });

        // Stop typing the moment the message is sent.
        $(document).on('bv:message:sent.bvconv', function () {
            clearTimeout(typingTimeout);
            postTypingState(false);
        });

        // Typing entre agentes (whisper)
        convChannel.listenForWhisper('typing', function (e) {
            if (!e || parseInt(e.user_id, 10) === myId()) return;
            if (e.is_typing) {
                showTypingIndicator();
            } else {
                clearTimeout(typingHideTimer);
                $('#bv-typing-ind').hide();
            }
        });

        // Typing del cliente desde el widget
        window.Echo.private('helpdesk.conversation.' + convId + '.typing')
            .listen('.typing', function () {
                showTypingIndicator();
            });

        // ─── Autosave borrador del composer en localStorage ──────────
        var draftKey = 'bv:draft:' + convId;
        var $composer = $('.bv-composer-input');
        var saved = localStorage.getItem(draftKey);
        if (saved && !$composer.val()) {
            $composer.val(saved);
            $composer[0]?.dispatchEvent(new Event('input', { bubbles: true }));
        }
        $(document).on('input.bvconv', '.bv-composer-input', function () {
            var val = $(this).val();
            if (val && val.trim()) localStorage.setItem(draftKey, val);
            else localStorage.removeItem(draftKey);
        });
        // Limpiar borrador tras envío exitoso
        $(document).on('bv:message:sent.bvconv', function () {
            localStorage.removeItem(draftKey);
            $('.bv-composer-input').val('');
        });
    };

    $(document).ready(function () {
        if (window.BvSelectedConversationId) {
            window.bvBindConversation(window.BvSelectedConversationId);
        }
    });
})();

// ─── Supervisor toma el control de una conversación que atiende el bot ───
// Extraído de inbox/index.blade.php. El botón #bv-btn-takeover vive en
// partials/thread.blade.php (bv-th-action--takeover).
$(document).on('click', '#bv-btn-takeover', function () {
    var $btn = $(this);
    var url = $btn.data('takeover-url');
    if (!url) return;
    $btn.prop('disabled', true);
    $.ajax({
        url: url,
        method: 'POST',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
    }).done(function (resp) {
        if (window.toastr) toastr.success((resp && resp.message) || 'Has tomado el control de la conversación.');
        $btn.remove();
        var $list = $('.bv-list').first();
        if ($list.length) {
            var params = new URLSearchParams(window.location.search);
            $.get('/panel/helpdesk/conversations/list', Object.fromEntries(params)).done(function (r) {
                if (r && typeof r.html === 'string') { $list.replaceWith(r.html); }
            });
        }
    }).fail(function (xhr) {
        if (window.toastr) toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo tomar el control.');
    }).always(function () {
        $btn.prop('disabled', false);
    });
});

// ─── Empuje activo: fuera de la ventana de 24h de WhatsApp, guiar al agente
// al panel de plantillas (HSM) y bloquear el envío de texto libre. Extraído
// de inbox/index.blade.php. data-bv-wa-window-closed vive en el
// .bv-composer de partials/thread.blade.php. ────────────────────────────
(function () {
    function steerWhatsAppWindow() {
        var $composer = $('.bv-composer');
        if (!$composer.length) return;
        var closed = $composer.attr('data-bv-wa-window-closed') === '1';
        var $replyTab = $composer.find('.bv-composer-tab[data-bv-tab="reply"]');
        if (closed) {
            // Abrir el panel de plantillas (dispara el handler delegado) y
            // bloquear la pestaña de respuesta libre; las notas internas siguen.
            var hsmTab = $composer.find('.bv-composer-tab[data-bv-tab="hsm"]')[0];
            if (hsmTab && !$composer.hasClass('bv-hsm-mode')) { hsmTab.click(); }
            $composer.addClass('bv-hsm-mode');
            $replyTab.prop('disabled', true).addClass('disabled');
        } else {
            $composer.removeClass('bv-hsm-mode');
            $replyTab.prop('disabled', false).removeClass('disabled');
        }
    }
    document.addEventListener('pane:loaded', steerWhatsAppWindow);
    $(function () { steerWhatsAppWindow(); });
})();

// ─── Reintentar envío de un mensaje saliente marcado como "no entregado" ───
// Extraído de inbox/index.blade.php. .bv-retry-send / .bv-send-failed viven
// en partials/thread.blade.php. Los textos de toastr eran
// helpdesk::helpdesk.inbox.thread.retry_send_ok/retry_send_error — se
// hardcodean en español aquí porque este archivo estático no pasa por el
// compilador de Blade (mismo patrón que el resto de textos de toastr de
// este archivo, p.ej. 'Texto copiado' / 'No se pudieron cargar los mensajes
// anteriores').
$(document).on('click', '.bv-retry-send', function () {
    var $btn = $(this);
    var url = $btn.data('bv-retry-url');
    if (!url) return;
    $btn.prop('disabled', true);
    $.ajax({
        url: url,
        method: 'POST',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
    }).done(function () {
        if (window.toastr) toastr.info('Reintentando el envío…');
        // Optimista: sustituir el indicador de fallo por el check de enviado.
        $btn.closest('.bv-send-failed').replaceWith('<span class="chk read bv-chk-read">✓✓</span>');
    }).fail(function (xhr) {
        if (window.toastr) toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo reintentar el envío.');
        $btn.prop('disabled', false);
    });
});

// ─── Respuesta rápida (modal #hdCannedOverlay) + envío de CSAT ─────────────
// Extraído de partials/thread.blade.php, donde vivía inline dentro de un
// @once/@push('scripts') que solo se emitía cuando había conversación
// seleccionada ($convo). El markup del modal (#hdCannedOverlay) sigue
// viviendo en ese partial, dentro del mismo `@if($convo)` que antes envolvía
// también este script — de ahí el guard de abajo: si el overlay no está en
// el DOM (sin conversación seleccionada en la carga inicial), no se registra
// nada, igual que antes. window.HdThreadCtx lo define thread.blade.php vía
// @json (contact./agent./company./conversation.* para reemplazar
// placeholders {{...}} en las plantillas insertadas).
(function () {
    var hdCannedOverlayEl = document.getElementById('hdCannedOverlay');
    if (!hdCannedOverlayEl) { return; }

    var hdCsrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

    // Contexto para reemplazar placeholders en plantillas
    var hdCtx = window.HdThreadCtx || {};

    function hdReplace(text) {
        if (!text) { return text; }
        return text.replace(/\{\{([^}]+)\}\}/g, function(match, key) {
            var k = key.trim();
            return hdCtx.hasOwnProperty(k) ? hdCtx[k] : match;
        });
    }

    function hdEscape(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    hdCannedOverlayEl.addEventListener('click', function(e) {
        if (e.target === this) closeCannedModal();
    });

    // Los .media-pill son <span role="button"> (filtros de categoría): activar con Enter/Espacio.
    hdCannedOverlayEl.addEventListener('keydown', function(e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('media-pill')) {
            e.preventDefault();
            e.target.click();
        }
    });

    // ── CANNED REPLIES ─────────────────────────────────────
    var hdCannedAll = [], hdCannedFiltered = [], hdCannedActive = -1, hdCannedSelId = null, hdCannedTimer = null, hdCannedCat = '';

    window.openCannedModal = function() {
        document.getElementById('hdCannedOverlay').classList.add('open');
        var inp = document.getElementById('hdCannedSearch');
        inp.value = ''; inp.focus();
        if (!hdCannedAll.length) { hdCannedFetch(''); }
        else { hdCannedRender(hdCannedApplyFilters(hdCannedAll, hdCannedCat)); }
    };
    window.closeCannedModal = function() {
        document.getElementById('hdCannedOverlay').classList.remove('open');
    };
    window.hdCannedFilter = function(cat) {
        hdCannedCat = cat;
        document.querySelectorAll('#hdCannedSeg .media-pill').forEach(function(b) {
            b.classList.toggle('on', b.dataset.cat === cat);
        });
        hdCannedRender(hdCannedApplyFilters(hdCannedAll, cat));
    };

    function hdCannedFetch(q) {
        var url = '/panel/helpdesk/canned-replies/search' + (q ? '?q=' + encodeURIComponent(q) : '');
        fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': hdCsrf } })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (!q) { hdCannedAll = data; hdCannedBuildSeg(); }
                hdCannedRender(hdCannedApplyFilters(data, hdCannedCat));
            });
    }

    function hdCannedBuildSeg() {
        var cats = [];
        hdCannedAll.forEach(function(r) { if (r.category && cats.indexOf(r.category) === -1) cats.push(r.category); });
        var seg = document.getElementById('hdCannedSeg');
        var html = '<span class="media-pill ' + (!hdCannedCat ? 'on' : '') + '" data-cat="" role="button" tabindex="0" onclick="hdCannedFilter(\'\')">'
            + 'Todas <span class="c">' + hdCannedAll.length + '</span></span>';
        cats.forEach(function(cat) {
            var cnt = hdCannedAll.filter(function(r){ return r.category === cat; }).length;
            html += '<span class="media-pill ' + (hdCannedCat === cat ? 'on' : '') + '" data-cat="' + hdEscape(cat)
                + '" role="button" tabindex="0" onclick="hdCannedFilter(\'' + cat.replace(/'/g,"\\'") + '\')">'
                + hdEscape(cat) + ' <span class="c">' + cnt + '</span></span>';
        });
        seg.innerHTML = html;
    }

    function hdCannedApplyFilters(list, cat) {
        var q = document.getElementById('hdCannedSearch').value.toLowerCase();
        return list.filter(function(r) {
            var matchCat = !cat || r.category === cat;
            var matchQ   = !q || (r.name && r.name.toLowerCase().includes(q))
                               || (r.shortcut && r.shortcut.toLowerCase().includes(q))
                               || (r.body && r.body.toLowerCase().includes(q));
            return matchCat && matchQ;
        });
    }

    function hdCannedRender(list) {
        hdCannedFiltered = list;
        hdCannedActive   = list.length ? 0 : -1;
        hdCannedSelId    = list.length ? list[0].id : null;
        var el = document.getElementById('hdCannedList');
        if (!list.length) {
            el.innerHTML = '<div class="bv-list-state">Sin resultados</div>';
            document.getElementById('hdCannedPreview').value = '';
            return;
        }
        el.innerHTML = list.map(function(r, i) {
            return '<button class="list-item ' + (i === 0 ? 'on' : '') + '" data-idx="' + i + '" onclick="hdCannedSelect(' + i + ')">'
                + (r.shortcut ? '<span class="kbd">/' + hdEscape(r.shortcut) + '</span>' : '<span class="kbd"></span>')
                + '<div class="body"><span class="t">' + hdEscape(r.name) + '</span>'
                + '<span class="s">' + (r.category ? hdEscape(r.category) + ' · ' : '') + 'usada ' + (r.usage_count || 0) + ' veces</span>'
                + '</div></button>';
        }).join('');
        document.getElementById('hdCannedPreview').value = hdReplace(list[0].body || '');
    }

    window.hdCannedSelect = function(idx) {
        hdCannedActive = idx;
        hdCannedSelId  = hdCannedFiltered[idx] ? hdCannedFiltered[idx].id : null;
        document.querySelectorAll('#hdCannedList .list-item').forEach(function(el, i) {
            el.classList.toggle('on', i === idx);
        });
        document.getElementById('hdCannedPreview').value = hdCannedFiltered[idx] ? hdReplace(hdCannedFiltered[idx].body || '') : '';
    };

    window.hdInsertCanned = function() {
        var txt = document.getElementById('hdCannedPreview').value;
        if (!txt.trim()) { return; }
        var ta = document.querySelector('.bv-composer-input');
        if (ta) { ta.value = txt; ta.focus(); ta.dispatchEvent(new Event('input')); }
        if (hdCannedSelId) {
            fetch('/panel/helpdesk/canned-replies/' + hdCannedSelId + '/use', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': hdCsrf }
            }).catch(function(){});
        }
        closeCannedModal();
    };

    var hdCannedSearchInp = document.getElementById('hdCannedSearch');
    hdCannedSearchInp.addEventListener('input', function() {
        clearTimeout(hdCannedTimer);
        var q = this.value.trim();
        hdCannedTimer = setTimeout(function() {
            if (q) { hdCannedFetch(q); }
            else { hdCannedRender(hdCannedApplyFilters(hdCannedAll, hdCannedCat)); }
        }, 250);
    });
    hdCannedSearchInp.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); if (hdCannedActive < hdCannedFiltered.length - 1) hdCannedSelect(hdCannedActive + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); if (hdCannedActive > 0) hdCannedSelect(hdCannedActive - 1); }
        else if (e.key === 'Enter') { e.preventDefault(); hdInsertCanned(); }
        else if (e.key === 'Escape') { closeCannedModal(); }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        if (document.getElementById('hdCannedOverlay').classList.contains('open')) { closeCannedModal(); return; }
    });

    // ── CSAT ───────────────────────────────────────────────
    $(document).on('click', '#bv-btn-send-csat', function() {
        var url = $(this).data('csat-url');
        if (!url) { toastr.error('No hay conversación seleccionada'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': hdCsrf, 'Accept': 'application/json' },
        }).done(function(resp) {
        }).fail(function(xhr) {
            var msg = xhr?.responseJSON?.message || 'No se pudo enviar la encuesta';
            toastr.error(msg);
        }).always(function() {
            $btn.prop('disabled', false);
            $('#bv-more-menu').removeClass('open');
        });
    });

    // ── Integración con el slash-menu de conversations.js (más abajo en este
    // mismo archivo) ─────────────────────────────────────────
    window.bvCannedRepliesUrl = '/panel/helpdesk/canned-replies/search';

    // Reemplazar marcadores de posición en el composer después de insertar una plantilla.
    // Usamos jQuery .on() porque el resto de este archivo dispara
    // $textarea.trigger('input'), que no siempre propaga al addEventListener nativo.
    $(document).on('input', '.bv-composer-input', function() {
        var ta = this;
        var val = ta.value;
        if (!/\{\{/.test(val)) return;
        var replaced = hdReplace(val);
        if (replaced !== val) {
            var pos = ta.selectionStart;
            ta.value = replaced;
            ta.setSelectionRange(pos, pos);
            // Evento nativo para auto-resize (ya sin {{}} no vuelve a procesar)
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
})();
