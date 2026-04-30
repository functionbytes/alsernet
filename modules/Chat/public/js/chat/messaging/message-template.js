(function ($) {
    'use strict';

    var initialized = false;
    var templateSearchTimeout = null;

    var state = {
        currentInput:  null,
        isOpen:        false,
        selectedIndex: -1,
        templates:     [],
        trigger:       '/',
        searchTerm:    '',
        currentPos:    0
    };

    function init() {
        if (initialized) { return; }
        initialized = true;
        var inputSelector = '.message-type-box';

        $(document).on('input', inputSelector, function (e) {
            state.currentInput = $(this);
            handleInput();
        });

        $(document).on('keydown', inputSelector, function (e) {
            state.currentInput = $(this);
            if (state.isOpen) { handleKeyDown(e); }
        });

        $(document).on('click', '.template-autocomplete-item', function () {
            selectTemplate($(this));
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.template-autocomplete-container').length &&
                !$(e.target).is(inputSelector) &&
                !$(e.target).closest(inputSelector).length) {
                close();
            }
        });

        $(document).on('mouseenter', '.template-autocomplete-item', function () {
            $('.template-autocomplete-item').removeClass('active').css('background-color', 'transparent');
            $(this).addClass('active').css('background-color', '#f0f0f0');
        });
    }

    function handleInput() {
        var input = state.currentInput;
        if (!input || !input.length) { return; }

        var value       = input.val();
        var cursorPos   = input[0].selectionStart;
        var beforeCursor = value.substring(0, cursorPos);
        var triggerIndex = beforeCursor.lastIndexOf(state.trigger);

        if (triggerIndex === -1) { close(); return; }

        if (triggerIndex > 0 &&
            beforeCursor[triggerIndex - 1] !== ' ' &&
            beforeCursor[triggerIndex - 1] !== '\n') {
            close(); return;
        }

        state.searchTerm = beforeCursor.substring(triggerIndex + 1).trim();
        state.currentPos = triggerIndex;

        clearTimeout(templateSearchTimeout);
        templateSearchTimeout = setTimeout(function () {
            fetchTemplates(state.searchTerm);
        }, 300);
    }

    function handleKeyDown(e) {
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectNext();
                break;
            case 'ArrowUp':
                e.preventDefault();
                selectPrevious();
                break;
            case 'Enter':
                if (state.selectedIndex >= 0) {
                    e.preventDefault();
                    selectTemplate($('.template-autocomplete-item.active'));
                }
                break;
            case 'Escape':
                close();
                break;
        }
    }

    function fetchTemplates(searchTerm) {
        var input = state.currentInput;
        if (!input || !input.length) { return; }

        var convId = input.attr('id').replace('message-input-', '');

        $.ajax({
            url:      (window.ChatConfig && window.ChatConfig.baseUrl || '/helpdesk/conversations') + '/' + convId + '/message-templates/search',
            type:     'GET',
            data:     { q: searchTerm },
            dataType: 'json',
            success: function (response) {
                state.templates = Array.isArray(response) ? response : (response.templates || []);
                showDropdown();
            },
            error: function (xhr) {
                console.error('Error fetching templates:', xhr.status, xhr.statusText);
            }
        });
    }

    function showDropdown() {
        if (!state.templates || !state.templates.length) { close(); return; }

        $('.template-autocomplete-container').remove();

        var templatesHtml = $.map(state.templates, function (template, index) {
            var name       = escapeHtml(template.name || template.short_code || 'Sin nombre');
            var content    = escapeHtml((template.content || '').substring(0, 60));
            var visibility = template.visibility || 'personal';

            return '<div class="template-autocomplete-item p-2 border-bottom d-flex align-items-start gap-2"' +
                ' data-index="' + index + '" style="cursor:pointer;">' +
                '<div style="flex:1;">' +
                '<div class="fw-semibold text-dark">' + name + '</div>' +
                '<small class="text-muted d-block text-truncate">' + content + '</small>' +
                '</div>' +
                '<span class="badge bg-light text-dark text-nowrap" style="font-size:10px;">' + visibility + '</span>' +
                '</div>';
        }).join('');

        var container = $('<div class="template-autocomplete-container position-fixed shadow border rounded bg-white"' +
            ' style="z-index:10000;max-height:300px;overflow-y:auto;min-width:250px;">' +
            templatesHtml + '</div>');

        if (state.currentInput && state.currentInput.length) {
            var inputOffset = state.currentInput.offset();
            var inputHeight = state.currentInput.outerHeight();
            container.css({
                top:   (inputOffset.top + inputHeight + 5) + 'px',
                left:  inputOffset.left + 'px',
                width: state.currentInput.outerWidth() + 'px'
            });
        }

        $('body').append(container);

        state.isOpen        = true;
        state.selectedIndex = -1;
    }

    function selectNext() {
        var items = $('.template-autocomplete-item');
        if (state.selectedIndex < items.length - 1) {
            state.selectedIndex++;
            updateSelection();
        }
    }

    function selectPrevious() {
        if (state.selectedIndex > 0) {
            state.selectedIndex--;
            updateSelection();
        }
    }

    function updateSelection() {
        var items = $('.template-autocomplete-item');
        items.removeClass('active').css('background-color', 'transparent');

        if (state.selectedIndex < 0 || state.selectedIndex >= items.length) { return; }

        var $item     = $(items[state.selectedIndex]).addClass('active').css('background-color', '#f0f0f0');
        var container = $('.template-autocomplete-container');
        var scrollTop = container.scrollTop();
        var itemTop   = $item[0].offsetTop;
        var itemH     = $item.outerHeight();
        var containerH = container.height();

        if (itemTop < scrollTop) {
            container.scrollTop(itemTop);
        } else if (itemTop + itemH > scrollTop + containerH) {
            container.scrollTop(itemTop + itemH - containerH);
        }
    }

    function selectTemplate($templateElement) {
        var index    = parseInt($templateElement.data('index'), 10);
        var template = state.templates[index];
        if (!template) { return; }

        var input = state.currentInput;
        var value = input.val();
        var cursorPos    = input[0].selectionStart;
        var beforeCursor = value.substring(0, cursorPos);
        var triggerIndex = beforeCursor.lastIndexOf(state.trigger);
        if (triggerIndex === -1) { return; }

        var convId = input.attr('id').replace('message-input-', '');

        fetchConversationData(convId, function (variables) {
            var processedContent = variables
                ? replaceVariables(template.content, variables)
                : template.content;

            var before   = value.substring(0, triggerIndex);
            var after    = value.substring(cursorPos);
            input.val(before + processedContent + after);

            var newCursorPos = triggerIndex + processedContent.length;
            input.focus();
            input[0].setSelectionRange(newCursorPos, newCursorPos);
            input.trigger('input');
            close();

            if (typeof toastr !== 'undefined') {
                toastr.success('Plantilla insertada', 'Éxito');
            }

            showPreview(processedContent, template.name, input);
        });
    }

    function fetchConversationData(convId, callback) {
        $.ajax({
            url:  (window.ChatConfig && window.ChatConfig.baseUrl || '/helpdesk/conversations') + '/' + convId + '/variables',
            type: 'GET',
            success: function (response) { callback(response); },
            error:   function ()         { callback(null); }
        });
    }

    function replaceVariables(content, vars) {
        var result   = content;
        var groups   = ['customer', 'contact', 'agent', 'conversation', 'account', 'system'];
        var fields   = {
            customer:     ['name', 'email', 'phone', 'city', 'country', 'id'],
            contact:      ['name', 'email', 'phone', 'city', 'country', 'id'],
            agent:        ['name', 'firstname', 'lastname', 'email'],
            conversation: ['id', 'reference', 'status'],
            account:      ['name', 'id'],
            system:       ['date', 'time', 'datetime', 'year']
        };

        $.each(groups, function (i, group) {
            if (!vars[group]) { return; }
            $.each(fields[group], function (j, field) {
                var placeholder = '\\{\\{' + group + '\\.' + field + '\\}\\}';
                result = result.replace(
                    new RegExp(placeholder, 'g'),
                    vars[group][field] || ('{{' + group + '.' + field + '}}')
                );
            });
        });

        return result;
    }

    function showPreview(content, templateName, input) {
        var preview = $(
            '<div class="alert alert-info d-flex align-items-start gap-3 mt-2" style="font-size:0.9rem;max-width:400px;">' +
            '<i class="fas fa-eye text-info mt-1"></i>' +
            '<div><strong>Plantilla: ' + escapeHtml(templateName) + '</strong>' +
            '<div class="mt-1" style="border-left:2px solid #0dcaf0;padding-left:10px;font-size:0.85rem;color:#0c5460;">' +
            escapeHtml(content.substring(0, 100)) + (content.length > 100 ? '...' : '') +
            '</div></div></div>'
        );

        input.closest('.chat-send-message-footer').find('.template-preview-alert').remove();
        preview.addClass('template-preview-alert');
        input.closest('.d-flex').before(preview);

        setTimeout(function () {
            preview.fadeOut(300, function () { $(this).remove(); });
        }, 5000);
    }

    function close() {
        $('.template-autocomplete-container').remove();
        state.isOpen        = false;
        state.selectedIndex = -1;
        state.templates     = [];
    }

    function escapeHtml(text) {
        return window.ChatUtils ? window.ChatUtils.escapeHtml(text) : String(text || '');
    }

    // Inicializar inmediatamente (el partial se inyecta vía AJAX; document.ready ya disparó)
    init();

})(jQuery);
