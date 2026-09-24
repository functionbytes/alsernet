{{-- Estilo "Línea de vida": identidad y salud | cronología única | tienda. --}}
<div class="ctf-shell c3l-shell">

    @include('contacts::contacts.show._hero')

    <div class="c3l-cols c3l-cols--linea">
        <aside class="c3l-col c3l-col-side">
            <div class="ctf-sec c3l-health" id="c3l-health">
                <div class="ctf-section-title"><span class="t">Salud del cliente</span></div>
                <div id="c3l-health-body"><div class="ctf-skel-line"></div></div>
            </div>
            @include('contacts::contacts.show._sec-notes')
            @include('contacts::contacts.show._sec-sources')
        </aside>

        <div class="c3l-col c3l-col-main">
            @include('contacts::contacts.show._sec-hist', ['histTitle' => 'Línea de vida'])
        </div>

        <aside class="c3l-col c3l-col-shop">
            @include('contacts::contacts.show._metrics')
            @include('contacts::contacts.show._sec-attn')
            @include('contacts::contacts.show._sec-buy')
            @include('contacts::contacts.show._sec-account')
        </aside>
    </div>

    @include('contacts::contacts.show._detail')

</div>
