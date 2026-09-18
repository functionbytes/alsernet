/**
 * chatflow-test-cases.js — HelpdeskChatFlow module
 *
 * Behaviour for the regression test-cases page: the dynamic step builder in
 * the "new scenario" modal, and running one or all scenarios via AJAX.
 *
 * Reads session flash messages and the "run all" URL from
 * window.HelpdeskChatFlowTestCases, emitted by test-cases.blade.php.
 */
(function ($) {
    'use strict';

    var config = window.HelpdeskChatFlowTestCases || {};
    var csrf = $('meta[name="csrf-token"]').attr('content');

    /** Escape user/bot strings before interpolating into HTML (avoid self-XSS). */
    function escHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function renderDetail(result) {
        if (result.error) { return '<div class="text-danger">' + escHtml(result.error) + '</div>'; }

        return result.steps.map(function (s, i) {
            var expect = s.expect ? ' · espera: «' + escHtml(s.expect) + '»' : '';
            var got = s.got ? escHtml(s.got) : '(sin respuesta)';
            return '<div class="d-flex gap-2 py-1">'
                + '<i class="fas fa-' + (s.matched ? 'check text-success' : 'xmark text-danger') + ' mt-1"></i>'
                + '<div>'
                + '<div><strong>' + (i + 1) + '.</strong> Cliente: «' + escHtml(s.input) + '»' + expect + '</div>'
                + '<div class="text-muted">Bot: ' + got + '</div>'
                + '</div></div>';
        }).join('');
    }

    function setBadge($row, passed) {
        var badge = passed
            ? '<span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>Pasó</span>'
            : '<span class="badge bg-danger-subtle text-danger"><i class="fas fa-xmark me-1"></i>Falló</span>';
        $row.find('.result-cell').html(badge);
    }

    function addStep(stepIndex) {
        $('#steps-container').append(
            '<div class="input-group input-group-sm mb-1 step-row">'
            + '<span class="input-group-text">' + (stepIndex + 1) + '</span>'
            + '<input type="text" name="steps[' + stepIndex + '][input]" class="form-control" placeholder="Mensaje del cliente (ej: 3)" required>'
            + '<input type="text" name="steps[' + stepIndex + '][expect_contains]" class="form-control" placeholder="Texto esperado en la respuesta">'
            + '<button type="button" class="btn btn-outline-secondary remove-step"><i class="fas fa-times"></i></button>'
            + '</div>'
        );
    }

    $(function () {
        if (config.successMessage) { toastr.success(config.successMessage); }
        if (config.errorMessage) { toastr.error(config.errorMessage); }

        var stepIndex = 0;
        addStep(stepIndex++);
        $('#add-step').on('click', function () { addStep(stepIndex++); });
        $('#steps-container').on('click', '.remove-step', function () { $(this).closest('.step-row').remove(); });

        $('.run-one-btn').on('click', function () {
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var caseId = $row.data('case-id');

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: $btn.data('url'),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function (result) {
                setBadge($row, result.passed);
                $('tr[data-detail-for="' + caseId + '"]')
                    .removeClass('d-none')
                    .find('.detail-content')
                    .html(renderDetail(result));
            }).fail(function () {
                toastr.error('Error al ejecutar el escenario');
            }).always(function () {
                $btn.prop('disabled', false).html('<i class="fas fa-play"></i>');
            });
        });

        $('#run-all-btn').on('click', function () {
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Ejecutando…');

            $.ajax({
                url: config.runAllUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
            }).done(function (data) {
                $.each(data.results, function (caseId, passed) {
                    setBadge($('tr[data-case-id="' + caseId + '"]'), passed);
                });
                $('#run-summary').html(
                    '<strong class="' + (data.failed ? 'text-danger' : 'text-success') + '">'
                    + data.passed + '/' + data.total + ' escenarios pasaron</strong>'
                );
                data.failed
                    ? toastr.warning(data.failed + ' escenario(s) fallaron')
                    : toastr.success('Todos los escenarios pasaron');
            }).fail(function () {
                toastr.error('Error al ejecutar los escenarios');
            }).always(function () {
                $btn.prop('disabled', false).html('<i class="fas fa-play me-1"></i> Ejecutar todos');
            });
        });
    });
})(jQuery);
