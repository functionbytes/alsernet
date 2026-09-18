/**
 * Helpdesk · Settings → Broadcasts (crear). Wizard de 4 pasos (segmento,
 * contenido, revision, confirmacion): navegacion, resumen en vivo y vista
 * previa del mensaje. Si el servidor vuelve con errores de validacion,
 * window.HdBroadcastCreateConfig.initialStep indica en que paso mostrarlos
 * (calculado en Blade porque depende de $errors). Select2 y el flash de
 * sesion los cubre settings-common.js.
 */
(function ($) {
    'use strict';

    $(function () {
        const config = window.HdBroadcastCreateConfig || {};

        let currentStep = 1;
        const totalSteps = 4;

        function updateStepper(step) {
            $('.stepper-step').each(function () {
                const stepNum = parseInt($(this).data('step'));
                const badge = $(this).find('.step-badge');
                const label = $(this).find('.step-label');

                if (stepNum === step) {
                    badge.removeClass('bg-light text-muted').addClass('bg-primary');
                    label.removeClass('text-muted').addClass('text-primary');
                } else if (stepNum < step) {
                    badge.removeClass('bg-light text-muted bg-primary').addClass('bg-success text-white');
                    label.removeClass('text-muted text-primary').addClass('text-success');
                } else {
                    badge.removeClass('bg-primary bg-success text-white').addClass('bg-light text-muted');
                    label.removeClass('text-primary text-success').addClass('text-muted');
                }
            });
        }

        function showStep(step) {
            $('.step-content').addClass('d-none');
            $('#step-' + step).removeClass('d-none');
            updateStepper(step);

            $('#btnPrev').toggleClass('d-none', step === 1);
            $('#btnNext').toggleClass('d-none', step === totalSteps);
            $('#btnSubmit').toggleClass('d-none', step !== totalSteps);

            if (step === 3) fillSummary();
            if (step === 4) fillConfirm();
        }

        function fillSummary() {
            const channelLabels = {
                whatsapp: 'WhatsApp', facebook: 'Facebook', instagram: 'Instagram',
                email: 'Email', web: 'Web',
            };
            const typeLabels = { text: 'Texto libre', hsm: 'Template HSM' };

            $('#summary-name').text($('[name="name"]').val() || '—');
            $('#summary-channel').text(channelLabels[$('[name="channel"]').val()] || '—');
            $('#summary-type').text(typeLabels[$('[name="template_type"]:checked').val()] || '—');
            $('#summary-tag').text($('[name="filters[tag]"]').val() || '—');
            $('#summary-filter-channel').text($('[name="filters[channel]"]').val() || '—');

            const body = $('[name="template_type"]:checked').val() === 'hsm'
                ? 'Template HSM: ' + $('[name="template_id"]').val()
                : $('#body').val();
            $('#summary-body').text(body || '—');
        }

        function fillConfirm() {
            const channelLabels = {
                whatsapp: 'WhatsApp', facebook: 'Facebook', instagram: 'Instagram',
                email: 'Email', web: 'Web',
            };
            const typeLabels = { text: 'Texto libre', hsm: 'Template HSM' };

            const name = $('[name="name"]').val() || 'Sin nombre';
            const channel = channelLabels[$('[name="channel"]').val()] || '—';
            const type = typeLabels[$('[name="template_type"]:checked').val()] || '—';

            $('#confirm-name').text(name);
            $('#confirm-summary').text('Canal: ' + channel + ' | Tipo: ' + type);
        }

        $('#btnNext').on('click', function () {
            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        });

        $('#btnPrev').on('click', function () {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });

        // Toggle body / template fields based on template_type
        $('[name="template_type"]').on('change', function () {
            const isHsm = $(this).val() === 'hsm';
            $('#bodyField').toggleClass('d-none', isHsm);
            $('#templateField').toggleClass('d-none', !isHsm);
        });

        // Trigger initial state
        $('[name="template_type"]:checked').trigger('change');

        // Live preview
        $('#body').on('input', function () {
            const text = $(this).val();
            const count = text.length;
            $('#charCount').text(count + ' / 4096 caracteres');
            $('#previewText').text(text || 'El mensaje aparecera aqui mientras escribes...')
                .toggleClass('fst-italic text-muted', !text);
        });

        // Si hay errores de validacion, muestra el paso donde ocurrieron.
        if (config.initialStep) {
            currentStep = config.initialStep;
            showStep(currentStep);
        }
    });
})(jQuery);
