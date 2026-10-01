/*!
 * HelpdeskAiPrompts · "Acciones IA" de la bandeja (source agent).
 * Los datos del cliente salen siempre de la conversación en el servidor: aquí
 * solo se elige la acción, se rellenan sus parámetros y se confirman la
 * identidad y las acciones que modifican datos. Eventos delegados: el botón y
 * el modal se vuelven a pintar al cambiar de conversación por AJAX.
 */
(function ($) {
    'use strict';

    if (window.BvAgentActionsLoaded) { return; }
    window.BvAgentActionsLoaded = true;

    var MODAL_ID = 'bv-agent-actions-modal';
    var state = { listUrl: '', runUrl: '', actions: [], context: {}, summary: '' };

    function $modal() { return $('#' + MODAL_ID); }
    function i18n() { return $modal().data('i18n') || {}; }
    function csrf() { return $('meta[name="csrf-token"]').attr('content'); }
    function currentAction() {
        var key = $('#aa-action').val();
        return state.actions.filter(function (a) { return a.key === key; })[0] || null;
    }
    function identityOk() {
        return !!state.context.identity_verified || $('#aa-identity').is(':checked');
    }

    // El modal vive dentro del panel del hilo (que se reemplaza por AJAX):
    // se mueve a <body> una vez y las copias posteriores se descartan.
    function mountModal() {
        var $all = $('#' + MODAL_ID);
        var $inBody = $all.filter(function () { return $(this).parent().is('body'); });
        if ($inBody.length) {
            $all.not($inBody).remove();
            return;
        }
        $all.first().appendTo('body');
        $all.slice(1).remove();
    }

    function showOnly(section) {
        $('#aa-loading').toggleClass('d-none', section !== 'loading');
        $('#aa-load-error').toggleClass('d-none', section !== 'error');
        $('#aa-empty').toggleClass('d-none', section !== 'empty');
        $('#aa-form').toggleClass('d-none', section !== 'form');
    }

    function resetResult() {
        $('#aa-result, #aa-insert').addClass('d-none');
        $('#aa-result-rows').empty();
        $('#aa-result-table').addClass('d-none');
        $('#aa-result-text').addClass('d-none').text('');
        state.summary = '';
    }

    function openModal($btn) {
        mountModal();
        state.listUrl = $btn.data('aa-list-url');
        state.runUrl = $btn.data('aa-run-url');
        state.actions = [];
        resetResult();
        showOnly('loading');
        $('#aa-run').prop('disabled', true);
        bootstrap.Modal.getOrCreateInstance($modal()[0]).show();

        $.getJSON(state.listUrl).done(function (resp) {
            state.actions = resp.actions || [];
            state.context = resp.context || {};
            renderForm();
        }).fail(function () {
            $('#aa-load-error').text(i18n().loadError);
            showOnly('error');
        });
    }

    function renderForm() {
        if (!state.actions.length) {
            showOnly('empty');
            return;
        }

        var $select = $('#aa-action').empty()
            .append($('<option>', { value: '', text: i18n().selectAction }));
        state.actions.forEach(function (a) {
            $select.append($('<option>', { value: a.key, text: a.name }));
        });

        $('#aa-identity').prop('checked', false);
        $('#aa-identity-ok').toggleClass('d-none', !state.context.identity_verified);
        $('#aa-identity-box').toggleClass('d-none', !!state.context.identity_verified || !state.context.has_customer);
        $('#aa-no-customer').toggleClass('d-none', !!state.context.has_customer);
        renderAction();
        showOnly('form');
    }

    function fieldControl(param) {
        var id = 'aa-arg-' + param.name;
        var base = { id: id, 'data-aa-arg': param.name };

        if (param.type === 'boolean') {
            return $('<div class="form-check">')
                .append($('<input>', $.extend({ type: 'checkbox', 'class': 'form-check-input' }, base)))
                .append($('<label class="form-check-label">').attr('for', id).text(param.description || param.name));
        }

        var $control;
        if (param.type === 'enum') {
            $control = $('<select class="form-select">').attr(base);
            $control.append($('<option>', { value: '', text: '' }));
            (param.enum || []).forEach(function (v) { $control.append($('<option>', { value: v, text: v })); });
        } else {
            var inputType = { email: 'email', integer: 'number', number: 'number' }[param.type] || 'text';
            $control = $('<input class="form-control" autocomplete="off">').attr($.extend({ type: inputType }, base));
            if (param.max_length) { $control.attr('maxlength', param.max_length); }
        }

        if (param.name === 'order_ref' && state.context.order_ref) { $control.val(state.context.order_ref); }
        if (param.name === 'email' && state.context.customer_email) { $control.val(state.context.customer_email); }

        return $('<div>')
            .append($('<label class="form-label">').attr('for', id).text(param.name + (param.required ? ' *' : '')))
            .append($control)
            .append(param.description ? $('<div class="form-text">').text(param.description) : null);
    }

    function renderAction() {
        var action = currentAction();
        var $params = $('#aa-params').empty();

        resetResult();
        $('#aa-description').text(action ? action.description || '' : '');
        $('#aa-write').prop('checked', false);
        $('#aa-write-box').toggleClass('d-none', !action || !action.write);
        $('#aa-run').prop('disabled', !action || !state.context.has_customer);

        if (!action) { return; }

        action.params.forEach(function (param) {
            $('<div class="col-12">').attr('data-aa-unverified-only', param.only_unverified ? '1' : '0')
                .append(fieldControl(param)).appendTo($params);
        });
        syncIdentity();
    }

    // El email solo lo pide la acción cuando la identidad no está verificada.
    function syncIdentity() {
        var verified = identityOk();
        $('#aa-params [data-aa-unverified-only="1"]').toggleClass('d-none', verified);
    }

    function collectArgs(action) {
        var args = {};
        var verified = identityOk();
        var missing = null;

        action.params.forEach(function (param) {
            if (param.only_unverified && verified) { return; }
            var $el = $('#aa-arg-' + param.name);
            var value = param.type === 'boolean' ? ($el.is(':checked') ? '1' : '') : $.trim($el.val() || '');
            $el.toggleClass('is-invalid', param.required && value === '');
            if (param.required && value === '' && !missing) { missing = param.name; }
            if (value !== '') { args[param.name] = value; }
        });

        return { args: args, missing: missing };
    }

    function flatten(value, path, rows) {
        if (value !== null && typeof value === 'object') {
            $.each(value, function (key, child) {
                var label = Array.isArray(value) ? String(Number(key) + 1) : key;
                flatten(child, path.concat(label), rows);
            });
            return rows;
        }
        rows.push({ label: path.join(' › '), value: value === null ? '—' : String(value) });
        return rows;
    }

    function renderResult(resp) {
        var $rows = $('#aa-result-rows').empty();
        var parsed = null;

        try { parsed = JSON.parse(resp.content); } catch (e) { parsed = null; }

        var rows = parsed !== null && typeof parsed === 'object' ? flatten(parsed, [], []) : [];

        rows.forEach(function (row) {
            $('<tr>').append($('<th class="fw-normal text-muted w-50">').text(row.label))
                .append($('<td>').text(row.value)).appendTo($rows);
        });
        $('#aa-result-table').toggleClass('d-none', !rows.length);
        $('#aa-result-text').toggleClass('d-none', rows.length > 0).text(rows.length ? '' : resp.content);
        $('#aa-status').attr('class', 'badge ms-1 ' + (resp.ok ? 'bg-success' : 'bg-warning text-dark'))
            .text(resp.ok ? 'OK' : resp.status);
        $('#aa-latency').text(i18n().latency + ': ' + resp.latency_ms + ' ms');
        $('#aa-result').removeClass('d-none');

        state.summary = rows.length
            ? rows.map(function (r) { return r.label + ': ' + r.value; }).join('\n')
            : resp.content;
        $('#aa-insert').toggleClass('d-none', !resp.ok);
    }

    function run() {
        var action = currentAction();
        if (!action) { return; }

        var collected = collectArgs(action);
        if (collected.missing) {
            toastr.warning(i18n().fieldRequired.replace(':field', collected.missing));
            return;
        }
        if (action.write && !$('#aa-write').is(':checked')) {
            toastr.warning(i18n().writeRequired);
            return;
        }

        var $btn = $('#aa-run').prop('disabled', true).text(i18n().running);
        resetResult();

        $.ajax({
            url: state.runUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            data: {
                action_key: action.key,
                args: collected.args,
                identity_confirmed: $('#aa-identity').is(':checked') ? 1 : 0,
                write_confirmed: $('#aa-write').is(':checked') ? 1 : 0
            }
        }).done(renderResult).fail(function (xhr) {
            if (xhr.status === 429) { toastr.error(i18n().throttled); return; }
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || i18n().runError);
        }).always(function () {
            $btn.prop('disabled', false).text(i18n().run);
        });
    }

    function insertIntoComposer() {
        var $input = $('.bv-composer-input').first();
        if (!$input.length || !state.summary) { return; }

        var current = $input.val();
        $input.val(current ? current + '\n' + state.summary : state.summary).trigger('input').trigger('focus');
        bootstrap.Modal.getOrCreateInstance($modal()[0]).hide();
        toastr.success(i18n().inserted);
    }

    $(document).on('click', '#bv-agent-actions-btn', function () { openModal($(this)); });
    $(document).on('change', '#aa-action', renderAction);
    $(document).on('change', '#aa-identity', syncIdentity);
    $(document).on('click', '#aa-run', run);
    $(document).on('click', '#aa-insert', insertIntoComposer);
    $(document).on('submit', '#aa-form', function (e) { e.preventDefault(); run(); });
}(window.jQuery));
