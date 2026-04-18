/**
 * Forms Module - Main JavaScript
 * Handles: form submission, multi-step navigation, conditional fields,
 * calculation fields, signature canvas, NPS, rating stars, image choice,
 * character counters, abandon tracking, auto-populate from URL params
 */
(function($) {
    'use strict';

    // ==========================================
    // INICIALIZACIÓN
    // ==========================================
    $(document).ready(function() {
        Object.keys(window.FormsConfig || {}).forEach(function(formId) {
            initForm(formId, window.FormsConfig[formId]);
        });
    });

    function initForm(formId, config) {
        const $form = $('#' + formId);
        if (!$form.length) return;

        initStartTime($form, formId);
        initAutoPopulate($form, config);
        initSelect2($form);
        initServiceCards($form);
        initConditionalFields($form, config);
        initCalculationFields($form, config);
        initSignatureFields($form);
        initRatingFields($form);
        initNpsFields($form);
        initImageChoice($form);
        initCharCounters($form);
        initColorPickers($form);
        initAbandonTracking($form, config);
        initValidation($form, config);
        initMultiStep($form, config);
        initEstimateEnhancements($form);
    }

    // ==========================================
    // TIEMPO DE COMPLETADO
    // ==========================================
    function initStartTime($form, formId) {
        const startTime = parseInt($form.find('[name="_start_time"]').val()) || Math.floor(Date.now() / 1000);
        $form.on('submit', function() {
            const elapsed = Math.floor(Date.now() / 1000) - startTime;
            $('#' + formId + '-time').val(elapsed);
        });
    }

    // ==========================================
    // AUTO-POPULATE DESDE URL PARAMS
    // ==========================================
    function initAutoPopulate($form, config) {
        const urlParams = new URLSearchParams(window.location.search);
        $form.find('[data-auto-populate]').each(function() {
            const param = $(this).data('auto-populate');
            const value = urlParams.get(param);
            if (value) $(this).val(value);
        });
    }

    // ==========================================
    // SERVICE CARDS (radio con data-icon)
    // ==========================================
    function initServiceCards($form) {
        var $radiosWithIcon = $form.find('[type="radio"][data-icon]');
        if (!$radiosWithIcon.length) return;

        $radiosWithIcon.each(function() {
            var $input = $(this);
            var icon   = $input.data('icon');
            var desc   = $input.data('description') || '';
            var $wrap  = $input.closest('.form-check');
            var $label = $wrap.find('label.form-check-label');
            var title  = $label.text().trim();

            $input.addClass('service-radio-input');
            $label.html(
                '<i class="' + icon + '"></i>' +
                '<h5>' + title + '</h5>' +
                (desc ? '<small class="d-block text-muted mt-1">' + desc + '</small>' : '')
            ).addClass('service-card-option');
            $wrap.addClass('service-option');

            $label.on('click', function() {
                $form.find('.service-card-option').removeClass('selected');
                $(this).addClass('selected');
            });
            if ($input.is(':checked')) $label.addClass('selected');
        });

        // Envolver todas las .service-option en un grid .service-selection
        var $options = $form.find('.service-option');
        var $grid = $('<div class="service-selection"></div>');
        $options.first().before($grid);
        $grid.append($options);
    }

    // ==========================================
    // SELECT2
    // ==========================================
    function initSelect2($form) {
        if (!$.fn.select2) return;

        if ($.fn.niceSelect) {
            $form.find('select').niceSelect('destroy');
        }

        $form.find('select').each(function() {
            var $select = $(this);
            var placeholder = $select.data('form-field-placeholder') || $select.find('option[value=""]').first().text() || '-- Seleccionar --';

            $select.select2({
                width: '100%',
                minimumResultsForSearch: Infinity,
                dropdownParent: $select.closest('.forms-wrapper'),
                placeholder: placeholder,
            });

            $select.on('change.select2validate', function() {
                if ($select.closest('form').data('validator')) {
                    $select.valid();
                }
            });
        });
    }

    // ==========================================
    // CAMPOS CONDICIONALES (show/hide)
    // ==========================================
    function initConditionalFields($form, config) {
        if (!config.conditions || !config.conditions.length) return;

        function evaluateConditions() {
            config.conditions.forEach(function(fieldConfig) {
                if (!fieldConfig.conditions || !fieldConfig.conditions.length) return;

                const $wrapper = $form.find('[name="' + fieldConfig.key + '"], [name="' + fieldConfig.key + '[]"]').closest('[data-conditions]');
                if (!$wrapper.length) return;

                let allMet = true;
                fieldConfig.conditions.forEach(function(cond) {
                    const $source = $form.find('[name="' + cond.field + '"], [name="' + cond.field + '[]"]');
                    let sourceVal = $source.is(':checkbox, :radio')
                        ? $form.find('[name="' + cond.field + '"]:checked, [name="' + cond.field + '[]"]:checked').map(function() { return this.value; }).get().join(',')
                        : $source.val();

                    let met = false;
                    switch (cond.operator || 'equals') {
                        case 'equals':       met = sourceVal == cond.value; break;
                        case 'not_equals':   met = sourceVal != cond.value; break;
                        case 'contains':     met = (sourceVal || '').includes(cond.value); break;
                        case 'not_empty':    met = !!(sourceVal || '').trim(); break;
                        case 'empty':        met = !(sourceVal || '').trim(); break;
                        case 'greater_than': met = parseFloat(sourceVal) > parseFloat(cond.value); break;
                        case 'less_than':    met = parseFloat(sourceVal) < parseFloat(cond.value); break;
                    }

                    if (cond.logic === 'OR') {
                        if (met) allMet = true;
                    } else {
                        if (!met) allMet = false;
                    }
                });

                $wrapper.toggleClass('d-none', !allMet);
                $wrapper.find(':input').prop('disabled', !allMet);
            });
        }

        $form.on('change input', ':input', evaluateConditions);
        evaluateConditions();
    }

    // ==========================================
    // CAMPOS CALCULADOS
    // ==========================================
    function initCalculationFields($form, config) {
        if (!config.calculationFields || !config.calculationFields.length) return;

        function recalculate() {
            config.calculationFields.forEach(function(calcField) {
                let formula = calcField.formula || '';
                formula = formula.replace(/\{(\w+)\}/g, function(match, fieldKey) {
                    return parseFloat($form.find('[name="' + fieldKey + '"]').val()) || 0;
                });
                try {
                    const result = Function('"use strict"; return (' + formula + ')')();
                    $form.find('[name="' + calcField.key + '"]').val(isNaN(result) ? '' : Math.round(result * 100) / 100);
                } catch (e) {}
            });
        }

        $form.on('input change', ':input:not(.forms-calculation)', recalculate);
    }

    // ==========================================
    // FIRMA DIGITAL (Canvas)
    // ==========================================
    function initSignatureFields($form) {
        $form.find('.forms-signature-canvas').each(function() {
            const canvas = this;
            const ctx = canvas.getContext('2d');
            const fieldKey = $(canvas).data('field');
            const $hidden = $form.find('[name="' + fieldKey + '"]');
            let drawing = false;
            let lastX = 0;
            let lastY = 0;

            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';

            function getPos(e) {
                const rect = canvas.getBoundingClientRect();
                const event = e.touches ? e.touches[0] : e;
                return {
                    x: (event.clientX - rect.left) * (canvas.width / rect.width),
                    y: (event.clientY - rect.top) * (canvas.height / rect.height)
                };
            }

            $(canvas)
                .on('mousedown touchstart', function(e) {
                    e.preventDefault();
                    drawing = true;
                    const pos = getPos(e);
                    lastX = pos.x;
                    lastY = pos.y;
                })
                .on('mousemove touchmove', function(e) {
                    e.preventDefault();
                    if (!drawing) return;
                    const pos = getPos(e);
                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();
                    lastX = pos.x;
                    lastY = pos.y;
                    $hidden.val(canvas.toDataURL('image/png'));
                })
                .on('mouseup touchend', function() {
                    drawing = false;
                });
        });

        $form.on('click', '.forms-signature-clear', function() {
            const canvasId = $(this).data('target');
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            $form.find('[name="' + $(canvas).data('field') + '"]').val('');
        });
    }

    // ==========================================
    // ESTRELLAS (RATING)
    // ==========================================
    function initRatingFields($form) {
        $form.on('click', '.forms-star', function() {
            const $rating = $(this).closest('.forms-rating');
            const value = parseInt($(this).data('value'));
            const fieldKey = $rating.data('field');

            $rating.find('.forms-star').each(function(i) {
                $(this).toggleClass('fas active', i < value).toggleClass('far', i >= value);
            });
            $form.find('[name="' + fieldKey + '"]').val(value);
        });

        $form.on('mouseenter', '.forms-star', function() {
            const $rating = $(this).closest('.forms-rating');
            const value = parseInt($(this).data('value'));
            $rating.find('.forms-star').each(function(i) {
                $(this).toggleClass('fas', i < value).toggleClass('far', i >= value);
            });
        });

        $form.on('mouseleave', '.forms-rating', function() {
            const $rating = $(this);
            const fieldKey = $rating.data('field');
            const currentVal = parseInt($form.find('[name="' + fieldKey + '"]').val()) || 0;
            $rating.find('.forms-star').each(function(i) {
                $(this).toggleClass('fas active', i < currentVal).toggleClass('far', i >= currentVal);
            });
        });
    }

    // ==========================================
    // NPS
    // ==========================================
    function initNpsFields($form) {
        $form.on('click', '.forms-nps-btn', function() {
            const $btn = $(this);
            const fieldKey = $btn.data('field');
            const value = $btn.data('value');

            $btn.closest('.forms-nps').find('.forms-nps-btn').removeClass('selected').css({ color: '', 'background-color': '' });
            $btn.addClass('selected');
            $form.find('[name="' + fieldKey + '"]').val(value);
        });
    }

    // ==========================================
    // IMAGE CHOICE
    // ==========================================
    function initImageChoice($form) {
        $form.on('click', '.forms-image-choice-item', function() {
            const $item = $(this);
            const $wrapper = $item.closest('.forms-image-choice');
            const fieldKey = $wrapper.data('field');

            $wrapper.find('.forms-image-choice-item').removeClass('selected');
            $item.addClass('selected');
            $form.find('[name="' + fieldKey + '"]').val($item.data('value'));
        });
    }

    // ==========================================
    // CONTADOR DE CARACTERES
    // ==========================================
    function initCharCounters($form) {
        $form.on('input', '[maxlength], textarea[maxlength]', function() {
            const $input = $(this);
            const id = $input.attr('id');
            const max = $input.attr('maxlength');
            const $counter = $form.find('.forms-char-counter[data-target="' + id + '"]');
            if (!$counter.length) return;

            const len = $input.val().length;
            $counter.text(len + (max ? '/' + max : '') + ' caracteres');
            $counter.toggleClass('text-danger', !!(max && len >= parseInt(max)));
        });
    }

    // ==========================================
    // COLOR PICKER
    // ==========================================
    function initColorPickers($form) {
        $form.on('input change', 'input[type="color"]', function () {
            $(this).closest('.d-flex').find('.color-picker-value').text($(this).val());
        });
    }

    // ==========================================
    // ABANDON TRACKING
    // ==========================================
    function initAbandonTracking($form, config) {
        if (!config.abandonTrackingEnabled) return;

        let abandonTimer = null;

        $form.on('change input', ':input:not([type=hidden])', function() {
            clearTimeout(abandonTimer);
            const $lastField = $(this);
            abandonTimer = setTimeout(function() {
                const partialData = {};
                $form.serializeArray().forEach(function(item) {
                    if (!item.name.startsWith('_')) partialData[item.name] = item.value;
                });

                $.ajax({
                    url: config.abandonUrl,
                    method: 'POST',
                    data: {
                        session_token: config.sessionToken,
                        partial_data: JSON.stringify(partialData),
                        email: $form.find('[type="email"]').first().val() || null,
                        current_step: getCurrentStep($form),
                        last_field_key: $lastField.attr('name') || ''
                    },
                    headers: { 'X-CSRF-TOKEN': config.csrfToken }
                });
            }, 3000);
        });

        // Restaurar datos si existen
        $.ajax({
            url: config.restoreUrl + '?token=' + config.sessionToken,
            method: 'GET',
            success: function(res) {
                if (!res.success || !res.data) return;
                Object.keys(res.data).forEach(function(key) {
                    const $input = $form.find('[name="' + key + '"]');
                    if ($input.length && !$input.val()) {
                        $input.val(res.data[key]).trigger('change');
                    }
                });
            }
        });
    }

    // ==========================================
    // MULTI-PASO
    // ==========================================
    function initMultiStep($form, config) {
        if (!config.isMultiStep) return;

        $form.on('click', '.forms-next-btn', function() {
            const currentStep = parseInt($(this).data('step'));
            if (validateStep($form, currentStep)) {
                showStep($form, config, currentStep + 1);
            }
        });

        $form.on('click', '.forms-prev-btn', function() {
            const currentStep = parseInt($(this).data('step'));
            showStep($form, config, currentStep - 1);
        });
    }

    function validateStep($form, step) {
        var $step = $form.find('[data-step="' + step + '"]');
        var validator = $form.data('validator');
        var valid = true;

        $step.find(':input').not('[type=hidden][name!="_hp"]').not('.forms-calculation').each(function() {
            var $el = $(this);
            if ($el.is('select') || $el.is(':visible')) {
                if (validator && !validator.element(this)) {
                    valid = false;
                }
            }
        });

        return valid;
    }

    function showStep($form, config, targetStep) {
        const formId = $form.attr('id');

        $form.find('.forms-step').addClass('d-none').removeClass('forms-step-active');
        $form.find('[data-step="' + targetStep + '"]').removeClass('d-none').addClass('forms-step-active');

        // Actualizar progress bar
        const progress = (targetStep / config.totalSteps) * 100;
        $('#' + formId + '-progress-bar').css('width', progress + '%');
        $('#' + formId + '-current-step').text(targetStep);

        // Actualizar dots/steps
        const $wrapper = $form.closest('.forms-wrapper');
        $wrapper.find('.forms-step-dot').each(function() {
            $(this).toggleClass('active', parseInt($(this).data('step')) <= targetStep);
        });
        $wrapper.find('.forms-step-label').each(function() {
            const s = parseInt($(this).data('step'));
            $(this).toggleClass('active', s <= targetStep);
            const completed = s < targetStep;
            $(this).toggleClass('step-completed', completed);
            const $num = $(this).find('.forms-step-number');
            if (completed) {
                if (!$num.find('i').length) $num.html('<i class="fa-solid fa-check" style="font-size:13px;"></i>');
            } else {
                if ($num.find('i').length) $num.text(s);
            }
        });

        applyLogicJumps($form, config, targetStep);
    }

    function applyLogicJumps($form, config, currentStep) {
        config.conditions.forEach(function(fieldConfig) {
            if (!fieldConfig.logicJumps || !fieldConfig.logicJumps.length) return;
            fieldConfig.logicJumps.forEach(function(jump) {
                const val = $form.find('[name="' + jump.field + '"]').val();
                if (val == jump.value) {
                    showStep($form, config, jump.go_to_step);
                }
            });
        });
    }

    function getCurrentStep($form) {
        const $activeStep = $form.find('.forms-step-active');
        return $activeStep.length ? parseInt($activeStep.data('step')) : 1;
    }

    // ==========================================
    // VALIDACIÓN Y SUBMIT
    // ==========================================
    function initValidation($form, config) {
        $form.validate({
            ignore: ':hidden:not(select):not([name="_hp"]), .forms-calculation',
            errorElement: 'div',
            errorClass: 'invalid-feedback',
            highlight: function(el) {
                var $el = $(el);
                $el.addClass('is-invalid');
                if ($el.is('select') && $el.data('select2')) {
                    $el.next('.select2-container').addClass('is-invalid');
                }
            },
            unhighlight: function(el) {
                var $el = $(el);
                $el.removeClass('is-invalid');
                if ($el.is('select') && $el.data('select2')) {
                    $el.next('.select2-container').removeClass('is-invalid');
                }
            },
            errorPlacement: function(error, element) {
                var $wrapper = element.closest('[class*="col-"]');
                if (!$wrapper.length) $wrapper = element.parent();
                var $fb = $wrapper.find('.invalid-feedback').first();
                if ($fb.length) {
                    $fb.text(error.text()).addClass('d-block');
                    return;
                }
                error.appendTo($wrapper);
            },
            success: function(label, element) {
                var $wrapper = $(element).closest('[class*="col-"]');
                if (!$wrapper.length) $wrapper = $(element).parent();
                $wrapper.find('.invalid-feedback').text('').removeClass('d-block');
            },
            submitHandler: function(form) {
                handleSubmit($(form), config);
            }
        });
    }

    function handleSubmit($form, config) {
        const $submitBtn = $form.find('.forms-submit-btn');
        $submitBtn.prop('disabled', true);
        $submitBtn.find('.forms-submit-text').addClass('d-none');
        $submitBtn.find('.forms-submit-spinner').removeClass('d-none');

        $.ajax({
            url: config.submitUrl,
            method: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': config.csrfToken },
            success: function(res) {
                if (res.redirect) {
                    window.location.href = res.redirect;
                    return;
                }
                showSuccessAnimation($form, config);
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false);
                $submitBtn.find('.forms-submit-text').removeClass('d-none');
                $submitBtn.find('.forms-submit-spinner').addClass('d-none');

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showValidationErrors($form, xhr.responseJSON.errors);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Ha ocurrido un error al enviar el formulario. Por favor, inténtalo de nuevo.');
                    } else {
                        alert('Ha ocurrido un error al enviar el formulario. Por favor, inténtalo de nuevo.');
                    }
                }
            }
        });
    }

    function showValidationErrors($form, errors) {
        $.each(errors, function(field, messages) {
            const cleanField = field.replace(/\.\d+$/, '');
            const $input = $form.find('[name="' + cleanField + '"], [name="' + cleanField + '[]"]').first();
            $input.addClass('is-invalid');
            $input.closest('.col-12, .col-md-6, .col-md-4, .col-md-3').find('.invalid-feedback').text(messages[0]);
        });

        const $firstError = $form.find('.is-invalid').first();
        if ($firstError.length) {
            $('html,body').animate({ scrollTop: $firstError.offset().top - 100 }, 400);
        }
    }

    function showSuccessAnimation($form, config) {
        const formId = $form.attr('id');
        const $success = $('#' + formId + '-success');

        if (config.successAnimation === 'confetti') {
            $form.fadeOut(400, function() {
                $success.removeClass('d-none').hide().fadeIn(400);
            });
            launchConfetti();
            return;
        }

        // fade (default) or checkmark
        $form.fadeOut(400, function() {
            $success.removeClass('d-none').hide().fadeIn(400);
        });
    }

    function launchConfetti() {
        const canvas = document.createElement('canvas');
        canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const colors = ['#90bb13', '#13C672', '#FEC90F', '#FA896B'];
        const particles = Array.from({ length: 80 }, function() {
            return {
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height - canvas.height,
                r: Math.random() * 6 + 2,
                d: Math.random() * 80 + 10,
                color: colors[Math.floor(Math.random() * 4)],
                tilt: Math.floor(Math.random() * 10) - 10,
            };
        });

        let angle = 0;
        let frame = 0;

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            angle += 0.01;
            particles.forEach(function(p) {
                ctx.beginPath();
                ctx.fillStyle = p.color;
                ctx.ellipse(p.x + Math.sin(angle) * p.d, p.y += 2, p.r, p.r / 2, p.tilt, 0, Math.PI * 2);
                ctx.fill();
                if (p.y > canvas.height) {
                    p.y = -10;
                    p.x = Math.random() * canvas.width;
                }
            });
            if (++frame < 150) {
                requestAnimationFrame(draw);
            } else {
                canvas.remove();
            }
        }

        draw();
    }

    // ==========================================
    // ESTIMATE FORM ENHANCEMENTS (presupuesto)
    // ==========================================
    function initEstimateEnhancements($form) {
        if (!$form.closest('.estimate-form-card').length) return;

        var STEP_META = [
            { title: 'Sus datos de contacto',        desc: 'Paso 1 de 5 \u2014 Rellene sus datos para que podamos enviarle el presupuesto personalizado y coordinar una visita t\u00e9cnica gratuita.' },
            { title: '\u00bfQu\u00e9 proyecto tiene en mente?',  desc: 'Paso 2 de 5 \u2014 Seleccione el servicio principal que desea presupuestar. Si necesita m\u00e1s de uno, puede indicarlo en el paso siguiente.' },
            { title: 'Detalles del proyecto',         desc: 'Paso 3 de 5 \u2014 Cu\u00e9ntenos las caracter\u00edsticas b\u00e1sicas del proyecto. Cuanta m\u00e1s informaci\u00f3n nos facilite, m\u00e1s preciso y ajustado ser\u00e1 el presupuesto.' },
            { title: 'Informaci\u00f3n adicional',    desc: 'Paso 4 de 5 \u2014 A\u00f1ada cualquier detalle relevante sobre su proyecto: materiales, preferencias, plazos o dudas t\u00e9cnicas. Tambi\u00e9n puede solicitar visita gratuita o consultar opciones de financiaci\u00f3n.' },
        ];

        // 1. Inject step titles/descriptions into each step panel
        $form.find('.forms-step').each(function(i) {
            var meta = STEP_META[i];
            if (!meta) return;
            $(this).prepend(
                '<h3 class="estimate-step-title">' + meta.title + '</h3>' +
                '<p class="estimate-step-desc">' + meta.desc + '</p>'
            );
        });

        // 1b. Step 4 enhancements: textarea help text, checkbox layout, como-conocio help
        var $step4 = $form.find('.forms-step[data-step="4"]');
        if ($step4.length) {
            // Add help text above "Mensaje / Observaciones" textarea
            var $mensajeLabel = $step4.find('label[for*="mensaje"]');
            if ($mensajeLabel.length && !$mensajeLabel.next('.estimate-field-hint').length) {
                $mensajeLabel.after(
                    '<p class="estimate-field-hint text-muted small mb-2">' +
                    'Describa su proyecto en detalle: preferencias de materiales, colores, acabados, ' +
                    'plazos espec\u00edficos, dudas t\u00e9cnicas, etc. Cuanta m\u00e1s informaci\u00f3n nos proporcione, ' +
                    'm\u00e1s preciso ser\u00e1 el presupuesto.</p>'
                );
            }

            // Checkboxes: full-width, bold title, move help_text small inside label
            $step4.find('[class*="col"]:has(.form-check)').each(function() {
                var $col = $(this);
                $col.removeClass('col-md-6').addClass('col-12');
                $col.find('.form-check').each(function() {
                    var $check   = $(this);
                    var $label   = $check.find('.form-check-label');
                    var $helpSmall = $check.nextAll('small').first();
                    var txt      = $label.text().trim();
                    var descHtml = $helpSmall.length ? $helpSmall.prop('outerHTML').replace(/class="[^"]*"/, 'class="d-block text-muted"') : '';
                    $label.html('<strong>' + txt + '</strong>' + descHtml);
                    $helpSmall.remove();
                });
            });
        }

        // 2. Convert last step's submit button to "Siguiente", add virtual step 5
        var $lastStep = $form.find('.forms-step').last();
        var lastStepNum = parseInt($lastStep.data('step'));
        var $submitBtn = $lastStep.find('.forms-submit-btn');

        if ($submitBtn.length) {
            $submitBtn.replaceWith(
                '<button type="button" class="btn btn-primary forms-next-btn w-100 mt-2" data-step="' + lastStepNum + '">' +
                'Siguiente <i class="fa-solid fa-arrow-right ms-1"></i></button>'
            );
        }

        // 3. Add step 5 to the step indicator
        var $progressDflex = $form.closest('.forms-wrapper').find('.forms-progress > .d-flex');
        $progressDflex.append(
            '<div class="forms-step-label" data-step="5">' +
            '<span class="forms-step-number">5</span>' +
            '<span class="forms-step-name">Env\u00edo</span>' +
            '</div>'
        );

        // 4. Read WA phone from existing link in the page
        var waPhone = '';
        $('a[href*="wa.me/"]').each(function() {
            var m = ($(this).attr('href') || '').match(/wa\.me\/([0-9]+)/);
            if (m) { waPhone = m[1]; return false; }
        });

        // 5. Build and append the virtual step 5
        var $step5 = $(
            '<div class="forms-step d-none" data-step="5">' +
                '<h3 class="estimate-step-title">Revise y env\u00ede su solicitud</h3>' +
                '<p class="estimate-step-desc">Paso 5 de 5 \u2014 Compruebe el resumen de su solicitud y elija c\u00f3mo prefiere recibir su presupuesto completamente gratuito.</p>' +
                '<div class="estimate-info-box mb-4">' +
                    '<div class="d-flex">' +
                        '<i class="fa-solid fa-circle-check text-success fs-5 me-3 mt-1"></i>' +
                        '<div>' +
                            '<h6 class="mb-2 fw-bold">Resumen de su solicitud</h6>' +
                            '<div class="estimate-resumen"></div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<h5 class="mb-3">Elija c\u00f3mo desea recibir su presupuesto:</h5>' +
                '<div class="submit-options">' +
                    '<button type="button" class="btn-estimate-submit btn-whatsapp estimate-wa-submit">' +
                        '<i class="fa-brands fa-whatsapp"></i> Enviar v\u00eda WhatsApp</button>' +
                    '<button type="submit" class="btn-estimate-submit btn-email-submit forms-submit-btn">' +
                        '<span class="forms-submit-text"><i class="fa-solid fa-envelope"></i> Enviar por Email</span>' +
                        '<span class="forms-submit-spinner d-none"><span class="spinner-border spinner-border-sm" role="status"></span></span>' +
                    '</button>' +
                '</div>' +
                '<p class="text-center text-muted mt-3 mb-0 small">Al enviar este formulario, acepta que contactemos con usted para proporcionarle un presupuesto</p>' +
                '<div class="mt-3">' +
                    '<button type="button" class="btn-estimate-prev btn-estimate-prev-full forms-prev-btn" data-step="5">' +
                        '<i class="fa-solid fa-arrow-left"></i> Volver al paso anterior</button>' +
                '</div>' +
            '</div>'
        );
        $form.append($step5);

        // 6. Build summary when navigating to step 5
        $form.on('click', '.forms-next-btn[data-step="' + lastStepNum + '"]', function() {
            var fields = [
                { label: 'Nombre',            name: 'nombre' },
                { label: 'Email',             name: 'email' },
                { label: 'Tel\u00e9fono',     name: 'telefono' },
                { label: 'Localidad',         name: 'localidad' },
                { label: 'Servicio',          name: 'servicio', radio: true },
                { label: 'Tipo inmueble',     name: 'tipo_inmueble' },
                { label: 'Tipo obra',         name: 'tipo_obra' },
                { label: 'Cantidad',          name: 'cantidad' },
                { label: 'Urgencia',          name: 'urgencia' },
                { label: 'Presupuesto aprox.', name: 'presupuesto_aprox' },
                { label: 'Mensaje',           name: 'mensaje' },
            ];
            var rows = '';
            fields.forEach(function(f) {
                var val = f.radio
                    ? $form.find('[name="' + f.name + '"]:checked').val()
                    : $form.find('[name="' + f.name + '"]').val();
                if (val && val.trim() && val !== 'Prefiero no especificar') {
                    rows += '<div class="mb-1"><strong>' + f.label + ':</strong> ' +
                        $('<span>').text(val).html() + '</div>';
                }
            });
            $step5.find('.estimate-resumen').html(rows || '<em class="text-muted">Sin informaci\u00f3n adicional</em>');
        });

        // 7. WhatsApp submit
        $form.on('click', '.estimate-wa-submit', function() {
            var t = '\ud83c\udfe0 *Solicitud de Presupuesto \u2014 Caixilharia Blanco*\n\n';
            t += '\ud83d\udc64 *Nombre:* '    + ($form.find('[name="nombre"]').val() || '\u2014')    + '\n';
            t += '\ud83d\udce7 *Email:* '     + ($form.find('[name="email"]').val() || '\u2014')     + '\n';
            t += '\ud83d\udcf1 *Tel\u00e9fono:* '  + ($form.find('[name="telefono"]').val() || '\u2014')  + '\n';
            t += '\ud83d\udccd *Localidad:* ' + ($form.find('[name="localidad"]').val() || '\u2014') + '\n';
            t += '\ud83d\udd27 *Servicio:* '  + ($form.find('[name="servicio"]:checked').val() || '\u2014') + '\n';
            var ms = $form.find('[name="mensaje"]').val();
            if (ms) t += '\n\ud83d\udcdd *Mensaje:* ' + ms;
            window.open('https://wa.me/' + waPhone + '?text=' + encodeURIComponent(t), '_blank');
        });
    }

})(jQuery);
