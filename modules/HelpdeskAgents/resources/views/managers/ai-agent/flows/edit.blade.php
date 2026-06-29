@extends('layouts.theme')

@section('title', 'Editar flujo: ' . $flow->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Editar flujo'])
@endsection

@section('content')

    @php
        $statusColor = match($flow->status) {
            'published' => 'success',
            'archived'  => 'secondary',
            default     => 'warning',
        };
        $statusLabels = ['draft' => 'Borrador', 'published' => 'Publicado', 'archived' => 'Archivado'];
    @endphp

    {{-- Page-level tabs --}}
    <ul class="nav nav-tabs mb-3" id="flowTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-meta" data-bs-toggle="tab" data-bs-target="#pane-meta" type="button" role="tab">
                <i class="fas fa-sliders me-1"></i> Metadatos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-nodes" data-bs-toggle="tab" data-bs-target="#pane-nodes" type="button" role="tab">
                <i class="fas fa-diagram-project me-1"></i> Nodos y conexiones
                <span class="badge bg-secondary ms-1" id="badge-node-count">{{ count($flow->nodes ?? []) }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="flowTabsContent">

        {{-- ==================== TAB: METADATOS ==================== --}}
        <div class="tab-pane fade show active" id="pane-meta" role="tabpanel">
            <div class="row g-3">

                <div class="col-12 col-lg-8">
                    <div class="card">
                        <form action="{{ route('helpdesk.ai.flows.update', $flow) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="card-header border-bottom p-3">
                                <h5 class="mb-0 fw-bold">Editar: {{ $flow->name }}</h5>
                                <small class="text-muted">Modifica los metadatos del flujo de IA</small>
                            </div>

                            <div class="card-body">
                                @include('core::components.alerts')

                                <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                                <p class="text-muted small mb-3">Nombre y descripcion del flujo de IA</p>
                                <div class="row g-3 mb-4">

                                    <div class="col-12">
                                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" name="name"
                                               class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name', $flow->name) }}"
                                               required>
                                        @error('name')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Descripcion</label>
                                        <textarea name="description" rows="3"
                                                  class="form-control @error('description') is-invalid @enderror"
                                                  placeholder="Describe el proposito de este flujo...">{{ old('description', $flow->description) }}</textarea>
                                        @error('description')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>

                                <h6 class="fw-semibold mb-1 border-bottom pb-2">Trigger</h6>
                                <p class="text-muted small mb-3">Evento que activa la ejecucion de este flujo</p>
                                <div class="row g-3 mb-4">

                                    <div class="col-12">
                                        <label class="form-label">Activador <span class="text-danger">*</span></label>
                                        <select name="trigger" class="form-select @error('trigger') is-invalid @enderror" required>
                                            <option value="message" {{ old('trigger', $flow->trigger) === 'message' ? 'selected' : '' }}>
                                                Mensaje — se activa al recibir un mensaje especifico
                                            </option>
                                            <option value="intent" {{ old('trigger', $flow->trigger) === 'intent' ? 'selected' : '' }}>
                                                Intencion — se activa al detectar una intencion del usuario
                                            </option>
                                            <option value="keyword" {{ old('trigger', $flow->trigger) === 'keyword' ? 'selected' : '' }}>
                                                Palabra clave — se activa cuando se menciona una palabra clave
                                            </option>
                                            <option value="conversation_start" {{ old('trigger', $flow->trigger) === 'conversation_start' ? 'selected' : '' }}>
                                                Inicio de conversacion — se activa al comenzar una nueva sesion
                                            </option>
                                        </select>
                                        @error('trigger')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>

                                <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion</h6>
                                <p class="text-muted small mb-3">Publicacion y estado del flujo</p>
                                <div class="row g-3">

                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Estado actual</label>
                                        <div class="form-control bg-light d-flex align-items-center gap-2">
                                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                {{ $statusLabels[$flow->status] ?? $flow->status }}
                                            </span>
                                            <small class="text-muted">
                                                @if($flow->status === 'draft')
                                                    Aun no publicado
                                                @elseif($flow->status === 'published')
                                                    Activo y funcional en conversaciones
                                                @else
                                                    Oculto del uso activo
                                                @endif
                                            </small>
                                        </div>
                                    </div>

                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                                <a href="{{ route('helpdesk.ai.flows.index') }}" class="btn btn-light w-100">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Help panel --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Sobre los flujos de IA</h6>
                            <p class="card-text text-muted">
                                Aqui editas los metadatos del flujo. Para disenar los nodos y la logica del flujo usa la pestana "Nodos y conexiones".
                            </p>
                        </div>
                        <hr class="my-0">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Buenas practicas</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres descriptivos que reflejen el objetivo del flujo</li>
                                <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Elige el trigger que mejor se adapte al contexto de activacion</li>
                                <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Publica el flujo solo cuando este completamente disenado y probado</li>
                            </ul>
                        </div>
                        <hr class="my-0">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Informacion del registro</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2 text-muted small">
                                    <span class="fw-semibold">Creado:</span> {{ $flow->created_at->format('d/m/Y H:i') }}
                                </li>
                                <li class="text-muted small">
                                    <span class="fw-semibold">Actualizado:</span> {{ $flow->updated_at->format('d/m/Y H:i') }}
                                </li>
                            </ul>
                        </div>

                        @if($flow->status === 'draft')
                            <hr class="my-0">
                            <div class="card-body">
                                <h6 class="card-title mb-3">Publicar flujo</h6>
                                <p class="text-muted small mb-3">Una vez que el flujo este listo, publicaló para activarlo en conversaciones.</p>
                                <form action="{{ route('helpdesk.ai.flows.publish', $flow) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-rocket me-1"></i> Publicar flujo
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- ==================== TAB: NODOS Y CONEXIONES ==================== --}}
        <div class="tab-pane fade" id="pane-nodes" role="tabpanel">
            <div class="row g-3">

                {{-- Node editor --}}
                <div class="col-12 col-lg-8">

                    {{-- Nodes card --}}
                    <div class="card mb-3">
                        <div class="card-header border-bottom p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-0 fw-bold">Nodos del flujo</h6>
                                <small class="text-muted">Arrastra para reordenar. Cada nodo es un paso del flujo.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="btn-add-node">
                                <i class="fas fa-plus me-1"></i> Agregar nodo
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div id="nodes-list" class="list-group list-group-flush">
                                {{-- Nodes rendered by JS --}}
                            </div>
                            <div id="nodes-empty" class="text-center py-5 text-muted d-none">
                                <i class="fas fa-diagram-project fa-2x mb-2 d-block"></i>
                                <small>No hay nodos aun. Haz clic en "Agregar nodo" para comenzar.</small>
                            </div>
                        </div>
                    </div>

                    {{-- Edges card --}}
                    <div class="card mb-3">
                        <div class="card-header border-bottom p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-0 fw-bold">Conexiones (edges)</h6>
                                <small class="text-muted">Define como se conectan los nodos entre si.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-edge">
                                <i class="fas fa-arrow-right-arrow-left me-1"></i> Agregar conexion
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="edges-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Desde</th>
                                            <th>Hasta</th>
                                            <th>Label</th>
                                            <th class="text-end">Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody id="edges-body">
                                        {{-- Edges rendered by JS --}}
                                    </tbody>
                                </table>
                            </div>
                            <div id="edges-empty" class="text-center py-4 text-muted d-none">
                                <small>No hay conexiones. Agrega nodos primero.</small>
                            </div>
                        </div>
                    </div>

                    {{-- Save structure --}}
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-primary flex-grow-1" id="btn-save-structure">
                            <i class="fas fa-save me-1"></i> Guardar estructura
                        </button>
                    </div>

                    {{-- JSON fallback accordion --}}
                    <div class="accordion" id="acc-json">
                        <div class="accordion-item border">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#json-pane">
                                    <i class="fas fa-code me-2"></i> Editar JSON avanzado
                                </button>
                            </h2>
                            <div id="json-pane" class="accordion-collapse collapse" data-bs-parent="#acc-json">
                                <div class="accordion-body">
                                    <p class="text-muted small mb-2">Edita el JSON directamente. Al guardar, sobreescribe los nodos y conexiones.</p>
                                    <div class="mb-2">
                                        <label class="form-label fw-semibold">Nodos (JSON)</label>
                                        <textarea id="json-nodes" class="form-control font-monospace" rows="8"
                                                  placeholder="[]"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Edges (JSON)</label>
                                        <textarea id="json-edges" class="form-control font-monospace" rows="5"
                                                  placeholder="[]"></textarea>
                                    </div>
                                    <button type="button" class="btn btn-warning w-100" id="btn-save-json">
                                        <i class="fas fa-bolt me-1"></i> Guardar JSON directamente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Side help --}}
                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Tipos de nodo</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary">input</span>
                                    <small class="text-muted">Entrada — recibe datos del usuario</small>
                                </li>
                                <li class="mb-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-info-subtle text-info">prompt</span>
                                    <small class="text-muted">Prompt — procesa con IA</small>
                                </li>
                                <li class="mb-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-warning-subtle text-warning">condition</span>
                                    <small class="text-muted">Condicion — ramifica segun logica</small>
                                </li>
                                <li class="mb-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-success-subtle text-success">action</span>
                                    <small class="text-muted">Accion — ejecuta una operacion</small>
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary">output</span>
                                    <small class="text-muted">Salida — responde al usuario</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Consejos</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2 text-muted small"><i class="fas fa-info-circle text-primary me-2"></i> Cada flujo debe comenzar con un nodo de tipo <strong>input</strong>.</li>
                                <li class="mb-2 text-muted small"><i class="fas fa-info-circle text-primary me-2"></i> Usa <strong>condition</strong> para bifurcar segun variables.</li>
                                <li class="text-muted small"><i class="fas fa-info-circle text-primary me-2"></i> El campo "Config JSON" acepta pares clave-valor en formato JSON.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection

@push('css')
<style>
.node-item { border-left: 3px solid #90bb13; }
.node-item .node-handle { cursor: grab; color: #adb5bd; }
.node-item .node-handle:hover { color: #6c757d; }
.node-item.sortable-ghost { opacity: 0.4; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
$(document).ready(function () {

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // ==================== State ====================

    const STRUCTURE_URL = '{{ route('helpdesk.ai.flows.structure', $flow) }}';
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    const NODE_TYPES = @json($nodeTypes);
    const NODE_TYPE_COLORS = {
        input: 'primary', prompt: 'info', condition: 'warning', action: 'success', output: 'secondary'
    };

    let nodes = @json($flow->nodes ?? []);
    let edges = @json($flow->edges ?? []);

    // ==================== Render nodes ====================

    function nodeTypeOptions(selected) {
        return Object.entries(NODE_TYPES)
            .map(([val, label]) => `<option value="${val}" ${val === selected ? 'selected' : ''}>${label}</option>`)
            .join('');
    }

    function renderNode(node, index) {
        const color = NODE_TYPE_COLORS[node.type] || 'secondary';
        const configJson = node.data ? JSON.stringify(node.data, null, 2) : '';
        return `
            <div class="list-group-item node-item py-3" data-id="${node.id}">
                <div class="d-flex align-items-start gap-3">
                    <div class="node-handle pt-1">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">Nombre del nodo</label>
                                <input type="text" class="form-control form-control-sm node-label"
                                       value="${escHtml(node.label || '')}" placeholder="Ej: Bienvenida">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">Tipo</label>
                                <select class="form-select form-select-sm node-type">
                                    ${nodeTypeOptions(node.type)}
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label form-label-sm mb-1">Config JSON <small class="text-muted">(opcional)</small></label>
                            <textarea class="form-control form-control-sm font-monospace node-config"
                                      rows="2" placeholder="{}">${escHtml(configJson)}</textarea>
                        </div>
                    </div>
                    <div class="ms-1 pt-1">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><button class="dropdown-item btn-remove-node" type="button">Eliminar nodo</button></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="badge bg-${color}-subtle text-${color} small">${NODE_TYPES[node.type] || node.type}</span>
                    <small class="text-muted ms-2">ID: ${escHtml(node.id)}</small>
                </div>
            </div>
        `;
    }

    function renderNodes() {
        const $list = $('#nodes-list');
        const $empty = $('#nodes-empty');
        $list.empty();

        if (nodes.length === 0) {
            $list.addClass('d-none');
            $empty.removeClass('d-none');
        } else {
            $list.removeClass('d-none');
            $empty.addClass('d-none');
            nodes.forEach((node, i) => $list.append(renderNode(node, i)));
        }

        updateBadge();
        syncJsonTextareas();
    }

    // ==================== Render edges ====================

    function nodeSelectOptions(selectedId) {
        const placeholder = `<option value="">-- Seleccionar --</option>`;
        const opts = nodes.map(n =>
            `<option value="${escHtml(n.id)}" ${n.id === selectedId ? 'selected' : ''}>${escHtml(n.label || n.id)}</option>`
        ).join('');
        return placeholder + opts;
    }

    function renderEdgeRow(edge, index) {
        return `
            <tr data-idx="${index}">
                <td>
                    <select class="form-select form-select-sm edge-source">
                        ${nodeSelectOptions(edge.source)}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm edge-target">
                        ${nodeSelectOptions(edge.target)}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm edge-label"
                           value="${escHtml(edge.label || '')}" placeholder="Opcional">
                </td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item btn-remove-edge" type="button">Eliminar</button></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `;
    }

    function renderEdges() {
        const $body = $('#edges-body');
        const $empty = $('#edges-empty');
        $body.empty();

        if (edges.length === 0) {
            $('#edges-table').addClass('d-none');
            $empty.removeClass('d-none');
        } else {
            $('#edges-table').removeClass('d-none');
            $empty.addClass('d-none');
            edges.forEach((edge, i) => $body.append(renderEdgeRow(edge, i)));
        }

        syncJsonTextareas();
    }

    // ==================== Collect state from DOM ====================

    function collectNodes() {
        const collected = [];
        $('#nodes-list .node-item').each(function () {
            const id = $(this).data('id');
            const label = $(this).find('.node-label').val().trim();
            const type = $(this).find('.node-type').val();
            const configRaw = $(this).find('.node-config').val().trim();
            let data = {};
            if (configRaw) {
                try { data = JSON.parse(configRaw); } catch (e) { data = { raw: configRaw }; }
            }
            collected.push({ id, label, type, data });
        });
        return collected;
    }

    function collectEdges() {
        const collected = [];
        $('#edges-body tr').each(function () {
            const source = $(this).find('.edge-source').val();
            const target = $(this).find('.edge-target').val();
            const label  = $(this).find('.edge-label').val().trim();
            if (source && target) {
                collected.push({ id: `e-${source}-${target}`, source, target, label });
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
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            success: function (res) {
                nodes = nodesData;
                edges = edgesData;
                updateBadge();
                syncJsonTextareas();
                toastr.success(res.message || successMsg || 'Estructura guardada', 'Exito');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Error al guardar la estructura';
                toastr.error(msg, 'Error');
            },
        });
    }

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
        const count = $('#nodes-list .node-item').length;
        $('#badge-node-count').text(count);
    }

    function syncJsonTextareas() {
        const n = collectNodes();
        const e = collectEdges();
        $('#json-nodes').val(JSON.stringify(n, null, 2));
        $('#json-edges').val(JSON.stringify(e, null, 2));
    }

    // ==================== Sortable --====================

    const sortable = new Sortable(document.getElementById('nodes-list'), {
        handle: '.node-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: function () {
            updateBadge();
            syncJsonTextareas();
        },
    });

    // ==================== Events ====================

    // Add node
    $('#btn-add-node').on('click', function () {
        const newNode = { id: generateId(), label: '', type: 'input', data: {} };
        nodes.push(newNode);
        renderNodes();
        renderEdges();
        $('#nodes-list .node-item:last-child .node-label').focus();
    });

    // Remove node (event delegation)
    $(document).on('click', '.btn-remove-node', function () {
        const $item = $(this).closest('.node-item');
        const id = $item.data('id');
        // Remove from edges too
        edges = edges.filter(e => e.source !== id && e.target !== id);
        nodes = nodes.filter(n => n.id !== id);
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
        const idx = $(this).closest('tr').data('idx');
        edges.splice(idx, 1);
        renderEdges();
    });

    // Save structure button
    $('#btn-save-structure').on('click', function () {
        const n = collectNodes();
        const e = collectEdges();
        saveStructure(n, e, 'Estructura guardada correctamente');
    });

    // Save raw JSON
    $('#btn-save-json').on('click', function () {
        let n, e;
        try {
            n = JSON.parse($('#json-nodes').val() || '[]');
            e = JSON.parse($('#json-edges').val() || '[]');
        } catch (err) {
            toastr.error('El JSON no es valido. Revisa la sintaxis.', 'Error');
            return;
        }
        nodes = n;
        edges = e;
        saveStructure(n, e, 'JSON guardado correctamente');
        renderNodes();
        renderEdges();
    });

    // ==================== Init ====================

    renderNodes();
    renderEdges();

});
</script>
@endpush
