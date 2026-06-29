{{--
  Integración Visual Editor ↔ módulo Forms.
  Incluye:
    - Dropdown AJAX de formularios en el shortcode builder de [form] y [form-link]
    - Live preview en el builder al cambiar atributos
    - Inspector enriquecido (stats, links) al seleccionar un bloque [form]
    - Indicador visual de forms rotos (inactivos / eliminados)
    - Quick-create form desde el panel de shortcodes
--}}
@php
    $formsPickerUrl = \Route::has('forms.internal.picker') ? route('forms.internal.picker') : null;
    $formsMetaBase = \Route::has('forms.internal.meta') ? url('panel/forms/internal') : null;
    $formsQuickStoreUrl = \Route::has('forms.internal.quick-store') ? route('forms.internal.quick-store') : null;
    $formsTemplates = array_keys((array) config('forms.template_library', []));
@endphp

<script>
window.FormsVE = {
    pickerUrl: @json($formsPickerUrl),
    metaBase: @json($formsMetaBase),
    quickStoreUrl: @json($formsQuickStoreUrl),
    templates: @json($formsTemplates),
    csrf: function () { return $('meta[name="csrf-token"]').attr('content'); },
    _metaCache: {},

    fetchMeta: function (formId) {
        if (!this.metaBase || !formId) return Promise.resolve(null);
        if (this._metaCache[formId]) return Promise.resolve(this._metaCache[formId]);
        var self = this;
        return $.get(this.metaBase + '/' + formId + '/meta')
            .then(function (res) {
                self._metaCache[formId] = res.data;
                return res.data;
            })
            .catch(function () { return null; });
    },

    invalidateMeta: function (formId) {
        delete this._metaCache[formId];
    },
};
</script>

<script>
(function ($) {
    'use strict';

    if (!window.FormsVE || !window.FormsVE.pickerUrl) return;

    /* =========================================================
       1) DROPDOWN DE FORMS EN EL BUILDER DEL VE
    ========================================================= */
    function enhanceFormShortcodeBuilder(sc) {
        if (!sc || !['form', 'form-link'].includes(sc.name)) return;

        var $idInput = $('#ve-scb-attr-id');
        if (!$idInput.length || $idInput.data('forms-picker-bound')) return;
        $idInput.data('forms-picker-bound', '1');

        // Reemplaza el text input por un <select> con select2 AJAX
        var $select = $('<select>').attr({
            id: 've-scb-attr-id',
            class: 'form-select ve-scb-attr-input',
        }).data('attr', 'id');

        $select.append('<option value="">-- Seleccionar formulario --</option>');
        if ($idInput.val()) {
            $select.append('<option value="' + $idInput.val() + '" selected>#' + $idInput.val() + '</option>');
        }

        $idInput.replaceWith($select);

        $select.select2({
            dropdownParent: $('#ve-shortcode-builder-modal'),
            width: '100%',
            placeholder: 'Buscar formulario por nombre o slug…',
            minimumInputLength: 0,
            ajax: {
                url: window.FormsVE.pickerUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (res) {
                    return {
                        results: (res.data || []).map(function (f) {
                            return {
                                id: f.id,
                                text: f.name + ' · ' + f.submissions_count + ' envíos',
                            };
                        }),
                    };
                },
                cache: true,
            },
        });

        // Cargar datos iniciales si el usuario ya tenía un id seleccionado
        if ($select.val()) {
            window.FormsVE.fetchMeta($select.val()).then(function (meta) {
                if (meta) {
                    $select.find('option[value="' + meta.id + '"]').text(meta.name + ' · ' + meta.submissions_count + ' envíos');
                    $select.trigger('change.select2');
                }
            });
        }

        $select.on('change', function () { if (window.updateScbPreview) window.updateScbPreview(sc); });
    }

    // Hook: envolvemos veOpenShortcodeBuilder para llamar nuestro enhancer
    var _veOpen = window.veOpenShortcodeBuilder;
    window.veOpenShortcodeBuilder = function (sc) {
        if (typeof _veOpen === 'function') _veOpen(sc);
        setTimeout(function () { enhanceFormShortcodeBuilder(sc); }, 60);
    };

    /* =========================================================
       2) LIVE PREVIEW al cambiar atributos en el builder
    ========================================================= */
    $(document).on('change input', '#ve-shortcode-builder-modal .ve-scb-attr-input', function () {
        var $preview = $('#ve-scb-live-preview');
        if (!$preview.length) return;
        var $idAttr = $('#ve-scb-attr-id');
        var formId = $idAttr.val();
        if (!formId || !window.FormsVE.metaBase) {
            $preview.html('<div class="text-muted small p-3">Selecciona un formulario para ver el preview…</div>');
            return;
        }
        var params = {};
        $('#ve-shortcode-builder-modal .ve-scb-attr-input').each(function () {
            var attr = $(this).data('attr');
            var val = $(this).val();
            if (attr && attr !== 'id' && val !== '' && val !== null) params[attr] = val;
        });

        $preview.html('<div class="text-center p-3"><div class="spinner-border spinner-border-sm" role="status"></div></div>');
        $.get(window.FormsVE.metaBase + '/' + formId + '/preview-html', params)
            .done(function (res) {
                $preview.html('<div class="ve-forms-preview-frame">' + (res.data.html || '') + '</div>');
            })
            .fail(function () {
                $preview.html('<div class="text-danger small p-3">Error cargando preview.</div>');
            });
    });

    // Inyectar contenedor de live preview en el modal del builder (una vez)
    $(document).on('shown.bs.modal', '#ve-shortcode-builder-modal', function () {
        var sc = window._currentSc || null;
        var name = $('#ve-scb-title').text();
        if (!['form', 'form-link'].includes(name) && !name.toLowerCase().includes('form')) return;
        if ($('#ve-scb-live-preview').length) return;
        $('#ve-scb-preview').after(
            '<div class="mt-3"><label class="form-label small text-muted">Preview</label>' +
            '<div id="ve-scb-live-preview" class="border rounded p-2 bg-white" style="max-height:320px;overflow:auto">' +
            '<div class="text-muted small p-3">Selecciona un formulario para ver el preview…</div>' +
            '</div></div>'
        );
    });

    /* =========================================================
       3) INSPECTOR ENRIQUECIDO al seleccionar bloque [form]
    ========================================================= */
    function parseFormIdFromSentinel(el) {
        if (!el) return null;
        var raw = el.getAttribute('data-ve-sc') || '';
        var decoded = $('<div>').html(raw).text();
        var m = decoded.match(/\[form(?:-link)?[^\]]*\sid="(\d+)"/i);
        return m ? m[1] : null;
    }

    function renderFormMetaInInspector(meta) {
        var $actions = $('#ve-inspector-actions');
        if (!$actions.length) return;

        // Elimina panel previo
        $('#ve-inspector-form-meta').remove();

        var unread = meta.unread_count > 0
            ? '<span class="badge bg-danger ms-1">' + meta.unread_count + '</span>'
            : '';
        var status = meta.is_active
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-secondary">Inactivo</span>';
        if (meta.is_expired) {
            status = '<span class="badge bg-warning text-dark">Expirado</span>';
        }

        var html = [
            '<div id="ve-inspector-form-meta" class="border rounded p-2 mt-2 bg-light">',
            '  <div class="fw-semibold small mb-1"><i class="fas fa-wpforms me-1"></i>' + $('<div>').text(meta.name).html() + ' ' + status + '</div>',
            '  <div class="small text-muted mb-2">' + meta.fields_count + ' campos · ' + meta.submissions_count + ' envíos' + unread + '</div>',
            '  <div class="d-flex gap-1 flex-wrap">',
            '    <a href="' + meta.edit_url + '" target="_blank" class="btn btn-sm btn-outline-primary">Editar</a>',
            '    <a href="' + meta.inbox_url + '" target="_blank" class="btn btn-sm btn-outline-secondary">Inbox</a>',
            '    <a href="' + meta.analytics_url + '" target="_blank" class="btn btn-sm btn-outline-secondary">Analytics</a>',
            '  </div>',
            '</div>',
        ].join('');

        $actions.append(html);
    }

    function onVeBlockSelected(el) {
        $('#ve-inspector-form-meta').remove();
        if (!el) return;

        // Busca el sentinel de form más cercano
        var sentinel = el.closest('[data-ve-sc]');
        if (!sentinel) return;
        var formId = parseFormIdFromSentinel(sentinel);
        if (!formId) return;

        window.FormsVE.fetchMeta(formId).then(function (meta) {
            if (meta) renderFormMetaInInspector(meta);
            markBrokenSentinel(sentinel, meta);
        });
    }

    // Escuchar eventos del VE: postMessage + click en iframe
    window.addEventListener('message', function (e) {
        var data = e.data || {};
        if (data.type === 've-block-selected' && data.scNodeId) {
            var frame = document.getElementById('ve-preview-frame');
            if (!frame || !frame.contentDocument) return;
            var el = frame.contentDocument.querySelector('[data-ve-node-id="' + data.scNodeId + '"]');
            onVeBlockSelected(el);
        }
        if (data.type === 've-block-deselected') {
            $('#ve-inspector-form-meta').remove();
        }
    });

    /* =========================================================
       4) INDICADOR VISUAL DE FORMS ROTOS
    ========================================================= */
    function markBrokenSentinel(sentinel, meta) {
        if (!sentinel) return;
        // El iframe recibe el marcado visual
        var isBroken = !meta || !meta.is_active || meta.is_expired;
        if (isBroken) {
            sentinel.classList.add('ve-form-broken');
            if (!sentinel.querySelector('.ve-form-broken-badge')) {
                var badge = sentinel.ownerDocument.createElement('div');
                badge.className = 've-form-broken-badge';
                badge.textContent = meta ? (meta.is_expired ? 'Formulario expirado' : 'Formulario inactivo') : 'Formulario no encontrado';
                sentinel.appendChild(badge);
            }
        } else {
            sentinel.classList.remove('ve-form-broken');
            var existing = sentinel.querySelector('.ve-form-broken-badge');
            if (existing) existing.remove();
        }
    }

    function scanAllSentinelsForBrokenForms() {
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;
        var sentinels = frame.contentDocument.querySelectorAll('[data-ve-sc]');
        sentinels.forEach(function (s) {
            var formId = parseFormIdFromSentinel(s);
            if (!formId) return;
            window.FormsVE.fetchMeta(formId).then(function (meta) {
                markBrokenSentinel(s, meta);
            });
        });
    }

    // Inyectar estilos en el iframe + reescaneo periódico
    function injectIframeStyles() {
        var frame = document.getElementById('ve-preview-frame');
        if (!frame || !frame.contentDocument) return;
        if (frame.contentDocument.getElementById('ve-forms-integration-style')) return;
        var style = frame.contentDocument.createElement('style');
        style.id = 've-forms-integration-style';
        style.textContent = `
            .ve-form-broken { position: relative; outline: 2px dashed #dc3545 !important; outline-offset: 4px; }
            .ve-form-broken-badge {
                position: absolute; top: 0; right: 0; background: #dc3545; color: #fff;
                font-size: 11px; padding: 2px 8px; border-radius: 0 0 0 6px; z-index: 999;
            }
        `;
        frame.contentDocument.head.appendChild(style);
    }

    // Lanzar escaneo cuando el iframe termina de cargar
    $(window).on('load', function () {
        setTimeout(function () {
            injectIframeStyles();
            scanAllSentinelsForBrokenForms();
        }, 1500);
    });

    $(document).on('click', '#btn-refresh-blocks', function () {
        setTimeout(scanAllSentinelsForBrokenForms, 400);
    });

    /* =========================================================
       5) QUICK-CREATE FORM desde el panel de shortcodes
    ========================================================= */
    function renderQuickCreateButton() {
        if (!window.FormsVE.quickStoreUrl) return;
        if ($('#ve-btn-quick-form').length) return;

        var $host = $('.ve-panel-head-actions').first();
        if (!$host.length) return;

        var $btn = $('<button type="button" class="ve-ibtn" id="ve-btn-quick-form" title="Crear formulario rápido"><i class="fa-solid fa-wand-magic-sparkles"></i></button>');
        $host.append($btn);

        $btn.on('click', function () {
            if ($('#ve-quick-form-modal').length === 0) {
                var templates = window.FormsVE.templates || [];
                var opts = templates.map(function (t) {
                    return '<option value="' + t + '">' + t + '</option>';
                }).join('');
                $('body').append([
                    '<div class="modal fade" id="ve-quick-form-modal" tabindex="-1" aria-hidden="true">',
                    '  <div class="modal-dialog modal-dialog-centered">',
                    '    <div class="modal-content">',
                    '      <div class="modal-header"><h6 class="modal-title fw-bold">Crear formulario rápido</h6>',
                    '      <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>',
                    '      <div class="modal-body">',
                    '        <div class="mb-3"><label class="form-label">Nombre del formulario</label>',
                    '        <input type="text" id="ve-qf-name" class="form-control" placeholder="Contacto Landing"></div>',
                    '        <div class="mb-3"><label class="form-label">Plantilla base</label>',
                    '        <select id="ve-qf-template" class="form-select">' + opts + '</select></div>',
                    '      </div>',
                    '      <div class="modal-footer flex-column align-items-stretch">',
                    '        <button type="button" class="btn btn-primary w-100 mb-2" id="ve-qf-confirm">Crear e insertar shortcode</button>',
                    '        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>',
                    '      </div></div></div></div>',
                ].join(''));
            }
            new bootstrap.Modal(document.getElementById('ve-quick-form-modal')).show();
        });
    }

    $(document).on('click', '#ve-qf-confirm', function () {
        var name = $('#ve-qf-name').val().trim();
        var template = $('#ve-qf-template').val();
        if (!name) {
            if (window.showToast) showToast('<i class="fas fa-exclamation-triangle me-1"></i>Ingresa un nombre.');
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Creando…');

        $.ajax({
            url: window.FormsVE.quickStoreUrl,
            method: 'POST',
            data: { _token: window.FormsVE.csrf(), name: name, template: template },
        }).done(function (res) {
            bootstrap.Modal.getInstance(document.getElementById('ve-quick-form-modal'))?.hide();
            var shortcode = res.data.shortcode || ('[form id="' + res.data.id + '" /]');
            // Insertar en el canvas
            var frame = document.getElementById('ve-preview-frame');
            var ck = frame?.contentDocument?.querySelector('.ck-content');
            if (ck) {
                var safeTag = shortcode.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/\[/g, '&#91;').replace(/\]/g, '&#93;');
                var div = frame.contentDocument.createElement('div');
                div.setAttribute('data-ve-sc', safeTag);
                div.innerHTML = '<p class="text-muted">[formulario recién creado · recarga para ver preview]</p>';
                ck.appendChild(div);
                if (window.isModified !== undefined) window.isModified = true;
            }
            if (window.showToast) showToast('<i class="fa-solid fa-check me-1"></i>Formulario "' + name + '" creado.');
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.errors
                ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                : 'Error creando el formulario.';
            if (window.showToast) showToast('<i class="fas fa-exclamation-triangle me-1"></i>' + msg);
        }).always(function () {
            $btn.prop('disabled', false).text('Crear e insertar shortcode');
        });
    });

    $(function () {
        setTimeout(renderQuickCreateButton, 300);
    });
})(jQuery);
</script>
