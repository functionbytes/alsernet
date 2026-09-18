@extends('layouts.theme')

@section('title', 'Editar flujo: ' . $flow->name)

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskagents/css/ai-settings.css') }}?v={{ @filemtime(public_path('modules/helpdeskagents/css/ai-settings.css')) }}">
@endpush

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

    {{-- Pestañas de la página: mismas .nav-tabs-underline del tema que en Ajustes IA --}}
    <ul class="nav nav-tabs-underline" id="flowTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-meta" data-bs-toggle="tab" data-bs-target="#pane-meta" type="button" role="tab">Metadatos</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-nodes" data-bs-toggle="tab" data-bs-target="#pane-nodes" type="button" role="tab">
                Nodos y conexiones
                <span class="ais-tab-count" id="badge-node-count">{{ count($flow->nodes ?? []) }}</span>
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

                                <h6 class="fw-semibold mb-1">Información básica</h6>
                                <p class="text-muted small mb-3">Nombre y descripción del flujo de IA</p>
                                <div class="row g-3 mb-4">

                                    <div class="col-12">
                                        <label class="form-label">Nombre <span class="ais-required">· obligatorio</span></label>
                                        <input type="text" name="name"
                                               class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name', $flow->name) }}"
                                               required>
                                        @error('name')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Descripción</label>
                                        <textarea name="description" rows="3"
                                                  class="form-control @error('description') is-invalid @enderror"
                                                  placeholder="Describe el propósito de este flujo…">{{ old('description', $flow->description) }}</textarea>
                                        @error('description')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>

                                <h6 class="fw-semibold mb-1">Trigger</h6>
                                <p class="text-muted small mb-3">Evento que activa la ejecución de este flujo</p>
                                <div class="row g-3 mb-4">

                                    <div class="col-12">
                                        <label class="form-label">Activador <span class="ais-required">· obligatorio</span></label>
                                        <select name="trigger" class="form-select @error('trigger') is-invalid @enderror" required>
                                            <option value="message" {{ old('trigger', $flow->trigger) === 'message' ? 'selected' : '' }}>
                                                Mensaje — se activa al recibir un mensaje específico
                                            </option>
                                            <option value="intent" {{ old('trigger', $flow->trigger) === 'intent' ? 'selected' : '' }}>
                                                Intención — se activa al detectar una intención del usuario
                                            </option>
                                            <option value="keyword" {{ old('trigger', $flow->trigger) === 'keyword' ? 'selected' : '' }}>
                                                Palabra clave — se activa cuando se menciona una palabra clave
                                            </option>
                                            <option value="conversation_start" {{ old('trigger', $flow->trigger) === 'conversation_start' ? 'selected' : '' }}>
                                                Inicio de conversación — se activa al comenzar una nueva sesión
                                            </option>
                                        </select>
                                        @error('trigger')
                                            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>

                                <h6 class="fw-semibold mb-1">Configuración</h6>
                                <p class="text-muted small mb-3">Publicación y estado del flujo</p>
                                <div class="row g-3">

                                    <div class="col-12">
                                        <label class="form-label">Estado actual</label>
                                        <div class="form-control bg-light d-flex align-items-center gap-2">
                                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                {{ $statusLabels[$flow->status] ?? $flow->status }}
                                            </span>
                                            <small class="text-muted">
                                                @if($flow->status === 'draft')
                                                    Aún no publicado
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
                                Aquí editas los metadatos del flujo. Para diseñar los nodos y la lógica usa la pestaña «Nodos y conexiones».
                            </p>
                        </div>
                        <hr class="my-0">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Buenas prácticas</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres descriptivos que reflejen el objetivo del flujo</li>
                                <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Elige el activador que mejor se adapte al contexto</li>
                                <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Publica el flujo solo cuando esté completamente diseñado y probado</li>
                            </ul>
                        </div>
                        <hr class="my-0">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Información del registro</h6>
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
                                <p class="text-muted small mb-3">Cuando el flujo esté listo, publícalo para activarlo en conversaciones.</p>
                                <form action="{{ route('helpdesk.ai.flows.publish', $flow) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100">Publicar flujo</button>
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
                            <button type="button" class="btn btn-sm btn-primary" id="btn-add-node">Agregar nodo</button>
                        </div>
                        <div class="card-body p-0">
                            <div id="nodes-list" class="list-group list-group-flush">
                                {{-- Nodes rendered by JS --}}
                            </div>
                            <div id="nodes-empty" class="text-center py-5 text-muted d-none">
                                <i class="fas fa-diagram-project fa-2x mb-2 d-block"></i>
                                <small>No hay nodos aún. Pulsa «Agregar nodo» para comenzar.</small>
                            </div>
                        </div>
                    </div>

                    {{-- Edges card --}}
                    <div class="card mb-3">
                        <div class="card-header border-bottom p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-0 fw-bold">Conexiones (edges)</h6>
                                <small class="text-muted">Define cómo se conectan los nodos entre sí.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-edge">Agregar conexión</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="edges-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Desde</th>
                                            <th>Hasta</th>
                                            <th>Etiqueta</th>
                                            <th class="text-end">Acción</th>
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
                        <button type="button" class="btn btn-primary flex-grow-1" id="btn-save-structure">Guardar estructura</button>
                    </div>

                    {{-- JSON fallback accordion --}}
                    <div class="accordion" id="acc-json">
                        <div class="accordion-item border">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#json-pane">
                                    Editar JSON avanzado
                                </button>
                            </h2>
                            <div id="json-pane" class="accordion-collapse collapse" data-bs-parent="#acc-json">
                                <div class="accordion-body">
                                    <p class="text-muted small mb-2">Edita el JSON directamente. Al guardar, sobrescribe los nodos y conexiones.</p>
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
                                    <button type="button" class="btn btn-secondary w-100" id="btn-save-json">Guardar JSON directamente</button>
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
                                    <span class="badge bg-secondary-subtle text-secondary">input</span>
                                    <small class="text-muted">Entrada — recibe datos del usuario</small>
                                </li>
                                <li class="mb-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary">prompt</span>
                                    <small class="text-muted">Prompt — procesa con IA</small>
                                </li>
                                <li class="mb-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary">condition</span>
                                    <small class="text-muted">Condición — ramifica según lógica</small>
                                </li>
                                <li class="mb-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary">action</span>
                                    <small class="text-muted">Acción — ejecuta una operación</small>
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
                                <li class="mb-2 text-muted small"><i class="fas fa-info-circle text-primary me-2"></i> Usa <strong>condition</strong> para bifurcar según variables.</li>
                                <li class="text-muted small"><i class="fas fa-info-circle text-primary me-2"></i> El campo "Config JSON" acepta pares clave-valor en formato JSON.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
window.HelpdeskAgentsFlowEdit = {
    structureUrl: '{{ route('helpdesk.ai.flows.structure', $flow) }}',
    nodeTypes: @json($nodeTypes),
    nodes: @json($flow->nodes ?? []),
    edges: @json($flow->edges ?? []),
    flashSuccess: @json(session('success')),
    flashError: @json(session('error')),
};
</script>
<script src="{{ asset('modules/helpdeskagents/js/ai-agent-flows-edit.js') }}?v={{ @filemtime(public_path('modules/helpdeskagents/js/ai-agent-flows-edit.js')) }}" defer></script>
@endpush
