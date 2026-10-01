(function ($) {
    'use strict';

    const i18n = (window.AiActionsConfig || {}).i18n || {};
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    const $modal = $('#action-test-modal');
    const $form = $('#action-test-form');
    const $params = $('#action-test-params');
    const $result = $('#action-test-result');
    const $run = $('#action-test-run');

    let testUrl = '';

    function paramField(param, index) {
        const id = 'action-test-arg-' + index;
        const name = 'args[' + param.name + ']';
        const $group = $('<div class="mb-3">').toggleClass('ai-test-unverified-only', !!param.only_unverified);
        const $label = $('<label class="form-label">').attr('for', id).text(param.name);

        if (param.required) {
            $label.append($('<span class="text-danger ms-1">').text('*'));
        }

        $group.append($label);
        $group.append(paramInput(param, id, name));

        if (param.description) {
            $group.append($('<small class="form-text text-muted d-block">').text(param.description));
        }

        return $group;
    }

    function paramInput(param, id, name) {
        if (param.type === 'boolean') {
            const $check = $('<div class="form-check form-switch">');
            $check.append($('<input type="checkbox" class="form-check-input" value="true">').attr({ id: id, name: name }));
            $check.append($('<label class="form-check-label">').attr('for', id).text(i18n.boolYes));

            return $check;
        }

        if (param.type === 'enum') {
            const $select = $('<select class="form-select">').attr({ id: id, name: name });
            $select.append($('<option>').val('').text('—'));
            (param.enum || []).forEach(function (value) {
                $select.append($('<option>').val(value).text(value));
            });

            return $select;
        }

        const types = { integer: 'number', number: 'number', email: 'email' };
        const $input = $('<input class="form-control">').attr({ id: id, name: name, type: types[param.type] || 'text' });

        if (param.type === 'number') {
            $input.attr('step', 'any');
        }
        if (param.max_length) {
            $input.attr('maxlength', param.max_length);
        }

        return $input;
    }

    function syncVerified() {
        const verified = $('#action-test-verified').is(':checked');

        $('#action-test-email-group').toggleClass('d-none', !verified);
        $params.find('.ai-test-unverified-only')
            .toggleClass('d-none', verified)
            .find('input, select').prop('disabled', verified);
    }

    function resetModal(link) {
        const params = link.data('params') || [];

        testUrl = link.data('url');
        $form[0].reset();
        $result.addClass('d-none');
        $('#action-test-name').text(link.data('name'));
        $('#action-test-write-warning').toggleClass('d-none', !link.data('write'));
        $params.empty();
        params.forEach(function (param, index) {
            $params.append(paramField(param, index));
        });
        $('#action-test-no-params').toggleClass('d-none', params.length > 0);
        $('#action-test-params-title').toggleClass('d-none', params.length === 0);
        syncVerified();
    }

    function renderResult(response) {
        const statuses = {
            ok: { badge: 'bg-success-subtle text-success', label: i18n.statusOk, help: i18n.helpOk },
            denied: { badge: 'bg-warning-subtle text-warning', label: i18n.statusDenied, help: i18n.helpDenied },
            error: { badge: 'bg-danger-subtle text-danger', label: i18n.statusError, help: i18n.helpError },
        };
        const status = statuses[response.status] || statuses.error;

        $('#action-test-status').attr('class', 'badge ' + status.badge).text(status.label);
        $('#action-test-status-help').text(status.help);
        $('#action-test-latency').text(response.latency_ms);
        $('#action-test-content').text(response.content || '');
        $result.removeClass('d-none');
    }

    function firstError(xhr) {
        const errors = xhr.responseJSON && xhr.responseJSON.errors;

        if (errors) {
            return Object.values(errors)[0][0];
        }

        return (xhr.responseJSON && xhr.responseJSON.message) || i18n.testFailed;
    }

    $(document).on('click', '.ai-action-test', function (e) {
        e.preventDefault();

        const $link = $(this);

        if (!$link.data('active')) {
            toastr.warning(i18n.inactive);
            return;
        }

        resetModal($link);
        $modal.modal('show');
    });

    $('#action-test-verified').on('change', syncVerified);

    $form.on('submit', function (e) {
        e.preventDefault();

        $run.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + i18n.running);

        $.ajax({
            url: testUrl,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrfToken },
        })
            .done(renderResult)
            .fail(function (xhr) {
                toastr.error(firstError(xhr));
            })
            .always(function () {
                $run.prop('disabled', false).text(i18n.run);
            });
    });
}(jQuery));
