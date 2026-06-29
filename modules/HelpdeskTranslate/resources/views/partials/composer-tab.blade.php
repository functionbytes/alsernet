{{--
    Composer tab button — rendered inside the helpdesk inbox composer tab bar.
    Self-gates on the module being enabled so the host view doesn't need to
    know about HelpdeskTranslate.
--}}
@if(\Nwidart\Modules\Facades\Module::find('HelpdeskTranslate')?->isEnabled())
<button class="bv-composer-tab" data-bv-tab="translate">
    <i class="fas fa-language bv-tab-icon"></i>{{ __('helpdesktranslate::messages.composer.tab') }}
</button>
@endif
