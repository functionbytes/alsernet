(function ($) {
    (function () {

        var busy = {
            productPreview: false, productSave: false,
            categoryPreview: false, categorySave: false,
            brandPreview: false, brandSave: false
        };

        if ($('#alc-category-tree-root').length) {
            // Un pequeño tick por si el árbol se inyecta al renderizar el tpl
            setTimeout(alcExpandPathToChecked, 0);
        }

        function ajax(url, data) {
            data = data || {};
            data.ajax = 1;
            return $.ajax({
                type: 'POST',
                url: url,
                data: data,
                dataType: 'json'
            }).fail(function (xhr, status, err) {
                if (typeof showErrorMessage === 'function') {
                    showErrorMessage('Error AJAX: ' + (xhr.responseText || status));
                } else {
                    console.error('AJAX error:', status, err, xhr && xhr.responseText);
                    alert('Error AJAX. Revisa consola.');
                }
            });
        }

        function renderTable($table, products) {
            if (!products || !products.length) {
                $table.html('<tr><td class="text-muted">Sin resultados</td></tr>');
                return;
            }
            var html = '<thead><tr>' +
                '<th>Img</th><th>ID</th><th>Ref</th><th>Nombre</th><th>Precio</th><th></th>' +
                '</tr></thead><tbody>';
            products.forEach(function (p) {
                html += '<tr>' +
                    '<td style="width:60px;">' + (p.image_url ? '<img src="' + p.image_url + '" style="max-width:50px;" />' : '') + '</td>' +
                    '<td>' + p.id_product + '</td>' +
                    '<td>' + (p.reference || '') + '</td>' +
                    '<td>' + (p.name || '') + '</td>' +
                    '<td>' + (p.price.toFixed(2) + '€' || '') + '</td>' +
                    '<td><a class="btn btn-xs btn-default" target="_blank" href="' + p.url + '"><i class="icon-external-link"></i></a></td>' +
                    '</tr>';
            });
            html += '</tbody>';
            $table.html(html);
        }

        function showConflicts($box, conflicts) {
            if (!conflicts || !conflicts.length) { $box.hide().empty(); return; }
            var html = '<strong>Atención:</strong> Se detectaron posibles conflictos:<ul>';
            conflicts.forEach(function (c) {
                var reason;
                switch (c.reason) {
                    case 'overlap_source_same_type':
                        reason = 'Solapamiento de FUENTES con otro registro del mismo tipo';
                        break;
                    case 'overlap_complements_same_type':
                        reason = 'Alguna referencia de COMPLEMENTARIOS ya existe en otro registro del mismo tipo';
                        break;
                    default:
                        reason = 'Conflicto';
                }
                html += '<li>Registro #' + c.id + ': ' + reason + '.</li>';
            });
            html += '</ul>';
            $box.html(html).show();
        }



        function getUrl(action) {
            var base = $('#alc-ajax-base').val();
            if (!base) base = window.location.href.split('#')[0];

            // Limpia residuos previos
            base = base
                .replace(/([?&])ajax=1(&|$)/, '$1')
                .replace(/([?&])action=[^&]*(&|$)/, '$1')
                .replace(/[?&]$/, '');

            // ✅ token asegurado
            if (!/[?&]token=/.test(base)) {
                var tok = currentToken();
                if (tok) base += (base.indexOf('?') === -1 ? '?' : '&') + 'token=' + encodeURIComponent(tok);
            }

            // Añado ajax y action
            base += (base.indexOf('?') === -1 ? '?' : '&') + 'ajax=1&action=' + encodeURIComponent(action);
            return base;
        }



        /* ========= DELEGACIÓN DE EVENTOS ========= */

        // PRODUCT: previsualizar
        $(document).on('click', '#alc-btn-preview-product', function () {
            if (busy.productPreview) return;
            busy.productPreview = true;

            var $btn = $('#alc-btn-preview-product');
            var $panel = $('#alc-form-product').closest('.panel');

            var complements = $('textarea[name="alc_complements"]').val();
            var sources = $('textarea[name="alc_sources"]').val();

            withLoading($.when(
                ajax(getUrl('ResolveProducts'), { list: sources }),
                ajax(getUrl('Preview'), { complements: complements })
            ), $btn, $panel).done(function (a, b) {
                var src = a && a[0] && a[0].data ? a[0].data : (a.data || []);
                var cmp = b && b[0] && b[0].data ? b[0].data : (b.data || []);
                renderTable($('#alc-table-sources'), src);
                renderTable($('#alc-table-complements'), cmp);
                $('#alc-preview-product').show();

                withLoading(ajax(getUrl('CheckConflicts'), {
                    type: 'product', sources: sources, complements: complements
                }), $btn, $panel).done(function (r) {
                    showConflicts($('#alc-conflicts'), (r && r.conflicts) ? r.conflicts : []);
                });
            }).always(function () { busy.productPreview = false; });
        });

        // PRODUCT: guardar
        $(document).on('click', '#alc-btn-save-product', function () {
            if (busy.productSave) return;
            busy.productSave = true;

            var id = $('#alc-edit-id').val() || '';
            var $btns = $('#alc-btn-save-product, #alc-btn-preview-product');
            var $panel = $('#alc-form-product').closest('.panel');

            withLoading(ajax(getUrl('SaveMapping'), {
                id: id,
                type: 'product',
                title: $('input[name="alc_title"]').val(),
                sources: $('textarea[name="alc_sources"]').val(),
                complements: $('textarea[name="alc_complements"]').val(),
                position: $('input[name="alc_position"]').val() || 0   // ← NUEVO
            }), $btns, $panel).done(function (r) {
                var $err = $('#alc-errors');
                if (r && r.success) {
                    // Redirección SIEMPRE al listado (URL fija que nos diste)
                    window.location = getListUrlWithMsg($('#alc-edit-id').length ? '2' : '1');
                } else {
                    var msgs = (r && r.errors) ? r.errors : ['Error desconocido.'];
                    $err.html('<ul><li>' + msgs.map(function (m) { return $('<div>').text(m).html(); }).join('</li><li>') + '</li></ul>').show();
                    if (typeof showErrorMessage === 'function') showErrorMessage('Revisa los errores del formulario.');
                }
            }).always(function () { busy.productSave = false; });
        });

        // PRODUCT: excluir (botón negro)
        $(document).on('click', '#alc-btn-exclude-product', function () {
            ajax(getUrl('ExcludeOnly'), {
                type: 'product',
                sources: $('textarea[name="alc_sources"]').val(),
                excluded: $('textarea[name="alc_excluded"]').val(),
                position: $('input[name="alc_position"]').val() || 0   // ← NUEVO (opcional)
            }).done(function (r) {
                if (r && r.success) {
                    if (typeof showSuccessMessage === 'function') showSuccessMessage('Exclusiones guardadas (#' + r.id + ').');
                } else {
                    if (typeof showErrorMessage === 'function') showErrorMessage('No se pudo guardar exclusiones.');
                }
            });
        });


        // CATEGORÍA: previsualizar origen + complementos
        $(document).on('click', '#alc-btn-preview-category', function () {
            if (busy.categoryPreview) return;
            busy.categoryPreview = true;

            var $btn = $('#alc-btn-preview-category');
            var $panel = $('#alc-form-category').closest('.panel');

            var cats = [];
            $('input[type="checkbox"][name="categoryBox[]"]:checked').each(function () { cats.push($(this).val()); });

            // ← NUEVO: leer marcas seleccionadas (ids)
            var brands = $('#alc-brands').val() || [];
            brands = brands.map(function (v) { return parseInt(v, 10) || 0; }).filter(Boolean);

            var complements = $('textarea[name="alc_complements"]').val();
            var excluded = $('textarea[name="alc_excluded"]').val();

            withLoading(ajax(getUrl('PreviewCategory'), {
                sources: cats,
                brands: brands, // ← NUEVO: enviar marcas
                complements: complements,
                excluded: excluded
            }), $btn, $panel).done(function (r) {
                renderTable($('#alc-table-sources-cat'), (r && r.sources_data) ? r.sources_data : []);
                renderTable($('#alc-table-complements'), (r && r.complements_data) ? r.complements_data : []);
                $('#alc-preview-category').show();

                withLoading(ajax(getUrl('CheckConflicts'), {
                    type: 'category',
                    sources: cats,
                    complements: complements
                    // (no hace falta brands aquí; tu backend de conflictos para category no lo usa)
                }), $btn, $panel).done(function (cr) {
                    showConflicts($('#alc-conflicts'), (cr && cr.conflicts) ? cr.conflicts : []);
                });
            }).always(function () { busy.categoryPreview = false; });
        });

        // CATEGORY: guardar
        $(document).on('click', '#alc-btn-save-category', function () {
            if (busy.categorySave) return;
            busy.categorySave = true;

            var $btns = $('#alc-btn-save-category, #alc-btn-preview-category');
            var $panel = $('#alc-form-category').closest('.panel');

            var cats = [];
            $('input[type="checkbox"][name="categoryBox[]"]:checked').each(function () { cats.push($(this).val()); });

            // marcas seleccionadas (ids)
            var brands = $('#alc-brands').val() || [];
            brands = brands.map(function (v) { return parseInt(v, 10) || 0; }).filter(Boolean);

            withLoading(ajax(getUrl('SaveMapping'), {
                id: $('#alc-edit-id').val() || '',
                type: 'category',
                title: $('input[name="alc_title"]').val() || '',
                sources: cats,
                brands: brands,
                complements: $('textarea[name="alc_complements"]').val(),
                excluded: $('textarea[name="alc_excluded"]').val(),
                position: $('input[name="alc_position"]').val() || 0   // ← NUEVO
            }), $btns, $panel).done(function (r) {
                var $err = $('#alc-errors');
                if (r && r.success) {
                    window.location = getListUrlWithMsg($('#alc-edit-id').length ? '2' : '1');
                } else {
                    var msgs = (r && r.errors) ? r.errors : ['Error desconocido.'];
                    $err.html('<ul><li>' + msgs.map(function (m) { return $('<div>').text(m).html(); }).join('</li><li>') + '</li></ul>').show();
                    if (typeof showErrorMessage === 'function') showErrorMessage('Revisa los errores del formulario.');
                }
            }).always(function () { busy.categorySave = false; });
        });

        // CATEGORY: excluir
        $(document).on('click', '#alc-btn-exclude-category', function () {
            var cats = [];
            $('input[type="checkbox"][name="categoryBox[]"]:checked').each(function () { cats.push($(this).val()); });
            ajax(getUrl('ExcludeOnly'), {
                type: 'category',
                sources: cats,
                excluded: $('textarea[name="alc_excluded"]').val(),
                position: $('input[name="alc_position"]').val() || 0   // ← NUEVO (opcional)
            }).done(function (r) {
                if (r && r.success) {
                    if (typeof showSuccessMessage === 'function') showSuccessMessage('Exclusiones guardadas (#' + r.id + ').');
                } else {
                    if (typeof showErrorMessage === 'function') showErrorMessage('No se pudo guardar exclusiones.');
                }
            });
        });

        // BRAND: previsualizar
        $(document).on('click', '#alc-btn-preview-brand', function () {
            if (busy.brandPreview) return;
            busy.brandPreview = true;

            var $btn = $('#alc-btn-preview-brand');
            var $panel = $('#alc-form-brand').closest('.panel');

            var complements = $('textarea[name="alc_complements"]').val() || '';
            var brands = $('select[name="alc_brands[]"]').val() || [];
            var excluded = $('textarea[name="alc_excluded"]').val() || '';

            withLoading(ajax(getUrl('PreviewBrand'), {
                sources: brands,        // ids de manufacturer
                complements: complements, // ← IMPORTANTE: enviarlo aquí
                excluded: excluded
            }), $btn, $panel).done(function (r) {
                var src = (r && r.sources_data) ? r.sources_data : [];
                var cmp = (r && r.complements_data) ? r.complements_data : [];

                // IDs correctos para BRAND
                renderTable($('#alc-table-sources-brand'), src);
                renderTable($('#alc-table-complements'), cmp);

                $('#alc-preview-brand').show();

                withLoading(ajax(getUrl('CheckConflicts'), {
                    type: 'brand',
                    sources: brands,
                    complements: complements
                }), $btn, $panel).done(function (cr) {
                    showConflicts($('#alc-conflicts'), (cr && cr.conflicts) ? cr.conflicts : []);
                });
            }).always(function () {
                busy.brandPreview = false;
            });
        });

        // BRAND: guardar
        $(document).on('click', '#alc-btn-save-brand', function () {
            if (busy.brandSave) return;
            busy.brandSave = true;

            var $btns = $('#alc-btn-save-brand, #alc-btn-preview-brand');
            var $panel = $('#alc-form-brand').closest('.panel');

            var id = $('#alc-edit-id').val() || '';
            var brands = $('select[name="alc_brands[]"]').val() || [];

            withLoading(ajax(getUrl('SaveMapping'), {
                id: id,
                type: 'brand',
                title: $('input[name="alc_title"]').val() || '',
                sources: brands,
                complements: $('textarea[name="alc_complements"]').val(),
                excluded: $('textarea[name="alc_excluded"]').val(),
                position: $('input[name="alc_position"]').val() || 0   // ← NUEVO
            }), $btns, $panel).done(function (r) {
                var $err = $('#alc-errors');
                if (r && r.success) {
                    window.location = getListUrlWithMsg($('#alc-edit-id').length ? '2' : '1');
                } else {
                    var msgs = (r && r.errors) ? r.errors : ['No se pudo guardar.'];
                    $err.html('<ul><li>' + msgs.map(function (m) { return $('<div>').text(m).html(); }).join('</li><li>') + '</li></ul>').show();
                    if (typeof showErrorMessage === 'function') showErrorMessage('Revisa los errores del formulario.');
                }
            }).always(function () { busy.brandSave = false; });
        });

        // BRAND: excluir
        $(document).on('click', '#alc-btn-exclude-brand', function () {
            var brands = $('select[name="alc_brands[]"]').val() || [];
            ajax(getUrl('ExcludeOnly'), {
                type: 'brand',
                sources: brands,
                excluded: $('textarea[name="alc_excluded"]').val(),
                position: $('input[name="alc_position"]').val() || 0   // ← NUEVO (opcional)
            }).done(function (r) {
                if (r && r.success) {
                    if (typeof showSuccessMessage === 'function') showSuccessMessage('Exclusiones guardadas (#' + r.id + ').');
                } else {
                    if (typeof showErrorMessage === 'function') showErrorMessage('No se pudo guardar exclusiones.');
                }
            });
        });


        // --- Árbol de categorías plegable estilo BO ---
        $(document).on('click', '#alc-category-tree-wrap .tree-folder-name', function (e) {
            // Evitar que click en checkbox/label dispare toggle
            if ($(e.target).is('input[type="checkbox"], label')) return;
            var $name = $(this);
            var $icon = $name.find('> i.icon-folder-close, > i.icon-folder-open');
            var $ul = $name.closest('li.tree-folder').children('ul.tree');
            if ($ul.is(':visible')) {
                $ul.slideUp('fast');
                if ($icon.length) $icon.removeClass('icon-folder-open').addClass('icon-folder-close');
            } else {
                $ul.slideDown('fast');
                if ($icon.length) $icon.removeClass('icon-folder-close').addClass('icon-folder-open');
            }
        });

        // Expandir/contraer todo
        $(document).on('click', '#alc-expand-all', function () {
            $('#alc-category-tree-root li.tree-folder > ul.tree').slideDown('fast');
            $('#alc-category-tree-root li.tree-folder > .tree-folder-name > i.icon-folder-close')
                .removeClass('icon-folder-close').addClass('icon-folder-open');
        });

        $(document).on('click', '#alc-collapse-all', function () {
            $('#alc-category-tree-root li.tree-folder > ul.tree').slideUp('fast');
            $('#alc-category-tree-root li.tree-folder > .tree-folder-name > i.icon-folder-open')
                .removeClass('icon-folder-open').addClass('icon-folder-close');
        });

        function alcExpandPathToChecked() {
            var $root = $('#alc-category-tree-root');
            if (!$root.length) return;

            var firstTarget = null;

            $root.find('input[name="categoryBox[]"]:checked').each(function () {
                var $cb = $(this);

                // Abre TODAS las carpetas ancestro de este checkbox
                $cb.parents('li.tree-folder').each(function () {
                    var $folder = $(this);
                    // Mostrar hijos
                    $folder.children('ul.tree').stop(true, true).show();
                    // Icono: abierto
                    $folder.children('.tree-folder-name')
                        .find('> i.icon-folder-close, > i.icon-folder-open')
                        .removeClass('icon-folder-close')
                        .addClass('icon-folder-open');
                });

                if (!firstTarget) firstTarget = $cb;
            });

            // (Opcional) scroll y highlight al primero
            if (firstTarget && firstTarget.length) {
                $('html, body').stop(true, true);
                var top = firstTarget.offset().top - 120;
                if (top > 0) $('html, body').animate({ scrollTop: top }, 200);
                var $row = firstTarget.closest('span.tree-folder-name, span.tree-item-name');
                $row.addClass('alc-pulse');
                setTimeout(function () { $row.removeClass('alc-pulse'); }, 1000);
            }
        }

        // Exponer para poder llamarla desde consola si quieres
        window.alcExpandPathToChecked = alcExpandPathToChecked;

        // --- Espera a que el árbol exista en el DOM, luego expande paths ---
        function whenTreeReady(cb) {
            var sel = '#alc-category-tree-root';

            // Si ya está, dispara en el siguiente tick
            if ($(sel).length) {
                setTimeout(cb, 0);
                return;
            }

            // Observa el body hasta que aparezca el árbol
            var obs = new MutationObserver(function () {
                if ($(sel).length) {
                    obs.disconnect();
                    setTimeout(cb, 0);
                }
            });
            obs.observe(document.documentElement || document.body, { childList: true, subtree: true });

            // Fallback por si el árbol llega tras onload
            $(window).on('load', function () {
                if ($(sel).length) {
                    setTimeout(cb, 0);
                }
            });

            // Último fallback: reintentos cortos
            var tries = 0;
            var iv = setInterval(function () {
                if ($(sel).length || tries++ > 20) {
                    clearInterval(iv);
                    if ($(sel).length) setTimeout(cb, 0);
                }
            }, 100);
        }

        $(function () {
            whenTreeReady(alcExpandPathToChecked);
        });

        // ===== Helpers de carga / bloqueo =====
        function alcBlock($btns, $panel) {
            // Bloquea botones
            $btns = $btns || $();
            $btns.each(function () { $(this).addClass('alc-btn-busy').prop('disabled', true); });

            // Overlay en el panel si existe
            if ($panel && $panel.length) {
                var $wrap = $panel.css('position') === 'static' ? $panel.css('position', 'relative') : $panel;
                if (!$panel.find('> .alc-loading-overlay').length) {
                    $panel.append('<div class="alc-loading-overlay"><span class="alc-loading-spinner"></span></div>');
                }
            }
        }

        function alcUnblock($btns, $panel) {
            $btns = $btns || $();
            $btns.removeClass('alc-btn-busy').prop('disabled', false);
            if ($panel && $panel.length) { $panel.find('> .alc-loading-overlay').remove(); }
        }

        // Conveniencia: bloquea/rehabilita por promesa
        function withLoading(promise, $btns, $panel) {
            alcBlock($btns, $panel);
            return promise.always(function () { alcUnblock($btns, $panel); });
        }

        // Toggle SOLO hijas/subhijas desde el checkbox auxiliar en carpetas
        $(document).on('change', '.alc-children-only', function () {
            var checked = $(this).is(':checked');
            // LI carpeta más cercano
            var $folder = $(this).closest('li.tree-folder');
            if (!$folder.length) return;

            // Todas las hijas y subhijas (sin el padre)
            var $descendants = $folder.find('> ul.tree input[name="categoryBox[]"], ul.tree ul.tree input[name="categoryBox[]"]');
            $descendants.prop('checked', checked);

            // (Opcional) Abrir la carpeta cuando se marcan hijas
            var $ul = $folder.children('ul.tree');
            var $icon = $folder.children('.tree-folder-name').find('> i.icon-folder-close, > i.icon-folder-open');
            if (checked) {
                $ul.stop(true, true).slideDown('fast');
                if ($icon.length) $icon.removeClass('icon-folder-close').addClass('icon-folder-open');
            }
        });

        // LABEL: previsualizar
        $(document).on('click', '#alc-btn-preview-label', function () {
            var labels = $('textarea[name="alc_labels"]').val();
            var complements = $('textarea[name="alc_complements"]').val();
            var excluded = $('textarea[name="alc_excluded"]').val();

            var $btns = $('#alc-btn-preview-label, #alc-btn-save-label').prop('disabled', true);
            var $err = $('#alc-errors').hide().empty();

            // loader simple
            var $loader = $('<div id="alc-loading" class="alert alert-info">Cargando...</div>').insertAfter($('#alc-form-label'));

            ajax(getUrl('PreviewLabel'), {
                sources: labels,
                complements: complements,
                excluded: excluded
            }).done(function (r) {
                renderTable($('#alc-table-sources-cat'), (r && r.sources_data) ? r.sources_data : []);
                renderTable($('#alc-table-complements'), (r && r.complements_data) ? r.complements_data : []);
                $('#alc-preview-label').show();

                // Conflictos/duplicados
                ajax(getUrl('CheckConflicts'), {
                    type: 'label',
                    sources: labels,
                    complements: complements
                }).done(function (cr) {
                    showConflicts($('#alc-conflicts'), (cr && cr.conflicts) ? cr.conflicts : []);
                });
            }).always(function () {
                $btns.prop('disabled', false);
                $('#alc-loading').remove();
            });
        });

        // LABEL: guardar
        $(document).on('click', '#alc-btn-save-label', function () {
            var id = $('#alc-edit-id').val() || '';
            var labels = $('textarea[name="alc_labels"]').val();
            var complements = $('textarea[name="alc_complements"]').val();
            var excluded = $('textarea[name="alc_excluded"]').val();

            var $btns = $('#alc-btn-preview-label, #alc-btn-save-label').prop('disabled', true);
            var $err = $('#alc-errors').hide().empty();
            var $loader = $('<div id="alc-loading" class="alert alert-info">Guardando...</div>').insertAfter($('#alc-form-label'));

            ajax(getUrl('SaveMapping'), {
                id: id,
                type: 'label',
                title: $('input[name="alc_title"]').val() || '',
                sources: labels,
                complements: complements,
                excluded: excluded,
                position: $('input[name="alc_position"]').val() || 0   // ← NUEVO
            }).done(function (r) {
                if (r && r.success) {
                    // Redirección SIEMPRE al listado (URL fija que nos diste)
                    window.location = getListUrlWithMsg($('#alc-edit-id').length ? '2' : '1');
                } else {
                    var msgs = (r && r.errors) ? r.errors : ['Error desconocido.'];
                    $err.html('<ul><li>' + msgs.map(function (m) { return $('<div>').text(m).html(); }).join('</li><li>') + '</li></ul>').show();
                    if (typeof showErrorMessage === 'function') showErrorMessage('Revisa los errores del formulario.');
                }
            }).always(function () {
                $btns.prop('disabled', false);
                $('#alc-loading').remove();
            });
        });

        function getListUrlWithMsg(flag) {
            var listUrl = $('#alc-list-url').val();
            if (!listUrl) listUrl = window.location.href.split('#')[0];

            // ✅ aseguro token
            if (!/[?&]token=/.test(listUrl)) {
                var tok = currentToken();
                if (tok) listUrl += (listUrl.indexOf('?') === -1 ? '?' : '&') + 'token=' + encodeURIComponent(tok);
            }

            // limpia alcmsg previo y agrega el nuevo
            listUrl = listUrl.replace(/([?&])alcmsg=\d+/, '').replace(/[?&]$/, '');
            listUrl += (listUrl.indexOf('?') === -1 ? '?' : '&') + 'alcmsg=' + (flag || '1');
            return listUrl;
        }


        function getTokenFromUrl(u) {
            var m = (u || '').match(/[?&]token=([^&#]+)/);
            return m ? m[1] : '';
        }


        function currentToken() {
            // 1) Intenta desde #alc-ajax-base (que viene con token)
            var base = $('#alc-ajax-base').val() || '';
            var t = getTokenFromUrl(base);
            if (t) return t;

            // 2) Intenta desde la URL actual
            t = getTokenFromUrl(window.location.href);
            if (t) return t;

            // 3) Último recurso: variable global (algunos BO la exponen)
            if (typeof window.token !== 'undefined' && window.token) return window.token;
            if (window.prestashop && window.prestashop.token) return window.prestashop.token;

            return ''; // si no hay, devolvemos vacío (pero en BO debería haber)
        }

    })();
})(jQuery);