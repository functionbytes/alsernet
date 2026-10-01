(function ($) {
    'use strict';

    const config = window.AiQualityConfig || {};
    const i18n = config.i18n || {};
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const POLL_INTERVAL_MS = 3000;
    const POLL_MAX_ATTEMPTS = 400;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function errorMessage(xhr, fallback) {
        const errors = xhr.responseJSON && xhr.responseJSON.errors;

        if (errors) {
            return Object.values(errors)[0][0];
        }

        return (xhr.responseJSON && xhr.responseJSON.message) || fallback;
    }

    // ---- Run a regression (queued job) and poll its report -----------------
    function pollReport(statusUrl, attempt) {
        if (attempt > POLL_MAX_ATTEMPTS) {
            return;
        }

        setTimeout(function () {
            $.getJSON(statusUrl)
                .done(function (report) {
                    if (!report.finished) {
                        pollReport(statusUrl, attempt + 1);

                        return;
                    }

                    if (report.status === 'failed') {
                        toastr.error(report.error || i18n.finishedFailed);
                    } else {
                        toastr.success(i18n.finishedOk);
                    }

                    window.location.reload();
                })
                .fail(function () {
                    pollReport(statusUrl, attempt + 1);
                });
        }, POLL_INTERVAL_MS);
    }

    $(document).on('click', '.quality-run-regression', function () {
        const $button = $(this);

        if ($button.prop('disabled')) {
            return;
        }

        $button.prop('disabled', true).text(i18n.running);

        $.ajax({
            url: $button.data('url'),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                toastr.info(response.message);
                pollReport(response.status_url, 0);
            },
            error: function (xhr) {
                toastr.error(errorMessage(xhr, i18n.runError));
                $button.prop('disabled', false).text(i18n.runRegression);
            },
        });
    });

    // ---- Report detail: per-question comparison ----------------------------
    function toolsText(tools) {
        return tools && tools.length ? tools.join(', ') : i18n.none;
    }

    function yesNo(value) {
        return value ? i18n.yes : i18n.no;
    }

    function verdictBadge(verdict) {
        const classes = {
            regression: 'bg-danger-subtle text-danger',
            ok: 'bg-success-subtle text-success',
            no_baseline: 'bg-secondary-subtle text-secondary',
            error: 'bg-warning-subtle text-warning',
        };

        return '<span class="badge ' + (classes[verdict] || classes.ok) + '">' + escapeHtml((i18n.verdicts || {})[verdict] || verdict) + '</span>';
    }

    function answerColumn(title, side, changedTools, changedEscalation) {
        if (!side) {
            return '<div class="col-md-6"><h6 class="fw-bold">' + escapeHtml(title) + '</h6><p class="text-muted small">' + escapeHtml(i18n.noBaseline) + '</p></div>';
        }

        return '<div class="col-md-6">'
            + '<h6 class="fw-bold">' + escapeHtml(title) + '</h6>'
            + '<p class="text-break mb-2">' + escapeHtml(side.answer) + '</p>'
            + '<small class="text-muted d-block">' + escapeHtml(i18n.tools) + ': ' + escapeHtml(toolsText(side.used_tools))
            + (changedTools ? ' <i class="fas fa-triangle-exclamation text-warning"></i>' : '') + '</small>'
            + '<small class="text-muted d-block">' + escapeHtml(i18n.escalated) + ': ' + escapeHtml(yesNo(side.escalated))
            + (changedEscalation ? ' <i class="fas fa-triangle-exclamation text-warning"></i>' : '') + '</small>'
            + '<small class="text-muted d-block">' + escapeHtml(i18n.length) + ': ' + escapeHtml(side.length) + '</small>'
            + '</div>';
    }

    function renderResult(result) {
        const judge = result.score !== null
            ? '<small class="text-muted d-block mt-2">' + escapeHtml(i18n.judge) + ': <strong>' + escapeHtml(result.score) + '/5</strong> ' + escapeHtml(result.judge_reason || '') + '</small>'
            : '';
        const error = result.error ? '<small class="text-danger d-block mt-2">' + escapeHtml(result.error) + '</small>' : '';

        return '<div class="card border mb-3"><div class="card-body">'
            + '<div class="d-flex justify-content-between align-items-start mb-3">'
            + '<div><small class="text-muted">' + escapeHtml(i18n.question) + '</small><div class="fw-semibold text-break">' + escapeHtml(result.question) + '</div></div>'
            + verdictBadge(result.verdict)
            + '</div>'
            + '<div class="row g-3">'
            + answerColumn(i18n.original, result.original, false, false)
            + answerColumn(i18n.newAnswer, result.new, result.tools_changed, result.escalation_changed)
            + '</div>' + judge + error
            + '</div></div>';
    }

    function renderSummary(report) {
        const summary = report.summary || {};
        let text = (i18n.summary || '')
            .replace(':total', report.questions_total)
            .replace(':real', summary.real_questions || 0)
            .replace(':regressions', report.regressions)
            .replace(':before', summary.escalations_before || 0)
            .replace(':after', summary.escalations_after || 0)
            .replace(':tools', summary.tools_changed || 0);

        if (summary.truncated_by_cost) {
            text += ' ' + i18n.truncated;
        }

        if (report.error) {
            text += ' ' + report.error;
        }

        return text;
    }

    $(document).on('click', '.quality-view-report', function () {
        $.getJSON($(this).data('url'))
            .done(function (report) {
                $('#quality-report-summary').text(renderSummary(report));
                $('#quality-report-body').html((report.results || []).map(renderResult).join(''));
                $('#quality-report-modal').modal('show');
            })
            .fail(function () {
                toastr.error(i18n.loadError);
            });
    });
}(jQuery));
