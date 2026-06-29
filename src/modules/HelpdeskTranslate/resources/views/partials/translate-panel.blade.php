{{--
    Translate panel — appended to the chat thread composer.
    Shown/hidden via the "Traducir" tab; the JS that toggles the `on`
    class still lives in modules/Helpdesk's compiled conversations.js
    (jQuery delegated handlers — no source available to extract).
--}}
@if(\Nwidart\Modules\Facades\Module::find('HelpdeskTranslate')?->isEnabled())
@once
@push('css')
<link rel="stylesheet" href="{{ asset('vendor/helpdesktranslate/translate-panel.css') }}?v={{ @filemtime(public_path('vendor/helpdesktranslate/translate-panel.css')) }}">
@endpush
@push('scripts')
<script>
window.HelpdeskTranslateI18n = {
    translated: @json(__('helpdesktranslate::messages.thread.js_translated')),
    error: @json(__('helpdesktranslate::messages.thread.js_error')),
    from: @json(__('helpdesktranslate::messages.thread.js_from')),
    btnTranslate: @json(__('helpdesktranslate::messages.thread.btn_translate')),
};
</script>
<script src="{{ asset('vendor/helpdesktranslate/translate-thread.js') }}?v={{ @filemtime(public_path('vendor/helpdesktranslate/translate-thread.js')) }}"></script>
@endpush
@endonce
<div class="bv-translate-panel" id="bv-translate-panel">
    <div class="bv-panel-head">
        <i class="fas fa-language"></i>
        <span>{{ __('helpdesktranslate::messages.panel.title') }}</span>
        <button id="bv-translate-close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="bv-tp-body">
        <div>
            <div class="bv-tp-lbl">{{ __('helpdesktranslate::messages.panel.mode_label') }}</div>
            <div class="bv-tp-mode-list">
                <div class="bv-tp-mode on" data-mode="incoming">
                    <i class="fas fa-arrow-down"></i>
                    <div>
                        <div class="t">{{ __('helpdesktranslate::messages.panel.mode_incoming') }}</div>
                        <div class="s">{{ __('helpdesktranslate::messages.panel.mode_incoming_desc') }}</div>
                    </div>
                </div>
                <div class="bv-tp-mode" data-mode="outgoing">
                    <i class="fas fa-arrow-up"></i>
                    <div>
                        <div class="t">{{ __('helpdesktranslate::messages.panel.mode_outgoing') }}</div>
                        <div class="s">{{ __('helpdesktranslate::messages.panel.mode_outgoing_desc') }}</div>
                    </div>
                </div>
                <div class="bv-tp-mode" data-mode="both">
                    <i class="fas fa-arrows-up-down"></i>
                    <div>
                        <div class="t">{{ __('helpdesktranslate::messages.panel.mode_both') }}</div>
                        <div class="s">{{ __('helpdesktranslate::messages.panel.mode_both_desc') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bv-tp-lang-row">
            <div class="bv-tp-lang-col">
                <div class="bv-tp-lbl">{{ __('helpdesktranslate::messages.panel.lang_from') }}</div>
                <select class="bv-tp-sel" id="bv-tp-from">
                    <option value="auto">{{ __('helpdesktranslate::messages.languages.auto') }}</option>
                    <option value="es">{{ __('helpdesktranslate::messages.languages.es') }}</option>
                    <option value="en">{{ __('helpdesktranslate::messages.languages.en') }}</option>
                    <option value="fr">{{ __('helpdesktranslate::messages.languages.fr') }}</option>
                    <option value="pt">{{ __('helpdesktranslate::messages.languages.pt') }}</option>
                </select>
            </div>
            <i class="fas fa-arrow-right bv-tp-lang-arrow"></i>
            <div class="bv-tp-lang-col">
                <div class="bv-tp-lbl">{{ __('helpdesktranslate::messages.panel.lang_to') }}</div>
                <select class="bv-tp-sel" id="bv-tp-to">
                    @php $defaultTarget = strtolower((string) (\Modules\Helpdesk\Models\Setting::get('helpdesktranslate.default_target') ?: config('helpdesktranslate.default_target', 'es'))); @endphp
                    <option value="es" {{ $defaultTarget === 'es' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.es') }}</option>
                    <option value="en" {{ $defaultTarget === 'en' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.en') }}</option>
                    <option value="fr" {{ $defaultTarget === 'fr' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.fr') }}</option>
                    <option value="pt" {{ $defaultTarget === 'pt' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.pt') }}</option>
                    <option value="de" {{ $defaultTarget === 'de' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.de') }}</option>
                    <option value="it" {{ $defaultTarget === 'it' ? 'selected' : '' }}>{{ __('helpdesktranslate::messages.languages.it') }}</option>
                </select>
            </div>
        </div>
    </div>
    <div class="bv-tp-foot">
        <button class="bv-panel-btn bv-panel-btn-cancel" id="bv-translate-close-2"><i class="fas fa-power-off"></i> {{ __('helpdesktranslate::messages.panel.btn_disable') }}</button>
        <button class="bv-panel-btn bv-panel-btn-confirm"><i class="fas fa-language"></i> {{ __('helpdesktranslate::messages.panel.btn_enable') }}</button>
    </div>
</div>
@endif
