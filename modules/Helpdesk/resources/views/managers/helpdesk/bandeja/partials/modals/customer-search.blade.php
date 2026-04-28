{{-- Modal: Buscar cliente --}}
<div class="bv-modal" data-bv-modal-name="search-customer">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Buscar cliente</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="mv4-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="csSearch" placeholder="Nombre, email, teléfono o ciudad…" autocomplete="off">
                <button class="x" id="csClear" style="display:none"><i class="fas fa-xmark"></i></button>
            </div>

            <div class="mv4-search-meta">
                <span id="csCount">6 resultados</span>
                <span style="flex:1"></span>
                <button class="mv4-pill-btn on" data-cs-filter="all">Todos</button>
                <button class="mv4-pill-btn" data-cs-filter="vip">VIP</button>
                <button class="mv4-pill-btn" data-cs-filter="b2b">B2B</button>
                <button class="mv4-pill-btn" data-cs-filter="recent">Recientes</button>
            </div>

            <div class="mv4-search-list" id="csResults">
                <div class="mv4-search-row" data-cs-id="1" data-cs-tag="vip" data-cs-city="madrid">
                    <div class="av c1">JG</div>
                    <div class="body">
                        <div class="row"><b>Julia García</b><span class="tag tag-vip">VIP</span></div>
                        <div class="meta">julia.g@gmail.com · +34 612 345 678 · Madrid</div>
                    </div>
                    <div class="stats">
                        <div><b>14</b><span>pedidos</span></div>
                        <div><b>€1.2k</b><span>LTV</span></div>
                    </div>
                    <i class="fas fa-chevron-right chev"></i>
                </div>
                <div class="mv4-search-row" data-cs-id="2" data-cs-tag="b2b" data-cs-city="barcelona">
                    <div class="av c2">MD</div>
                    <div class="body">
                        <div class="row"><b>Marcos Díaz</b><span class="tag tag-b2b">B2B</span></div>
                        <div class="meta">marcos@acme.com · +34 622 110 220 · Barcelona</div>
                    </div>
                    <div class="stats">
                        <div><b>48</b><span>pedidos</span></div>
                        <div><b>€8.4k</b><span>LTV</span></div>
                    </div>
                    <i class="fas fa-chevron-right chev"></i>
                </div>
                <div class="mv4-search-row" data-cs-id="3" data-cs-tag="nuevo" data-cs-city="sevilla">
                    <div class="av c3">SP</div>
                    <div class="body">
                        <div class="row"><b>Sandra Peña</b><span class="tag tag-nuevo">Nuevo</span></div>
                        <div class="meta">sandra.p@hotmail.es · +34 699 112 233 · Sevilla</div>
                    </div>
                    <div class="stats">
                        <div><b>1</b><span>pedidos</span></div>
                        <div><b>€42</b><span>LTV</span></div>
                    </div>
                    <i class="fas fa-chevron-right chev"></i>
                </div>
                <div class="mv4-search-row" data-cs-id="4" data-cs-tag="recurrente" data-cs-city="valencia">
                    <div class="av c4">PH</div>
                    <div class="body">
                        <div class="row"><b>Pablo Hernández</b><span class="tag tag-recurrente">Recurrente</span></div>
                        <div class="meta">pablo.h@yahoo.es · +34 633 998 877 · Valencia</div>
                    </div>
                    <div class="stats">
                        <div><b>6</b><span>pedidos</span></div>
                        <div><b>€340</b><span>LTV</span></div>
                    </div>
                    <i class="fas fa-chevron-right chev"></i>
                </div>
                <div class="mv4-search-row" data-cs-id="5" data-cs-tag="vip" data-cs-city="bilbao">
                    <div class="av c5">LR</div>
                    <div class="body">
                        <div class="row"><b>Lucía Romero</b><span class="tag tag-vip">VIP</span></div>
                        <div class="meta">lucia.r@gmail.com · +34 644 552 211 · Bilbao</div>
                    </div>
                    <div class="stats">
                        <div><b>22</b><span>pedidos</span></div>
                        <div><b>€2.1k</b><span>LTV</span></div>
                    </div>
                    <i class="fas fa-chevron-right chev"></i>
                </div>
                <div class="mv4-search-row" data-cs-id="6" data-cs-tag="b2b" data-cs-city="malaga">
                    <div class="av c6">DN</div>
                    <div class="body">
                        <div class="row"><b>David Núñez</b><span class="tag tag-b2b">B2B</span></div>
                        <div class="meta">david@startup.io · +34 655 443 322 · Málaga</div>
                    </div>
                    <div class="stats">
                        <div><b>18</b><span>pedidos</span></div>
                        <div><b>€3.6k</b><span>LTV</span></div>
                    </div>
                    <i class="fas fa-chevron-right chev"></i>
                </div>

                <div class="mv4-empty" id="csEmpty" style="display:none">
                    <i class="far fa-circle-question"></i>
                    <p>Sin resultados</p>
                    <button class="btn-primary" style="margin-top:8px">
                        <i class="fas fa-user-plus"></i> Crear cliente nuevo
                    </button>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary" data-bv-close>Cancelar</button>
            <div style="margin-left:auto;display:flex;gap:8px">
                <button class="btn-secondary">
                    <i class="fas fa-user-plus"></i> Crear nuevo cliente
                </button>
                <button class="btn-primary" id="csBtnOpen" disabled>
                    <i class="fas fa-arrow-right"></i> Abrir conversación
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var selectedId = null;
    var activeFilter = 'all';

    function filterRows() {
        var q = $('#csSearch').val().toLowerCase();
        var visible = 0;

        $('.mv4-search-row').each(function() {
            var name = $(this).find('b').first().text().toLowerCase();
            var meta = $(this).find('.meta').text().toLowerCase();
            var tag = $(this).data('cs-tag');

            var matchQ = !q || name.includes(q) || meta.includes(q);
            var matchF = activeFilter === 'all'
                || (activeFilter === 'vip' && tag === 'vip')
                || (activeFilter === 'b2b' && tag === 'b2b')
                || (activeFilter === 'recent' && (tag === 'nuevo' || tag === 'recurrente'));

            $(this).toggle(matchQ && matchF);
            if (matchQ && matchF) { visible++; }
        });

        $('#csCount').text(visible + ' resultado' + (visible !== 1 ? 's' : ''));
        $('#csEmpty').toggle(visible === 0);
    }

    $(document).on('input', '#csSearch', function() {
        var q = $(this).val();
        $('#csClear').toggle(q.length > 0);
        filterRows();
    });

    $(document).on('click', '#csClear', function() {
        $('#csSearch').val('');
        $(this).hide();
        filterRows();
    });

    $(document).on('click', '[data-cs-filter]', function() {
        activeFilter = $(this).data('cs-filter');
        $('[data-cs-filter]').removeClass('on');
        $(this).addClass('on');
        filterRows();
    });

    $(document).on('click', '.mv4-search-row', function() {
        selectedId = $(this).data('cs-id');
        $('.mv4-search-row').removeClass('on');
        $(this).addClass('on');
        $('#csBtnOpen').prop('disabled', false);
    });

    $(document).on('bv:modal:open', function(e, name) {
        if (name !== 'search-customer') return;
        selectedId = null;
        activeFilter = 'all';
        $('#csSearch').val('');
        $('#csClear').hide();
        $('#csBtnOpen').prop('disabled', true);
        $('.mv4-search-row').removeClass('on').show();
        $('[data-cs-filter]').removeClass('on');
        $('[data-cs-filter="all"]').addClass('on');
        $('#csEmpty').hide();
        $('#csCount').text('6 resultados');
    });
}());
</script>
