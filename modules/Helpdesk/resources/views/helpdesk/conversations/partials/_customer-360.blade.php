{{-- Customer 360 Panel — datos cross-platform de Engagement + ERP/PrestaShop --}}
<div id="customer360Panel"
     data-customer-id="{{ $conversation->customer->id }}"
     data-inbox-id="{{ $conversation->inbox_id }}">

    {{-- Engagement --}}
    <div class="rsp-section">
        <div class="lbl">
            <i class="fa-solid fa-gauge-high"></i> Engagement
            <i class="fa-solid fa-arrows-rotate add" role="button" id="customer360Refresh" title="Refrescar datos"></i>
        </div>
        <div id="c360-engagement" class="bv-c360-block">
            <div class="rsp-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>
        </div>
    </div>

    {{-- Top eventos --}}
    <div class="rsp-section">
        <div class="lbl"><i class="fa-solid fa-bolt"></i> Top eventos</div>
        <div id="c360-top-events" class="bv-c360-block">
            <div class="rsp-empty">Sin eventos registrados</div>
        </div>
    </div>

    {{-- Pedidos --}}
    <div class="rsp-section">
        <div class="lbl"><i class="fa-solid fa-bag-shopping"></i> Pedidos</div>
        <div id="c360-orders" class="bv-c360-block">
            <div class="rsp-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>
        </div>
    </div>

    {{-- Datos del cliente --}}
    <div class="rsp-section">
        <div class="lbl"><i class="fa-solid fa-database"></i> Datos del cliente</div>
        <div id="c360-data" class="bv-c360-block">
            <div class="rsp-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>
        </div>
    </div>

    {{-- Recomendaciones --}}
    <div class="rsp-section">
        <div class="lbl"><i class="fa-solid fa-location-dot"></i> Recomendaciones</div>
        <div id="c360-recommendations" class="bv-c360-block">
            <div class="rsp-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando…</div>
        </div>
    </div>

</div>

@push('scripts')
<script src="{{ asset('js/customer-360.js') }}?v={{ file_exists(public_path('js/customer-360.js')) ? filemtime(public_path('js/customer-360.js')) : 1 }}"></script>
@endpush
