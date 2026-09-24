{{--
   Inbox slot del módulo HelpdeskErp: pestañas Gestión, Finanzas y
   Fidelización del panel derecho. Si el módulo se desactiva, desaparecen.
   Recibe: $rpCust

   Todo sale de UNA llamada diferida (ErpChat.overview(), que consulta el
   manager en paralelo). La pinta erp-inbox.js. Aquí no se consulta el ERP en
   servidor: Oracle puede tardar y bloquearía el render del panel.

   El marcado inicial es un esqueleto y NO un .bv-tab-empty: el core
   (syncRightTabVisibility) oculta el botón de una pestaña cuyo único hijo es
   .bv-tab-empty, y entonces no habría forma de abrirla.

   CSS (erp-panel.css) y JS (erp-inbox.js) se cargan desde los modales del
   módulo, que siempre están presentes cuando el módulo está activo. Nada de
   <script> ni <link> aquí: el panel se sustituye entero al cambiar de
   conversación.
--}}
@php
    $ercRelinkUrl = ($rpCust && \Illuminate\Support\Facades\Route::has('manager.helpdesk.erp.customers.relink'))
        ? route('manager.helpdesk.erp.customers.relink', ['customerId' => $rpCust->id])
        : '';
@endphp

{{-- Gestión: cliente, avisos, últimos pedidos y dirección de envío --}}
<div class="bv-right-tab-content bv-tab-hidden erc-panel" data-bv-tab-content="erp-orders" id="bv-erp-orders"
     data-erc-tab="orders"
     data-erp-customer-id="{{ $rpCust?->id }}"
     data-relink-url="{{ $ercRelinkUrl }}">
    <div class="erc-stack" data-erc-body>
        <div class="erc-card">
            <div class="erc-card-body">
                <div class="erc-cust">
                    <span class="erc-avatar">{{ $rpCust ? mb_strtoupper(mb_substr($rpCust->name ?: ($rpCust->email ?: '?'), 0, 2)) : '?' }}</span>
                    <span class="erc-cust-body">
                        <span class="nm">{{ $rpCust?->name ?: ($rpCust?->email ?? '—') }}</span>
                        <span class="s">Cliente en Gestión</span>
                    </span>
                </div>
                <span class="erc-skel erc-skel--line"></span>
                <span class="erc-skel erc-skel--line erc-skel--short"></span>
            </div>
        </div>
        <span class="erc-skel erc-skel--card"></span>
        <span class="erc-skel"></span>
    </div>
</div>

{{-- Finanzas: saldo, riesgo, deudas y accesos a documentos --}}
<div class="bv-right-tab-content bv-tab-hidden erc-panel" data-bv-tab-content="erp-finance" id="bv-erp-finance"
     data-erc-tab="finance">
    <div class="erc-stack" data-erc-body>
        <span class="erc-skel erc-skel--card"></span>
        <span class="erc-skel"></span>
    </div>
</div>

{{-- Fidelización: puntos, movimientos, vales y bonos --}}
<div class="bv-right-tab-content bv-tab-hidden erc-panel" data-bv-tab-content="erp-loyalty" id="bv-erp-loyalty"
     data-erc-tab="loyalty">
    <div class="erc-stack" data-erc-body>
        <span class="erc-skel erc-skel--card"></span>
        <span class="erc-skel"></span>
    </div>
</div>
