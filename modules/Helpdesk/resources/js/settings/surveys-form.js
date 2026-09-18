/**
 * Formulario de encuesta (alta/edicion) — modules/Helpdesk/resources/views/settings/surveys/form.blade.php
 * Requiere: window.SurveyFormConfig = { questionCount: number } (inline en el blade)
 */
$(document).ready(function () {
    HDSettingsCommon.initFormSelect2();

    var questionCount = (window.SurveyFormConfig && window.SurveyFormConfig.questionCount) || 0;

    $('#addQuestion').on('click', function () {
        const template = document.getElementById('questionTemplate').innerHTML;
        const uuid = Math.random().toString(36).substr(2, 9);
        const html = template.replace(/__IDX__/g, questionCount).replace(/""/g, '"' + uuid + '"');
        const $el = $(html);
        $el.find('.question-number').text(questionCount + 1);
        $el.find('input[type="hidden"][name*="[id]"]').val(uuid);
        $('#questionsContainer').append($el);
        $el.find('.form-select').select2({ width: '100%' });
        questionCount++;
    });

    $(document).on('click', '.remove-question', function () {
        $(this).closest('.question-item').remove();
        $('#questionsContainer .question-item').each(function (i) {
            $(this).find('.question-number').text(i + 1);
        });
    });
});
