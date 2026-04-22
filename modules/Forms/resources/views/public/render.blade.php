@php
    $formId = 'form-' . $form->id . '-' . uniqid();
    $theme = $shortcodeConfig['theme'] ?? $form->theme ?? 'default';
    $display = $shortcodeConfig['display'] ?? 'inline';
    $columns = $shortcodeConfig['columns'] ?? null;
    $showTitle = $shortcodeConfig['show_title'] ?? false;
    $locale = app()->getLocale();
    $_i18nForms = [
        'es' => ['send' => 'Enviar', 'sending' => 'Enviando…', 'submitted' => 'Formulario enviado.', 'loading' => 'Cargando formulario…'],
        'pt' => ['send' => 'Enviar', 'sending' => 'A enviar…', 'submitted' => 'Formulário enviado.', 'loading' => 'A carregar formulário…'],
        'en' => ['send' => 'Send', 'sending' => 'Sending…', 'submitted' => 'Form submitted.', 'loading' => 'Loading form…'],
        'fr' => ['send' => 'Envoyer', 'sending' => 'Envoi en cours…', 'submitted' => 'Formulaire envoyé.', 'loading' => 'Chargement du formulaire…'],
    ][$locale] ?? ['send' => 'Enviar', 'sending' => 'Enviando…', 'submitted' => 'Formulario enviado.', 'loading' => 'Cargando formulario…'];
    $buttonText = $shortcodeConfig['button_text'] ?? $form->submit_button_text ?? $_i18nForms['send'];
    $buttonColor = $shortcodeConfig['button_color'] ?? null;
    $isMultiStep = $form->is_multi_step && count($form->fields->pluck('step_number')->unique()) > 1;
    $steps = $isMultiStep ? $form->fields->pluck('step_number')->unique()->sort()->values() : collect([1]);
    $totalSteps = $steps->count();
    $floatingLabel = $form->floating_label ?? false;
    $captchaEnabled = ($form->captcha_enabled ?? false) && class_exists(\Modules\Captcha\Facades\Captcha::class);
@endphp

{{-- CSS del tema — base cargado una vez por request --}}
@once
<link rel="stylesheet" href="{{ asset('modules/forms/css/forms.css') }}">
<link rel="stylesheet" href="{{ asset('core/select2/css/select2.min.css') }}">
<style>
.select2-container.is-invalid .select2-selection { border-color: #dc3545 !important; }
.select2-container.is-invalid + .invalid-feedback { display: block; }
</style>
@endonce

{{-- Tema específico: un link por tema (no por instancia) --}}
@if($theme !== 'default')
@push('forms-themes-'.$theme)
<link rel="stylesheet" href="{{ asset('modules/forms/css/themes/' . $theme . '.css') }}">
@endpush
@endif

@php
    $lazy = ! empty($shortcodeConfig['lazy']) && $display === 'inline' && ! app()->bound('ve_preview_wrap');
@endphp

@if($lazy)
<div class="forms-lazy-wrapper" data-lazy-form="{{ $formId }}" data-form-slug="{{ $form->slug }}" aria-busy="true">
    <div class="forms-lazy-placeholder text-center py-5">
        <div class="spinner-border text-secondary" role="status" aria-hidden="true"></div>
        <p class="text-muted small mt-2 mb-0">{{ $_i18nForms['loading'] }}</p>
    </div>
    <template class="forms-lazy-template">
@endif

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

@if($lazy)
    </template>
</div>
@endif

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
@once
<script>
window.FormsAssets = window.FormsAssets || { loaded: {} };
(function () {
    function loadScript(src, onload) {
        if (window.FormsAssets.loaded[src]) { if (onload) onload(); return; }
        window.FormsAssets.loaded[src] = true;
        var s = document.createElement('script');
        s.src = src;
        if (onload) s.onload = onload;
        document.head.appendChild(s);
    }

    function waitForJQuery(cb) {
        if (typeof jQuery !== 'undefined') { cb(); return; }
        var t = setInterval(function () {
            if (typeof jQuery !== 'undefined') { clearInterval(t); cb(); }
        }, 20);
    }

    var jqvSrc   = 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js';
    var localeSrc = @json($validateLocale ? 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/localization/messages_'.$validateLocale.'.js' : null);
    var select2Src = '{{ asset('core/select2/js/select2.min.js') }}';
    var formsSrc   = '{{ asset('modules/forms/js/forms.js') }}';

    waitForJQuery(function () {
        loadScript(jqvSrc, function () {
            var after = function () { loadScript(formsSrc); };
            if (localeSrc) { loadScript(localeSrc, function () { loadScript(select2Src, after); }); }
            else { loadScript(select2Src, after); }
        });
    });
})();
</script>
@endonce
@once

{{-- Auto-populate de UTM y parámetros auto_populate_param --}}
<script>
(function () {
    function populateFromQueryString() {
        if (typeof URLSearchParams === 'undefined') return;
        var params = new URLSearchParams(window.location.search);
        if (!params.toString()) return;

        // 1) UTM estándar: llena inputs con name= utm_source, utm_medium, etc.
        ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(function (key) {
            var value = params.get(key);
            if (!value) return;
            document.querySelectorAll('input[name="' + key + '"], input[data-auto-populate="' + key + '"]')
                .forEach(function (el) { el.value = value; });
        });

        // 2) Cualquier campo con data-auto-populate="X" busca el param X en la URL
        document.querySelectorAll('input[data-auto-populate], textarea[data-auto-populate], select[data-auto-populate]')
            .forEach(function (el) {
                var paramKey = el.getAttribute('data-auto-populate');
                var value = params.get(paramKey);
                if (value && !el.value) el.value = value;
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', populateFromQueryString);
    } else {
        populateFromQueryString();
    }
})();
</script>

{{-- Mejora progresiva de accesibilidad (aria-*) --}}
<script>
(function () {
    // Mapa key/type → autocomplete. Orden: match exacto > heurística por substring.
    var AUTOCOMPLETE_MAP_EXACT = {
        email: 'email',
        correo: 'email',
        tel: 'tel',
        telefono: 'tel',
        phone: 'tel',
        mobile: 'tel',
        name: 'name',
        nombre: 'given-name',
        apellido: 'family-name',
        apellidos: 'family-name',
        last_name: 'family-name',
        first_name: 'given-name',
        full_name: 'name',
        company: 'organization',
        empresa: 'organization',
        organization: 'organization',
        job_title: 'organization-title',
        puesto: 'organization-title',
        address: 'street-address',
        direccion: 'street-address',
        street: 'street-address',
        city: 'address-level2',
        ciudad: 'address-level2',
        state: 'address-level1',
        region: 'address-level1',
        provincia: 'address-level1',
        country: 'country',
        pais: 'country',
        zip: 'postal-code',
        postal: 'postal-code',
        codigo_postal: 'postal-code',
        cp: 'postal-code',
        url: 'url',
        website: 'url',
        web: 'url',
        birthday: 'bday',
        fecha_nacimiento: 'bday',
    };

    var INPUTMODE_BY_TYPE = {
        email: 'email',
        tel: 'tel',
        url: 'url',
        number: 'decimal',
    };

    function guessAutocomplete(el) {
        if (el.hasAttribute('autocomplete')) return;
        var key = (el.name || el.getAttribute('data-key') || '').toLowerCase().replace(/[^a-z_]/g, '');
        var type = (el.type || '').toLowerCase();

        if (type && ['email', 'tel', 'url'].includes(type) && !el.name) {
            el.setAttribute('autocomplete', type);
            return;
        }
        if (AUTOCOMPLETE_MAP_EXACT[key]) {
            el.setAttribute('autocomplete', AUTOCOMPLETE_MAP_EXACT[key]);
            return;
        }
        if (type === 'email') { el.setAttribute('autocomplete', 'email'); return; }
        if (type === 'tel') { el.setAttribute('autocomplete', 'tel'); return; }
        if (type === 'url') { el.setAttribute('autocomplete', 'url'); return; }
    }

    function applyInputmodeAndCasing(el) {
        var type = (el.type || '').toLowerCase();
        var tag = el.tagName.toLowerCase();
        var key = (el.name || '').toLowerCase();

        // inputmode complementario al type
        if (!el.hasAttribute('inputmode') && INPUTMODE_BY_TYPE[type]) {
            el.setAttribute('inputmode', INPUTMODE_BY_TYPE[type]);
        }

        // autocapitalize/autocorrect según contexto
        if (!el.hasAttribute('autocapitalize')) {
            if (type === 'email' || type === 'url' || type === 'password') {
                el.setAttribute('autocapitalize', 'off');
            } else if (tag === 'textarea') {
                el.setAttribute('autocapitalize', 'sentences');
            } else if (['name', 'nombre', 'apellido', 'apellidos', 'first_name', 'last_name', 'full_name'].includes(key)) {
                el.setAttribute('autocapitalize', 'words');
            }
        }
        if (!el.hasAttribute('autocorrect')) {
            if (type === 'email' || type === 'url' || type === 'tel') {
                el.setAttribute('autocorrect', 'off');
            }
        }
        if (!el.hasAttribute('spellcheck')) {
            if (type === 'email' || type === 'url' || type === 'tel') {
                el.setAttribute('spellcheck', 'false');
            }
        }
    }

    function setupCharCounter(textarea) {
        var max = parseInt(textarea.getAttribute('maxlength') || '0', 10);
        if (!max || textarea.dataset.charCounterBound === '1') return;
        textarea.dataset.charCounterBound = '1';

        var counter = document.createElement('small');
        counter.className = 'forms-char-counter text-muted d-block text-end mt-1';
        counter.setAttribute('aria-live', 'polite');
        counter.textContent = (textarea.value || '').length + '/' + max;
        textarea.insertAdjacentElement('afterend', counter);

        var update = function () {
            var len = (textarea.value || '').length;
            counter.textContent = len + '/' + max;
            counter.classList.toggle('text-danger', len >= max);
        };
        textarea.addEventListener('input', update);
    }

    function ensureAriaLiveRegion(form) {
        var region = form.querySelector('[data-forms-aria-live]');
        if (region) return region;
        region = document.createElement('div');
        region.setAttribute('aria-live', 'polite');
        region.setAttribute('aria-atomic', 'true');
        region.setAttribute('data-forms-aria-live', '');
        region.className = 'visually-hidden forms-sr-only';
        form.prepend(region);
        return region;
    }


    function setupSuccessAnnouncer(form) {
        var success = form.querySelector('.forms-success-message');
        if (!success || success.dataset.a11yBound === '1') return;
        success.dataset.a11yBound = '1';

        success.setAttribute('role', 'status');
        success.setAttribute('aria-live', 'polite');

        var observer = new MutationObserver(function () {
            if (!success.classList.contains('d-none')) {
                var region = ensureAriaLiveRegion(form);
                region.textContent = (success.textContent || @json($_i18nForms['submitted'])).trim();
                form.dispatchEvent(new CustomEvent('forms:submitted-ok', { bubbles: true }));
                // Limpiar error summary si lo había
                var summary = form.querySelector('.forms-error-summary');
                if (summary) summary.style.display = 'none';
            }
        });
        observer.observe(success, { attributes: true, attributeFilter: ['class'] });
    }

    function setupLoadingStateOnSubmit(form) {
        if (form.dataset.loadingStateBound === '1') return;
        form.dataset.loadingStateBound = '1';
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"], [data-forms-submit]');
            if (!btn) return;
            var original = btn.innerHTML;
            btn.dataset.originalHtml = original;
            btn.setAttribute('aria-busy', 'true');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' +
                (btn.dataset.loadingText || @json($_i18nForms['sending']));
            // Rehabilitar si falla (listener opcional via evento)
            setTimeout(function () {
                if (btn.disabled && document.body.contains(btn)) {
                    // recovery por si el submit rompe silenciosamente
                    btn.disabled = false;
                    btn.removeAttribute('aria-busy');
                    btn.innerHTML = original;
                }
            }, 30000);
        });
    }

    // Inline validation on-blur usando Constraint Validation API (fallback si
    // jquery-validate no está configurado en ese modo).
    function setupInlineValidation(form) {
        if (form.dataset.inlineValidationBound === '1') return;
        form.dataset.inlineValidationBound = '1';

        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.type === 'hidden') return;
            el.addEventListener('blur', function () {
                if (!el.willValidate) return;
                var ok = el.checkValidity();
                el.classList.toggle('is-invalid', !ok);
                el.classList.toggle('is-valid', ok && (el.value || '').length > 0);
                el.setAttribute('aria-invalid', ok ? 'false' : 'true');

                var fb = el.parentElement && el.parentElement.querySelector('.invalid-feedback');
                if (!ok && fb && !fb.textContent.trim()) {
                    var msg = (typeof $.validator !== 'undefined' && $.validator.messages && $.validator.messages.required)
                        ? (typeof $.validator.messages.required === 'function' ? $.validator.messages.required.call({param: null}, el) : $.validator.messages.required)
                        : el.validationMessage;
                    var _d = document.createElement('textarea');
                    _d.innerHTML = msg;
                    fb.textContent = _d.value;
                }
            });
            el.addEventListener('input', function () {
                if (el.classList.contains('is-invalid') && el.checkValidity()) {
                    el.classList.remove('is-invalid');
                    el.setAttribute('aria-invalid', 'false');
                }
            });
        });
    }

    function setupLocalStorageDraft(form) {
        try {
            var slug = form.getAttribute('data-form-slug') || form.id;
            if (!slug) return;
            var key = 'forms.draft.' + slug;

            // Rehidratar
            var raw = localStorage.getItem(key);
            if (raw) {
                var data = JSON.parse(raw);
                Object.entries(data).forEach(function ([name, value]) {
                    var el = form.querySelector('[name="' + CSS.escape(name) + '"]');
                    if (!el || el.type === 'hidden' || el.type === 'file' || el.type === 'password') return;
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = el.value === value;
                    } else if (el.value === '' || el.value === el.defaultValue) {
                        el.value = value;
                    }
                });
            }

            // Persistir cada 3s después del último input
            var timer;
            form.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    var snapshot = {};
                    form.querySelectorAll('input, select, textarea').forEach(function (el) {
                        if (!el.name || el.type === 'hidden' || el.type === 'file' || el.type === 'password') return;
                        if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
                        if ((el.value || '').length) snapshot[el.name] = el.value;
                    });
                    localStorage.setItem(key, JSON.stringify(snapshot));
                }, 3000);
            });

            // Limpiar al enviar con éxito
            form.addEventListener('forms:submitted-ok', function () {
                localStorage.removeItem(key);
            });
        } catch (e) { /* localStorage no disponible */ }
    }

    function setupMultiStepFocus(form) {
        if (form.dataset.stepFocusBound === '1') return;
        form.dataset.stepFocusBound = '1';
        form.addEventListener('forms:step-changed', function (e) {
            var step = e.detail && e.detail.step;
            if (!step) return;
            var target = form.querySelector('[data-step="' + step + '"] input:not([type=hidden]), ' +
                                           '[data-step="' + step + '"] select, ' +
                                           '[data-step="' + step + '"] textarea');
            if (target) setTimeout(function () { target.focus(); }, 120);
        });
    }

    function enhanceA11y(root) {
        (root || document).querySelectorAll('.forms-form').forEach(function (form) {
            if (form.dataset.a11yEnhanced === '1') return;
            form.dataset.a11yEnhanced = '1';

            form.querySelectorAll('[required]').forEach(function (el) {
                el.setAttribute('aria-required', 'true');
            });

            form.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (!el.id) return;
                var small = el.nextElementSibling;
                if (small && small.tagName === 'SMALL' && small.classList.contains('text-muted')) {
                    var helpId = el.id + '-help';
                    small.id = small.id || helpId;
                    el.setAttribute('aria-describedby', small.id);
                }
            });

            form.querySelectorAll('.form-label .text-danger').forEach(function (el) {
                el.setAttribute('aria-hidden', 'true');
            });

            // Enhancers nuevos
            form.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (el.type === 'hidden') return;
                guessAutocomplete(el);
                applyInputmodeAndCasing(el);
            });

            form.querySelectorAll('textarea').forEach(setupCharCounter);

            ensureAriaLiveRegion(form);
            setupLoadingStateOnSubmit(form);
            setupInlineValidation(form);
            setupLocalStorageDraft(form);
            setupMultiStepFocus(form);

            setupSuccessAnnouncer(form);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { enhanceA11y(); });
    } else {
        enhanceA11y();
    }
    document.addEventListener('forms:lazy-loaded', function (e) { enhanceA11y(e.target); });
})();
</script>

{{-- Lazy-load forms con IntersectionObserver --}}
<script>
(function () {
    function materialize(wrapper) {
        var tpl = wrapper.querySelector('template.forms-lazy-template');
        if (!tpl) return;
        var frag = tpl.content.cloneNode(true);
        wrapper.innerHTML = '';
        wrapper.appendChild(frag);
        wrapper.setAttribute('aria-busy', 'false');
        wrapper.dataset.lazyLoaded = '1';
        wrapper.dispatchEvent(new CustomEvent('forms:lazy-loaded', { bubbles: true, detail: { slug: wrapper.dataset.formSlug } }));
    }

    function init() {
        var wrappers = document.querySelectorAll('[data-lazy-form]:not([data-lazy-loaded="1"])');
        if (!wrappers.length) return;

        if (!('IntersectionObserver' in window)) {
            wrappers.forEach(materialize);
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    materialize(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '200px 0px' });

        wrappers.forEach(function (w) { io.observe(w); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
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
