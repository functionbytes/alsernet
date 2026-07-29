(function () {
    var page = 1, pageSize = 20, lastFilters = null;

    function msg(text, cls) {
        var el = document.getElementById('load_msg');
        if (!el) return;
        el.className = 'help-block ' + (cls || '');
        el.textContent = text || '';
    }

    function showLoader(show) {
        var wrap = document.getElementById('results_wrap');
        var loader = document.getElementById('results_loader');
        if (!wrap || !loader) return;
        wrap.style.display = '';
        loader.style.display = show ? '' : 'none';
    }

    function fillBrandOptions(options) {
        var sel = document.getElementById('brand_list');
        if (!sel) return;
        sel.innerHTML = '';
        var optDefault = document.createElement('option');
        optDefault.value = '';
        optDefault.textContent = 'Selecciona una marca';
        sel.appendChild(optDefault);
        (options || []).forEach(function (o) {
            if (!o || !o.id_manufacturer || !o.name) return;
            var opt = document.createElement('option');
            opt.value = o.id_manufacturer;
            opt.textContent = o.name;
            sel.appendChild(opt);
        });
    }


    function renderBaseAttributes(list) {
        var wrap = document.getElementById('base_attrs_wrap');
        var ul = document.getElementById('base_attrs');
        if (!wrap || !ul) return;
        ul.innerHTML = '';
        if (Array.isArray(list) && list.length) {
            list.forEach(function (txt) {
                var li = document.createElement('li');
                li.textContent = txt;
                ul.appendChild(li);
            });
            wrap.style.display = '';
        } else {
            wrap.style.display = 'none';
        }
    }

    function fetchFilters() {
        var ref = document.getElementById('alser_ref').value.trim();
        if (!ref) { msg('Ingresa una referencia.', 'text-danger'); return; }
        msg('Cargando filtros...', 'text-info');

        var url = new URL(ALSERNET.ajax_url, window.location.origin);
        url.searchParams.set('action', 'loadFilters');
        url.searchParams.set('reference', ref);
        var filterLang = document.getElementById('filter_lang') ? document.getElementById('filter_lang').value : ALSERNET.id_lang;
        url.searchParams.set('filter_id_lang', filterLang);

        fetch(url.toString(), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j.ok) { msg(j.message || 'Error'); return; }
                var p = j.payload;

                document.getElementById('base_and_filters').style.display = '';
                document.getElementById('filters_form').style.display = '';

                // Panel izquierdo (producto base)
                document.getElementById('base_img').src = p.image || '';
                document.getElementById('base_name').textContent = p.name || '';
                document.getElementById('base_price').textContent = (typeof p.price === 'number' ? p.price.toFixed(2) : p.price);

                // Características bajo precio
                renderBaseAttributes(p.attributes || []);

                // Filtros derecha
                document.getElementById('exclude_id').value = p.id_product;
                document.getElementById('id_product_attribute').value = p.id_product_attribute || 0;
                document.getElementById('name_like').value = p.name || '';
                document.getElementById('category_name').value = p.category || '';
                document.getElementById('id_category').value = p.id_category || '';

                // Precios vacíos
                document.getElementById('price_from').value = '';
                document.getElementById('price_to').value = '';

                // ✅ Marcas: cargar SOLO aquí (desde loadFilters) y habilitar el select
                fillBrandOptions(p.brand_options || []);
                // var brandSel = document.getElementById('brand_list');
                // if (brandSel) {
                //     brandSel.disabled = false; // por si venía bloqueado de búsquedas anteriores
                // }

                msg('Filtros cargados', 'text-success');

                // Auto-buscar (si lo mantienes)
                search(1);
            })
            .catch(function () { msg('Error de red', 'text-danger'); });
    }

    function collectFilters() {
        var brandSel = document.getElementById('brand_list');
        var brandIds = brandSel
            ? Array.from(brandSel.options).filter(function (o) { return o.selected && o.value && o.value !== '0'; }).map(function (o) { return o.value; })
            : [];

        return {
            id_category: document.getElementById('id_category').value,
            id_brand_list: brandIds,
            name_like: document.getElementById('name_like').value,
            price_from: document.getElementById('price_from').value,
            price_to: document.getElementById('price_to').value,
            exclude_id: document.getElementById('exclude_id').value,
            filter_id_lang: document.getElementById('filter_lang') ? document.getElementById('filter_lang').value : ALSERNET.id_lang
        };
    }

    function renderResults(payload) {
        var wrap = document.getElementById('results_wrap');
        var grid = document.getElementById('results_grid');
        if (!wrap || !grid) return;

        document.getElementById('total_found').textContent = payload.total;
        grid.innerHTML = '';

        var items = payload.items || [];

        // Agrupar en filas de 3
        for (var i = 0; i < items.length; i += 3) {
            var row = document.createElement('div');
            row.className = 'row mx-0';
            grid.appendChild(row);

            for (var j = 0; j < 3 && (i + j) < items.length; j++) {
                var it = items[i + j];

                var col = document.createElement('div');
                col.className = 'col-12 col-sm-6 col-md-4 p-2 d-flex';
                col.style.padding = '10px';

                var card = document.createElement('div');
                card.className = 'w-100 h-100 border border-dark rounded bg-white shadow-sm d-flex flex-column';
                card.style.padding = '8px';
                card.style.backgroundColor = '#f5f5f5';

                var a = document.createElement('a');
                a.href = it.url || '#';
                a.target = '_new';
                a.className = 'd-block bg-secondary text-light text-center rounded-top';
                a.innerHTML = '<div class="font-weight-bold py-1">Id Producto ' + it.id_product + '</div>' +
                    '<div class="small pb-1">' + (it.name || '') + '</div>';
                card.appendChild(a);

                var bodyRow = document.createElement('div');
                bodyRow.className = 'row no-gutters mt-2 flex-grow-1';

                var left = document.createElement('div');
                left.className = 'col-6 text-center d-flex align-items-start justify-content-center p-2';
                left.style.background = '#f9f9f9';
                left.innerHTML = it.image
                    ? '<img src="' + it.image + '" style="max-width:100%; max-height:160px; object-fit:contain;">'
                    : '<div class="text-muted small">Sin imagen</div>';

                var right = document.createElement('div');
                right.className = 'col-6 p-2 d-flex flex-column';

                var table = document.createElement('table');
                table.className = 'table table-sm mb-0';
                table.style.fontSize = '0.85rem';

                var thead = document.createElement('thead');
                thead.className = 'thead-light';
                thead.innerHTML = '<tr>' +
                    '<th class="py-1">Ref.</th>' +
                    '<th class="py-1">Caract.</th>' +
                    '<th class="py-1 text-right">Stock</th>' +
                    '<th class="py-1 text-right">Precio</th>' +
                    '</tr>';

                var tbody = document.createElement('tbody');

                (it.combinations || []).forEach(function (c) {
                    var tr = document.createElement('tr');

                    var tdRef = document.createElement('td');
                    tdRef.className = 'py-1 font-weight-bold text-dark align-middle';
                    tdRef.textContent = c.reference || '-';

                    var tdPairs = document.createElement('td');
                    tdPairs.className = 'py-1 align-middle';
                    tdPairs.textContent = (c.pairs || []).join(', ');

                    var tdStock = document.createElement('td');
                    tdStock.className = 'py-1 text-right align-middle';
                    tdStock.textContent = (c.stock == null ? 0 : c.stock);

                    var tdPrice = document.createElement('td');
                    tdPrice.className = 'py-1 text-right align-middle font-weight-bold';
                    var priceTxt = (typeof c.price === 'number') ? c.price.toFixed(2) : (c.price || '');
                    tdPrice.textContent = priceTxt ? (priceTxt + '€') : '';

                    tr.appendChild(tdRef);
                    tr.appendChild(tdPairs);
                    tr.appendChild(tdStock);
                    tr.appendChild(tdPrice);
                    tbody.appendChild(tr);
                });

                table.appendChild(thead);
                table.appendChild(tbody);
                right.appendChild(table);

                bodyRow.appendChild(left);
                bodyRow.appendChild(right);
                card.appendChild(bodyRow);

                col.appendChild(card);
                row.appendChild(col);
            }
        }

        showLoader(false);
        document.getElementById('page_info').textContent = 'Página ' + page;
    }

    function search(pageWanted) {
        page = pageWanted || 1;
        var filters = collectFilters();
        lastFilters = filters;

        var form = new FormData();
        Object.keys(filters).forEach(function (k) {
            if (Array.isArray(filters[k])) {
                var key = (k === 'id_brand_list') ? 'id_brand_list[]' : k + '[]';
                filters[k].forEach(function (v) { form.append(key, v); });
            } else {
                form.append(k, filters[k]);
            }
        });
        form.append('page', page);
        form.append('page_size', 20);

        var url = new URL(ALSERNET.ajax_url, window.location.origin);
        url.searchParams.set('action', 'searchRelated');

        showLoader(true);

        fetch(url.toString(), { method: 'POST', body: form, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j.ok) { showLoader(false); alert('Error en la búsqueda'); return; }
                renderResults(j.payload);

                // ❌ Ya NO actualizamos marcas aquí
                // if (j.payload.brand_options) { fillBrandOptions(j.payload.brand_options); }

            })
            .catch(function () { showLoader(false); alert('Error de red'); });
    }


    document.addEventListener('DOMContentLoaded', function () {
        var btnLoad = document.getElementById('btn_load_filters');
        if (btnLoad) btnLoad.addEventListener('click', function (e) {
            e.preventDefault(); fetchFilters();
        });

        var btnSearch = document.getElementById('btn_search');
        if (btnSearch) btnSearch.addEventListener('click', function (e) {
            e.preventDefault();
            // ✅ Bloquear select de marcas a partir de la primera búsqueda manual
            // var brandSel = document.getElementById('brand_list');
            // if (brandSel) {
            //     brandSel.disabled = true;
            // }
            search(1);
        });

        var prev = document.getElementById('prev_page');
        if (prev) prev.addEventListener('click', function () {
            if (page > 1) search(page - 1);
        });

        var next = document.getElementById('next_page');
        if (next) next.addEventListener('click', function () {
            search(page + 1);
        });
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


})();
