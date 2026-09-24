{{-- Extensión "orderedit" · pieza 11 (ps-order-reorder): hoja "Repetir pedido"
     dentro del workspace de pedido. orderedit.js la mueve una vez a
     .bv-po-body y añade la tarjeta de acceso en la pestaña Estado. Los
     permisos solo deciden qué se ofrece: el controlador los vuelve a exigir. --}}
@php
    $psOrdereditUser = auth()->user();
    $psOrdereditCanReorder = (bool) $psOrdereditUser?->can('helpdeskprestashop.orders.reorder');
@endphp
@if ($psOrdereditCanReorder)
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/orderedit.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/orderedit.css')) }}">

<div class="ps-sheet bv-hidden psc-orderedit-sheet" id="psOrdereditReorderSheet">
    <div class="ps-sheet-head">
        <span class="ic"><i class="fas fa-rotate-right"></i></span>
        <span>
            <span class="lbl">Pedido · Repetir</span>
            <span class="ttl">Repetir pedido <span class="psc-orderedit-ref" id="psOrdereditRef"></span></span>
        </span>
        <button type="button" class="bv-modal-close" id="psOrdereditClose" aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
    </div>

    <div class="ps-sheet-body">
        <div class="psc-loading" id="psOrdereditLoading">Calculando precios y stock actuales…</div>
        <div class="psc-note psc-note--warn bv-hidden" id="psOrdereditError"><span class="psc-note-txt"></span></div>

        {{-- Paso 1: selección de líneas --}}
        <div class="psc-orderedit-step bv-hidden" id="psOrdereditForm">
            <p class="psc-sheet-intro">Se crea un carrito nuevo del cliente en la tienda con las líneas marcadas, al precio de hoy. El pedido original no cambia.</p>
            <div class="psc-orderedit-lines" id="psOrdereditLines"></div>
            <div class="psc-note psc-note--info bv-hidden" id="psOrdereditPriceNote"><span class="psc-note-txt"></span></div>
            <div class="psc-note psc-note--lock bv-hidden" id="psOrdereditMirrorNote"><span class="psc-note-txt">El cliente tiene un carrito abierto en la tienda: la tienda sincroniza sus carritos, así que estas líneas también aparecerán en él y, si ya tenía alguno de estos productos, su cantidad pasará a ser la del carrito nuevo.</span></div>
            <div class="psc-note psc-note--lock bv-hidden" id="psOrdereditAddressNote"><span class="psc-note-txt">El cliente no tiene ninguna dirección activa: la elegirá al pagar.</span></div>
            <div class="psc-orderedit-total">
                <span class="k">Total del carrito nuevo</span>
                <span class="v" id="psOrdereditTotal"></span>
            </div>
            <p class="psc-orderedit-hint">Solo productos, sin gastos de envío ni descuentos: los calcula la tienda al pagar.</p>
        </div>

        {{-- Paso 2: resultado --}}
        <div class="psc-orderedit-step bv-hidden" id="psOrdereditDone">
            <div class="psc-note psc-note--good"><span class="psc-note-txt" id="psOrdereditDoneText"></span></div>
            <div class="psc-orderedit-lines" id="psOrdereditDoneLines"></div>
            <div class="psc-note psc-note--info bv-hidden" id="psOrdereditSkipped"><span class="psc-note-txt"></span></div>
            <div class="psc-note psc-note--lock bv-hidden" id="psOrdereditMailNote"><span class="psc-note-txt"></span></div>
        </div>
    </div>

    <div class="ps-sheet-foot">
        <div class="psc-orderedit-acts" id="psOrdereditFormActs">
            <button type="button" class="psc-btn psc-btn--primary is-disabled" id="psOrdereditCreate" disabled>Crear carrito</button>
            <button type="button" class="psc-btn psc-btn--outline is-disabled bv-hidden" id="psOrdereditCreateLink" disabled>Crear y enviar enlace al cliente</button>
            <button type="button" class="psc-btn psc-btn--outline" id="psOrdereditCancel">Cancelar</button>
        </div>
        <div class="psc-orderedit-acts bv-hidden" id="psOrdereditDoneActs">
            <button type="button" class="psc-btn psc-btn--primary bv-hidden" id="psOrdereditInsertLink">Avisar al cliente en el chat</button>
            <button type="button" class="psc-btn psc-btn--outline" id="psOrdereditBack">Volver al pedido</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/orderedit.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/orderedit.js')) }}" defer></script>
@endpush
@endonce
@endif
