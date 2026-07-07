{{-- Modal: Error de permiso de micrófono (#66 ve-mic-permission) --}}
<div class="bv-modal" data-bv-modal-name="mic-permission">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-microphone-slash"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-perm-hero">
                <div class="bv-perm-hero__ic"><i class="fas fa-microphone-slash"></i></div>
                <div class="bv-perm-hero__t">{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_title') }}</div>
            </div>

            <div class="bv-perm-step">
                <div class="bv-perm-step__h"><span class="bv-perm-step__num">1</span> {{ __('helpdesk::helpdesk.inbox.modals.mic_permission_step1_h') }}</div>
                <ul>
                    <li>{!! __('helpdesk::helpdesk.inbox.modals.mic_permission_step1_li1') !!}</li>
                    <li>{!! __('helpdesk::helpdesk.inbox.modals.mic_permission_step1_li2') !!}</li>
                    <li>{!! __('helpdesk::helpdesk.inbox.modals.mic_permission_step1_li3') !!}</li>
                    <li>{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_step1_li4') }}</li>
                </ul>
            </div>

            <div class="bv-perm-step">
                <div class="bv-perm-step__h"><span class="bv-perm-step__num">2</span> {{ __('helpdesk::helpdesk.inbox.modals.mic_permission_step2_h') }}</div>
                <p class="bv-perm-step__hint">{!! __('helpdesk::helpdesk.inbox.modals.mic_permission_step2_hint') !!}</p>
                <ul>
                    <li>{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_step2_li1') }} <i class="fas fa-lock bv-x36"></i> {{ __('helpdesk::helpdesk.inbox.modals.mic_permission_step2_li1b') }}</li>
                    <li>{!! __('helpdesk::helpdesk.inbox.modals.mic_permission_step2_li2') !!}</li>
                    <li>{!! __('helpdesk::helpdesk.inbox.modals.mic_permission_step2_li3') !!}</li>
                    <li>{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_step2_li4') }}</li>
                    <li>{!! __('helpdesk::helpdesk.inbox.modals.mic_permission_step2_li5') !!}</li>
                </ul>
            </div>

            <div class="bv-perm-step">
                <div class="bv-perm-step__h"><span class="bv-perm-step__num">3</span> {{ __('helpdesk::helpdesk.inbox.modals.mic_permission_step3_h') }}</div>
                <p class="bv-x37">{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_step3_paste') }}</p>
                <div class="bv-perm-url" id="bvMicPermUrl" data-url="chrome://settings/content/microphone">
                    <span>chrome://settings/content/microphone</span>
                    <button type="button" class="bv-perm-url__copy" title="{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_copy') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_copy') }}"><i class="far fa-copy"></i></button>
                </div>
                <ul>
                    <li>{!! __('helpdesk::helpdesk.inbox.modals.mic_permission_step3_li1') !!}</li>
                    <li>{!! __('helpdesk::helpdesk.inbox.modals.mic_permission_step3_li2') !!}</li>
                </ul>
            </div>

            <div class="bv-perm-diag" id="bvMicDiag" style="display:none">
                <div class="bv-perm-diag__h">{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_diagnostics') }}</div>
                <div class="bv-perm-diag__body"></div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-mic-reload">{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_reload') }}</button>
            <button class="btn-secondary" id="bv-mic-retry">{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_retry') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.mic_permission_exit') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    $(document).on('bv:modal:open', function (e, name, data) {
        if (name !== 'mic-permission') { return; }
        if (data && data.errorCode) {
            var html = '<div>Error: <code>' + $('<span>').text(data.errorCode).html() + '</code></div>';
            if (data.permission) { html += '<div>Permiso: <code>' + $('<span>').text(data.permission).html() + '</code></div>'; }
            $('#bvMicDiag .bv-perm-diag__body').html(html);
            $('#bvMicDiag').show();
        } else {
            $('#bvMicDiag').hide();
        }
    });

    $(document).on('click', '.bv-perm-url__copy', function () {
        var url = $(this).closest('.bv-perm-url').data('url') || $(this).closest('.bv-perm-url').find('span').text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                if (window.toastr) { toastr.success('URL copiada'); }
            });
        }
    });

    $(document).on('click', '#bv-mic-reload', function () {
        window.location.reload();
    });

    $(document).on('click', '#bv-mic-retry', function () {
        $('[data-bv-modal-name="mic-permission"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
        $(document).trigger('bv:mic:retry');
    });

}(window.jQuery));
</script>
@endpush
@endonce
