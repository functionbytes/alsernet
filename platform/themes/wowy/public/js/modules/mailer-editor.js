/**
 * MailerEditor — shared CodeMirror utilities for the Mailer module.
 * Used by: templates/edit, templates/create, components/edit, components/create.
 */
window.MailerEditor = (function ($) {
    'use strict';

    // Base variables available in all templates
    var baseVars = [
        'SITE_NAME', 'SITE_URL', 'SITE_LOGO_URL',
        'COMPANY_NAME', 'COMPANY_ADDRESS', 'COMPANY_CITY', 'COMPANY_POSTAL_CODE',
        'COMPANY_COUNTRY', 'COMPANY_PHONE', 'COMPANY_EMAIL',
        'SUPPORT_EMAIL', 'SUPPORT_PHONE',
        'USER_NAME', 'USER_FIRST_NAME', 'USER_LAST_NAME', 'USER_EMAIL', 'USER_PHONE',
        'CURRENT_DATE', 'CURRENT_YEAR', 'CURRENT_MONTH',
        'LOGIN_URL', 'RESET_PASSWORD_URL', 'VERIFY_EMAIL_URL',
        'ACCOUNT_DASHBOARD_URL', 'UNSUBSCRIBE_URL',
    ];

    var allVars = baseVars.slice();
    var editorInstance = null;

    // ── CodeMirror hint helper ────────────────────────────────────────────────

    function registerHintHelpers(extraVars) {
        if (extraVars && extraVars.length) {
            extraVars.forEach(function (v) {
                if (allVars.indexOf(v) === -1) { allVars.push(v); }
            });
        }

        if (typeof CodeMirror === 'undefined') { return; }

        CodeMirror.registerHelper('hint', 'mailerVars', function (cm) {
            var cur = cm.getCursor();
            var token = cm.getTokenAt(cur);
            var start = token.start;
            var end = cur.ch;
            var line = cm.getLine(cur.line);
            var word = line.slice(start, end);

            // Trigger on "{" prefix
            var bracePos = line.lastIndexOf('{', end - 1);
            if (bracePos !== -1 && line.indexOf('}', bracePos) === -1) {
                var partial = line.slice(bracePos + 1, end).toUpperCase();
                var matches = allVars.filter(function (v) {
                    return v.indexOf(partial) === 0;
                }).map(function (v) {
                    return { text: '{' + v + '}', displayText: v };
                });

                if (matches.length) {
                    return {
                        list: matches,
                        from: CodeMirror.Pos(cur.line, bracePos),
                        to: CodeMirror.Pos(cur.line, end),
                    };
                }
            }

            return null;
        });
    }

    // ── Init CodeMirror ───────────────────────────────────────────────────────

    function initCodeMirror() {
        if (typeof CodeMirror === 'undefined') {
            console.error('MailerEditor: CodeMirror not loaded');
            return null;
        }

        var textarea = document.getElementById('content');
        if (!textarea) {
            console.error('MailerEditor: #content textarea not found');
            return null;
        }

        editorInstance = CodeMirror.fromTextArea(textarea, {
            mode: 'htmlmixed',
            theme: 'monokai',
            lineNumbers: true,
            lineWrapping: true,
            autoCloseTags: true,
            autoCloseBrackets: true,
            matchBrackets: true,
            indentUnit: 2,
            tabSize: 2,
            extraKeys: {
                'Ctrl-S': function (cm) { _saveForm(); },
                'Cmd-S': function (cm) { _saveForm(); },
                'Ctrl-Space': function (cm) {
                    CodeMirror.showHint(cm, function (cm) {
                        return CodeMirror.hint.mailerVars(cm) || CodeMirror.hint.html(cm);
                    });
                },
                'Tab': function (cm) {
                    if (typeof emmetCodeMirror !== 'undefined') {
                        emmetCodeMirror(cm);
                    } else {
                        cm.execCommand('indentMore');
                    }
                },
                'Ctrl-/': function (cm) {
                    var from = cm.getCursor('from');
                    var to = cm.getCursor('to');
                    var lines = cm.getRange(from, to) || cm.getLine(from.line);
                    if (lines.trim().startsWith('<!--')) {
                        cm.replaceRange(
                            lines.replace(/<!--\s?/, '').replace(/\s?-->/, ''),
                            from, to
                        );
                    } else {
                        cm.replaceRange('<!-- ' + lines + ' -->', from, to);
                    }
                },
                'Ctrl-Alt-Enter': function (cm) {
                    if (typeof emmetCodeMirror !== 'undefined') {
                        emmetCodeMirror(cm);
                    }
                },
            },
        });

        editorInstance.setSize(null, 500);

        return editorInstance;
    }

    // ── Autocomplete on typing ────────────────────────────────────────────────

    function bindAutocomplete(cm) {
        if (!cm) { return; }
        cm.on('keyup', function (cm, event) {
            if (event.key === '{') {
                CodeMirror.showHint(cm, CodeMirror.hint.mailerVars, { completeSingle: false });
            }
        });
    }

    // ── Status helpers ────────────────────────────────────────────────────────

    function updateEditorStatus(status, icon, colorClass) {
        var $badge = $('#editorStatus');
        if (!$badge.length) { return; }

        var colorMap = { success: 'bg-success', warning: 'bg-warning text-dark', danger: 'bg-danger', info: 'bg-info text-dark' };
        $badge.removeClass('bg-success bg-warning bg-danger bg-info bg-black text-dark text-white');

        if (colorMap[colorClass]) {
            $badge.addClass(colorMap[colorClass]);
        } else {
            $badge.addClass('bg-black text-white');
        }

        $badge.html(icon ? '<i class="fas fa-' + icon + ' me-1"></i>' + status : status);
    }

    function updatePreviewStatus(status) {
        var $el = $('#previewStatus');
        if ($el.length) { $el.text(status); }
    }

    // ── Format code ───────────────────────────────────────────────────────────

    function formatCode(cm) {
        if (!cm) { return; }
        var raw = cm.getValue();
        var formatted = raw;

        if (typeof html_beautify !== 'undefined') {
            formatted = html_beautify(raw, {
                indent_size: 2,
                wrap_line_length: 120,
                preserve_newlines: true,
                max_preserve_newlines: 2,
            });
        }

        cm.setValue(formatted);
        updateEditorStatus('Formateado', 'check', 'success');
        setTimeout(function () { updateEditorStatus('Listo', 'circle-check', 'success'); }, 1500);
    }

    // ── Insert variable at cursor ─────────────────────────────────────────────

    function insertVariable(name, cm) {
        cm = cm || editorInstance;
        if (!cm) { return; }
        var cursor = cm.getCursor();
        cm.replaceRange('{' + name + '}', cursor);
        cm.focus();
    }

    // ── Internal: save form ───────────────────────────────────────────────────

    function _saveForm() {
        var $form = $('form#formEdit, form#formCreate').first();
        if ($form.length && editorInstance) {
            editorInstance.save();
            $form.submit();
        }
    }

    // Public API
    return {
        registerHintHelpers: registerHintHelpers,
        initCodeMirror: initCodeMirror,
        bindAutocomplete: bindAutocomplete,
        updateEditorStatus: updateEditorStatus,
        updatePreviewStatus: updatePreviewStatus,
        formatCode: formatCode,
        insertVariable: insertVariable,
    };

}(window.jQuery));
