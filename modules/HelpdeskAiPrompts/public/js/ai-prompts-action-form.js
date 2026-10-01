(function ($) {
    'use strict';

    const config = window.AiActionFormConfig || {};
    const i18n = config.i18n || {};
    const $form = $('#action-form');
    const $params = $('#params-list');
    let nextIndex = config.paramCount || 0;

    // ---- Parameters repeater ------------------------------------------------
    function rowTemplate(index) {
        return $(paramRowHtml(index));
    }

    function paramRowHtml(index) {
        const $tpl = $('#param-row-template');
        return $tpl.html().replace(/__INDEX__/g, index);
    }

    function reindexParams() {
        $params.find('.ai-param-row').each(function (position) {
            const $row = $(this);

            $row.attr('data-index', position);
            $row.find('[name]').each(function () {
                this.name = this.name.replace(/^parameters\[\d+\]/, 'parameters[' + position + ']');
            });
            $row.find('input[type=checkbox]').each(function () {
                $(this).attr('id', 'param-required-' + position).next('label').attr('for', 'param-required-' + position);
            });
            $row.find('[data-error]').attr('data-error', 'parameters.' + position);
        });
        $('#params-empty').toggleClass('d-none', $params.children().length > 0);
    }

    $('#param-add').on('click', function () {
        $params.append(rowTemplate(nextIndex++));
        reindexParams();
    });

    $params.on('click', '.ai-param-remove', function () {
        $(this).closest('.ai-param-row').remove();
        reindexParams();
    });

    $params.on('change', '.ai-param-type', function () {
        const type = $(this).val();
        const $row = $(this).closest('.ai-param-row');

        $row.find('.ai-param-enum').toggleClass('d-none', type !== 'enum');
        $row.find('.ai-param-string').toggleClass('d-none', type !== 'string');
    });

    // ---- Tags (response fields) ---------------------------------------------
    function addTag(target, name, raw) {
        const value = (raw || '').trim();
        const $container = $(target);

        if (!value) {
            return;
        }

        const exists = $container.find('input').filter(function () { return this.value === value; }).length > 0;
        if (exists) {
            return;
        }

        $container.append(
            $('<span class="badge bg-light-secondary text-dark ai-tag">').text(value)
                .append($('<input type="hidden">').attr('name', name).val(value))
                .append($('<button type="button" class="ai-tag-remove">').html('&times;'))
        );
    }

    $(document).on('keydown', '.ai-action-tag-add', function (e) {
        if (e.key !== 'Enter' && e.key !== ',') {
            return;
        }
        e.preventDefault();
        addTag($(this).data('target'), $(this).data('name'), $(this).val());
        $(this).val('');
    });

    $(document).on('blur', '.ai-action-tag-add', function () {
        addTag($(this).data('target'), $(this).data('name'), $(this).val());
        $(this).val('');
    });

    $(document).on('click', '.ai-tag-remove', function () {
        $(this).closest('.ai-tag').remove();
    });

    // ---- Rules: forced values -----------------------------------------------
    const $ownership = $('#a-ownership');
    const $verified = $('#a-requires-verified');
    const $confirm = $('#a-confirm');

    function syncRules() {
        const spec = config.isBridge ? (config.allowlist || {})[$('#a-bridge-action').val()] : null;
        const isWrite = !!spec && spec.mode === 'write';
        const needsOwner = !!spec && (isWrite || spec.customer);

        $confirm.prop('disabled', isWrite);
        if (isWrite) {
            $confirm.prop('checked', true);
        }
        $('#confirm-forced-note').toggleClass('d-none', !isWrite);

        $ownership.find('option[value="none"]').prop('disabled', needsOwner);
        if (needsOwner && (!$ownership.val() || $ownership.val() === 'none')) {
            $ownership.val('verified');
        }

        const forceVerified = $ownership.val() === 'verified';
        $verified.prop('disabled', forceVerified);
        if (forceVerified) {
            $verified.prop('checked', true);
        }
    }

    function syncBridgeHint() {
        const spec = (config.allowlist || {})[$('#a-bridge-action').val()];
        if (!spec) {
            return;
        }

        const parts = [spec.mode === 'write' ? i18n.specWrite : i18n.specRead];
        if (spec.customer) {
            parts.push(i18n.specCustomer);
        }
        if (spec.order === true) {
            parts.push(i18n.specOrder);
        } else if (spec.order === 'optional') {
            parts.push(i18n.specOrderOptional);
        }
        $('#bridge-spec-hint').text(parts.join(' · '));
    }

    $('#a-bridge-action').on('change', function () {
        syncBridgeHint();
        syncRules();
    });
    $ownership.on('change', syncRules);

    // ---- HTTP auth ----------------------------------------------------------
    function syncAuth() {
        const type = $('#a-auth-type').val();

        $('#auth-header-group').toggleClass('d-none', type !== 'header');
        $('#auth-secret-group').toggleClass('d-none', type === 'none');
    }
    $('#a-auth-type').on('change', syncAuth);

    // ---- Submit + 422 errors per field --------------------------------------
    function clearErrors() {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[data-error]').text('').removeClass('d-block');
        $('#action-form-errors').addClass('d-none').empty();
    }

    function findSlot(key) {
        let current = key;

        while (current) {
            const $slot = $form.find('[data-error="' + current + '"]');
            if ($slot.length) {
                return $slot.first();
            }
            const cut = current.lastIndexOf('.');
            current = cut < 0 ? '' : current.slice(0, cut);
        }

        return null;
    }

    function inputFor(key) {
        const name = key.replace(/\.(\w+)/g, '[$1]');

        return $form.find('[name="' + name + '"], [name="' + name + '[]"]').first();
    }

    function showErrors(errors) {
        const orphans = [];

        $.each(errors, function (key, messages) {
            const $slot = findSlot(key);

            inputFor(key).addClass('is-invalid');
            if ($slot) {
                const previous = $slot.text();
                $slot.text(previous ? previous + ' ' + messages[0] : messages[0]).addClass('d-block');
                $slot.prevAll('.form-control, .form-select').first().addClass('is-invalid');
            } else {
                orphans.push(messages[0]);
            }
        });

        if (orphans.length) {
            $('#action-form-errors').removeClass('d-none').html(orphans.map(function (m) {
                return $('<div>').text(m).prop('outerHTML');
            }).join(''));
        }

        const $first = $form.find('.is-invalid, .d-block[data-error]').first();
        if ($first.length) {
            $first[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();
        reindexParams();

        const $submit = $('#action-form-submit');
        $submit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + i18n.saving);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
            .done(function (response) {
                window.location.href = response.redirect;
            })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors(xhr.responseJSON.errors);
                    toastr.error(i18n.reviewErrors);
                } else {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || i18n.saveFailed);
                }
                $submit.prop('disabled', false).text(i18n.save);
            });
    });

    syncBridgeHint();
    syncRules();
    syncAuth();
}(jQuery));
