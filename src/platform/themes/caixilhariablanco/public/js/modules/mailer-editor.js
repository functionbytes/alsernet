/**
 * Shared Mailer Editor utilities
 * Initializes CodeMirror and provides common helpers for mailer templates/components
 */
const MailerEditor = (function () {
    'use strict';

    let editorInstance = null;

    function getTextarea() {
        return document.getElementById('editor') || document.querySelector('textarea[name="content"]');
    }

    function initCodeMirror() {
        const textarea = getTextarea();
        if (!textarea) {
            console.warn('[MailerEditor] No editor textarea found');
            return null;
        }

        if (typeof CodeMirror === 'undefined') {
            console.warn('[MailerEditor] CodeMirror not loaded');
            return null;
        }

        editorInstance = CodeMirror.fromTextArea(textarea, {
            mode: 'htmlmixed',
            theme: 'default',
            lineNumbers: true,
            lineWrapping: true,
            autoCloseTags: true,
            matchTags: true,
            indentUnit: 2,
            tabSize: 2,
            extraKeys: {
                'Ctrl-Space': 'autocomplete',
                'Ctrl-/': 'toggleComment',
                'Cmd-/': 'toggleComment',
            },
        });

        editorInstance.setSize('100%', '500px');

        return editorInstance;
    }

    function registerHintHelpers(extraVars) {
        if (typeof CodeMirror === 'undefined') return;

        extraVars = extraVars || [];

        CodeMirror.registerHelper('hint', 'html', function (cm) {
            const cur = cm.getCursor();
            const token = cm.getTokenAt(cur);
            const start = token.start;
            const end = cur.ch;
            const word = token.string.slice(0, end - start);

            const hints = [
                'div', 'span', 'p', 'a', 'img', 'table', 'tr', 'td', 'th',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li',
                'strong', 'em', 'br', 'hr', 'style', 'class', 'id',
                'href', 'src', 'alt', 'width', 'height', 'border',
                'bgcolor', 'color', 'align', 'valign', 'cellpadding', 'cellspacing',
            ];

            const list = hints.filter(function (h) {
                return h.indexOf(word) === 0;
            }).sort();

            return {
                list: list,
                from: CodeMirror.Pos(cur.line, start),
                to: CodeMirror.Pos(cur.line, end),
            };
        });
    }

    function bindAutocomplete(editor) {
        if (!editor || typeof CodeMirror === 'undefined') return;

        editor.on('inputRead', function (cm, change) {
            if (change.text.length === 1 && /[a-zA-Z<]/.test(change.text[0])) {
                CodeMirror.commands.autocomplete(cm);
            }
        });
    }

    function updateEditorStatus(status, icon, colorClass) {
        const $status = $('#editorStatus');
        if (!$status.length) return;

        icon = icon || 'info-circle';
        colorClass = colorClass || 'secondary';

        $status.html('<i class="fas fa-' + icon + ' me-1"></i>' + status);
        $status.removeClass('text-secondary text-warning text-success text-danger')
            .addClass('text-' + colorClass);
    }

    function updatePreviewStatus(status) {
        const $status = $('#previewStatus');
        if (!$status.length) return;
        $status.text(status);
    }

    function formatCode(editor) {
        if (!editor || typeof html_beautify === 'undefined') return;

        const cursor = editor.getCursor();
        const content = editor.getValue();
        const formatted = html_beautify(content, {
            indent_size: 2,
            wrap_line_length: 120,
            preserve_newlines: true,
            max_preserve_newlines: 2,
        });

        editor.setValue(formatted);
        editor.setCursor(cursor);
        updateEditorStatus('Formatado', 'check', 'success');
    }

    function togglePreview(isExpanded) {
        const $panel = $('#previewPanel');
        if (!$panel.length) return isExpanded;

        isExpanded = !isExpanded;

        if (isExpanded) {
            $panel.removeClass('d-none');
            $('#editorPanel').removeClass('col-12').addClass('col-lg-6');
        } else {
            $panel.addClass('d-none');
            $('#editorPanel').removeClass('col-lg-6').addClass('col-12');
        }

        if (editorInstance) {
            editorInstance.refresh();
        }

        return isExpanded;
    }

    function toggleVariables() {
        const $panel = $('#variablesPanel');
        if (!$panel.length) return;
        $panel.toggleClass('d-none');
    }

    function insertVariable(name, editor) {
        if (!editor) return;

        const variable = '{' + name + '}';
        const doc = editor.getDoc();
        const cursor = doc.getCursor();

        doc.replaceRange(variable, cursor);
        editor.focus();
    }

    return {
        initCodeMirror: initCodeMirror,
        registerHintHelpers: registerHintHelpers,
        bindAutocomplete: bindAutocomplete,
        updateEditorStatus: updateEditorStatus,
        updatePreviewStatus: updatePreviewStatus,
        formatCode: formatCode,
        togglePreview: togglePreview,
        toggleVariables: toggleVariables,
        insertVariable: insertVariable,
    };
})();
