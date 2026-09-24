{{-- Estilo "Maestro-detalle": lista de contactos fija + la ficha clásica.
     J/K cambian de contacto, / busca, «.» abre las acciones. --}}
<div class="c3l-master">

    <aside class="c3l-rail" id="c3l-rail" aria-label="Contactos">
        <div class="c3l-rail-head">
            <div class="c3l-rail-title">
                <a href="{{ route('contacts.index') }}">Contactos</a>
                <span class="c3l-sub ms-auto" id="c3l-rail-count"></span>
            </div>
            <label class="visually-hidden" for="c3l-rail-search">Buscar contactos</label>
            <input type="search" id="c3l-rail-search" class="ct-finput" placeholder="Buscar por nombre, email o teléfono" autocomplete="off">
            <div class="c3l-rail-filters" role="group" aria-label="Filtro rápido">
                <button type="button" class="ctf-filter-pill is-active" data-c3l-rail-view="all">Todos</button>
                <button type="button" class="ctf-filter-pill" data-c3l-rail-view="open">Chat abierto</button>
                <button type="button" class="ctf-filter-pill" data-c3l-rail-view="vip">VIP</button>
                <button type="button" class="ctf-filter-pill" data-c3l-rail-view="risk">En riesgo</button>
            </div>
        </div>
        <div class="c3l-rail-list" id="c3l-rail-list">
            @include('contacts::contacts.partials._skeleton', ['rows' => 6])
        </div>
        <div class="c3l-rail-keys">
            <span><kbd>J</kbd> <kbd>K</kbd> moverse</span>
            <span><kbd>/</kbd> buscar</span>
            <span><kbd>.</kbd> acciones</span>
        </div>
    </aside>

    <div class="c3l-master-main">
        @include('contacts::contacts.show.layouts.clasica')
    </div>

</div>
