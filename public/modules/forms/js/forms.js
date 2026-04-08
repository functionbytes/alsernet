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
        const $step = $form.find('[data-step="' + step + '"]');
        let valid = true;

        $step.find(':input[required]:visible').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('Este campo es obligatorio');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
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
            $(this).toggleClass('active', parseInt($(this).data('step')) <= targetStep);
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
            ignore: ':hidden:not([name="_hp"]), .forms-calculation',
            errorElement: 'div',
            errorClass: 'invalid-feedback',
            highlight: function(el) { $(el).addClass('is-invalid'); },
            unhighlight: function(el) { $(el).removeClass('is-invalid'); },
            errorPlacement: function(error, element) {
                element.closest('.col-12, .col-md-6, .col-md-4, .col-md-3').append(error);
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

})(jQuery);
