{{-- Modal: Resolver conversación con calificación CSAT (#6 ve-resolve) --}}
<div class="bv-modal" data-bv-modal-name="resolve">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box bv-modal-icon-box--success"><i class="fas fa-check"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.resolve_header_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.resolve_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.resolve_field_summary') }} <span class="bv-req">*</span></label>
                <textarea id="resolveNotes" class="bv-form-input" rows="3" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.resolve_placeholder_summary') }}"></textarea>
            </div>

            <div class="bv-frow">
                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.resolve_field_final_status') }}</label>
                    <select id="resolveFinalStatus" class="bv-form-input">
                        <option value="resolved">{{ __('helpdesk::helpdesk.inbox.modals.resolve_status_resolved') }}</option>
                        <option value="closed_no_solution">{{ __('helpdesk::helpdesk.inbox.modals.resolve_status_closed_no_solution') }}</option>
                    </select>
                </div>
                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.resolve_field_category') }}</label>
                    <select id="resolveCategory" class="bv-form-input">
                        <option value="solved">{{ __('helpdesk::helpdesk.inbox.modals.resolve_category_solved') }}</option>
                        <option value="cannot_reproduce">{{ __('helpdesk::helpdesk.inbox.modals.resolve_category_cannot_reproduce') }}</option>
                        <option value="duplicate">{{ __('helpdesk::helpdesk.inbox.modals.resolve_category_duplicate') }}</option>
                        <option value="not_applicable">{{ __('helpdesk::helpdesk.inbox.modals.resolve_category_not_applicable') }}</option>
                    </select>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.resolve_field_request_rating') }} <span class="bv-form-hint">CSAT</span></label>
                <div class="bv-rating-row" id="bvRatingRow">
                    <button type="button" class="bv-rating-btn" data-score="1" title="{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_very_bad') }}">😤<span>{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_very_bad') }}</span></button>
                    <button type="button" class="bv-rating-btn" data-score="2" title="{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_bad') }}">😞<span>{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_bad') }}</span></button>
                    <button type="button" class="bv-rating-btn" data-score="3" title="{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_regular') }}">😐<span>{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_regular') }}</span></button>
                    <button type="button" class="bv-rating-btn" data-score="4" title="{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_good') }}">😊<span>{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_good') }}</span></button>
                    <button type="button" class="bv-rating-btn on" data-score="5" title="{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_excellent') }}">😄<span>{{ __('helpdesk::helpdesk.inbox.modals.resolve_rating_excellent') }}</span></button>
                </div>
            </div>

            <div class="bv-minfo bv-minfo--success" id="resolveEmailHint" style="display:none">
                <i class="fas fa-paper-plane"></i>
                <div>{{ __('helpdesk::helpdesk.inbox.modals.resolve_email_hint') }} <b id="resolveEmailAddr"></b>.</div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-resolve-with-csat">{{ __('helpdesk::helpdesk.inbox.modals.resolve_btn_with_csat') }}</button>
            <button class="btn-secondary" id="bv-resolve-without-csat">{{ __('helpdesk::helpdesk.inbox.modals.resolve_btn_without_csat') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/resolve.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/resolve.js')) }}"></script>
@endpush
@endonce
