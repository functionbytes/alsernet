{{-- Modal: Traducción de conversación (#46 ve-translate) --}}
<div class="bv-modal" data-bv-modal-name="translate">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-language"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">COMPOSER · TRADUCCIÓN</span>
                <div class="bv-modal-title">Traducir mensaje</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">Texto a traducir</label>
                <textarea id="translateInput" class="bv-form-input" rows="3" placeholder="Escribe o pega el texto a traducir…" style="resize:vertical"></textarea>
            </div>

            <div class="bv-frow" style="display:flex;gap:10px">
                <div class="bv-form-field" style="flex:1">
                    <label class="bv-form-label">Idioma origen</label>
                    <select id="translateFrom" class="bv-form-input">
                        <option value="auto">Detectar automáticamente</option>
                        <option value="es">Español</option>
                        <option value="en">Inglés</option>
                        <option value="fr">Francés</option>
                        <option value="de">Alemán</option>
                        <option value="it">Italiano</option>
                        <option value="pt">Portugués</option>
                        <option value="nl">Neerlandés</option>
                        <option value="ru">Ruso</option>
                        <option value="zh">Chino</option>
                        <option value="ar">Árabe</option>
                        <option value="ja">Japonés</option>
                    </select>
                </div>
                <div style="display:flex;align-items:flex-end;padding-bottom:4px">
                    <button type="button" id="translateSwapLangs" class="btn-secondary" style="padding:5px 8px" title="Intercambiar idiomas">
                        <i class="fas fa-right-left"></i>
                    </button>
                </div>
                <div class="bv-form-field" style="flex:1">
                    <label class="bv-form-label">Idioma destino</label>
                    <select id="translateTo" class="bv-form-input">
                        <option value="es" selected>Español</option>
                        <option value="en">Inglés</option>
                        <option value="fr">Francés</option>
                        <option value="de">Alemán</option>
                        <option value="it">Italiano</option>
                        <option value="pt">Portugués</option>
                        <option value="nl">Neerlandés</option>
                        <option value="ru">Ruso</option>
                        <option value="zh">Chino</option>
                        <option value="ar">Árabe</option>
                        <option value="ja">Japonés</option>
                    </select>
                </div>
            </div>

            <div id="translateResult" style="display:none;margin-top:10px;padding:10px 12px;background:var(--bv-bg-subtle,#f9fafb);border-radius:6px">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--bv-text-muted,#6b7280);margin-bottom:5px">Traducción</div>
                <div id="translateResultText" style="font-size:13px;color:var(--bv-text,#111827);white-space:pre-wrap"></div>
                <div style="display:flex;gap:6px;margin-top:8px">
                    <button type="button" id="bv-translate-copy" class="btn-secondary" style="font-size:11px;padding:3px 8px">
                        <i class="far fa-copy me-1"></i> Copiar
                    </button>
                    <button type="button" id="bv-translate-insert" class="btn-secondary" style="font-size:11px;padding:3px 8px">
                        <i class="fas fa-arrow-down-to-line me-1"></i> Insertar en compositor
                    </button>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-translate-go">Traducir</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'translate') { return; }
        // Pre-fill with selected text if any
        var sel = window.getSelection ? window.getSelection().toString().trim() : '';
        if (sel) { $('#translateInput').val(sel); }
        $('#translateResult').hide();
        $('#translateInput').focus();
    });

    $(document).on('click', '#translateSwapLangs', function () {
        var from = $('#translateFrom').val();
        var to   = $('#translateTo').val();
        if (from !== 'auto') {
            $('#translateFrom').val(to);
            $('#translateTo').val(from);
        }
    });

    $(document).on('click', '#bv-translate-go', function () {
        var text = $('#translateInput').val().trim();
        if (!text) {
            if (window.toastr) { toastr.warning('Escribe el texto a traducir'); }
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Traduciendo…');
        $('#translateResult').hide();

        $.ajax({
            url: '/panel/helpdesk/translate',
            method: 'POST',
            data: { text: text, from: $('#translateFrom').val(), to: $('#translateTo').val() },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            if (resp.success && resp.translated) {
                $('#translateResultText').text(resp.translated);
                $('#translateResult').show();
            } else {
                if (window.toastr) { toastr.error(resp.message || 'Sin resultado de traducción'); }
            }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al traducir';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Traducir');
        });
    });

    $(document).on('click', '#bv-translate-copy', function () {
        var text = $('#translateResultText').text();
        if (!text) { return; }
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () {
                if (window.toastr) { toastr.success('Copiado al portapapeles'); }
            });
        } else {
            var $tmp = $('<textarea>').val(text).appendTo('body').select();
            document.execCommand('copy');
            $tmp.remove();
            if (window.toastr) { toastr.success('Copiado al portapapeles'); }
        }
    });

    $(document).on('click', '#bv-translate-insert', function () {
        var text = $('#translateResultText').text();
        if (!text) { return; }
        var $ta = $('.bv-composer textarea, .bv-composer [contenteditable]').first();
        if ($ta.is('[contenteditable]')) {
            $ta.focus();
            document.execCommand('insertText', false, text);
        } else {
            var cur = $ta.val();
            $ta.val(cur ? cur + '\n' + text : text);
            $ta.trigger('input');
        }
        closeBvModal('translate');
        if (window.toastr) { toastr.success('Traducción insertada'); }
    });

}(window.jQuery));
</script>
@endpush
@endonce
