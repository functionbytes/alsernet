{{-- Modal: Solicitar revisión de supervisor (#74 ve-supervisor-review) --}}
<div class="bv-modal" data-bv-modal-name="supervisor-review">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-user-shield"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_header_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-info-table" id="supRevInfoTable">
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_info_conversation') }}</span>
                    <span class="bv-info-table__v" id="supRevConvRef">—</span>
                </div>
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_info_role') }}</span>
                    <span class="bv-info-table__v" id="supRevUserRole">—</span>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_field_type') }} <span class="bv-req">*</span></label>
                <div class="bv-opt-list" id="supRevTypeList">
                    <button type="button" class="bv-opt on" data-rev-type="approve_response">
                        <div class="bv-opt__ic"><i class="far fa-circle-check"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_type_approve_response') }}</span>
                            <span class="bv-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_type_approve_response_desc') }}</span>
                        </div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-rev-type="approve_discount">
                        <div class="bv-opt__ic"><i class="fas fa-euro-sign"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_type_approve_discount') }}</span>
                            <span class="bv-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_type_approve_discount_desc') }}</span>
                        </div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-rev-type="reopen_case">
                        <div class="bv-opt__ic"><i class="fas fa-undo"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_type_reopen_case') }}</span>
                            <span class="bv-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_type_reopen_case_desc') }}</span>
                        </div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-rev-type="change_policy">
                        <div class="bv-opt__ic"><i class="fas fa-rotate-right"></i></div>
                        <div class="bv-opt__body">
                            <span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_type_change_policy') }}</span>
                            <span class="bv-opt__s">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_type_change_policy_desc') }}</span>
                        </div>
                        <div class="bv-opt__radio"></div>
                    </button>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_field_comment') }}</label>
                <textarea id="supRevComment" class="bv-form-input" rows="3" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_placeholder_comment') }}"></textarea>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sup-rev-submit">{{ __('helpdesk::helpdesk.inbox.modals.supervisor_review_btn_submit') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _supRevType = 'approve_response';

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'supervisor-review') { return; }
        _supRevType = 'approve_response';
        $('#supRevTypeList .bv-opt').removeClass('on');
        $('#supRevTypeList .bv-opt[data-rev-type="approve_response"]').addClass('on');
        $('#supRevComment').val('').focus();

        var convId = getConvId();
        $('#supRevConvRef').text(convId ? '#' + convId : '—');
        $('#supRevUserRole').text($('[data-user-role]').first().attr('data-user-role') || '—');
    });

    $(document).on('click', '#supRevTypeList .bv-opt', function () {
        $('#supRevTypeList .bv-opt').removeClass('on');
        $(this).addClass('on');
        _supRevType = $(this).data('rev-type');
    });

    $(document).on('click', '#bv-sup-rev-submit', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando…');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/supervisor-review',
            method: 'POST',
            data: {
                review_type: _supRevType,
                comment:     $('#supRevComment').val().trim(),
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('supervisor-review');
            if (window.toastr) { toastr.success('Solicitud enviada al supervisor'); }
            $(document).trigger('bv:supervisor:review:requested', [convId, _supRevType]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al enviar la solicitud';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Enviar solicitud');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce
