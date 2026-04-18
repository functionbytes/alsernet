@php
    $formId = 'form-' . $form->id . '-' . uniqid();
    $theme = $shortcodeConfig['theme'] ?? $form->theme ?? 'default';
    $display = $shortcodeConfig['display'] ?? 'inline';
    $columns = $shortcodeConfig['columns'] ?? null;
    $showTitle = $shortcodeConfig['show_title'] ?? false;
    $buttonText = $shortcodeConfig['button_text'] ?? $form->submit_button_text ?? 'Enviar';
    $buttonColor = $shortcodeConfig['button_color'] ?? null;
    $locale = app()->getLocale();
    $isMultiStep = $form->is_multi_step && count($form->fields->pluck('step_number')->unique()) > 1;
    $steps = $isMultiStep ? $form->fields->pluck('step_number')->unique()->sort()->values() : collect([1]);
    $totalSteps = $steps->count();
    $floatingLabel = $form->floating_label ?? false;
    $captchaEnabled = ($form->captcha_enabled ?? false) && class_exists(\Modules\Captcha\Facades\Captcha::class);
@endphp

{{-- CSS del tema --}}
<link rel="stylesheet" href="{{ asset('modules/forms/css/forms.css') }}">
@if($theme !== 'default')
<link rel="stylesheet" href="{{ asset('modules/forms/css/themes/' . $theme . '.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('core/select2/css/select2.min.css') }}">
<style>
.select2-container.is-invalid .select2-selection {
    border-color: #dc3545 !important;
}
.select2-container.is-invalid + .invalid-feedback {
    display: block;
}
</style>

{{-- Wrapper según display mode --}}
@if($display === 'popup')
    {{-- Botón trigger --}}
    <button type="button" class="btn btn-primary forms-popup-trigger" data-form-id="{{ $formId }}">
        <i class="fas fa-envelope me-1"></i> {{ $buttonText }}
    </button>
    {{-- Modal Bootstrap --}}
    <div class="modal fade forms-popup-modal" id="modal-{{ $formId }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $form->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('forms::public.partials.form-body', compact('form', 'formId', 'theme', 'columns', 'isMultiStep', 'steps', 'totalSteps', 'floatingLabel', 'buttonText', 'buttonColor', 'showTitle', 'captchaEnabled'))
                </div>
            </div>
        </div>
    </div>
@elseif($display === 'slide_in')
    {{-- Slide-in flotante --}}
    <div class="forms-slide-in" id="slide-{{ $formId }}" data-delay="{{ $shortcodeConfig['delay'] ?? 0 }}" data-position="{{ $shortcodeConfig['position'] ?? 'bottom_right' }}" style="display:none">
        <div class="forms-slide-in-header d-flex align-items-center justify-content-between p-3">
            <strong>{{ $form->name }}</strong>
            <button type="button" class="btn-close forms-slide-in-close"></button>
        </div>
        <div class="forms-slide-in-body p-3">
            @include('forms::public.partials.form-body', compact('form', 'formId', 'theme', 'columns', 'isMultiStep', 'steps', 'totalSteps', 'floatingLabel', 'buttonText', 'buttonColor', 'showTitle', 'captchaEnabled'))
        </div>
    </div>
@else
    {{-- Inline (default) --}}
    @include('forms::public.partials.form-body', compact('form', 'formId', 'theme', 'columns', 'isMultiStep', 'steps', 'totalSteps', 'floatingLabel', 'buttonText', 'buttonColor', 'showTitle', 'captchaEnabled'))
@endif

@once
@php
$validateLocaleMap = [
    'ar'    => 'ar',    'az' => 'az',    'bg' => 'bg',
    'bn'    => 'bn_BD', 'ca' => 'ca',    'cs' => 'cs',
    'da'    => 'da',    'de' => 'de',    'el' => 'el',
    'es'    => 'es',    'et' => 'et',    'eu' => 'eu',
    'fa'    => 'fa',    'fi' => 'fi',    'fr' => 'fr',
    'gl'    => 'gl',    'he' => 'he',    'hr' => 'hr',
    'hu'    => 'hu',    'hy' => 'hy_AM', 'id' => 'id',
    'is'    => 'is',    'it' => 'it',    'ja' => 'ja',
    'ka'    => 'ka',    'kk' => 'kk',    'ko' => 'ko',
    'lt'    => 'lt',    'lv' => 'lv',    'mk' => 'mk',
    'my'    => 'my',    'nl' => 'nl',    'no' => 'no',
    'pl'    => 'pl',    'pt' => 'pt_PT', 'ro' => 'ro',
    'ru'    => 'ru',    'sk' => 'sk',    'sl' => 'sl',
    'sr'    => 'sr',    'sv' => 'sv',    'th' => 'th',
    'tr'    => 'tr',    'uk' => 'uk',    'ur' => 'ur',
    'vi'    => 'vi',    'zh' => 'zh',
];
$validateLocale = $validateLocaleMap[$locale] ?? null;
@endphp
<script defer src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
@if($validateLocale)
<script defer src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/localization/messages_{{ $validateLocale }}.js"></script>
@endif
<script defer src="{{ asset('core/select2/js/select2.min.js') }}"></script>
<script defer src="{{ asset('modules/forms/js/forms.js') }}"></script>
@endonce

{{-- Configuración del formulario --}}
<script>
window.FormsConfig = window.FormsConfig || {};
window.FormsConfig['{{ $formId }}'] = {
    formId: {{ $form->id }},
    slug: '{{ $form->slug }}',
    submitUrl: '{{ route('forms.public.submit', $form->slug) }}',
    abandonUrl: '{{ route('forms.public.abandon', $form->slug) }}',
    restoreUrl: '{{ route('forms.public.restore', $form->slug) }}',
    isMultiStep: {{ $isMultiStep ? 'true' : 'false' }},
    totalSteps: {{ $totalSteps }},
    successMessage: @json($form->success_message ?? config('forms.default_success_message')),
    redirectUrl: @json($form->redirect_url),
    successAnimation: '{{ $form->success_animation ?? 'fade' }}',
    display: '{{ $display }}',
    csrfToken: '{{ csrf_token() }}',
    locale: '{{ $locale }}',
    conditions: @json($form->fields->map(fn($f) => ['key' => $f->key, 'conditions' => $f->conditions, 'logicJumps' => $f->logic_jumps])->filter(fn($f) => !empty($f['conditions']) || !empty($f['logicJumps']))->values()),
    calculationFields: @json($form->fields->where('type', 'calculation')->map(fn($f) => ['key' => $f->key, 'formula' => $f->formula])->values()),
    sessionToken: '{{ session()->getId() }}',
    abandonTrackingEnabled: {{ $form->is_active ? 'true' : 'false' }},
};
@if($display === 'popup')
$(document).on('click', '[data-form-id="{{ $formId }}"]', function() {
    $('#modal-{{ $formId }}').modal('show');
});
@endif
@if($display === 'slide_in')
$(document).ready(function() {
    const delay = {{ $shortcodeConfig['delay'] ?? 3000 }};
    setTimeout(function() { $('#slide-{{ $formId }}').slideDown(400); }, delay);
    $(document).on('click', '#slide-{{ $formId }} .forms-slide-in-close', function() {
        $('#slide-{{ $formId }}').slideUp(400);
    });
});
@endif
</script>
