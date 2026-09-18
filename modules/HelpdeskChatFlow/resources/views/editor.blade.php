@extends('layouts.theme')

@section('title', isset($chatFlow) ? 'Editar: ' . $chatFlow->name : 'Nuevo flow')

@section('page_header')
    @include('core::components.card', ['title' => isset($chatFlow) ? 'Editar flow' : 'Nuevo flow'])
@endsection

@section('content')

<div class="d-flex flex-column editor-full-height">

    {{-- Canvas + Config panel --}}
    <div class="d-flex flex-grow-1 overflow-hidden">

        {{-- React editor root --}}
        <div
            id="chatflow-editor-root"
            class="flex-grow-1"
            data-props="{{ json_encode([
                'chatFlowId'     => $chatFlow->id,
                'chatFlowName'   => $chatFlow->name,
                'chatFlowStatus' => $chatFlow->status,
                'nodes'          => $chatFlow->nodes ?? [],
                'settings'       => $chatFlow->trigger_conditions ?? [],
                'agents'         => $agents ?? [],
                'groups'         => $groups ?? [],
                'saveUrl'        => route('chatflow.update', $chatFlow),
                'publishUrl'     => route('chatflow.publish', $chatFlow),
                'indexUrl'       => route('chatflow.index'),
                'csrfToken'      => csrf_token(),
            ]) }}"
        ></div>

        {{-- Test / Preview panel --}}
        <div id="test-panel" class="test-panel d-none">
            <div class="test-panel-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                <span class="fw-semibold"><i class="fas fa-flask me-2 text-primary"></i>Probar flow</span>
                <div class="d-flex gap-2">
                    <button id="btn-test-restart" class="btn btn-sm btn-outline-secondary" title="Reiniciar">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                    <button id="btn-test-close" class="btn btn-sm btn-outline-secondary" title="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div id="test-messages" class="test-messages"></div>
            <div class="test-input-bar border-top p-2">
                <div class="input-group input-group-sm">
                    <input type="text" id="test-input" class="form-control" placeholder="Escribe un mensaje…" autocomplete="off" disabled>
                    <button id="btn-test-send" class="btn btn-test-send" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            <input type="file" id="test-file-input" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.webp">
        </div>
    </div>

</div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('modules/helpdeskchatflow/css/chatflow.css') }}?v={{ @filemtime(public_path('modules/helpdeskchatflow/css/chatflow.css')) }}">
@endpush

@push('scripts')
{{-- ChatFlow React editor --}}
@vite('modules/HelpdeskChatFlow/resources/js/chatflow-editor.tsx')

<script>
window.HelpdeskChatFlowEditor = {
    testStartUrl: @json(route('chatflow.test.start', $chatFlow)),
    testSendUrl: @json(route('chatflow.test.send')),
    testUploadUrl: @json(route('chatflow.test.upload', $chatFlow)),
    successMessage: @json(session('success')),
    errorMessage: @json(session('error')),
};
</script>
<script src="{{ asset('modules/helpdeskchatflow/js/chatflow-editor-test-panel.js') }}?v={{ @filemtime(public_path('modules/helpdeskchatflow/js/chatflow-editor-test-panel.js')) }}" defer></script>
@endpush
