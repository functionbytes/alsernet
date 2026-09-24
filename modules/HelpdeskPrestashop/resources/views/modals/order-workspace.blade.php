{{-- Workspace de pedido PrestaShop (bv-modal) — datos reales vía el bridge.
     Diseño fiel al mockup Alvarez "pedido-workspace": columna principal con el
     contenido del pedido + panel lateral con pestañas Estado/Envío/Cliente/Pago/
     Historial y las acciones de cambio de estado y asignación de seguimiento. --}}
<div class="bv-modal" data-bv-modal-name="ps-order-workspace">
    <div class="bv-modal-dialog xxl bv-po-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-bag-shopping"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label"><i class="fas fa-store"></i> PrestaShop</span>
                <div class="bv-modal-title"><span id="powTitle">Pedido</span></div>
            </div>
            <span class="bv-po-status" id="powStatus"></span>
            <button type="button" class="btn-secondary btn-sm bv-po-doc-btn bv-hidden" id="powInvoiceBtn">Factura</button>
            <button type="button" class="btn-secondary btn-sm bv-po-doc-btn bv-hidden" id="powSlipBtn">Albarán</button>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body bv-po-body">
            {{-- Estado de carga / error --}}
            <div class="bv-po-loading" id="powLoading"><i class="fas fa-spinner fa-spin"></i> Cargando pedido…</div>
            <div class="bv-po-error bv-hidden" id="powError"><i class="fas fa-triangle-exclamation"></i> <span></span></div>

            <div class="bv-po-grid bv-hidden" id="powGrid">
                {{-- ── Columna principal: contenido del pedido ── --}}
                <div class="bv-po-main">
                    <div class="bv-po-card">
                        <div class="bv-po-card-h">
                            <div class="bv-po-card-ht">
                                <span class="t">Contenido del pedido</span>
                                <span class="s" id="powSummary"></span>
                            </div>
                            <button type="button" class="btn-secondary btn-sm bv-hidden" id="powInsertCard">Insertar en el chat</button>
                            <a class="bv-po-store-link bv-hidden" id="powStoreLink" href="#" target="_blank" rel="noopener" title="Ver en la tienda"><i class="fas fa-store"></i></a>
                        </div>
                        <div id="powLines"></div>
                        <div class="bv-po-totals" id="powTotals"></div>
                    </div>
                </div>

                {{-- ── Panel lateral: pestañas ── --}}
                <div class="bv-po-side" id="powSide">
                    <div class="bv-po-tabs" id="powTabs">
                        <button type="button" class="bv-po-tab" data-po-tab="cliente" title="Cliente"><i class="far fa-address-card"></i><span class="bv-po-tab-lbl">Cliente</span></button>
                        <button type="button" class="bv-po-tab on" data-po-tab="estado" title="Estado"><i class="fas fa-circle-info"></i><span class="bv-po-tab-lbl">Estado</span></button>
                        <button type="button" class="bv-po-tab" data-po-tab="envio" title="Envío"><i class="fas fa-truck"></i><span class="bv-po-tab-lbl">Envío</span></button>
                        <button type="button" class="bv-po-tab" data-po-tab="pago" title="Pago"><i class="fas fa-credit-card"></i><span class="bv-po-tab-lbl">Pago</span></button>
                        <button type="button" class="bv-po-tab" data-po-tab="correos" title="Correos"><i class="fas fa-paper-plane"></i><span class="bv-po-tab-lbl">Correos</span></button>
                        <button type="button" class="bv-po-tab" data-po-tab="notas" title="Notas"><i class="fas fa-pen-to-square"></i><span class="bv-po-tab-lbl">Notas</span></button>
                        <button type="button" class="bv-po-tab" data-po-tab="historial" title="Historial"><i class="fas fa-clock-rotate-left"></i><span class="bv-po-tab-lbl">Historial</span></button>
                    </div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="cliente" id="powPanelCliente"></div>
                    <div class="bv-po-panel" data-po-panel="estado" id="powPanelEstado"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="envio" id="powPanelEnvio"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="pago" id="powPanelPago"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="correos" id="powPanelCorreos"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="notas" id="powPanelNotas"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="historial" id="powPanelHistorial"></div>
                </div>
            </div>

            {{-- ── Hoja "Iniciar devolución" a pantalla completa (pestaña Pago → Devolución) ── --}}
            <div class="ps-sheet bv-hidden" id="psRetSheet">
                <div class="ps-sheet-head">
                    <span class="ic"><i class="fas fa-rotate-left"></i></span>
                    <span>
                        <span class="lbl">PrestaShop · <span id="psRetRef"></span></span>
                        <span class="ttl">{{ __('helpdeskprestashop::chat.returns.start') }}</span>
                    </span>
                    <button type="button" class="bv-modal-close" id="psRetClose"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="ps-sheet-body">
                    <div class="psc-note psc-note--warn bv-hidden" id="psRetError"><span class="psc-note-txt"></span></div>
                    <p class="psc-sheet-intro">{{ __('helpdeskprestashop::chat.returns.sheet_intro') }}</p>
                    <div class="ps-sec-label">
                        <span>{{ __('helpdeskprestashop::chat.returns.lines') }}</span>
                        <span class="ln"></span>
                        <label class="ps-stock-label">
                            <input type="checkbox" id="psRetAll">
                            <span>{{ __('helpdeskprestashop::chat.returns.select_all') }}</span>
                        </label>
                    </div>
                    <div id="psRetLines"></div>

                    {{-- StartOrderReturnRequest (backend real) solo acepta items[].order_detail_id
                         y items[].quantity — no hay campo de motivo/detalle en PrestaShop para este
                         endpoint. Se capturan igualmente y, si el agente escribe algo, se guardan
                         como nota interna de la conversación al enviar (mismo patrón que
                         product-recommend.js con #prInternalNote), en vez de omitir el campo o
                         fingir que llega a PrestaShop. --}}
                    <div class="psc-field">
                        <span class="lbl">{{ __('helpdeskprestashop::chat.returns.reason') }}</span>
                        <select id="psRetReason" class="psc-select2">
                            <option value=""></option>
                            <option value="defectuoso">Producto defectuoso</option>
                            <option value="talla">Talla o modelo incorrecto</option>
                            <option value="no_corresponde">No corresponde a la descripción</option>
                            <option value="danado">Llegó dañado</option>
                            <option value="duplicado">Pedido duplicado</option>
                            <option value="cambio_opinion">Cambio de opinión</option>
                            <option value="otro">Otro motivo</option>
                        </select>
                    </div>
                    <div class="psc-field">
                        <span class="lbl">{{ __('helpdeskprestashop::chat.returns.detail') }}</span>
                        <textarea id="psRetDetail" rows="2"></textarea>
                    </div>

                    <div class="ps-ret-summary">
                        <span class="c" id="psRetCount"></span>
                        <span class="a" id="psRetAmount"></span>
                    </div>
                </div>
                <div class="ps-sheet-foot">
                    <button type="button" class="psc-btn psc-btn--primary is-disabled" id="psRetSubmit" disabled>
                        {{ __('helpdeskprestashop::chat.returns.submit') }}
                    </button>
                    <button type="button" class="psc-btn psc-btn--outline" id="psRetCancel">
                        {{ __('helpdeskprestashop::chat.actions.cancel') }}
                    </button>
                </div>
            </div>

            {{-- ── Hoja "Cambiar dirección" a pantalla completa (pestaña Cliente → Editar) ── --}}
            <div class="ps-sheet bv-hidden" id="psAddrSheet">
                <div class="ps-sheet-head">
                    <span class="ic"><i class="fas fa-location-dot"></i></span>
                    <span>
                        <span class="lbl">PrestaShop · <span id="psAddrRef"></span></span>
                        <span class="ttl">{{ __('helpdeskprestashop::chat.addresses.change') }}</span>
                    </span>
                    <button type="button" class="bv-modal-close" id="psAddrClose"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="ps-sheet-body">
                    <div id="psAddrCurrent"></div>
                    <div class="psc-note psc-note--lock bv-hidden" id="psAddrWarn"><span class="psc-note-txt"></span></div>

                    <div class="psc-seg" id="psAddrTypeSeg">
                        <button type="button" class="is-on" data-addr-type="delivery">{{ __('helpdeskprestashop::chat.addresses.shipping') }}</button>
                        <button type="button" data-addr-type="invoice">{{ __('helpdeskprestashop::chat.addresses.billing') }}</button>
                    </div>

                    <div class="search-field">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" class="finput" id="psAddrSearch" placeholder="{{ __('helpdeskprestashop::chat.addresses.search') }}" autocomplete="off">
                    </div>

                    <div id="psAddrList"></div>

                    <button type="button" class="psc-btn psc-btn--dashed" id="psAddrNewToggle">
                        {{ __('helpdeskprestashop::chat.addresses.new') }}
                    </button>

                    {{-- Sub-panel "Crear dirección nueva" (pieza 27) — POST real a
                         /ps/addresses. País = países activos de PS (ps/ext/address/countries);
                         provincia = country.states del país elegido, se repuebla al
                         cambiarlo. DNI y "por defecto" solo aparecen si el país lo exige /
                         la tienda tiene la marca de dirección por defecto. --}}
                    <div class="bv-hidden" id="psAddrNewForm">
                        <div class="psc-fieldrow">
                            <div class="psc-field">
                                <span class="lbl">Nombre</span>
                                <input type="text" class="finput" id="psAddrNewFirstname" maxlength="64">
                            </div>
                            <div class="psc-field">
                                <span class="lbl">Apellidos</span>
                                <input type="text" class="finput" id="psAddrNewLastname" maxlength="64">
                            </div>
                        </div>
                        <div class="psc-fieldrow">
                            <div class="psc-field">
                                <span class="lbl">Alias (opcional)</span>
                                <input type="text" class="finput" id="psAddrNewAlias" maxlength="32">
                            </div>
                            <div class="psc-field">
                                <span class="lbl">Empresa (opcional)</span>
                                <input type="text" class="finput" id="psAddrNewCompany" maxlength="64">
                            </div>
                        </div>
                        <div class="psc-field">
                            <span class="lbl">Dirección</span>
                            <input type="text" class="finput" id="psAddrNewAddress1" maxlength="128">
                        </div>
                        <div class="psc-field">
                            <span class="lbl">Dirección · línea 2 (opcional)</span>
                            <input type="text" class="finput" id="psAddrNewAddress2" maxlength="128">
                        </div>
                        <div class="psc-fieldrow">
                            <div class="psc-field">
                                <span class="lbl">Código postal</span>
                                <input type="text" class="finput" id="psAddrNewPostcode" maxlength="12">
                            </div>
                            <div class="psc-field">
                                <span class="lbl">Ciudad</span>
                                <input type="text" class="finput" id="psAddrNewCity" maxlength="64">
                            </div>
                        </div>
                        <div class="psc-fieldrow">
                            <div class="psc-field">
                                <span class="lbl">País</span>
                                <select id="psAddrNewCountry" class="psc-select2"></select>
                            </div>
                            <div class="psc-field bv-hidden" id="psAddrNewStateWrap">
                                <span class="lbl">Provincia / estado</span>
                                <select id="psAddrNewState" class="psc-select2"></select>
                            </div>
                        </div>
                        <span class="psc-address-hint" id="psAddrNewCountryHint">Las provincias se cargan del país elegido.</span>
                        <div class="psc-field bv-hidden" id="psAddrNewDniWrap">
                            <span class="lbl">Documento de identidad</span>
                            <input type="text" class="finput mono" id="psAddrNewDni" maxlength="16">
                            <span class="hint">Este país lo exige para enviar.</span>
                        </div>
                        <div class="psc-fieldrow">
                            <div class="psc-field">
                                <span class="lbl">Teléfono (opcional)</span>
                                <input type="text" class="finput" id="psAddrNewPhone" maxlength="32">
                            </div>
                            <div class="psc-field">
                                <span class="lbl">Móvil (opcional)</span>
                                <input type="text" class="finput" id="psAddrNewPhoneMobile" maxlength="32">
                            </div>
                        </div>
                        <label class="psc-check bv-hidden" id="psAddrNewDefaultWrap">
                            <input type="checkbox" id="psAddrNewDefault">
                            <span>Usar como dirección de envío por defecto</span>
                        </label>
                        <div class="psc-address-actions">
                            <button type="button" class="psc-btn psc-btn--primary" id="psAddrNewSave">
                                {{ __('helpdeskprestashop::chat.addresses.save') }}
                            </button>
                            <button type="button" class="psc-btn psc-btn--outline" id="psAddrNewCancel">
                                {{ __('helpdeskprestashop::chat.actions.cancel') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="ps-sheet-foot">
                    <button type="button" class="psc-btn psc-btn--primary is-disabled" id="psAddrSave" disabled>
                        Aplicar al pedido
                    </button>
                    <button type="button" class="psc-btn psc-btn--outline" id="psAddrCancel">
                        {{ __('helpdeskprestashop::chat.actions.cancel') }}
                    </button>
                </div>
            </div>

            {{-- ── Hoja "Anular pedido" (pieza 07): cambio real al estado de
                 cancelación de PrestaShop + motivo como nota interna del
                 pedido. Confirmación escribiendo el nº de pedido. ── --}}
            <div class="ps-sheet bv-hidden" id="psCancelSheet">
                <div class="ps-sheet-head">
                    <span class="ic"><i class="fas fa-ban"></i></span>
                    <span>
                        <span class="lbl">PrestaShop · <span id="psCancelRef"></span></span>
                        <span class="ttl">Anular pedido</span>
                    </span>
                    <button type="button" class="bv-modal-close" id="psCancelClose"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="ps-sheet-body">
                    <div class="psc-note psc-note--lock">
                        <span class="psc-note-txt">Acción no reversible. El pedido pasa a <b id="psCancelTarget"></b> y se libera el stock reservado.</span>
                    </div>
                    <div class="psc-field">
                        <span class="lbl">Motivo</span>
                        <select id="psCancelReason">
                            <option value="">Selecciona un motivo</option>
                            <option value="no_lo_quiere">El cliente ya no lo quiere</option>
                            <option value="error">Error en el pedido</option>
                            <option value="sin_stock">Sin stock</option>
                            <option value="fraude">Sospecha de fraude</option>
                        </select>
                    </div>
                    <div class="psc-note psc-note--warn bv-hidden" id="psCancelPaid"><span class="psc-note-txt"></span></div>
                    <label class="psc-check">
                        <input type="checkbox" id="psCancelNotify" checked>
                        <span>Avisar al cliente por email</span>
                    </label>
                    <div class="psc-field">
                        <span class="lbl">Escribe el nº de pedido para confirmar</span>
                        <input type="text" class="finput mono" id="psCancelConfirm" autocomplete="off">
                    </div>
                </div>
                <div class="ps-sheet-foot">
                    <button type="button" class="psc-btn psc-btn--danger is-disabled" id="psCancelSubmit" disabled>Anular pedido</button>
                    <button type="button" class="psc-btn psc-btn--outline" id="psCancelCancel">Volver al pedido</button>
                </div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn-secondary w-100" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a fichero propio: se cachea en el navegador en vez de
         re-descargarse en cada render del inbox. Fuente en
         modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar.

         order-workspace.min.js (npm run build:module-assets) es OPCIONAL,
         mismo criterio "cae si está desactualizado" que tickets-app.js (ver
         scripts/build-module-assets.mjs): se sirve solo si existe Y es más
         reciente que su fuente. --}}
    @php
        $orderWorkspaceSrcMtime = @filemtime(base_path('modules/HelpdeskPrestashop/public/js/order-workspace.js'));
        $orderWorkspaceMinMtime = @filemtime(base_path('modules/HelpdeskPrestashop/public/js/order-workspace.min.js'));
        $useOrderWorkspaceMin = $orderWorkspaceMinMtime !== false && $orderWorkspaceSrcMtime !== false && $orderWorkspaceMinMtime >= $orderWorkspaceSrcMtime;
    @endphp
    @if ($useOrderWorkspaceMin)
    <script src="{{ asset('modules/helpdeskprestashop/js/order-workspace.min.js') }}?v={{ $orderWorkspaceMinMtime }}" defer></script>
    @else
    <script src="{{ asset('modules/helpdeskprestashop/js/order-workspace.js') }}?v={{ $orderWorkspaceSrcMtime }}" defer></script>
    @endif
@endpush
@endonce
