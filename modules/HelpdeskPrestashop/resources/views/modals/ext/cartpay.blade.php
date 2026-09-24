{{-- Extensión "cartpay".
     · Pieza 02 (ps-order-payment): hoja "Cobro pendiente" dentro del
       workspace de pedido. cartpay.js la mueve una vez a .bv-po-body y añade
       la tarjeta de acceso en la pestaña Pago solo si el pedido tiene importe
       pendiente. No hay enlace de pago: ningún módulo de la tienda lo ofrece
       para un pedido ya creado; se envían los datos de transferencia reales.
     · Pieza 32 (ps-cart-convert): bloque "Convertir o vaciar" que cartpay.js
       añade al cuerpo del modal de carrito (#psCartModal) cada vez que se
       repinta. Incluye la casilla "Enviar el correo de confirmación" cuando
       el puente tiene el hook actionEmailSendBefore (1.2.5); si no, un aviso.
       Estos flags solo deciden qué se ofrece: el controlador vuelve a exigir
       cada permiso. --}}
@php
    $psCartpayUser = auth()->user();
    $psCartpayCanPay = (bool) $psCartpayUser?->can('helpdeskprestashop.orders.view');
    $psCartpayCanConvert = (bool) $psCartpayUser?->can('helpdeskprestashop.cartpay.convert');
    $psCartpayCanEmpty = (bool) $psCartpayUser?->can('helpdeskprestashop.cartpay.empty');
@endphp
@if ($psCartpayCanPay || $psCartpayCanConvert || $psCartpayCanEmpty)
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/cartpay.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/cartpay.css')) }}">

<div class="bv-hidden" id="psCartpayCfg"
     data-can-pay="{{ $psCartpayCanPay ? 1 : 0 }}"
     data-can-convert="{{ $psCartpayCanConvert ? 1 : 0 }}"
     data-can-empty="{{ $psCartpayCanEmpty ? 1 : 0 }}"></div>

@if ($psCartpayCanPay)
<div class="ps-sheet bv-hidden psc-cartpay-sheet" id="psCartpayPaySheet">
    <div class="ps-sheet-head">
        <span class="ic"><i class="fas fa-building-columns"></i></span>
        <span>
            <span class="lbl">Pedido · Cobro</span>
            <span class="ttl">Datos de pago <span class="psc-cartpay-ref" id="psCartpayPayRef"></span></span>
        </span>
        <button type="button" class="bv-modal-close" id="psCartpayPayClose" aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
    </div>

    <div class="ps-sheet-body">
        <div class="psc-cartpay-amount">
            <span class="k">Importe pendiente</span>
            <span class="v" id="psCartpayPayAmount"></span>
            <span class="s" id="psCartpayPayState"></span>
        </div>

        <div class="psc-preview">
            <div class="psc-preview-hd">Se insertará este texto</div>
            <div class="psc-preview-body psc-cartpay-pre" id="psCartpayPayText"></div>
        </div>

        <div class="psc-note psc-note--info">
            <span class="psc-note-txt">La tienda no ofrece enlace de pago para un pedido ya creado: el cliente paga por transferencia con estos datos y la referencia del pedido como concepto.</span>
        </div>
        <div class="psc-note psc-note--lock">
            <span class="psc-note-txt">La factura fiscal la emite Gestión (ERP); la tienda tiene la facturación desactivada, así que no se puede adjuntar desde aquí.</span>
        </div>
    </div>

    <div class="ps-sheet-foot">
        <button type="button" class="psc-btn psc-btn--primary" id="psCartpayPayInsert">Insertar en el chat</button>
        <button type="button" class="psc-btn psc-btn--outline" id="psCartpayPayCancel">Cancelar</button>
    </div>
</div>
@endif

@once
    @push('scripts')
        <script src="{{ asset('modules/helpdeskprestashop/js/ext/cartpay.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/cartpay.js')) }}" defer></script>
    @endpush
@endonce
@endif
