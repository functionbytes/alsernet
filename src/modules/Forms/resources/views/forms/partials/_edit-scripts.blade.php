<script src="{{ url('others/filemanager/js/jquery-ui-1.12.1/jquery-ui.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/xml-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify-html.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/foldcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/foldgutter.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/fold/brace-fold.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/display/fullscreen.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/search/searchcursor.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/search/search.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/dialog/dialog.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/selection/active-line.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/addon/comment/comment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify-css.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-beautify@1.14.9/dist/beautify.js"></script>
<script>
    const FORM_ID   = {{ $form->id }};
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    const urls = {
        fieldsStore:  '{{ route('settings.forms.fields.store', $form) }}',
        fieldsReorder:'{{ route('settings.forms.fields.reorder', $form) }}',
        fieldEditBase:'{{ route('settings.forms.fields.index', $form) }}',
        preview:      '{{ route('settings.forms.preview', $form) }}',
    };

    const LAYOUT_TYPES          = ['section_header', 'html_block', 'divider', 'spacer'];
    const TYPES_WITH_OPTIONS    = ['select', 'radio', 'checkbox', 'image_choice'];
    const TYPES_WITH_MIN_MAX    = ['slider', 'number', 'rating', 'nps'];
    const TYPES_WITH_CONSENT    = ['consent', 'newsletter_consent'];

    // ─── Sortable ──────────────────────────────────────────────────────────────
    $('#fieldsList').sortable({
        handle: '.drag-handle',
        items: 'tr',
        placeholder: 'field-sort-placeholder',
        forcePlaceholderSize: true,
        update: function () {
            const order = [];
            $('#fieldsList tr.field-item').each(function (index) {
                order.push({ id: $(this).data('id'), sort_order: index });
            });
            $.ajax({
                url: urls.fieldsReorder,
                method: 'POST',
                data: { items: order },
                headers: { 'X-CSRF-TOKEN': csrfToken },
            });
        }
    });

    // ─── Vista previa en tab ───────────────────────────────────────────────────
    const previewPublicUrl = '{{ route('forms.public.preview.public', [$form, $previewToken]) }}';
    const previewDeviceConfig = {
        desktop: { showBrowser: true,  showMobile: false },
        tablet:  { showBrowser: false, showMobile: false },
        mobile:  { showBrowser: false, showMobile: true  },
    };

    $('#vista-tab').on('shown.bs.tab', function () {
        if (!$('#previewTabFrame').attr('src')) {
            $('#previewTabFrame').attr('src', previewPublicUrl);
        }
    });

    $('#btnRefreshPreviewForm').on('click', function () {
        const frame = document.getElementById('previewTabFrame');
        if (frame.src) {
            frame.src = frame.src;
        } else {
            frame.src = previewPublicUrl;
        }
    });

    $(document).on('click', '.btn-preview-device', function () {
        const device = $(this).data('device');
        const cfg    = previewDeviceConfig[device];

        $('.btn-preview-device').removeClass('active');
        $(this).addClass('active');

        $('#previewTabDeviceWrap')
            .removeClass('device-desktop device-tablet device-mobile')
            .addClass('device-' + device);

        $('#previewTabBrowserBar').toggleClass('d-none', !cfg.showBrowser);
        $('#previewTabMobileNotch, #previewTabMobileHome').toggleClass('d-none', !cfg.showMobile);
    });

    // ─── Abrir modal para agregar ──────────────────────────────────────────────
    $(document).on('click', '.btn-add-field', function () {
        const type = $(this).data('type');
        resetFieldModal();
        $('#fieldModalLabel').text('Agregar campo');
        $('#fieldId').val('');
        $('#fieldType').val(type).trigger('change');
        new bootstrap.Modal(document.getElementById('fieldModal')).show();
    });

    // ─── Mostrar/ocultar grupos según tipo ─────────────────────────────────────
    $('#fieldType').on('change', function () {
        const type = $(this).val();
        const isLayout  = LAYOUT_TYPES.includes(type);

        $('#optionsGroup').toggleClass('d-none', !TYPES_WITH_OPTIONS.includes(type));
        $('#htmlContentGroup').toggleClass('d-none', type !== 'html_block');
        $('#consentTextGroup').toggleClass('d-none', !TYPES_WITH_CONSENT.includes(type));
        $('.field-trans-consent-group').toggleClass('d-none', !TYPES_WITH_CONSENT.includes(type));
        $('#minValueGroup, #maxValueGroup, #stepValueGroup').toggleClass('d-none', !TYPES_WITH_MIN_MAX.includes(type));
        $('#formulaGroup').toggleClass('d-none', type !== 'calculation');
        $('#likertRowsGroup').toggleClass('d-none', type !== 'likert');
        $('#placeholderGroup').toggleClass('d-none', isLayout || type === 'signature');
    });

    // ─── Auto-generar key desde label ─────────────────────────────────────────
    $('#fieldLabel').on('input', function () {
        if ($('#fieldId').val()) return;
        const key = $(this).val()
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '');
        $('#fieldKey').val(key);
    });

    // ─── Añadir opción (select/radio/checkbox) ─────────────────────────────────
    $(document).on('click', '#addOption', function () {
        const idx = $('#optionsList .option-row').length;
        $('#optionsList').append(buildOptionRow('', '', idx));
    });

    $(document).on('click', '.btn-remove-option', function () {
        $(this).closest('.option-row').remove();
    });

    function buildOptionRow(label, value, idx) {
        const displayValue = value ?? label;
        return `<div class="input-group input-group-sm mb-1 option-row">
            <input type="text" class="form-control option-label" value="${label}" placeholder="Etiqueta ${idx + 1}">
            <input type="text" class="form-control option-value" value="${displayValue}" placeholder="Valor ${idx + 1}" style="max-width:120px">
            <button type="button" class="btn btn-outline-danger btn-remove-option">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    }

    // ─── Añadir pregunta Likert ────────────────────────────────────────────────
    $(document).on('click', '#addLikertRow', function () {
        const idx = $('#likertRowsList .likert-row').length;
        $('#likertRowsList').append(
            `<div class="input-group input-group-sm mb-1 likert-row">
                <input type="text" class="form-control" name="likert_rows[]" placeholder="Pregunta ${idx + 1}">
                <button type="button" class="btn btn-outline-danger btn-remove-likert">
                    <i class="fas fa-times"></i>
                </button>
            </div>`
        );
    });

    $(document).on('click', '.btn-remove-likert', function () {
        $(this).closest('.likert-row').remove();
    });

    // ─── Guardar campo ─────────────────────────────────────────────────────────
    $('#saveField').on('click', function () {
        clearFieldErrors();

        const fieldId = $('#fieldId').val();
        const isEdit  = !!fieldId;

        const payload = {
            type:             $('#fieldType').val(),
            label:            $('#fieldLabel').val(),
            key:              $('#fieldKey').val(),
            width:            $('#fieldWidth').val(),
            placeholder:      $('#fieldPlaceholder').val(),
            help_text:        $('#fieldHelpText').val(),
            label_position:   $('#fieldLabelPosition').val(),
            is_required:      parseInt($('#fieldRequired').val()) || 0,
            show_char_counter:parseInt($('#fieldCharCounter').val()) || 0,
            html_content:     $('#fieldHtmlContent').val(),
            consent_text:     $('#fieldConsentText').val(),
            min_value:        $('#fieldMinValue').val(),
            max_value:        $('#fieldMaxValue').val(),
            step_value:       $('#fieldStepValue').val(),
            formula:             $('#fieldFormula').val(),
            auto_populate_param: $('#fieldAutoPopulate').val(),
            step_number:         $('#fieldStepNumber').val() || null,
        };

        const options = [];
        $('#optionsList .option-row').each(function () {
            const label = $(this).find('.option-label').val().trim();
            const value = $(this).find('.option-value').val().trim();
            if (label) options.push({ value: value || label, label });
        });
        if (options.length) payload.options = options;

        const likertRows = [];
        $('#likertRowsList .likert-row input').each(function () {
            const v = $(this).val().trim();
            if (v) likertRows.push(v);
        });
        if (likertRows.length) payload.likert_rows = likertRows;

        const translations = {};
        $('.field-trans-label').each(function () {
            const locale = $(this).data('locale');
            const label = $(this).val().trim();
            if (label) {
                translations[locale] = translations[locale] || {};
                translations[locale].label = label;
            }
        });
        $('.field-trans-placeholder').each(function () {
            const locale = $(this).data('locale');
            const ph = $(this).val().trim();
            if (ph) {
                translations[locale] = translations[locale] || {};
                translations[locale].placeholder = ph;
            }
        });
        $('.field-trans-consent').each(function () {
            const locale = $(this).data('locale');
            const ct = $(this).val().trim();
            if (ct) {
                translations[locale] = translations[locale] || {};
                translations[locale].consent_text = ct;
            }
        });
        if (Object.keys(translations).length) payload.translations = translations;

        payload.conditions = collectConditions();

        const url    = isEdit ? `${urls.fieldEditBase}/${fieldId}` : urls.fieldsStore;
        const method = isEdit ? 'PATCH' : 'POST';

        $('#saveField').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url, method,
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                toastr.success(isEdit ? 'Campo actualizado' : 'Campo añadido');
                bootstrap.Modal.getInstance(document.getElementById('fieldModal')).hide();
                reloadFieldsList();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    if (errors.type)  showFieldError('typeError',  '#fieldType',  errors.type[0]);
                    if (errors.label) showFieldError('labelError', '#fieldLabel', errors.label[0]);
                    if (errors.key)   showFieldError('keyError',   '#fieldKey',   errors.key[0]);
                } else {
                    toastr.error('Error al guardar el campo');
                }
            },
            complete: function () {
                $('#saveField').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar campo');
            }
        });
    });

    // ─── Editar campo ──────────────────────────────────────────────────────────
    $(document).on('click', '.btn-edit-field', function () {
        const fieldId = $(this).data('id');

        $.get(`${urls.fieldEditBase}/${fieldId}/edit`, function (res) {
            resetFieldModal();
            populateFieldModal(res.field);
            $('#fieldModalLabel').text('Editar campo');
            $('#fieldId').val(fieldId);
            new bootstrap.Modal(document.getElementById('fieldModal')).show();
        }).fail(function () {
            toastr.error('Error al cargar el campo');
        });
    });

    function populateFieldModal(field) {
        $('#fieldType').val(field.type).trigger('change');
        $('#fieldLabel').val(field.label);
        $('#fieldKey').val(field.key);
        $('#fieldWidth').val(field.width || 'full');
        $('#fieldPlaceholder').val(field.placeholder);
        $('#fieldHelpText').val(field.help_text);
        $('#fieldLabelPosition').val(field.label_position || 'top');
        $('#fieldRequired').val(field.is_required ? '1' : '0');
        $('#fieldCharCounter').val(field.show_char_counter ? '1' : '0');
        $('#fieldHtmlContent').val(field.html_content);
        $('#fieldConsentText').val(field.consent_text);
        $('#fieldMinValue').val(field.min_value ?? 0);
        $('#fieldMaxValue').val(field.max_value ?? 5);
        $('#fieldStepValue').val(field.step_value ?? 1);
        $('#fieldFormula').val(field.formula);
        if (field.step_number) { $('#fieldStepNumber').val(field.step_number); }

        const trans = field.translations || {};
        $('.field-trans-label').each(function () {
            const locale = $(this).data('locale');
            $(this).val(trans[locale]?.label || '');
        });
        $('.field-trans-placeholder').each(function () {
            const locale = $(this).data('locale');
            $(this).val(trans[locale]?.placeholder || '');
        });
        $('.field-trans-consent').each(function () {
            const locale = $(this).data('locale');
            $(this).val(trans[locale]?.consent_text || '');
        });

        if (field.options && Array.isArray(field.options)) {
            field.options.forEach((opt, idx) => {
                const label = typeof opt === 'object' ? (opt.label ?? opt.value ?? '') : opt;
                const value = typeof opt === 'object' ? (opt.value ?? opt.label ?? '') : opt;
                $('#optionsList').append(buildOptionRow(label, value, idx));
            });
        }
        if (field.likert_rows && Array.isArray(field.likert_rows)) {
            field.likert_rows.forEach((row, idx) => {
                $('#likertRowsList').append(
                    `<div class="input-group input-group-sm mb-1 likert-row">
                        <input type="text" class="form-control" name="likert_rows[]" value="${row}" placeholder="Pregunta ${idx + 1}">
                        <button type="button" class="btn btn-outline-danger btn-remove-likert"><i class="fas fa-times"></i></button>
                    </div>`
                );
            });
        }

        populateConditions(field.conditions || []);
    }

    // ─── Eliminar campo ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete-field', function () {
        const fieldId = $(this).data('id');
        const label   = $(this).data('label');
        if (!confirm(`¿Eliminar el campo "${label}"?`)) return;

        $.ajax({
            url: `${urls.fieldEditBase}/${fieldId}`,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success('Campo eliminado');
                $(`#field-item-${fieldId}`).remove();
                updateFieldCount();
                if ($('#fieldsList tr.field-item').length === 0) {
                    $('#emptyState').removeClass('d-none');
                    $('#fieldsTable').addClass('d-none');
                }
            },
            error: function () {
                toastr.error('Error al eliminar el campo');
            }
        });
    });

    // ─── Duplicar campo ───────────────────────────────────────────────────────
    $(document).on('click', '.btn-duplicate-field', function () {
        var btn = $(this);
        var url = btn.data('url');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                if (response.success) {
                    toastr.success('Campo duplicado correctamente.');
                    reloadFieldsList();
                }
            },
            error: function () {
                toastr.error('Error al duplicar el campo.');
                btn.prop('disabled', false).html('<i class="fas fa-copy"></i>');
            }
        });
    });

    // ─── Toggles de protección (honeypot / captcha) ───────────────────────────
    $(document).on('change', '.btn-protection-toggle', function () {
        var field = $(this).data('field');
        var value = parseInt($(this).val()) || 0;
        var payload = {};
        payload[field] = value;

        $.ajax({
            url: '{{ route('settings.forms.protection', $form) }}',
            method: 'PATCH',
            data: payload,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success('Configuración guardada');
            },
            error: function () {
                toastr.error('Error al guardar');
            }
        });
    });

    // ─── Agregar paso ─────────────────────────────────────────────────────────
    $('#btnAddStep').on('click', function () {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Agregando...');

        $.ajax({
            url: '{{ route('settings.forms.steps.add', $form) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                toastr.success('Paso ' + res.step.number + ' agregado correctamente');
                location.reload();
            },
            error: function () {
                toastr.error('Error al agregar el paso');
                btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i> Agregar paso');
            }
        });
    });

    // ─── Resetear modal al cerrarlo ───────────────────────────────────────────
    $('#fieldModal').on('hidden.bs.modal', function () {
        resetFieldModal();
    });

    // ─── QR Modal ─────────────────────────────────────────────────────────────
    $('#btnShowQr').on('click', function () {
        var $img = $('#qrImage');
        if (!$img.attr('src')) {
            $img.attr('src', $img.data('src'));
        }
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    });

    // ─── Copiar shortcode ─────────────────────────────────────────────────────
    $(document).on('click', '.btn-copy-shortcode', function () {
        const shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode).then(function () {
            toastr.success('Shortcode copiado al portapapeles');
        });
    });

    // ─── Filtrar tipos de campo en sidebar ────────────────────────────────────
    $('#fieldTypeSearch').on('input', function () {
        const query = $(this).val().toLowerCase().trim();
        $('.field-type-tile').each(function () {
            const label = $(this).find('span').text().toLowerCase();
            const type  = $(this).data('type').toLowerCase();
            $(this).toggle(!query || label.includes(query) || type.includes(query));
        });
    });

    // ─── Helpers ───────────────────────────────────────────────────────────────
    function resetFieldModal() {
        $('#fieldId').val('');
        $('#fieldType').val('text').trigger('change');
        $('#fieldLabel, #fieldKey, #fieldPlaceholder, #fieldHelpText, #fieldAutoPopulate').val('');
        $('.field-trans-label, .field-trans-placeholder, .field-trans-consent').val('');
        $('#fieldHtmlContent, #fieldConsentText, #fieldFormula').val('');
        $('#fieldWidth').val('full');
        $('#fieldLabelPosition').val('top');
        $('#fieldRequired, #fieldCharCounter').val('0');
        $('#fieldMinValue').val(0);
        $('#fieldMaxValue').val(5);
        $('#fieldStepValue').val(1);
        $('#optionsList, #likertRowsList, #conditionRulesList').empty();
        updateConditionsBadge();
        $('#tab-campo-btn').tab('show');
        clearFieldErrors();
    }

    function clearFieldErrors() {
        ['typeError', 'labelError', 'keyError'].forEach(id => $('#' + id).text('').hide());
        $('#fieldType, #fieldLabel, #fieldKey').removeClass('is-invalid');
    }

    function showFieldError(divId, inputSelector, message) {
        $(inputSelector).addClass('is-invalid');
        $('#' + divId).text(message).show();
    }

    function reloadFieldsList() {
        location.reload();
    }

    function updateFieldCount() {
        const count = $('#fieldsList tr.field-item').length;
        $('#statsFieldCount, #statsFieldCountFooter, #statsFieldCountHeader, #statsFieldCountSidebar').text(count);
    }

    // ── CodeMirror: editores de CSS y JS ──────────────────────────────────────
    const CODE_TABS = ['estilo-tab', 'js-tab'];

    function makeEditorExtraKeys(editor) {
        return {
            'Ctrl-Space': 'autocomplete',
            'Ctrl-/': 'toggleComment',
            'Ctrl-S': function () { saveCodeEditors(); },
            'Ctrl-F': 'findPersistent',
            'Ctrl-H': 'replace',
            'F11': function (ed) {
                ed.setOption('fullScreen', !ed.getOption('fullScreen'));
                $('#btnFullscreen i').toggleClass('fa-expand fa-compress');
            },
            'Esc': function (ed) {
                if (ed.getOption('fullScreen')) {
                    ed.setOption('fullScreen', false);
                    $('#btnFullscreen i').removeClass('fa-compress').addClass('fa-expand');
                }
            },
        };
    }

    const editorCss = CodeMirror.fromTextArea(document.getElementById('custom_css'), {
        mode: 'css',
        theme: 'default',
        lineNumbers: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        styleActiveLine: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: makeEditorExtraKeys(),
    });

    const editorJs = CodeMirror.fromTextArea(document.getElementById('custom_js'), {
        mode: 'javascript',
        theme: 'default',
        lineNumbers: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        styleActiveLine: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: makeEditorExtraKeys(),
    });

    const editorHtml = CodeMirror.fromTextArea(document.getElementById('estructura_html'), {
        mode: 'htmlmixed',
        theme: 'default',
        lineNumbers: true,
        readOnly: true,
        styleActiveLine: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
        extraKeys: { 'F11': function (cm) { cm.setOption('fullScreen', !cm.getOption('fullScreen')); } },
    });

    const htmlSourceUrl = '{{ route('settings.forms.html-source', $form) }}';
    let estructuraLoaded = false;

    function loadEstructuraHtml() {
        editorHtml.setValue('Cargando...');
        $.get(htmlSourceUrl, function (data) {
            const formatted = typeof html_beautify !== 'undefined'
                ? html_beautify(data.html, { indent_size: 4, wrap_line_length: 120 })
                : data.html;
            editorHtml.setValue(formatted);
            editorHtml.refresh();
            estructuraLoaded = true;
        }).fail(function () {
            editorHtml.setValue('Error al cargar el HTML.');
        });
    }

    $('#estructura-tab').on('shown.bs.tab', function () {
        editorHtml.refresh();
        if (!estructuraLoaded) { loadEstructuraHtml(); }
    });

    $('#btnRefreshEstructura').on('click', function () { loadEstructuraHtml(); });

    $('#btnCopyEstructura').on('click', function () {
        const html = editorHtml.getValue();
        if (!html) { return; }
        navigator.clipboard.writeText(html).then(function () {
            toastr.success('HTML copiado al portapapeles.');
        }).catch(function () {
            toastr.error('No se pudo copiar.');
        });
    });

    // Refrescar editor al mostrar su tab
    $('#estilo-tab').on('shown.bs.tab', function () { editorCss.refresh(); });
    $('#js-tab').on('shown.bs.tab', function () { editorJs.refresh(); });

    // Mostrar/ocultar toolbar y botón guardar según el tab activo
    $('#formBuilderTabs button').on('shown.bs.tab', function () {
        const id = $(this).attr('id');
        const isCodeTab = CODE_TABS.includes(id);
        $('#editorToolbar').toggleClass('d-none', !isCodeTab);
        $('#btnSaveCode').toggleClass('d-none', !isCodeTab);
        $('#btnFooterPreview').toggleClass('d-none', isCodeTab);
        $('#keyboardShortcutsCard').toggleClass('d-none', !isCodeTab);
    });

    // Guardar CSS y JS vía AJAX
    function saveCodeEditors() {
        const $btn = $('#btnSaveCode');
        $btn.prop('disabled', true);
        $('#editorStatus').text('Guardando...');

        $.ajax({
            url: '{{ route('settings.forms.update', $form) }}',
            method: 'POST',
            data: {
                _method: 'PATCH',
                _token: csrfToken,
                name: '{{ addslashes($form->name) }}',
                custom_css: editorCss.getValue(),
                custom_js: editorJs.getValue(),
            },
            success: function () {
                $('#editorStatus').text('Guardado');
                toastr.success('Cambios guardados correctamente.');
                setTimeout(function () { $('#editorStatus').text('Listo'); }, 2000);
            },
            error: function () {
                toastr.error('Error al guardar los cambios.');
                $('#editorStatus').text('Error');
            },
            complete: function () {
                $btn.prop('disabled', false);
            },
        });
    }

    $('#btnSaveCode').on('click', saveCodeEditors);

    // Toolbar: obtener editor activo
    function activeCodeEditor() {
        return $('#estilo-tab').hasClass('active') ? editorCss : editorJs;
    }

    // Formatear
    $('#btnFormat').on('click', function () {
        if ($('#estilo-tab').hasClass('active') && typeof css_beautify !== 'undefined') {
            editorCss.setValue(css_beautify(editorCss.getValue(), { indent_size: 2 }));
        } else if ($('#js-tab').hasClass('active') && typeof js_beautify !== 'undefined') {
            editorJs.setValue(js_beautify(editorJs.getValue(), { indent_size: 2 }));
        }
        toastr.info('Código formateado.');
    });

    // Colapsar / Expandir
    $('#btnFoldAll').on('click', function () {
        const ed = activeCodeEditor();
        for (let i = 0; i < ed.lineCount(); i++) { ed.foldCode({ line: i, ch: 0 }); }
    });
    $('#btnUnfoldAll').on('click', function () {
        const ed = activeCodeEditor();
        for (let i = 0; i < ed.lineCount(); i++) { ed.foldCode({ line: i, ch: 0 }, null, 'unfold'); }
    });

    // Ajuste de línea
    let wrapEnabled = false;
    $('#btnWrapLines').on('click', function () {
        wrapEnabled = !wrapEnabled;
        [editorCss, editorJs].forEach(ed => ed.setOption('lineWrapping', wrapEnabled));
        $(this).toggleClass('active', wrapEnabled);
    });

    // Pantalla completa
    $('#btnFullscreen').on('click', function () {
        const ed = activeCodeEditor();
        const isFs = !ed.getOption('fullScreen');
        [editorCss, editorJs].forEach(e => e.setOption('fullScreen', false));
        ed.setOption('fullScreen', isFs);
        $(this).find('i').toggleClass('fa-expand', !isFs).toggleClass('fa-compress', isFs);
    });

    // Tema claro / oscuro
    let editorDark = false;
    $('#btnEditorTheme').on('click', function () {
        editorDark = !editorDark;
        const theme = editorDark ? 'monokai' : 'default';
        [editorCss, editorJs].forEach(ed => ed.setOption('theme', theme));
        $('#editorToolbar').toggleClass('dark', editorDark);
        $(this).toggleClass('active', editorDark);
    });

    // ── Tooltips ─────────────────────────────────────────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ─── Campos disponibles para condiciones ───────────────────────────────────
    window._formFields = @json($form->fields->where('is_visible', true)->map(fn ($f) => ['key' => $f->key, 'label' => $f->label])->values());

    function buildConditionRow(rule) {
        rule = rule || {};
        const fieldsOptions = (window._formFields || []).map(function (f) {
            return '<option value="' + f.key + '"' + (rule.field === f.key ? ' selected' : '') + '>' + f.label + '</option>';
        }).join('');

        const operators = [
            ['equals', 'es igual a'], ['not_equals', 'no es igual a'],
            ['contains', 'contiene'], ['not_empty', 'no está vacío'],
            ['empty', 'está vacío'], ['greater_than', 'mayor que'], ['less_than', 'menor que'],
        ];
        const opOptions = operators.map(function (o) {
            return '<option value="' + o[0] + '"' + (rule.operator === o[0] ? ' selected' : '') + '>' + o[1] + '</option>';
        }).join('');

        const hideValue = ['not_empty', 'empty'].includes(rule.operator);

        return $('<div class="condition-rule d-flex align-items-center gap-2 flex-wrap">' +
            '<select class="form-select form-select-sm cond-field" style="max-width:160px">' + fieldsOptions + '</select>' +
            '<select class="form-select form-select-sm cond-operator" style="max-width:150px">' + opOptions + '</select>' +
            '<input type="text" class="form-control form-control-sm cond-value' + (hideValue ? ' d-none' : '') + '" style="max-width:130px" placeholder="Valor" value="' + (rule.value || '') + '">' +
            '<div class="form-check form-check-inline mb-0 ms-1">' +
                '<input class="form-check-input cond-logic-or" type="checkbox"' + (rule.logic === 'OR' ? ' checked' : '') + ' title="Usar OR en vez de AND">' +
                '<label class="form-check-label small text-muted">O</label>' +
            '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-condition"><i class="fas fa-times"></i></button>' +
        '</div>');
    }

    function collectConditions() {
        const rules = [];
        $('#conditionRulesList .condition-rule').each(function () {
            const field    = $(this).find('.cond-field').val();
            const operator = $(this).find('.cond-operator').val();
            const value    = $(this).find('.cond-value').val();
            const isOr     = $(this).find('.cond-logic-or').is(':checked');
            if (!field) { return; }
            const rule = { field, operator, value };
            if (isOr) { rule.logic = 'OR'; }
            rules.push(rule);
        });
        return rules;
    }

    function populateConditions(conditions) {
        $('#conditionRulesList').empty();
        (conditions || []).forEach(function (rule) {
            $('#conditionRulesList').append(buildConditionRow(rule));
        });
        updateConditionsBadge();
    }

    function updateConditionsBadge() {
        const count = $('#conditionRulesList .condition-rule').length;
        $('#condicionesBadge').text(count).toggleClass('d-none', count === 0);
    }

    $(document).on('click', '#addConditionRule', function () {
        const defaultField = (window._formFields || [])[0];
        $('#conditionRulesList').append(buildConditionRow({ field: defaultField ? defaultField.key : '', operator: 'equals', value: '' }));
        updateConditionsBadge();
    });

    $(document).on('click', '.btn-remove-condition', function () {
        $(this).closest('.condition-rule').remove();
        updateConditionsBadge();
    });

    $(document).on('change', '.cond-operator', function () {
        const hideValue = ['not_empty', 'empty'].includes($(this).val());
        $(this).closest('.condition-rule').find('.cond-value').toggleClass('d-none', hideValue);
    });

    // ─── Auto-translate con DeepL ────────────────────────────────────────────────
    $(document).on('click', '#btnAutoTranslate', function () {
        const fieldId = $('#fieldId').val();
        if (!fieldId) {
            toastr.warning('Guarda el campo primero antes de traducir.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Traduciendo...');

        $.ajax({
            url: urls.fieldBase + '/' + fieldId + '/translate',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (!res.success && !res.translations) {
                    toastr.error(res.message || 'Error al traducir.');
                    return;
                }

                const trans = res.translations || {};
                $('.field-trans-label').each(function () {
                    const locale = $(this).data('locale');
                    if (trans[locale]?.label) $(this).val(trans[locale].label);
                });
                $('.field-trans-placeholder').each(function () {
                    const locale = $(this).data('locale');
                    if (trans[locale]?.placeholder) $(this).val(trans[locale].placeholder);
                });
                $('.field-trans-consent').each(function () {
                    const locale = $(this).data('locale');
                    if (trans[locale]?.consent_text) $(this).val(trans[locale].consent_text);
                });

                const hasErrors = res.errors && Object.keys(res.errors).length;
                if (hasErrors) {
                    toastr.warning(res.message || 'Traducción con errores parciales.');
                } else {
                    toastr.success(res.message || 'Traducciones generadas correctamente.');
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Error al conectar con DeepL.';
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i>Traducir con DeepL');
            },
        });
    });

    // ─── Formula shortcodes ─────────────────────────────────────────────────────
    const allFormFields = @json($form->fields->map(fn ($f) => ['key' => $f->name, 'label' => $f->label, 'type' => $f->type])->values());

    function renderFormulaShortcuts() {
        const $wrap = $('#formulaShortcuts');
        $wrap.empty();
        const numericTypes = ['number', 'slider', 'rating', 'nps', 'calculation'];
        const fields = allFormFields.filter(f => numericTypes.includes(f.type));
        if (!fields.length) {
            $wrap.html('<small class="text-muted fst-italic">Sin campos numéricos aún</small>');
            return;
        }
        fields.forEach(function (f) {
            $('<button>', { type: 'button', class: 'btn btn-sm btn-outline-secondary formula-chip' })
                .html('<code>{' + f.key + '}</code> <span class="text-muted formula-chip-label">' + f.label + '</span>')
                .on('click', function () {
                    const el = document.getElementById('fieldFormula');
                    const sc = '{' + f.key + '}';
                    const s = el.selectionStart, e = el.selectionEnd;
                    el.value = el.value.substring(0, s) + sc + el.value.substring(e);
                    el.focus();
                    el.setSelectionRange(s + sc.length, s + sc.length);
                })
                .appendTo($wrap);
        });
    }

    renderFormulaShortcuts();
    $(document).on('shown.bs.tab', '#constructor-tab', renderFormulaShortcuts);

    // Advanced options chevron toggle
    $('#advancedOptions').on('show.bs.collapse hide.bs.collapse', function () {
        $('#btnAdvancedOptions .advanced-toggle-icon').toggleClass('fa-chevron-down fa-chevron-up');
    });

    // Update type badge in modal header
    const typeIconMap = {
        text: 'fas fa-font', textarea: 'fas fa-align-left', email: 'fas fa-at',
        phone: 'fas fa-phone', number: 'fas fa-hashtag', date: 'fas fa-calendar-alt',
        time: 'fas fa-clock', url: 'fas fa-link', select: 'fas fa-caret-square-down',
        radio: 'far fa-dot-circle', checkbox: 'far fa-check-square', image_choice: 'far fa-image',
        file: 'fas fa-paperclip', rating: 'fas fa-star', slider: 'fas fa-sliders-h',
        nps: 'fas fa-chart-bar', likert: 'fas fa-table', signature: 'fas fa-signature',
        calculation: 'fas fa-calculator', address: 'fas fa-map-marker-alt',
        section_header: 'fas fa-heading', html_block: 'fas fa-code',
        divider: 'fas fa-minus', spacer: 'fas fa-arrows-alt-v',
        consent: 'fas fa-user-check', newsletter_consent: 'fas fa-newspaper',
        hidden: 'far fa-eye-slash', password: 'fas fa-key', color_picker: 'fas fa-palette',
    };
    const typeLabels = {
        text: 'Texto corto', textarea: 'Texto largo', email: 'Email', phone: 'Teléfono',
        number: 'Número', date: 'Fecha', time: 'Hora', url: 'URL', select: 'Select',
        radio: 'Radio', checkbox: 'Checkbox', image_choice: 'Imagen', file: 'Archivo',
        rating: 'Rating', slider: 'Slider', nps: 'NPS', likert: 'Likert',
        signature: 'Firma', calculation: 'Cálculo', address: 'Dirección',
        section_header: 'Sección', html_block: 'HTML', divider: 'Divisor',
        spacer: 'Espacio', consent: 'Consentimiento', newsletter_consent: 'Newsletter',
        hidden: 'Oculto', password: 'Contraseña', color_picker: 'Color',
    };
    function updateTypeBadge(type) {
        const icon = typeIconMap[type] || 'fas fa-question';
        const label = typeLabels[type] || type;
        $('#fieldTypeBadge').html('<i class="' + icon + ' me-1"></i>' + label);
    }
    $('#fieldType').on('change', function () { updateTypeBadge($(this).val()); });

    // ─── Configuración del formulario ─────────────────────────────────────────
    $('.config-select2').select2({ width: '100%', minimumResultsForSearch: Infinity });

    $('#configSendConfirmation').on('change', function () {
        $('#confirmationFields').toggleClass('d-none', $(this).val() !== '1');
    });

    $('#btnSaveConfig').on('click', function () {
        const $btn = $(this);
        const $text = $btn.find('.btn-save-text');
        $btn.prop('disabled', true);
        $text.text('Guardando...');

        $.ajax({
            url: '{{ route('settings.forms.update', $form) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: {
                _method: 'PATCH',
                name:                     $('#configName').val(),
                description:              $('#configDescription').val(),
                category_id:              $('#configCategory').val() || '',
                is_active:                $('#configIsActive').val(),
                submit_button_text:       $('#configButtonText').val(),
                button_position:          $('#configButtonPosition').val(),
                success_message:          $('#configSuccessMessage').val(),
                redirect_url:             $('#configRedirectUrl').val(),
                success_animation:        $('#configSuccessAnimation').val(),
                admin_notification_email: $('#configAdminEmail').val(),
                send_confirmation:        parseInt($('#configSendConfirmation').val()) || 0,
                email_field_key:          $('#configEmailFieldKey').val(),
                confirmation_subject:     $('#configConfirmationSubject').val(),
                confirmation_message:     $('#configConfirmationMessage').val(),
                max_submissions:          $('#configMaxSubmissions').val() || '',
                retention_days:           $('#configRetentionDays').val() || '',
                webhook_url:              $('#configWebhookUrl').val(),
                floating_label:           parseInt($('#configFloatingLabel').val()) || 0,
                honeypot_enabled:         parseInt($('#honeypotToggle').val()) || 0,
                captcha_enabled:          parseInt($('#captchaToggle').val()) || 0,
                custom_css:               typeof editorCss !== 'undefined' ? editorCss.getValue() : '',
                custom_js:                typeof editorJs !== 'undefined' ? editorJs.getValue() : '',
            },
            success: function (res) {
                toastr.success('Configuración guardada correctamente.');
                if (res.name) {
                    $('h5.mb-0.fw-bold').first().text(res.name);
                    $('#formTitleDisplay').text(res.name);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    const first = Object.values(errors)[0]?.[0];
                    toastr.error(first || 'Por favor corrige los errores.');
                } else {
                    toastr.error('Error al guardar la configuración.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
                $text.text('Guardar configuración');
            },
        });
    });
</script>
