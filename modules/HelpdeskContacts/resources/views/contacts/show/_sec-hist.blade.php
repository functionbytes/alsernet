<div class="ctf-sec ctf-sec-hist">
    <div class="ctf-section-title">
        <span class="t">{{ $histTitle ?? 'Historial' }}</span>
        <span class="ctf-filter-pills d-none" id="ctf-hist-filters">
            <button type="button" class="ctf-filter-pill is-active" data-ctf-hist-filter="all">Todo</button>
            <button type="button" class="ctf-filter-pill" data-ctf-hist-filter="comercio">Comercio</button>
            <button type="button" class="ctf-filter-pill" data-ctf-hist-filter="mensajes">Mensajes</button>
            <button type="button" class="ctf-filter-pill" data-ctf-hist-filter="ficha">Ficha</button>
        </span>
    </div>
    <div id="ctf-hist-body">
        @include('contacts::contacts.partials._skeleton', ['rows' => 4])
    </div>
</div>
