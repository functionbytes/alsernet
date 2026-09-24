{{-- Estilo "Qué hacer ahora": acciones sugeridas como tarjetas, conversación
     en curso y compromisos abiertos; el resto de la ficha debajo. --}}
<div class="ctf-shell c3l-shell">

    @include('contacts::contacts.show._hero')
    @include('contacts::contacts.show._metrics')

    <div class="c3l-band">
        @include('contacts::contacts.show._sec-attn', ['attnTitle' => 'Qué hacer ahora'])
    </div>

    <div class="c3l-cols c3l-cols--acciones">
        <section class="ctf-sec c3l-card" id="c3l-live">
            <div class="ctf-section-title">
                <span class="t">Conversación en curso</span>
                <a class="ctf-link-btn ms-auto d-none" id="c3l-live-link" href="#">Abrir en el inbox</a>
            </div>
            <div id="c3l-live-body"><div class="ctf-skel-line mb-2"></div><div class="ctf-skel-line"></div></div>
        </section>
        <section class="ctf-sec c3l-card" id="c3l-commit">
            <div class="ctf-section-title">
                <span class="t">Compromisos abiertos</span>
                <span class="n" id="c3l-commit-count">…</span>
            </div>
            <div id="c3l-commit-body"><div class="ctf-skel-line mb-2"></div><div class="ctf-skel-line"></div></div>
        </section>
    </div>

    <div class="ctf-body">
        <div class="ctf-col-left">
            @include('contacts::contacts.show._sec-hist')
        </div>
        <div class="ctf-col-right">
            @include('contacts::contacts.show._sec-buy')
            @include('contacts::contacts.show._sec-account')
            @include('contacts::contacts.show._sec-sources')
            @include('contacts::contacts.show._sec-notes')
        </div>
    </div>

    @include('contacts::contacts.show._detail')

</div>
