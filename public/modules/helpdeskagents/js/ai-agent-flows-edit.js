/**
 * ai-agent-flows-edit.js — HelpdeskAgents module
 *
 * Editor visual de un flujo de IA (managers/ai-agent/flows/edit.blade.php,
 * pestaña "Nodos y conexiones"): alta/baja/orden de nodos, edición de
 * conexiones (edges), sincronización con el editor JSON avanzado y guardado
 * de la estructura contra el backend.
 *
 * Depende de: jQuery, toastr, SortableJS (globales) y
 * window.HelpdeskAgentsFlowEdit (estado inicial y URLs, inyectados por la
 * propia vista).
 */
(function ($) {
    'use strict';

    var config = window.HelpdeskAgentsFlowEdit || {};
    var STRUCTURE_URL = config.structureUrl;
    var NODE_TYPES = config.nodeTypes || {};

    // El tipo de nodo es una categoría, no un estado: todos en gris neutro.
    // Antes cada tipo llevaba un color de Bootstrap distinto (azul, cian,
    // ámbar, verde, gris) y el listado parecía un semáforo sin significado.
    var NODE_TYPE_COLORS = {
        input: 'secondary', prompt: 'secondary', condition: 'secondary', action: 'secondary', output: 'secondary',
    };

    var nodes = config.nodes || [];
    var edges = config.edges || [];

    // ==================== Helpers ====================

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function generateId() {
        return 'node-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
    }

    function updateBadge() {
        var count = $('#nodes-list .node-item').length;
        $('#badge-node-count').text(count);
    }

    function syncJsonTextareas() {
        var n = collectNodes();
        var e = collectEdges();
        $('#json-nodes').val(JSON.stringify(n, null, 2));
        $('#json-edges').val(JSON.stringify(e, null, 2));
    }

    // ==================== Render nodes ====================

    function nodeTypeOptions(selected) {
        return Object.entries(NODE_TYPES)
            .map(function (entry) {
                var val = entry[0];
                var label = entry[1];
                return '<option value="' + val + '" ' + (val === selected ? 'selected' : '') + '>' + label + '</option>';
            })
            .join('');
    }

    function renderNode(node) {
        var color = NODE_TYPE_COLORS[node.type] || 'secondary';
        var configJson = node.data ? JSON.stringify(node.data, null, 2) : '';
        return '\
            <div class="list-group-item node-item py-3" data-id="' + node.id + '">\
                <div class="d-flex align-items-start gap-3">\
                    <div class="node-handle pt-1">\
                        <i class="fas fa-grip-vertical"></i>\
                    </div>\
                    <div class="flex-grow-1">\
                        <div class="row g-2 mb-2">\
                            <div class="col-12 col-md-6">\
                                <label class="form-label form-label-sm mb-1">Nombre del nodo</label>\
                                <input type="text" class="form-control form-control-sm node-label"\
                                       value="' + escHtml(node.label || '') + '" placeholder="Ej: Bienvenida">\
                            </div>\
                            <div class="col-12 col-md-6">\
                                <label class="form-label form-label-sm mb-1">Tipo</label>\
                                <select class="form-select form-select-sm node-type">\
                                    ' + nodeTypeOptions(node.type) + '\
                                </select>\
                            </div>\
                        </div>\
                        <div>\
                            <label class="form-label form-label-sm mb-1">Config JSON <small class="text-muted">(opcional)</small></label>\
                            <textarea class="form-control form-control-sm font-monospace node-config"\
                                      rows="2" placeholder="{}">' + escHtml(configJson) + '</textarea>\
                        </div>\
                    </div>\
                    <div class="ms-1 pt-1">\
                        <div class="dropdown">\
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">\
                                <i class="fas fa-ellipsis-vertical"></i>\
                            </button>\
                            <ul class="dropdown-menu dropdown-menu-end">\
                                <li><button class="dropdown-item btn-remove-node" type="button">Eliminar nodo</button></li>\
                            </ul>\
                        </div>\
                    </div>\
                </div>\
                <div class="mt-2">\
                    <span class="badge bg-' + color + '-subtle text-' + color + ' small">' + (NODE_TYPES[node.type] || node.type) + '</span>\
                    <small class="text-muted ms-2">ID: ' + escHtml(node.id) + '</small>\
                </div>\
            </div>\
        ';
    }

    function renderNodes() {
        var $list = $('#nodes-list');
        var $empty = $('#nodes-empty');
        $list.empty();

        if (nodes.length === 0) {
            $list.addClass('d-none');
            $empty.removeClass('d-none');
        } else {
            $list.removeClass('d-none');
            $empty.addClass('d-none');
            nodes.forEach(function (node) { $list.append(renderNode(node)); });
        }

        updateBadge();
        syncJsonTextareas();
    }

    // ==================== Render edges ====================

    function nodeSelectOptions(selectedId) {
        var placeholder = '<option value="">-- Seleccionar --</option>';
        var opts = nodes.map(function (n) {
            return '<option value="' + escHtml(n.id) + '" ' + (n.id === selectedId ? 'selected' : '') + '>' + escHtml(n.label || n.id) + '</option>';
        }).join('');
        return placeholder + opts;
    }

    function renderEdgeRow(edge, index) {
        return '\
            <tr data-idx="' + index + '">\
                <td>\
                    <select class="form-select form-select-sm edge-source">\
                        ' + nodeSelectOptions(edge.source) + '\
                    </select>\
                </td>\
                <td>\
                    <select class="form-select form-select-sm edge-target">\
                        ' + nodeSelectOptions(edge.target) + '\
                    </select>\
                </td>\
                <td>\
                    <input type="text" class="form-control form-control-sm edge-label"\
                           value="' + escHtml(edge.label || '') + '" placeholder="Opcional">\
                </td>\
                <td class="text-end">\
                    <div class="dropdown">\
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">\
                            <i class="fas fa-ellipsis-vertical"></i>\
                        </button>\
                        <ul class="dropdown-menu dropdown-menu-end">\
                            <li><button class="dropdown-item btn-remove-edge" type="button">Eliminar</button></li>\
                        </ul>\
                    </div>\
                </td>\
            </tr>\
        ';
    }

    function renderEdges() {
        var $body = $('#edges-body');
        var $empty = $('#edges-empty');
        $body.empty();

        if (edges.length === 0) {
            $('#edges-table').addClass('d-none');
            $empty.removeClass('d-none');
        } else {
            $('#edges-table').removeClass('d-none');
            $empty.addClass('d-none');
            edges.forEach(function (edge, i) { $body.append(renderEdgeRow(edge, i)); });
        }

        syncJsonTextareas();
    }

    // ==================== Collect state from DOM ====================

    function collectNodes() {
        var collected = [];
        $('#nodes-list .node-item').each(function () {
            var id = $(this).data('id');
            var label = $(this).find('.node-label').val().trim();
            var type = $(this).find('.node-type').val();
            var configRaw = $(this).find('.node-config').val().trim();
            var data = {};
            if (configRaw) {
                try { data = JSON.parse(configRaw); } catch (e) { data = { raw: configRaw }; }
            }
            collected.push({ id: id, label: label, type: type, data: data });
        });
        return collected;
    }

    function collectEdges() {
        var collected = [];
        $('#edges-body tr').each(function () {
            var source = $(this).find('.edge-source').val();
            var target = $(this).find('.edge-target').val();
            var label = $(this).find('.edge-label').val().trim();
            if (source && target) {
                collected.push({ id: 'e-' + source + '-' + target, source: source, target: target, label: label });
            }
        });
        return collected;
    }

    // ==================== Save structure ====================

    function saveStructure(nodesData, edgesData, successMsg) {
        $.ajax({
            url: STRUCTURE_URL,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ nodes: nodesData, edges: edgesData }),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
            success: function (res) {
                nodes = nodesData;
                edges = edgesData;
                updateBadge();
                syncJsonTextareas();
                toastr.success(res.message || successMsg || 'Estructura guardada', 'Éxito');
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar la estructura';
                toastr.error(msg, 'Error');
            },
        });
    }

    $(document).ready(function () {
        if (config.flashSuccess) {
            toastr.success(config.flashSuccess, 'Éxito');
        }
        if (config.flashError) {
            toastr.error(config.flashError, 'Error');
        }

        var sortable = new Sortable(document.getElementById('nodes-list'), {
            handle: '.node-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                updateBadge();
                syncJsonTextareas();
            },
        });

        // Add node
        $('#btn-add-node').on('click', function () {
            nodes.push({ id: generateId(), label: '', type: 'input', data: {} });
            renderNodes();
            renderEdges();
            $('#nodes-list .node-item:last-child .node-label').focus();
        });

        // Remove node (event delegation)
        $(document).on('click', '.btn-remove-node', function () {
            var $item = $(this).closest('.node-item');
            var id = $item.data('id');
            // Remove from edges too
            edges = edges.filter(function (e) { return e.source !== id && e.target !== id; });
            nodes = nodes.filter(function (n) { return n.id !== id; });
            $item.remove();
            updateBadge();
            renderEdges();
            syncJsonTextareas();

            if ($('#nodes-list .node-item').length === 0) {
                $('#nodes-list').addClass('d-none');
                $('#nodes-empty').removeClass('d-none');
            }
        });

        // Sync JSON on node input changes
        $(document).on('input change', '.node-label, .node-type, .node-config', function () {
            syncJsonTextareas();
        });

        // Add edge
        $('#btn-add-edge').on('click', function () {
            edges.push({ id: generateId(), source: '', target: '', label: '' });
            renderEdges();
        });

        // Remove edge
        $(document).on('click', '.btn-remove-edge', function () {
            var idx = $(this).closest('tr').data('idx');
            edges.splice(idx, 1);
            renderEdges();
        });

        // Save structure button
        $('#btn-save-structure').on('click', function () {
            saveStructure(collectNodes(), collectEdges(), 'Estructura guardada correctamente');
        });

        // Save raw JSON
        $('#btn-save-json').on('click', function () {
            var n, e;
            try {
                n = JSON.parse($('#json-nodes').val() || '[]');
                e = JSON.parse($('#json-edges').val() || '[]');
            } catch (err) {
                toastr.error('El JSON no es válido. Revisa la sintaxis.', 'Error');
                return;
            }
            nodes = n;
            edges = e;
            saveStructure(n, e, 'JSON guardado correctamente');
            renderNodes();
            renderEdges();
        });

        renderNodes();
        renderEdges();
    });
})(jQuery);
