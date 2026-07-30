{{-- Modal: Estrategia de auto-asignación (#78 ve-auto-assign) --}}
<div class="bv-modal" data-bv-modal-name="auto-assign">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-shuffle"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close aria-label="{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_close_aria') }}"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="field">
                <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_field_enabled') }}</label>
                <select class="fselect" id="aaEnabled">
                    <option value="1">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_enabled_on') }}</option>
                    <option value="0">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_enabled_off') }}</option>
                </select>
            </div>

            <div class="field">
                <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_field_strategy') }} <span class="req">*</span></label>
                <div class="reason-list" id="aaStrategyList">
                    <button type="button" class="reason on" data-bv-value="round_robin">
                        <div class="ic"><i class="fas fa-arrows-rotate"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_strategy_round_robin_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_strategy_round_robin_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                    <button type="button" class="reason" data-bv-value="least_load">
                        <div class="ic"><i class="fas fa-scale-balanced"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_strategy_load_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_strategy_load_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                    <button type="button" class="reason" data-bv-value="skills">
                        <div class="ic"><i class="fas fa-wand-magic-sparkles"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_strategy_skills_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_strategy_skills_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                    <button type="button" class="reason" data-bv-value="manual">
                        <div class="ic"><i class="fas fa-hand"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_strategy_manual_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_strategy_manual_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                </div>
            </div>

            <div class="frow">
                <div class="field">
                    <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_field_retry') }}</label>
                    <select class="fselect" id="aaRetry">
                        <option value="off">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_retry_off') }}</option>
                        <option value="2">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_retry_2') }}</option>
                        <option value="5">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_retry_5') }}</option>
                        <option value="15">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_retry_15') }}</option>
                    </select>
                </div>
                <div class="field">
                    <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_field_fallback') }}</label>
                    <select class="fselect" id="aaFallback">
                        <option value="queue">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_fallback_queue') }}</option>
                        <option value="supervisor">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_fallback_supervisor') }}</option>
                    </select>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-auto-assign-save">{{ __('helpdesk::helpdesk.inbox.modals.auto_assign_save') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    function csrf() { return $('meta[name="csrf-token"]').attr('content'); }

    function fill(data) {
        var strategy = (data && data.strategy) || 'round_robin';
        $('#aaEnabled').val(data && data.enabled ? '1' : '0');
        $('#aaStrategyList .reason').removeClass('on');
        $('#aaStrategyList .reason[data-bv-value="' + strategy + '"]').addClass('on');
        $('#aaRetry').val((data && data.retry) || 'off');
        $('#aaFallback').val((data && data.fallback) || 'queue');
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'auto-assign') { return; }
        $.ajax({
            url: '/panel/helpdesk/auto-assignment', method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
        }).done(fill);
    });

    $(document).on('click', '#aaStrategyList .reason', function () {
        $('#aaStrategyList .reason').removeClass('on');
        $(this).addClass('on');
    });

    $(document).on('click', '#bv-auto-assign-save', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/auto-assignment', method: 'PUT',
            data: {
                enabled: $('#aaEnabled').val(),
                strategy: $('#aaStrategyList .reason.on').data('bv-value') || 'round_robin',
                retry: $('#aaRetry').val(),
                fallback: $('#aaFallback').val(),
            },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function () {
            $('[data-bv-modal-name="auto-assign"]').removeClass('on');
            if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
            if (window.toastr) { toastr.success('Estrategia de auto-asignación guardada'); }
        }).fail(function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar la estrategia';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

}(window.jQuery));
</script>
@endpush
@endonce
