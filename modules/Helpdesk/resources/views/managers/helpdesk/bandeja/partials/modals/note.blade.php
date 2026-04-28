{{-- Modal: Notas internas --}}
<div class="bv-modal" data-bv-modal-name="note">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Notas internas</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Compose --}}
            <div class="mv4-note-compose">
                <div class="head">
                    <div class="av c1">ML</div>
                    <span>Como <b>María López</b></span>
                    <span style="flex:1"></span>
                    <label class="pin">
                        <input type="checkbox" id="notePin"> <i class="fas fa-thumbtack"></i> Fijar
                    </label>
                </div>
                <textarea id="noteBody" rows="3" placeholder="Escribe una nota interna… usa @ para mencionar al equipo"></textarea>
                <div class="tools">
                    <button class="tt" data-tt="Mencionar"><i class="fas fa-at"></i></button>
                    <button class="tt" data-tt="Etiqueta"><i class="fas fa-tag"></i></button>
                    <button class="tt" data-tt="Adjuntar"><i class="fas fa-paperclip"></i></button>
                    <button class="tt" data-tt="Enlace"><i class="fas fa-link"></i></button>
                    <span style="flex:1;text-align:right;font-size:11px;color:#78350f;opacity:.7">Solo visible para el equipo</span>
                </div>
            </div>

            {{-- Notas existentes --}}
            <div class="mv4-sec-title" style="margin-top:18px">Notas existentes (3)</div>
            <div class="mv4-notes-list">

                <div class="mv4-note pinned">
                    <span class="pin-flag"><i class="fas fa-thumbtack"></i></span>
                    <div class="av c1">ML</div>
                    <div class="body">
                        <div class="head"><b>María L.</b><span>hace 2h</span></div>
                        <div class="txt">Cliente VIP — aplicar descuentos proactivamente en tickets resueltos con &gt;24h SLA.</div>
                    </div>
                    <div class="actions">
                        <button class="tt" data-tt="Editar"><i class="far fa-pen-to-square"></i></button>
                        <button class="tt" data-tt="Eliminar"><i class="far fa-trash-can"></i></button>
                    </div>
                </div>

                <div class="mv4-note">
                    <div class="av c2">CR</div>
                    <div class="body">
                        <div class="head"><b>Carlos R.</b><span>ayer</span></div>
                        <div class="txt">Prefiere comunicación por WhatsApp. Llamar solo en casos urgentes.</div>
                    </div>
                    <div class="actions">
                        <button class="tt" data-tt="Fijar"><i class="fas fa-thumbtack"></i></button>
                        <button class="tt" data-tt="Editar"><i class="far fa-pen-to-square"></i></button>
                        <button class="tt" data-tt="Eliminar"><i class="far fa-trash-can"></i></button>
                    </div>
                </div>

                <div class="mv4-note">
                    <div class="av c3">AT</div>
                    <div class="body">
                        <div class="head"><b>Ana T.</b><span>12 abr</span></div>
                        <div class="txt">Compró el plan anual con 20% descuento. Renovación: 12 abr 2026.</div>
                    </div>
                    <div class="actions">
                        <button class="tt" data-tt="Fijar"><i class="fas fa-thumbtack"></i></button>
                        <button class="tt" data-tt="Editar"><i class="far fa-pen-to-square"></i></button>
                        <button class="tt" data-tt="Eliminar"><i class="far fa-trash-can"></i></button>
                    </div>
                </div>

            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary" data-bv-close>Cerrar</button>
            <div style="margin-left:auto">
                <button class="btn-primary" id="noteBtnSave">
                    <i class="fas fa-check"></i> Añadir nota
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    $(document).on('click', '#noteBtnSave', function() {
        var txt = $('#noteBody').val().trim();
        if (!txt) {
            toastr.warning('Escribe algo antes de guardar');
            return;
        }
        toastr.success('Nota guardada');
        $('#noteBody').val('');
        $('#notePin').prop('checked', false);
        window.BvModal && window.BvModal.close('note');
    });

    $(document).on('click', '.mv4-note .actions [data-tt="Eliminar"]', function() {
        $(this).closest('.mv4-note').fadeOut(200, function() { $(this).remove(); });
    });

    $(document).on('bv:modal:open', function(e, name) {
        if (name !== 'note') return;
        $('#noteBody').val('');
        $('#notePin').prop('checked', false);
    });
}());
</script>
