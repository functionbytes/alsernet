{{-- Modal: Recomendar producto de PrestaShop — Diseño C · dos columnas --}}
<div class="bv-modal" data-bv-modal-name="ps-product-recommend">
    <div class="bv-modal-dialog xl">

        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-gift"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · PRODUCTOS</span>
                <div class="bv-modal-title"><span>Recomendar producto</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body ps-modal-body">

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
                    <select id="prCategoryFilter" class="ps-sort-select">
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
        <div class="bv-modal-foot" id="prFootDefault">
            <button class="btn-secondary" data-bv-close><i class="fas fa-xmark"></i>Cerrar</button>
        </div>

        {{-- Footer con producto seleccionado --}}
        <div class="bv-modal-foot bv-hidden" id="prFootSelected">
            <div class="ps-note-wrap" id="prNoteWrap">
                <textarea class="ps-note-input" id="prInternalNote" rows="2" placeholder="Nota interna (opcional)…"></textarea>
            </div>
            <div class="ps-footer-actions">
                <button class="btn-secondary" id="prSendToChat" type="button"><i class="fas fa-paper-plane"></i>Recomendar en chat</button>
                <button class="ps-email-btn" id="prSendEmail" type="button"><i class="fas fa-envelope"></i> Email</button>
            </div>
            <div class="ps-qty-wrap">
                <button class="ps-qty-btn" id="prQtyMinus" type="button"><i class="fas fa-minus"></i></button>
                <input class="ps-qty-input" id="prQtyInput" type="number" value="1" min="1" max="999" readonly>
                <button class="ps-qty-btn" id="prQtyPlus" type="button"><i class="fas fa-plus"></i></button>
                <button class="btn-primary" id="prAddToCart" type="button"><i class="fas fa-cart-plus"></i>Añadir al carrito</button>
            </div>
            <button class="btn-secondary" data-bv-close type="button"><i class="fas fa-xmark"></i>Cerrar</button>
        </div>

    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a fichero propio: se cachea en el navegador en vez de
         re-descargarse en cada render del inbox. Fuente en
         modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskprestashop/js/product-recommend.js') }}?v={{ @filemtime(base_path('modules/HelpdeskPrestashop/public/js/product-recommend.js')) }}" defer></script>
@endpush
@endonce
