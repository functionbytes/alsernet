{{--
    Reusable Custom HTML editor body (toolbar + WYSIWYG textarea + source/preview split).
    Ported to campaign:: for Page Templates Custom HTML editing.

    Required vars:
        $labels       array of localized strings (keys: edit_source, visual, themeDark,
                      themeLight, toggleTheme, tags_hint)
        $currentHtml  initial textarea value

    Optional vars:
        $textareaName        form field name (default: 'html')
        $editorId            DOM id for the textarea/editor (default: 'html-editor')
        $tagsHint            full hint string (default: $labels['tags_hint'])
        $firstSwitchWarning  array ['title', 'message', 'confirm'] — when set, the first
                             Save shows a McDialog.confirm() warning that the template is
                             about to be locked into Custom HTML mode (one-way switch).

    Parent view contract:
        - @section('head') must load tinymce.min.js + refactor/js/codemirror-html.js
        - Wrap this @include inside a <form id="html-form"> ... </form>
--}}
@php
    $textareaName        ??= 'html';
    $editorId            ??= 'html-editor';
    $tagsHint            ??= ($labels['tags_hint'] ?? '');
    $firstSwitchWarning  ??= null;
@endphp

<div class="html-mode-toolbar">
    <button type="button" id="html-theme-toggle" class="mc-btn mc-btn-secondary mc-btn-sm" style="display:none" title="{{ $labels['toggleTheme'] }}">
        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'palette', 'size' => 14])
        <span id="html-theme-label">{{ $labels['themeDark'] }}</span>
    </button>
    <button type="button" id="html-toggle-source" class="mc-btn mc-btn-secondary mc-btn-sm" disabled>
        <span id="html-toggle-icon-source">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'code', 'size' => 14])
        </span>
        <span id="html-toggle-icon-visual" style="display:none">
            @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'eye', 'size' => 14])
        </span>
        <span id="html-toggle-label">{{ $labels['edit_source'] }}</span>
    </button>
</div>

<div id="html-mode-wysiwyg">
    <textarea name="{{ $textareaName }}" id="{{ $editorId }}">{{ old($textareaName, $currentHtml) }}</textarea>
</div>

<div id="html-mode-source" style="display:none">
    <div class="html-source-split">
        <div class="html-source-pane">
            <div id="html-cm"></div>
        </div>
        <div class="html-preview-pane">
            <iframe id="html-preview" srcdoc=""></iframe>
        </div>
    </div>
</div>

@if (isset($errors) && $errors->has($textareaName)) <span class="mc-form-error">{{ $errors->first($textareaName) }}</span> @endif

@if (!empty($tagsHint))
<p class="mc-wizard-tags-hint">{{ $tagsHint }}</p>
@endif

<script>
(function () {
    var textarea       = document.getElementById(@json($editorId));
    var wysiwygBox     = document.getElementById('html-mode-wysiwyg');
    var sourceBox      = document.getElementById('html-mode-source');
    var cmHost         = document.getElementById('html-cm');
    var previewFrame   = document.getElementById('html-preview');
    var toggleBtn      = document.getElementById('html-toggle-source');
    var toggleLabel    = document.getElementById('html-toggle-label');
    var iconSource     = document.getElementById('html-toggle-icon-source');
    var iconVisual     = document.getElementById('html-toggle-icon-visual');
    var themeBtn       = document.getElementById('html-theme-toggle');
    var themeLabel     = document.getElementById('html-theme-label');
    var form           = textarea.closest('form');

    var labels = {
        source: @json($labels['edit_source']),
        visual: @json($labels['visual']),
        themeDark: @json($labels['themeDark']),
        themeLight: @json($labels['themeLight'])
    };

    var CM_THEME_KEY = 'mc-cm-theme';
    var mode = 'visual';
    var cmInstance = null;
    var previewTimer = null;

    function initialCmTheme() {
        var stored = null;
        try {
            stored = localStorage.getItem(CM_THEME_KEY);
        } catch (e) {
            console.warn('localStorage read failed for CM theme', e);
        }
        if (stored === 'dark' || stored === 'light') return stored;
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function updateThemeBtn(themeName) {
        themeLabel.textContent = themeName === 'dark' ? labels.themeLight : labels.themeDark;
    }

    function tinyConfig() {
        return {
            selector: 'textarea#' + textarea.id,
            height: 600,
            menubar: 'edit insert view format table tools',
            plugins: 'link image lists table code fullscreen preview media charmap emoticons searchreplace autolink visualblocks wordcount',
            toolbar: 'undo redo | bold italic underline strikethrough | forecolor backcolor | link image media | bullist numlist | alignleft aligncenter alignright alignjustify | outdent indent | removeformat | code fullscreen preview',
            toolbar_sticky: true,
            forced_root_block: '',
            convert_urls: false,
            remove_script_host: false,
            valid_elements: '*[*]',
            extended_valid_elements: 'script[src|type|async|defer],style[type|media],link[href|rel|type|media]',
            language: '{{ App::getLocale() }}',
            branding: false,
            promotion: false,
            setup: function (editor) {
                editor.on('init', function () {
                    toggleBtn.disabled = false;
                });
            }
        };
    }

    function initTiny() {
        tinymce.init(tinyConfig());
    }

    function destroyTiny() {
        var ed = tinymce.get(textarea.id);
        if (ed) {
            ed.save();
            tinymce.remove('textarea#' + textarea.id);
        }
    }

    function renderPreview(htmlStr) {
        try {
            var doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
            doc.open();
            doc.write(htmlStr || '');
            doc.close();
        } catch (e) {
            // iframe not ready — ignore
        }
    }

    function schedulePreview(htmlStr) {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(function () {
            renderPreview(htmlStr);
        }, 500);
    }

    function ensureCM() {
        if (cmInstance) return;
        if (!window.MCCodeMirror) {
            console.error('CodeMirror bundle not loaded');
            return;
        }
        var theme = initialCmTheme();
        cmInstance = window.MCCodeMirror.create(cmHost, textarea.value || '', {
            theme: theme,
            onChange: function (val) {
                textarea.value = val;
                schedulePreview(val);
            }
        });
        updateThemeBtn(theme);
    }

    function switchToSource() {
        destroyTiny();
        wysiwygBox.style.display = 'none';
        sourceBox.style.display = '';
        themeBtn.style.display = '';
        ensureCM();
        cmInstance.setValue(textarea.value || '');
        renderPreview(textarea.value || '');
        mode = 'source';
        toggleLabel.textContent = labels.visual;
        iconSource.style.display = 'none';
        iconVisual.style.display = '';
    }

    function switchToVisual() {
        if (cmInstance) {
            textarea.value = cmInstance.getValue();
        }
        sourceBox.style.display = 'none';
        themeBtn.style.display = 'none';
        wysiwygBox.style.display = '';
        toggleBtn.disabled = true;
        initTiny();
        mode = 'visual';
        toggleLabel.textContent = labels.source;
        iconSource.style.display = '';
        iconVisual.style.display = 'none';
    }

    toggleBtn.addEventListener('click', function () {
        if (toggleBtn.disabled) return;
        if (mode === 'visual') {
            switchToSource();
        } else {
            switchToVisual();
        }
    });

    themeBtn.addEventListener('click', function () {
        if (!cmInstance) return;
        var next = cmInstance.getTheme() === 'dark' ? 'light' : 'dark';
        cmInstance.setTheme(next);
        updateThemeBtn(next);
        try {
            localStorage.setItem(CM_THEME_KEY, next);
        } catch (e) {
            console.warn('localStorage write failed for CM theme', e);
        }
    });

    var firstSwitchWarning = @json($firstSwitchWarning);
    var firstSwitchConfirmed = false;

    if (form) {
        form.addEventListener('submit', function (e) {
            if (mode === 'source' && cmInstance) {
                textarea.value = cmInstance.getValue();
            }

            if (firstSwitchWarning && !firstSwitchConfirmed) {
                e.preventDefault();
                e.stopPropagation();
                var submitBtn = e.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
                var onConfirm = function () {
                    firstSwitchConfirmed = true;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                };
                var onCancel = function () {
                    if (submitBtn) submitBtn.classList.remove('is-loading');
                };
                if (window.McDialog) {
                    window.McDialog.confirm({
                        title: firstSwitchWarning.title,
                        message: firstSwitchWarning.message,
                        type: 'warning',
                        confirmText: firstSwitchWarning.confirm,
                        onConfirm: onConfirm,
                        onCancel: onCancel
                    });
                } else if (window.confirm(firstSwitchWarning.message)) {
                    onConfirm();
                } else {
                    onCancel();
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTiny();
    });
})();
</script>
