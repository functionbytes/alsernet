{{-- Modal: Recomendar producto de PrestaShop — Diseño C · dos columnas.
     Cabecera y pie migrados al sistema de modales v2 (.modal/.modal-head/
     .modal-icon/.modal-label/.modal-foot), el mismo que ya usan
     "Escalar a ticket" y "Ver conversación anterior". El cuerpo se queda
     con sus clases .ps-* propias (layout de 2 columnas, necesita su propio
     grid — el .modal-body genérico no sirve aquí).

     CTA principal = "Recomendar en chat": es la única acción que hoy
     funciona de verdad contra el bridge. "Añadir al carrito" depende de
     AssistedCartController, cuyas rutas están comentadas en
     routes/managers.php porque el módulo Ecommerce no se trae a este
     proyecto — se deshabilita con una nota en vez de dejar un botón que
     siempre da 404, y ya no ocupa una fila entera como si fuera la acción
     esperada. --}}
<div class="bv-modal" data-bv-modal-name="ps-product-recommend" data-ps-admin-url="{{ config('helpdeskprestashop.admin_url') }}">
    <div class="modal w-xl">

        <div class="modal-head">
            <div class="modal-icon"><i class="fas fa-gift"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">Chat · Productos</div>
                <div class="modal-title">Recomendar producto</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="modal-body ps-modal-body">

            {{-- ── IZQUIERDA: buscador + lista ─────────────────────────────── --}}
            <div class="ps-list-pane">
                <div class="search-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="prProductSearch"
                           placeholder="Nombre, ID o referencia…" autocomplete="off">
                </div>
                <div class="ps-stock-filter">
                    <label class="ps-stock-label">
                        <input type="checkbox" id="prInStockOnly">
                        <span>Solo en stock</span>
                    </label>
                    <select id="prSortBy" class="ps-sort-select">
                        <option value="">Relevancia</option>
                        <option value="name_asc">Nombre A-Z</option>
                        <option value="name_desc">Nombre Z-A</option>
                        <option value="price_asc">Precio ↑</option>
                        <option value="price_desc">Precio ↓</option>
                        <option value="stock_desc">Stock disponible</option>
                    </select>
                    {{-- Deshabilitado: ni el bridge ni el fallback de BD devuelven
                         category_id en la búsqueda de productos (solo el nombre de
                         categoría como texto), así que el filtro JS por ID
                         (String(p.category_id) === catId) siempre comparaba contra
                         `undefined` y daba 0 resultados con cualquier categoría real
                         seleccionada — confirmado en QA. Arreglarlo de verdad
                         requiere tocar el módulo alsernetbridge (repo de PrestaShop,
                         fuera de este proyecto) para que categorice by id. --}}
                    <select id="prCategoryFilter" class="ps-sort-select" disabled
                            title="Filtro no disponible: el bridge no devuelve el ID de categoría por producto">
                        <option value="">Todas las categorías</option>
                    </select>
                </div>

                <div class="ps-sec-label">
                    <span id="prSectionTitle">Compras anteriores</span>
                    <span class="ct bv-hidden" id="prResultCount"></span>
                    <span class="ln"></span>
                </div>

                <div class="ps-prc-list ps-prc-list--full" id="prProductList">
                    <div class="bv-oc-loading">
                        <i class="fas fa-spinner fa-spin"></i> Cargando…
                    </div>
                </div>
            </div>

            {{-- ── DERECHA: detalle del producto seleccionado ───────────────── --}}
            <div class="ps-detail-pane">

                {{-- Estado vacío --}}
                <div class="ps-detail-empty" id="prDetailEmpty">
                    <i class="fas fa-hand-pointer"></i>
                    <div>Selecciona un producto de la lista</div>
                </div>

                {{-- Detalle del producto --}}
                <div class="bv-hidden" id="prDetailZone">

                    {{-- Card principal --}}
                    <div class="ps-pc">
                        <div class="ps-pc-top">
                            <div class="ps-pc-thumb" id="prDThumb"></div>
                            <div class="ps-pc-info">
                                <div class="ps-pc-nm" id="prDName"></div>
                                <div class="ps-pc-cat" id="prDCat"></div>
                            </div>
                            <div class="ps-pc-price" id="prDPrice"></div>
                        </div>
                        <hr class="ps-pc-hr">
                        <div class="ps-pc-rows" id="prDRows"></div>
                    </div>

                    {{-- Atributos / combinaciones --}}
                    <div class="ps-attr-block bv-hidden" id="prAttrBlock">
                        <div class="ps-sec-label ps-sec-label--combos">
                            <span>Combinaciones</span>
                            <span class="ct bv-hidden" id="prComboCount"></span>
                            <span class="ln"></span>
                        </div>
                        <p class="ps-combos-desc">Selecciona una variante para ver su referencia y disponibilidad.</p>
                        <div id="prAttrGroups"></div>
                        <div class="ps-attr-sel bv-hidden" id="prAttrSel">
                            <span>Combinación:</span>
                            <span class="ref" id="prAttrSelRef"></span>
                            <span class="sp"></span>
                            <span class="ps-prc-stock" id="prAttrSelStock"></span>
                        </div>
                    </div>

                    {{-- Precios por volumen --}}
                    <div id="prVolBlock"></div>

                    {{-- Alternativas relacionadas --}}
                    <div class="ps-alt-block bv-hidden" id="prAltBlock">
                        <div class="ps-sec-label">
                            <span>Alternativas relacionadas</span>
                            <span class="ln"></span>
                        </div>
                        <div class="ps-alt-list" id="prAltList"></div>
                    </div>

                    {{-- Historial de recomendaciones previas --}}
                    <div class="ps-alt-block bv-hidden" id="prHistBlock">
                        <div class="ps-sec-label">
                            <span>Ya recomendados</span>
                            <span class="ln"></span>
                        </div>
                        <div class="ps-hist-list" id="prHistList"></div>
                    </div>

                </div>
            </div>

        </div>

        {{-- Footer sin selección --}}
        <div class="modal-foot" id="prFootDefault">
            <button class="btn btn-outline" data-bv-close type="button"><i class="fas fa-xmark"></i>Cerrar</button>
        </div>

        {{-- Footer con producto seleccionado --}}
        <div class="modal-foot bv-hidden" id="prFootSelected">
            <textarea class="ps-note-input" id="prInternalNote" rows="1" placeholder="Nota interna (opcional)…"></textarea>
            <div class="ps-footer-actions">
                <button class="btn btn-primary" id="prSendToChat" type="button"><i class="fas fa-paper-plane"></i>Recomendar en chat</button>
                <button class="btn btn-outline" id="prSendEmail" type="button"><i class="fas fa-envelope"></i>Email</button>
            </div>
            <div class="ps-qty-wrap">
                <button class="ps-qty-btn" id="prQtyMinus" type="button" disabled><i class="fas fa-minus"></i></button>
                <input class="ps-qty-input" id="prQtyInput" type="number" value="1" min="1" max="999" readonly disabled>
                <button class="ps-qty-btn" id="prQtyPlus" type="button" disabled><i class="fas fa-plus"></i></button>
                <button class="btn btn-outline" id="prAddToCart" type="button" disabled
                        title="El carrito asistido no está disponible en este entorno todavía">
                    <i class="fas fa-cart-plus"></i>Añadir al carrito
                </button>
                <span class="ps-cart-tip">No disponible en este entorno</span>
            </div>
        </div>

    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a fichero propio: se cachea en el navegador en vez de
         re-descargarse en cada render del inbox. Fuente en
         modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar.

         product-recommend.min.js (npm run build:module-assets) es OPCIONAL,
         mismo criterio "cae si está desactualizado" que tickets-app.js (ver
         scripts/build-module-assets.mjs): se sirve solo si existe Y es más
         reciente que su fuente. --}}
    @php
        $productRecommendSrcMtime = @filemtime(base_path('modules/HelpdeskPrestashop/public/js/product-recommend.js'));
        $productRecommendMinMtime = @filemtime(base_path('modules/HelpdeskPrestashop/public/js/product-recommend.min.js'));
        $useProductRecommendMin = $productRecommendMinMtime !== false && $productRecommendSrcMtime !== false && $productRecommendMinMtime >= $productRecommendSrcMtime;
    @endphp
    @if ($useProductRecommendMin)
    <script src="{{ asset('modules/helpdeskprestashop/js/product-recommend.min.js') }}?v={{ $productRecommendMinMtime }}" defer></script>
    @else
    <script src="{{ asset('modules/helpdeskprestashop/js/product-recommend.js') }}?v={{ $productRecommendSrcMtime }}" defer></script>
    @endif
@endpush
@endonce
