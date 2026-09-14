@extends('layouts.theme')

@section('title', 'Agente IA · Helpdesk')

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskagents/css/agents.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskagents/css/ai-settings.css') }}?v={{ @filemtime(public_path('modules/helpdeskagents/css/ai-settings.css')) }}">
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Agente IA · Helpdesk'])
@endsection

@section('content')
<div class="ais-page">

    {{-- ── Cabecera ─────────────────────────────────────────────────── --}}
    <div class="ais-head">
        <div class="ais-head-text">
            <div class="ais-eyebrow">Helpdesk · Inteligencia artificial</div>
            <h1 class="ais-title">Agente IA</h1>
            <p class="ais-subtitle">Quién es el agente, con qué modelo responde y con qué instrucciones trabaja.</p>
        </div>
        @if ($hasAgent)
            <div class="ais-head-actions">
                <button type="button" class="btn btn-primary" id="btn-save-top">Guardar cambios</button>
            </div>
        @endif
    </div>

    @include('core::components.alerts')

    @if ($errors->any())
        <div class="alert alert-warning" role="alert">
            <strong>No se pudo guardar</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Tira de estado ───────────────────────────────────────────── --}}
    <div class="ais-status">
        @if ($hasAgent)
            @php
                $dotClass = match ($agent->status) {
                    'active' => 'ais-dot-active',
                    'paused' => 'ais-dot-paused',
                    default => '',
                };
            @endphp
            <div class="ais-status-state">
                <span class="ais-dot {{ $dotClass }}"></span>
                <span class="ais-status-label">{{ $statuses[$agent->status] ?? $agent->status }}</span>
            </div>
            <div class="ais-status-sep"></div>
            <div class="ais-status-body">
                <div class="ais-status-name">{{ $agent->name }}</div>
                <div class="ais-status-meta">
                    {{ $providers[$agent->provider]['label'] ?? $agent->provider }} · {{ $agent->model }}@if ($agent->enabled_at) · en marcha desde el {{ $agent->enabled_at->format('d/m/Y') }}@endif
                </div>
            </div>
            <div class="ais-status-side">
                {{ ($agent->getApiKey() ?? null) ? 'Clave de API guardada' : 'Sin clave de API' }}
            </div>
        @else
            <div class="ais-status-state">
                <span class="ais-dot"></span>
                <span class="ais-status-label">Sin configurar</span>
            </div>
            <div class="ais-status-sep"></div>
            <div class="ais-status-body">
                <span class="ais-status-hint">Todavía no hay ningún agente. Nada de lo que se configure aquí afecta a las conversaciones hasta que lo actives.</span>
            </div>
        @endif
    </div>

    {{-- ── Pestañas ─────────────────────────────────────────────────── --}}
    <ul class="nav nav-tabs-underline mb-0" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-settings" role="tab" aria-selected="true">Configuración</a>
        </li>
        @if ($hasAgent)
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-tags" role="tab" aria-selected="false">Etiquetas <span class="ais-tab-count" id="tags-count">0</span></a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-tools" role="tab" aria-selected="false">Herramientas <span class="ais-tab-count" id="tools-count">0</span></a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-knowledge" role="tab" aria-selected="false">Base de conocimiento <span class="ais-tab-count" id="knowledge-count">0</span></a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="{{ route('helpdesk.ai.flows.index') }}">Flujos</a>
            </li>
        @else
            <li class="nav-item" role="presentation"><span class="nav-link ais-tab-disabled">Etiquetas</span></li>
            <li class="nav-item" role="presentation"><span class="nav-link ais-tab-disabled">Herramientas</span></li>
            <li class="nav-item" role="presentation"><span class="nav-link ais-tab-disabled">Base de conocimiento</span></li>
            <li class="nav-item" role="presentation"><span class="nav-link ais-tab-disabled">Flujos</span></li>
            <li class="ais-tabs-note">Disponibles cuando el agente exista</li>
        @endif
    </ul>

    {{-- ── Contenido de pestañas ────────────────────────────────────── --}}
    <div class="tab-content" id="aiAgentTabContent">

        <div class="tab-pane fade show active" id="tab-settings" role="tabpanel">

            @php
                // Tras un fallo de validación el formulario tiene que quedar a la
                // vista aunque no haya agente: si volviera la guía, los errores y
                // lo ya tecleado quedarían escondidos detrás del botón.
                $showForm = $hasAgent || $errors->any();
            @endphp

            @unless ($showForm)
                {{-- Primer arranque: guía de tres pasos; el formulario espera detrás. --}}
                <div class="ais-body" id="ais-setup">
                    <div class="ais-main">
                        <div class="ais-card ais-setup">
                            <div class="ais-setup-intro">
                                <h2 class="ais-section-title">Crea el primer agente</h2>
                                <p class="ais-subtitle mb-0">Son tres decisiones. Puedes dejarlo en Inactivo y probarlo con calma: no responde a nadie hasta que tú lo actives.</p>
                            </div>
                            <div>
                                <div class="ais-step">
                                    <span class="ais-step-num">1</span>
                                    <div>
                                        <div class="ais-step-title">Elige proveedor y modelo</div>
                                        <div class="ais-step-body">Anthropic, OpenAI, Google Gemini o un modelo local por Ollama. Puedes cambiarlo después sin perder el resto.</div>
                                    </div>
                                </div>
                                <div class="ais-step">
                                    <span class="ais-step-num">2</span>
                                    <div>
                                        <div class="ais-step-title">Pega la clave de API</div>
                                        <div class="ais-step-body">Se guarda cifrada. El botón <strong>Probar</strong> confirma que la clave vale antes de guardar nada.</div>
                                    </div>
                                </div>
                                <div class="ais-step">
                                    <span class="ais-step-num">3</span>
                                    <div>
                                        <div class="ais-step-title">Escribe las instrucciones</div>
                                        <div class="ais-step-body">Qué tono usa, qué puede prometer y cuándo tiene que pasarle la conversación a una persona. Es lo que más nota el cliente.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="ais-setup-cta">
                                <button type="button" class="btn btn-primary" id="ais-setup-start">Configurar el agente</button>
                                <span class="ais-counter">Se crea en estado Inactivo</span>
                            </div>
                        </div>
                    </div>
                    <div class="ais-rail">
                        @include('helpdeskagents::managers.ai-agent.partials.settings-rail', ['showUsage' => false])
                        <div class="ais-note-card">
                            <h3 class="ais-note-title">Sin coste hasta activarlo</h3>
                            <p class="ais-note-body">Guardar la configuración no llama al proveedor. Solo se consume cuando el agente está Activo y atiende una conversación.</p>
                        </div>
                    </div>
                </div>
            @endunless

            <div @unless ($showForm) hidden @endunless id="ais-form-wrap">
                @include('helpdeskagents::managers.ai-agent.partials.settings-tab', ['agent' => $agent, 'providers' => $providers, 'statuses' => $statuses, 'hasAgent' => $hasAgent, 'usage' => $usage ?? null])
            </div>

        </div>

        <div class="tab-pane fade" id="tab-tags" role="tabpanel">
            <div id="tags-container">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando…</span></div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-tools" role="tabpanel">
            <div id="tools-container">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando…</span></div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-knowledge" role="tabpanel">
            <div id="knowledge-container">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando…</span></div>
                </div>
            </div>
        </div>

    </div>
</div>

@include('core::components.delete')

{{-- Modales --}}
@include('helpdeskagents::managers.ai-agent.modals.tag-modal')
@include('helpdeskagents::managers.ai-agent.modals.tool-modal')
@include('helpdeskagents::managers.ai-agent.modals.knowledge-modal')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Primer arranque: la guía cede el sitio al formulario.
    $('#ais-setup-start').on('click', function () {
        document.getElementById('ais-setup').hidden = true;
        document.getElementById('ais-form-wrap').hidden = false;
        document.getElementById('ais-form-wrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // El "Guardar cambios" de la cabecera envía el mismo formulario.
    $('#btn-save-top').on('click', function () {
        document.getElementById('settingsForm')?.requestSubmit();
    });

    // Carga perezosa de pestañas: cada una pide su parcial la primera vez.
    const loadedTabs = { settings: true, tags: false, tools: false, knowledge: false };

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const tabName = $(e.target).attr('href').replace('#tab-', '');

        if (loadedTabs[tabName] === false) {
            loadTabContent(tabName);
            loadedTabs[tabName] = true;
        }
    });

    function loadTabContent(tabName) {
        const container = $(`#${tabName}-container`);

        const urls = {
            tags: '{{ route("helpdesk.ai.tags.index") }}',
            tools: '{{ route("helpdesk.ai.tools.index") }}',
            knowledge: '{{ route("helpdesk.ai.knowledge.index") }}',
        };

        if (!urls[tabName]) {
            return;
        }

        $.ajax({
            url: urls[tabName],
            method: 'GET',
            success: function (response) {
                container.html(response);
                updateTabCounter(tabName);
            },
            error: function () {
                container.html('<div class="alert alert-warning">Error al cargar el contenido. Vuelve a intentarlo.</div>');
            }
        });
    }

    function updateTabCounter(tabName) {
        const count = $(`#${tabName}-container`).find('[data-count-item]').length;
        $(`#${tabName}-count`).text(count);
    }

    window.showSuccess = function (message) { toastr.success(message, 'Éxito'); };
    window.showError = function (message) { toastr.error(message, 'Error'); };

    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endpush
