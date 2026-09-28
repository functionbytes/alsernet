(function ($) {
    'use strict';

    const config = window.AiPromptsConfig || {};
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function ajaxHeaders(overrideMethod) {
        const headers = { 'X-CSRF-TOKEN': csrfToken };

        if (overrideMethod) {
            headers['X-HTTP-Method-Override'] = overrideMethod;
        }

        return headers;
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    // ---- Delete confirmation modal ----------------------------------------
    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
        $('#delete-modal').modal('show');
    });

    // ---- Active toggle (cases + blocks) ------------------------------------
    $(document).on('change', '.ai-toggle-active', function () {
        const $toggle = $(this);
        const url = $toggle.data('url');
        const wasChecked = $toggle.prop('checked');

        $toggle.prop('disabled', true);

        $.ajax({
            url: url,
            method: 'POST',
            headers: ajaxHeaders('PATCH'),
            success: function (response) {
                $toggle.prop('checked', response.is_active);
                toastr.success(response.is_active ? 'Activado.' : 'Desactivado.');
            },
            error: function (xhr) {
                $toggle.prop('checked', !wasChecked);
                toastr.error(xhr.responseJSON?.message ?? 'No se pudo actualizar.');
            },
            complete: function () {
                $toggle.prop('disabled', false);
            },
        });
    });

    // ---- Duplicate case modal ----------------------------------------------
    $(document).on('click', '.ai-duplicate-case', function (e) {
        e.preventDefault();
        $('#duplicate-form').attr('action', $(this).data('url'));
        $('#duplicate-case-name').text($(this).data('name'));
    });

    // ---- Run saved case's test_questions -----------------------------------
    $(document).on('click', '.ai-run-test', function (e) {
        e.preventDefault();
        const url = $(this).data('url');
        openTestResultsModal(function () {
            return $.ajax({ url: url, method: 'POST', headers: ajaxHeaders() });
        });
    });

    function openTestResultsModal(requestFactory) {
        const $modal = $('#test-results-modal');
        const $loading = $('#test-results-loading');
        const $empty = $('#test-results-empty');
        const $table = $('#test-results-table');
        const $body = $('#test-results-body');

        $body.empty();
        $table.addClass('d-none');
        $empty.addClass('d-none');
        $loading.removeClass('d-none');
        $modal.modal('show');

        requestFactory()
            .done(function (response) {
                renderTestResults(response.results || []);
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'No se pudieron ejecutar las pruebas.');
                $modal.modal('hide');
            })
            .always(function () {
                $loading.addClass('d-none');
            });
    }

    function renderTestResults(results) {
        const $empty = $('#test-results-empty');
        const $table = $('#test-results-table');
        const $body = $('#test-results-body');

        if (results.length === 0) {
            $empty.removeClass('d-none');
            return;
        }

        $table.removeClass('d-none');

        results.forEach(function (result) {
            const passed = !!result.passed;
            const badgeClass = passed ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
            const badgeLabel = passed ? config.i18n.resultPass : config.i18n.resultFail;
            const failures = (result.failures || []).map(escapeHtml).join('<br>');

            $body.append(`
                <tr>
                    <td class="small">${escapeHtml(result.question)}</td>
                    <td class="small">${escapeHtml(result.answer)}</td>
                    <td class="small">${(result.used_tools || []).map(escapeHtml).join(', ') || '—'}</td>
                    <td class="text-center">
                        <span class="badge ${badgeClass}" data-bs-toggle="${failures ? 'tooltip' : ''}" title="${failures}">${badgeLabel}</span>
                    </td>
                </tr>
            `);
        });

        $body.find('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });
    }

    window.AiPromptsRenderTestResults = renderTestResults;
    window.AiPromptsOpenTestResultsModal = openTestResultsModal;

    // ---- Probador (tester) --------------------------------------------------
    function testerPayload() {
        return {
            question: $('#tester-question').val(),
            channel: $('#tester-channel').val(),
            locale: $('#tester-locale').val(),
            logged_in: $('#tester-logged-in').is(':checked') ? 1 : 0,
        };
    }

    function showTesterResults() {
        $('#tester-empty').addClass('d-none');
        $('#tester-results').removeClass('d-none');
    }

    function renderDetection(response) {
        showTesterResults();

        const methodLabels = {
            keyword: config.i18n.methodKeyword,
            llm: config.i18n.methodLlm,
            default: config.i18n.methodDefault,
            forced: config.i18n.methodForced,
            none: config.i18n.methodNone,
        };

        $('#tester-result-case').text(response.case_name || config.i18n.noneDetected);
        $('#tester-result-method').text(methodLabels[response.routed_by] || response.routed_by || '—');
        $('#tester-result-prompt').text(response.system || '');
    }

    $('#tester-detect').on('click', function () {
        const question = $('#tester-question').val().trim();

        if (!question) {
            toastr.warning('Escribe una pregunta.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true);
        $('#tester-execution').addClass('d-none');

        $.ajax({
            url: config.testerDetectUrl,
            method: 'POST',
            data: testerPayload(),
            headers: ajaxHeaders(),
        })
            .done(renderDetection)
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'No se pudo detectar el caso.');
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    });

    $('#tester-execute').on('click', function () {
        const question = $('#tester-question').val().trim();

        if (!question) {
            toastr.warning('Escribe una pregunta.');
            return;
        }

        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + $btn.text().trim());

        $.ajax({
            url: config.testerExecuteUrl,
            method: 'POST',
            data: testerPayload(),
            headers: ajaxHeaders(),
        })
            .done(function (response) {
                renderDetection(response);
                $('#tester-execution').removeClass('d-none');
                $('#tester-result-answer').text(response.answer || '');
                $('#tester-result-tools').text((response.used_tools || []).join(', ') || '—');
                $('#tester-result-action').text(response.action || '—');
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'No se pudo ejecutar el agente.');
            })
            .always(function () {
                $btn.prop('disabled', false).html(originalHtml);
            });
    });

    $('#tester-copy-prompt').on('click', function () {
        const text = $('#tester-result-prompt').text();

        if (!text) {
            return;
        }

        navigator.clipboard.writeText(text).then(function () {
            toastr.success(config.i18n.copied);
        });
    });

}(jQuery));
