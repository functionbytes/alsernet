/**
 * Partial settings/drip-campaigns/_form.blade.php.
 * window.HdDripCampaignFormConfig = { isCreating } lo imprime el Blade.
 *
 * Requiere settings-common-batch3.js (window.HdSettingsCommon) cargado antes.
 */
$(document).ready(function () {
    window.HdSettingsCommon.initFormSelect2('.form-select');

    var cfg = window.HdDripCampaignFormConfig || {};
    var triggerTypesWithValue = ['tag_added'];
    var stepCount = $('#steps-container .step-card').length;

    // Disparador: mostrar/ocultar campo de valor
    function toggleTriggerValue() {
        var type = $('#trigger_type').val();
        var needsValue = triggerTypesWithValue.indexOf(type) !== -1;
        $('#trigger_value_wrapper').toggle(needsValue);
        if (! needsValue) {
            $('#trigger_value').val('');
        }
    }

    $('#trigger_type').on('change', toggleTriggerValue);
    toggleTriggerValue();

    // Agregar paso
    function addStep() {
        var template = $('#step-template').html();
        var html = template
            .replace(/INDEX_DISPLAY/g, stepCount + 1)
            .replace(/INDEX/g, stepCount);

        $('#steps-container').append(html);
        stepCount++;
        renumberSteps();
    }

    // Renumerar pasos tras agregar/eliminar
    function renumberSteps() {
        $('#steps-container .step-card').each(function (i) {
            $(this).attr('data-index', i);
            $(this).find('.step-label').text('Paso ' + (i + 1));
            $(this).find('input, select, textarea').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/steps\[\d+\]/, 'steps[' + i + ']'));
                }
            });
        });
        stepCount = $('#steps-container .step-card').length;
    }

    // En create, inicializar con 1 paso vacio
    if (cfg.isCreating) {
        addStep();
    }

    $('#btn-add-step').on('click', function () {
        addStep();
    });

    // Eliminar paso
    $(document).on('click', '.btn-remove-step', function () {
        $(this).closest('.step-card').remove();
        renumberSteps();
    });
});
