{{-- Estilo "Clásica": la ficha B tal y como estaba. --}}
<div class="ctf-shell">

    @include('contacts::contacts.show._hero')
    @include('contacts::contacts.show._metrics')

    <div class="ctf-body">
        <div class="ctf-col-left">
            @include('contacts::contacts.show._sec-attn')
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
