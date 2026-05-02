{{-- Modal: Adjuntar ubicación --}}
<div class="bv-modal" data-bv-modal-name="attach-location">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-location-dot bv-modal-title-icon bv-modal-title-icon--danger"></i> Compartir ubicación</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="alert info">
                <i class="fas fa-circle-info lead"></i>
                <div>La ubicación se enviará como mapa estático con coordenadas. El cliente podrá abrirla en su app de mapas.</div>
            </div>

            {{-- Tipo de ubicación --}}
            <div class="bv-seg" id="attach-location-type">
                <button class="active" data-loc-type="current">
                    <i class="fas fa-location-crosshairs"></i> Mi ubicación
                </button>
                <button data-loc-type="search">
                    <i class="fas fa-magnifying-glass"></i> Buscar dirección
                </button>
                <button data-loc-type="saved">
                    <i class="far fa-bookmark"></i> Guardadas
                </button>
            </div>

            {{-- Search address (oculto por defecto) --}}
            <div class="bv-modal-search bv-hidden bv-mt-12" id="attach-location-search-wrap">
                <i class="fas fa-magnifying-glass"></i>
                <input id="attach-location-search" type="text" placeholder="Calle, ciudad, código postal…" autocomplete="off">
            </div>

            {{-- Saved addresses --}}
            <div class="bv-opt-list bv-mt-12" id="attach-location-saved">
                <button class="bv-opt on" data-loc-id="hq">
                    <i class="fas fa-building bv-icon-channel-email"></i>
                    <div class="body">
                        <div class="name">Oficina central</div>
                        <div class="sub">Calle Mayor 123, 28001 Madrid</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </button>
                <button class="bv-opt" data-loc-id="warehouse">
                    <i class="fas fa-warehouse bv-icon-channel-email"></i>
                    <div class="body">
                        <div class="name">Almacén</div>
                        <div class="sub">Polígono Industrial Norte, 28850 Torrejón</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </button>
                <button class="bv-opt" data-loc-id="store">
                    <i class="fas fa-store bv-icon-channel-email"></i>
                    <div class="body">
                        <div class="name">Tienda física</div>
                        <div class="sub">Centro Comercial Plaza Norte 2, Madrid</div>
                    </div>
                    <span class="bv-modal-radio-dot"></span>
                </button>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="attach-location-send">Enviar ubicación</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="attach-location"] .bv-seg button', function () {
    $('[data-bv-modal-name="attach-location"] .bv-seg button').removeClass('active');
    $(this).addClass('active');
    var type = $(this).data('loc-type');
    $('#attach-location-search-wrap').toggleClass('bv-hidden', type !== 'search');
    $('#attach-location-saved').toggleClass('bv-hidden', type !== 'saved');
});

$(document).on('click', '[data-bv-modal-name="attach-location"] .bv-opt', function () {
    $('[data-bv-modal-name="attach-location"] .bv-opt').removeClass('on');
    $(this).addClass('on');
});
</script>
@endpush
@endonce
