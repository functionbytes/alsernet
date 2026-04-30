{{-- Modal: Adjuntar contacto --}}
<div class="bv-modal" data-bv-modal-name="attach-contact">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-address-card bv-modal-title-icon"></i> Adjuntar contacto</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="alert info">
                <i class="fas fa-circle-info lead"></i>
                <div>El contacto se enviará al cliente como tarjeta vCard. Podrá guardarlo en su agenda con un toque.</div>
            </div>

            {{-- Search --}}
            <div class="bv-modal-search bv-mb-12">
                <i class="fas fa-magnifying-glass"></i>
                <input id="attach-contact-search" type="text" placeholder="Buscar contacto por nombre, email o teléfono…" autocomplete="off">
            </div>

            {{-- Contact list --}}
            <div class="bv-right-section-title bv-mb-8">Contactos recientes</div>
            <div class="bv-opt-list" id="attach-contact-list">
                <button class="bv-opt" data-contact-id="1">
                    <div class="bv-av c1">JR</div>
                    <div class="body">
                        <div class="name">Juan Ruiz</div>
                        <div class="sub">juan@empresa.com · +34 600 123 456</div>
                    </div>
                    <i class="fas fa-paperclip bv-icon-sm"></i>
                </button>
                <button class="bv-opt" data-contact-id="2">
                    <div class="bv-av c2">ML</div>
                    <div class="body">
                        <div class="name">María López</div>
                        <div class="sub">maria@empresa.com · +34 666 987 654</div>
                    </div>
                    <i class="fas fa-paperclip bv-icon-sm"></i>
                </button>
                <button class="bv-opt" data-contact-id="3">
                    <div class="bv-av c3">CR</div>
                    <div class="body">
                        <div class="name">Carlos Ramírez</div>
                        <div class="sub">carlos@empresa.com · +34 612 345 678</div>
                    </div>
                    <i class="fas fa-paperclip bv-icon-sm"></i>
                </button>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="attach-contact-send" disabled>
                <i class="fas fa-paper-plane"></i> Enviar contacto
            </button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="attach-contact"] .bv-opt', function () {
    $('[data-bv-modal-name="attach-contact"] .bv-opt').removeClass('on');
    $(this).addClass('on');
    $('#attach-contact-send').prop('disabled', false);
});
</script>
@endpush
@endonce
