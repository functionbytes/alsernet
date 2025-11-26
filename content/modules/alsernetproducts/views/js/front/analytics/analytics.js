$(document).ready(function(){

    window.waitFor = function(fnCheck, { interval = 50, timeout = 2000 } = {}) {
        return new Promise((resolve, reject) => {
            const t0 = Date.now();
            const id = setInterval(() => {
                try {
                    const val = fnCheck();
                    if (val) { clearInterval(id); resolve(val); }
                    else if (Date.now() - t0 >= timeout) { clearInterval(id); resolve(null); }
                } catch (e) { clearInterval(id); reject(e); }
            }, interval);
        });
    };

    const $blocks = $('.analytics-products');
    if (!$blocks.length) return;

    $blocks.each(function () {

        const $el = $(this);
        if ($el.data('ga-fired') === 1) return;

        const category = $el.data('category') || '';
        const type     = $el.data('type') || '';

        var link = '/modules/alsernetproducts/controllers/routes.php?category='
            + encodeURIComponent(category)
            + '&type=' + encodeURIComponent(type);

        $.ajax({
            cache: true,
            url: link
        }).done(async function(results) {   // 👈 hacemos async la función
            if(results.status === "success") {

                window.CartGTMHelper = window.CartGTMHelper || window.GTMCartHelper;

                const helper = await window.waitFor(
                    () => window.GTMCartHelper || window.CartGTMHelper,
                    { timeout: 1500 }
                );

                let rawItems = [];
                try {
                    rawItems = Array.isArray(results.data.product_analytics)
                        ? results.data.product_analytics
                        : JSON.parse(results.data.product_analytics || '[]');
                } catch { rawItems = []; }

                const items = rawItems
                    .map(i => {
                        const item_id   = String(i.item_id ?? i.id ?? i.product_id ?? '');
                        const item_name = String(i.item_name ?? i.name ?? '');
                        return item_id && item_name ? {
                            item_id,
                            item_name,
                            item_unique_id: String(i.item_unique_id ?? i.unique_id ?? item_id),
                            item_brand:     i.item_brand ?? i.brand ?? '',
                            item_category:  i.item_category ?? i.category ?? '',
                            item_variant:   i.item_variant ?? i.variant ?? '',
                            item_variant2:  i.item_variant2 ?? i.variant2 ?? '',
                            price:          Number.isFinite(+i.price)    ? +i.price    : undefined,
                            discount:       Number.isFinite(+i.discount) ? +i.discount : undefined,
                            quantity:       Number.isFinite(+i.quantity) ? +i.quantity : 1
                        } : null;
                    })
                    .filter(Boolean);

                const list_name = results.data.list_name ?? '';
                const list_id   = results.data.list_id   ?? '';

                if (helper && typeof helper.trackViewItemList === 'function') {
                    if (items.length) {
                        try {
                            await helper.trackViewItemList(
                                { list_name, list_id },
                                items
                            );
                        } catch (e) {
                            console.warn('trackViewItemList falló:', e);
                        }
                    } else {
                        console.warn('view_item_list: no hay ítems válidos para enviar.');
                    }
                } else {
                    console.warn('CartGTMHelper/GTMCartHelper no disponible; se continúa sin tracking.');
                }
            }
        }).fail(function() {
            console.log("Error en la carga de datos.");
        });

    });

});
