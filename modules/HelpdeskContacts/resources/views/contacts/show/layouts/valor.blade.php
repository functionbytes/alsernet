{{-- Estilo "Valor del cliente": indicadores, gasto por mes, canales y
     productos; los pedidos sustituyen al bloque "Compras". --}}
<div class="ctf-shell c3l-shell">

    @include('contacts::contacts.show._hero')

    <div class="c3l-kpis" id="c3l-kpis">
        @for($i = 0; $i < 5; $i++)
            <div class="c3l-kpi"><div class="ctf-skel-line"></div></div>
        @endfor
    </div>

    <div class="c3l-cols c3l-cols--valor">
        <section class="ctf-sec c3l-card c3l-span-2">
            <div class="ctf-section-title">
                <span class="t">Gasto por mes</span>
                <span class="c3l-sub" id="c3l-spend-range"></span>
            </div>
            <div id="c3l-spend"><div class="ctf-skel-line"></div></div>
            <div class="ctf-section-title c3l-mt">
                <span class="t">Por dónde nos escribe</span>
                <span class="c3l-sub ms-auto" id="c3l-channels-total"></span>
            </div>
            <div id="c3l-channels"><div class="ctf-skel-line"></div></div>
        </section>
        <section class="ctf-sec c3l-card">
            <div class="ctf-section-title"><span class="t">Lo que más compra</span></div>
            <div id="c3l-products"><div class="ctf-skel-line mb-2"></div><div class="ctf-skel-line"></div></div>
        </section>
        <section class="ctf-sec c3l-card c3l-span-3">
            <div class="ctf-section-title">
                <span class="t">Últimos pedidos</span>
                <span class="ms-auto" id="c3l-orders-more"></span>
            </div>
            <div id="c3l-orders"><div class="ctf-skel-line"></div></div>
        </section>
    </div>

    <div class="ctf-body">
        <div class="ctf-col-left">
            @include('contacts::contacts.show._sec-attn')
            @include('contacts::contacts.show._sec-hist')
        </div>
        <div class="ctf-col-right">
            @include('contacts::contacts.show._sec-account')
            @include('contacts::contacts.show._sec-sources')
            @include('contacts::contacts.show._sec-notes')
        </div>
    </div>

    @include('contacts::contacts.show._detail')

</div>
