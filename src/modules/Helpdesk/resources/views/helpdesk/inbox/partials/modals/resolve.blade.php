{{-- Modal: Resolver conversación con calificación CSAT (#6 ve-resolve) --}}
<div class="bv-modal" data-bv-modal-name="resolve">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box bv-modal-icon-box--success"><i class="fas fa-check"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">BANDEJA · RESOLUCIÓN</span>
                <div class="bv-modal-title">Resolver conversación</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">Resumen de resolución <span class="bv-req">*</span></label>
                <textarea id="resolveNotes" class="bv-form-input" rows="3" placeholder="¿Cómo se resolvió el problema?"></textarea>
            </div>

            <div class="bv-frow">
                <div class="bv-form-field">
                    <label class="bv-form-label">Estado final</label>
                    <select id="resolveFinalStatus" class="bv-form-input">
                        <option value="resolved">Resuelto</option>
                        <option value="closed_no_solution">Cerrado sin solución</option>
                    </select>
                </div>
                <div class="bv-form-field">
                    <label class="bv-form-label">Categoría</label>
                    <select id="resolveCategory" class="bv-form-input">
                        <option value="solved">Solucionado</option>
                        <option value="cannot_reproduce">Sin reproducir</option>
                        <option value="duplicate">Duplicado</option>
                        <option value="not_applicable">No aplica</option>
                    </select>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Solicitar calificación al cliente <span class="bv-form-hint">CSAT</span></label>
                <div class="bv-rating-row" id="bvRatingRow">
                    <button type="button" class="bv-rating-btn" data-score="1" title="Muy malo">😤<span>Muy malo</span></button>
                    <button type="button" class="bv-rating-btn" data-score="2" title="Malo">😞<span>Malo</span></button>
                    <button type="button" class="bv-rating-btn" data-score="3" title="Regular">😐<span>Regular</span></button>
                    <button type="button" class="bv-rating-btn" data-score="4" title="Bueno">😊<span>Bueno</span></button>
                    <button type="button" class="bv-rating-btn on" data-score="5" title="Excelente">😄<span>Excelente</span></button>
                </div>
            </div>

            <div class="bv-minfo bv-minfo--success" id="resolveEmailHint" style="display:none">
                <i class="fas fa-paper-plane"></i>
                <div>Se enviará el resumen y la encuesta a <b id="resolveEmailAddr"></b>.</div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-resolve-with-csat">Resolver y enviar encuesta</button>
            <button class="btn-secondary" id="bv-resolve-without-csat">Resolver sin encuesta</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _resolveScore = 5;

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'resolve') { return; }
        $('#resolveNotes').val('').focus();
        _resolveScore = 5;
        $('#bvRatingRow .bv-rating-btn').removeClass('on');
        $('#bvRatingRow .bv-rating-btn[data-score="5"]').addClass('on');

        var email = $('[data-customer-email]').first().attr('data-customer-email') || '';
        if (email) {
            $('#resolveEmailAddr').text(email);
            $('#resolveEmailHint').show();
        } else {
            $('#resolveEmailHint').hide();
        }
    });

    $(document).on('click', '#bvRatingRow .bv-rating-btn', function () {
        $('#bvRatingRow .bv-rating-btn').removeClass('on');
        $(this).addClass('on');
        _resolveScore = parseInt($(this).data('score'), 10);
    });

    function doResolve(sendCsat) {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var notes = $('#resolveNotes').val().trim();
        var $btn = sendCsat ? $('#bv-resolve-with-csat') : $('#bv-resolve-without-csat');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Resolviendo…');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/resolve',
            method: 'POST',
            data: {
                notes:           notes,
                final_status:    $('#resolveFinalStatus').val(),
                category:        $('#resolveCategory').val(),
                send_csat:       sendCsat ? 1 : 0,
                csat_score:      sendCsat ? _resolveScore : null,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('resolve');
            if (window.toastr) { toastr.success('Conversación resuelta'); }
            $(document).trigger('bv:conversation:resolved', [convId]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al resolver la conversación';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    $(document).on('click', '#bv-resolve-with-csat', function () { doResolve(true); });
    $(document).on('click', '#bv-resolve-without-csat', function () { doResolve(false); });

}(window.jQuery));
</script>
@endpush
@endonce
