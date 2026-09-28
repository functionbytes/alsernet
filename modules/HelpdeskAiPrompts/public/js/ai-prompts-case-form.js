(function ($) {
    'use strict';

    const config = window.AiPromptsCaseFormConfig || {};
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // A counter seeded far beyond any server-rendered index guarantees every
    // cloned row gets a name/id that never collides with an existing one.
    let rowCounter = Date.now();

    function nextIndex() {
        rowCounter += 1;

        return rowCounter;
    }

    function cloneTemplate(templateId, containerSelector) {
        const html = document.getElementById(templateId).innerHTML.trim();
        const index = nextIndex();
        const $row = $(html.split('__INDEX__').join(String(index)));
        $(containerSelector).append($row);

        return $row;
    }

    // ---- Character counters -------------------------------------------------
    $('textarea[data-counter]').each(function () {
        const $textarea = $(this);
        const $counter = $($textarea.data('counter'));

        function update() {
            $counter.text($textarea.val().length);
        }

        $textarea.on('input', update);
        update();
    });

    // ---- Repeaters: examples --------------------------------------------------
    $('#add-example').on('click', function () {
        cloneTemplate('example-row-template', '#examples-rows');
    });

    // ---- Repeaters: test questions --------------------------------------------
    $('#add-test-question').on('click', function () {
        cloneTemplate('test-question-row-template', '#test-questions-rows');
    });

    $(document).on('click', '.ai-remove-row', function () {
        $(this).closest('.ai-example-row, .ai-test-question-row').remove();
    });

    // ---- Tag inputs (keywords, locales, url_contains, must[_not]_contain) -----
    function addTag(containerSelector, value) {
        const trimmed = value.trim();

        if (trimmed === '') {
            return;
        }

        const $container = $(containerSelector);
        const name = $container.data('name') + '[]';
        const $tag = $(
            '<span class="badge bg-light-secondary text-dark ai-tag"></span>'
        )
            .text(trimmed)
            .append($('<input>', { type: 'hidden', name: name, value: trimmed }))
            .append($('<button>', { type: 'button', class: 'ai-tag-remove', html: '&times;' }));

        $container.append($tag);
    }

    $(document).on('keydown', '.ai-tag-add', function (e) {
        if (e.key !== 'Enter' && e.key !== ',') {
            return;
        }

        e.preventDefault();
        addTag($(this).data('target'), $(this).val());
        $(this).val('');
    });

    $(document).on('blur', '.ai-tag-add', function () {
        addTag($(this).data('target'), $(this).val());
        $(this).val('');
    });

    $(document).on('click', '.ai-tag-remove', function () {
        $(this).closest('.ai-tag').remove();
    });

    // ---- Probar borrador --------------------------------------------------
    $('#test-draft-btn').on('click', function () {
        const $btn = $(this);
        const originalHtml = $btn.html();
        const $modal = $('#test-results-modal');
        const $loading = $('#test-results-loading');
        const $empty = $('#test-results-empty');
        const $table = $('#test-results-table');
        const $body = $('#test-results-body');

        $body.empty();
        $table.addClass('d-none');
        $empty.addClass('d-none');
        $loading.removeClass('d-none');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + $btn.text().trim());
        $modal.modal('show');

        $.ajax({
            url: config.testDraftUrl,
            method: 'POST',
            data: $('#case-form').serialize(),
            headers: { 'X-CSRF-TOKEN': csrfToken },
        })
            .done(function (response) {
                renderResults(response.results || []);
            })
            .fail(function (xhr) {
                $modal.modal('hide');
                toastr.error(xhr.responseJSON?.message ?? 'No se pudieron ejecutar las pruebas.');
            })
            .always(function () {
                $loading.addClass('d-none');
                $btn.prop('disabled', false).html(originalHtml);
            });

        function renderResults(results) {
            if (results.length === 0) {
                $empty.removeClass('d-none');
                return;
            }

            $table.removeClass('d-none');

            results.forEach(function (result) {
                const passed = !!result.passed;
                const badgeClass = passed ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                const badgeLabel = passed ? config.i18n.resultPass : config.i18n.resultFail;
                const failures = (result.failures || []).join(' · ');

                $body.append(
                    $('<tr>').append(
                        $('<td class="small">').text(result.question),
                        $('<td class="small">').text(result.answer),
                        $('<td class="small">').text((result.used_tools || []).join(', ') || '—'),
                        $('<td class="text-center">').append(
                            $('<span class="badge">').addClass(badgeClass).attr('title', failures).text(badgeLabel)
                        )
                    )
                );
            });
        }
    });

}(jQuery));
