{{-- Extensión "refunds": reembolso parcial (pieza 08), resolver devolución
     (pieza 35) e instrucciones de retorno (pieza 36).

     - #psRefundsSheet y #psRefundsRmaSheet son hojas internas del workspace
       de pedido: refunds.js las mueve una vez dentro de su .bv-po-body (nunca
       un modal encima de otro).
     - ps-refunds-rma es el mini-modal que se abre desde las tarjetas RMA del
       tab Devoluciones del panel derecho, donde no hay ningún modal abierto.
     Los permisos y límites solo deciden qué se ofrece: los controladores los
     vuelven a comprobar. --}}
@php
    $psRefundsUser = auth()->user();
    $psRefundsLimit = $psRefundsUser?->can('helpdeskprestashop.refunds.approve')
        ? (float) config('helpdeskprestashop.ext.refunds.approver_limit', 500)
        : ($psRefundsUser?->can('helpdeskprestashop.refunds.issue') ? (float) config('helpdeskprestashop.ext.refunds.agent_limit', 50) : 0);
    $psRefundsInstructions = (array) config('helpdeskprestashop.ext.refunds.return_instructions', []);
    $psRefundsAddress = array_values(array_filter(array_map('trim', explode('|', (string) ($psRefundsInstructions['address'] ?? ''))), 'strlen'));
@endphp
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/refunds.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/refunds.css')) }}">

<div class="bv-hidden" id="psRefundsCfg"
     data-limit="{{ $psRefundsLimit }}"
     data-can-resolve="{{ $psRefundsUser?->can('helpdeskprestashop.returns.resolve') ? 1 : 0 }}"
     data-states="{{ json_encode((array) config('helpdeskprestashop.ext.refunds.rma_states', [])) }}"
     data-carrier="{{ $psRefundsInstructions['carrier'] ?? '' }}"
     data-address="{{ json_encode($psRefundsAddress) }}"
     data-validity="{{ (int) ($psRefundsInstructions['validity_days'] ?? 14) }}"
     data-steps="{{ json_encode(array_values((array) ($psRefundsInstructions['steps'] ?? []))) }}"></div>

{{-- ── Hoja "Reembolso parcial" (pieza 08) ── --}}
<div class="ps-sheet bv-hidden" id="psRefundsSheet">
    <div class="ps-sheet-head">
        <span class="ic"><i class="fas fa-receipt"></i></span>
        <span>
            <span class="lbl">Pedido · Reembolso · <span id="psRefundsRef"></span></span>
            <span class="ttl">Reembolso parcial</span>
        </span>
        <button type="button" class="bv-modal-close" id="psRefundsClose" aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="ps-sheet-body">
        <div class="psc-note psc-note--warn bv-hidden" id="psRefundsError"><span class="psc-note-txt"></span></div>
        <div class="psc-loading bv-hidden" id="psRefundsLoading">Consultando el pedido en PrestaShop…</div>

        <div class="bv-hidden" id="psRefundsForm">
            <div class="psc-note psc-note--lock bv-hidden" id="psRefundsUnpaid">
                <span class="psc-note-txt">Este pedido no consta como pagado en PrestaShop: no se puede emitir un reembolso.</span>
            </div>

            <div class="ps-sec-label"><span>Líneas del pedido</span><span class="ln"></span></div>
            <div class="psc-refunds-lines" id="psRefundsLines"></div>

            <label class="psc-check bv-hidden" id="psRefundsShippingWrap">
                <input type="checkbox" id="psRefundsShipping">
                <span id="psRefundsShippingLbl">Devolver también los gastos de envío</span>
            </label>
            <label class="psc-check bv-hidden" id="psRefundsRestockWrap">
                <input type="checkbox" id="psRefundsRestock">
                <span>Reponer el stock de las unidades reembolsadas</span>
            </label>

            <div class="psc-field">
                <span class="lbl">Destino del reembolso</span>
                <div class="psc-seg" id="psRefundsDest">
                    <button type="button" class="is-on" data-dest="payment">Al método de pago</button>
                    <button type="button" data-dest="voucher">Como vale de la tienda</button>
                </div>
                <span class="hint" id="psRefundsDestHint"></span>
            </div>

            <div class="psc-row psc-row--total">
                <span class="k">Total a reembolsar</span>
                <span class="v" id="psRefundsTotal">0,00 €</span>
            </div>
            <div class="psc-row">
                <span class="k">Tu límite por reembolso</span>
                <span class="v mono" id="psRefundsLimit"></span>
            </div>
            <div class="psc-row bv-hidden" id="psRefundsAlreadyRow">
                <span class="k">Ya reembolsado en este pedido</span>
                <span class="v mono" id="psRefundsAlready"></span>
            </div>

            <div class="psc-note psc-note--info">
                <span class="psc-note-txt">Genera el albarán de reembolso que luego aparece en la tarjeta de Reembolsos del tab Pedidos. PrestaShop se lo notifica al cliente por email.</span>
            </div>
        </div>
    </div>
    <div class="ps-sheet-foot">
        <button type="button" class="psc-btn psc-btn--primary is-disabled" id="psRefundsSubmit" disabled>Emitir reembolso</button>
        <button type="button" class="psc-btn psc-btn--outline bv-hidden" id="psRefundsApproval">Pedir aprobación</button>
        <button type="button" class="psc-btn psc-btn--outline" id="psRefundsCancel">Cancelar</button>
    </div>
</div>

{{-- ── Resolver devolución (pieza 35) como hoja del workspace de pedido ── --}}
<div class="ps-sheet bv-hidden psc-refunds-rma" id="psRefundsRmaSheet">
    <div class="ps-sheet-head">
        <span class="ic"><i class="fas fa-rotate-left"></i></span>
        <span>
            <span class="lbl">Devolución · RMA</span>
            <span class="ttl">Resolver <span class="psc-refunds-chip" data-rma-chip></span></span>
        </span>
        <button type="button" class="bv-modal-close" data-rma-cancel aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="ps-sheet-body" data-rma-slot></div>
    <div class="ps-sheet-foot">
        <button type="button" class="psc-btn psc-btn--primary is-disabled" data-rma-apply disabled>Aprobar devolución</button>
        <button type="button" class="psc-btn psc-btn--danger bv-hidden" data-rma-deny>Denegar con motivo</button>
        <button type="button" class="psc-btn psc-btn--outline" data-rma-cancel>Cancelar</button>
    </div>
</div>

{{-- ── Resolver devolución (pieza 35) como mini-modal del panel derecho ── --}}
<div class="bv-modal" data-bv-modal-name="ps-refunds-rma">
    <div class="modal w-sm psc-refunds-rma">
        <div class="modal-head">
            <div class="modal-icon"><i class="fas fa-rotate-left"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">Devolución · RMA</div>
                <div class="modal-title">Resolver <span class="psc-refunds-chip" data-rma-chip></span></div>
            </div>
            <button class="modal-close" data-bv-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body psc-refunds-rma-body" data-rma-slot></div>
        <div class="modal-foot">
            <button type="button" class="psc-btn psc-btn--primary is-disabled" data-rma-apply disabled>Aprobar devolución</button>
            <button type="button" class="psc-btn psc-btn--danger bv-hidden" data-rma-deny>Denegar con motivo</button>
            <button type="button" class="psc-btn psc-btn--outline" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/refunds.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/refunds.js')) }}" defer></script>
@endpush
@endonce
