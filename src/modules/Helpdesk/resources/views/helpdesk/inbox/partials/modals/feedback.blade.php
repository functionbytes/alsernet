{{-- Modal: Reportar bug o sugerencia (#85 ve-feedback) --}}
<div class="bv-modal" data-bv-modal-name="feedback">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-bug"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">FEEDBACK</span>
                <div class="bv-modal-title">Cuéntanos qué pasó</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">Tipo</label>
                <div class="bv-prio-bar" id="feedbackTypeBar">
                    <button type="button" class="bv-prio-card on" data-fb-type="bug">
                        <div class="bv-prio-card__ic"><i class="fas fa-bug"></i></div>
                        <span class="bv-prio-card__lbl">Bug</span>
                    </button>
                    <button type="button" class="bv-prio-card" data-fb-type="idea">
                        <div class="bv-prio-card__ic"><i class="fas fa-lightbulb"></i></div>
                        <span class="bv-prio-card__lbl">Idea</span>
                    </button>
                    <button type="button" class="bv-prio-card" data-fb-type="praise">
                        <div class="bv-prio-card__ic"><i class="far fa-thumbs-up"></i></div>
                        <span class="bv-prio-card__lbl">Elogio</span>
                    </button>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Descríbelo <span class="bv-req">*</span></label>
                <textarea id="feedbackDescription" class="bv-form-input" rows="4" placeholder="¿Qué esperabas que pasara? ¿Qué pasó realmente?"></textarea>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Adjuntar</label>
                <div class="bv-dropzone-mini" id="feedbackDropzone">
                    <i class="far fa-image"></i> Pegar o subir captura de pantalla
                    <input type="file" id="feedbackFile" accept="image/*" style="display:none">
                </div>
                <div id="feedbackFilePreview" style="display:none;font-size:11.5px;margin-top:5px">
                    <i class="fas fa-image me-1"></i><span id="feedbackFileName"></span>
                    <button type="button" id="feedbackFileRemove" style="margin-left:8px;border:none;background:none;cursor:pointer;font-size:10px;color:var(--text-muted)"><i class="fas fa-xmark"></i></button>
                </div>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="feedbackIncludeTechInfo" checked>
                Incluir información técnica de mi sesión
            </label>
            <label class="bv-check">
                <input type="checkbox" id="feedbackContactMe">
                Contactarme cuando se resuelva
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-feedback-submit">Enviar feedback</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _fbType = 'bug';
    var _fbFile = null;

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'feedback') { return; }
        _fbType = 'bug';
        _fbFile = null;
        $('#feedbackDescription').val('').focus();
        $('#feedbackIncludeTechInfo').prop('checked', true);
        $('#feedbackContactMe').prop('checked', false);
        $('#feedbackTypeBar .bv-prio-card').removeClass('on');
        $('#feedbackTypeBar .bv-prio-card[data-fb-type="bug"]').addClass('on');
        $('#feedbackFile').val('');
        $('#feedbackFilePreview').hide();
    });

    $(document).on('click', '#feedbackTypeBar .bv-prio-card', function () {
        $('#feedbackTypeBar .bv-prio-card').removeClass('on');
        $(this).addClass('on');
        _fbType = $(this).data('fb-type');
    });

    $(document).on('click', '#feedbackDropzone', function () { $('#feedbackFile').trigger('click'); });

    $(document).on('change', '#feedbackFile', function () {
        _fbFile = this.files[0] || null;
        if (_fbFile) {
            $('#feedbackFileName').text(_fbFile.name);
            $('#feedbackFilePreview').show();
        }
    });

    $(document).on('click', '#feedbackFileRemove', function () {
        _fbFile = null;
        $('#feedbackFile').val('');
        $('#feedbackFilePreview').hide();
    });

    $(document).on('click', '#bv-feedback-submit', function () {
        var desc = $('#feedbackDescription').val().trim();
        if (!desc) {
            if (window.toastr) { toastr.warning('La descripción es obligatoria'); }
            $('#feedbackDescription').focus();
            return;
        }

        var fd = new FormData();
        fd.append('type',           _fbType);
        fd.append('description',    desc);
        fd.append('include_tech',   $('#feedbackIncludeTechInfo').is(':checked') ? '1' : '0');
        fd.append('contact_me',     $('#feedbackContactMe').is(':checked') ? '1' : '0');
        if (_fbFile) { fd.append('screenshot', _fbFile); }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando…');

        $.ajax({
            url: '/panel/helpdesk/feedback',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('feedback');
            if (window.toastr) { toastr.success('Gracias por tu feedback'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al enviar feedback';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Enviar feedback');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce
