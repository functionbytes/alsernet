{{-- Extensión "address" — pieza 05 (hoja ps-ship-claim: incidencia de envío).
     La hoja se define aquí y address.js la mueve dentro del cuerpo del
     workspace de pedido la primera vez (nunca un modal encima de otro).
     "Abrir reclamación" = nota interna del pedido en PrestaShop (no hay API
     de transportista); "Guardar como nota interna" = nota de la conversación.
     La pieza 27 (país/provincia en "Crear dirección nueva") vive en
     order-workspace.blade.php/js; aquí solo se carga su CSS. --}}
@php
    $psAddressCanClaim = (bool) auth()->user()?->can('helpdeskprestashop.orders.ship_claim');
    $psAddressClaimTypes = (array) config('helpdeskprestashop.ext.address.ship_claim.types', []);
    $psAddressCss = 'modules/helpdeskprestashop/css/ext/address.css';
    $psAddressJs = 'modules/helpdeskprestashop/js/ext/address.js';
@endphp
<link rel="stylesheet" href="{{ asset($psAddressCss) }}?v={{ @filemtime(public_path($psAddressCss)) }}">

@if ($psAddressCanClaim)
<div class="ps-sheet bv-hidden" id="psAddressClaimSheet"
     data-url-template="{{ url('panel/helpdesk/customers/__CUSTOMER__/ps/ext/address/orders/__ORDER__/ship-claim') }}"
     data-max-attachments="{{ (int) config('helpdeskprestashop.ext.address.ship_claim.max_attachments', 10) }}">
    <div class="ps-sheet-head">
        <span class="ic"><i class="fas fa-truck-ramp-box"></i></span>
        <span>
            <span class="lbl">Pedido · Incidencia · <span id="psAddressClaimRef"></span></span>
            <span class="ttl">Incidencia de envío</span>
        </span>
        <button type="button" class="bv-modal-close" id="psAddressClaimClose" aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="ps-sheet-body">
        <div class="psc-seg" id="psAddressClaimType">
            @foreach ($psAddressClaimTypes as $key => $label)
                <button type="button" data-claim-type="{{ $key }}" class="{{ $loop->first ? 'is-on' : '' }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="psc-address-ship" id="psAddressClaimShip"></div>

        <div class="psc-field">
            <span class="lbl">Fotos del cliente</span>
            <div class="psc-address-files" id="psAddressClaimFiles"></div>
        </div>

        <div class="psc-field">
            <span class="lbl">Detalle para la reclamación</span>
            <textarea id="psAddressClaimDetail" rows="3" maxlength="1200"></textarea>
        </div>

        <div class="psc-note psc-note--info">
            <span class="psc-note-txt">Sin conexión con el transportista: «Abrir reclamación» deja la incidencia como nota interna del pedido en PrestaShop para que logística la tramite.</span>
        </div>
    </div>
    <div class="ps-sheet-foot">
        <button type="button" class="psc-btn psc-btn--primary" id="psAddressClaimSubmit">Abrir reclamación</button>
        <button type="button" class="psc-btn psc-btn--outline" id="psAddressClaimNote">Guardar como nota interna</button>
        <button type="button" class="psc-btn psc-btn--outline" id="psAddressClaimCancel">Cancelar</button>
    </div>
</div>

@once
@push('scripts')
    <script src="{{ asset($psAddressJs) }}?v={{ @filemtime(public_path($psAddressJs)) }}" defer></script>
@endpush
@endonce
@endif
