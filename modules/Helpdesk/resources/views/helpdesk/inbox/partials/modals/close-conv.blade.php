{{-- Modal: Cerrar conversación --}}
<div class="bv-modal" data-bv-modal-name="close-conv">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-check"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_label') }}</div>
                <div class="modal-title">
                    {{ __('helpdesk::helpdesk.inbox.modals.close_conv_title') }}
                    @if(!empty($selectedConversation))<span class="chip">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <div class="field">
                <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_label') }} <span class="req">*</span></div>
                <div class="reason-list" id="close-reasons">
                    <div class="reason on" data-reason="resolved">
                        <input type="radio" name="close_reason" value="resolved" checked class="d-none">
                        <div class="ic"><i class="fa-solid fa-check-double"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_resolved') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_resolved_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </div>
                    <div class="reason" data-reason="duplicated">
                        <input type="radio" name="close_reason" value="duplicated" class="d-none">
                        <div class="ic"><i class="fa-solid fa-copy"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_duplicated') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_duplicated_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </div>
                    <div class="reason" data-reason="spam">
                        <input type="radio" name="close_reason" value="spam" class="d-none">
                        <div class="ic"><i class="fa-solid fa-ban"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_spam') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_spam_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </div>
                    <div class="reason" data-reason="unresponsive">
                        <input type="radio" name="close_reason" value="unresponsive" class="d-none">
                        <div class="ic"><i class="fa-regular fa-clock"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_unresponsive') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_unresponsive_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </div>
                    <div class="reason" data-reason="other">
                        <input type="radio" name="close_reason" value="other" class="d-none">
                        <div class="ic"><i class="fa-solid fa-ellipsis"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_reason_other') }}</span>
                            <input type="text" id="close-other-input"
                                   placeholder="{{ __('helpdesk::helpdesk.inbox.modals.close_conv_other_placeholder') }}"
                                   class="finput bv-close-other-input mt-1"
                                   style="display:none">
                        </div>
                        <div class="radio"></div>
                    </div>
                </div>
            </div>

            <div class="field">
                <label class="check">
                    <input type="checkbox" id="close-csat">
                    {{ __('helpdesk::helpdesk.inbox.modals.close_conv_send_csat') }}
                </label>
                <label class="check">
                    <input type="checkbox" id="close-notify">
                    {{ __('helpdesk::helpdesk.inbox.modals.close_conv_notify_template') }}
                </label>
            </div>

            <div class="field">
                <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_final_note_label') }} <span class="hint">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_final_note_hint') }}</span></div>
                <textarea class="finput bv-modal-ta" rows="3"
                          placeholder="{{ __('helpdesk::helpdesk.inbox.modals.close_conv_final_note_placeholder') }}"></textarea>
            </div>

        </div>

        <div class="modal-foot">
            <button class="btn btn-primary" id="bv-close-apply">{{ __('helpdesk::helpdesk.inbox.modals.close_conv_apply') }}</button>
            <button class="btn btn-outline" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="close-conv"] .reason', function () {
    $('[data-bv-modal-name="close-conv"] .reason').removeClass('on');
    $(this).addClass('on');
    $(this).find('input[type="radio"]').prop('checked', true);
    $('#close-other-input').hide();
    if ($(this).data('reason') === 'other') {
        $('#close-other-input').show().focus();
    }
});


</script>
@endpush
@endonce
