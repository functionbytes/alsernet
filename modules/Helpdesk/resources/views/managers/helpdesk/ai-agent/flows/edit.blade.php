@extends('layouts.theme')

@section('title', 'Editar Flujo - Agente IA')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">{{ $flow->name }}</h4>
                    <p class="text-muted mb-0">{{ $flow->description }}</p>
                </div>
                <div>
                    <span class="badge bg-{{ $flow->status === 'published' ? 'success' : ($flow->status === 'archived' ? 'secondary' : 'warning') }}">
                        {{ ucfirst($flow->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- React Flow Editor Component -->
    <div id="flow-editor-root" style="height: calc(100vh - 250px);"></div>
</div>

@push('scripts')
<script>
    // Pass initial data to React component
    window.flowEditorProps = {
        flowId: {{ $flow->id }},
        flow: @json($flow)
    };
</script>
@vite('resources/js/helpdesk/app.tsx')
@endpush

<style>
    .flow-editor {
        position: relative;
        background: #fafafa;
    }

    .flow-toolbar,
    .flow-actions,
    .flow-properties {
        background: white;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .flow-toolbar .btn-group,
    .flow-actions .btn-group {
        display: flex;
        gap: 5px;
    }

    /* Node Styles */
    .react-flow__node {
        border-radius: 8px;
        border: 2px solid #ccc;
        background: white;
        padding: 15px;
        min-width: 150px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .react-flow__node.selected {
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }

    /* Node Type Colors */
    .input-node {
        background: #e7f3ff;
        border-color: #0066cc;
    }

    .prompt-node {
        background: #fff3e7;
        border-color: #ff9800;
    }

    .condition-node {
        background: #f3e5f5;
        border-color: #9c27b0;
    }

    .action-node {
        background: #e8f5e9;
        border-color: #4caf50;
    }

    .output-node {
        background: #fce4ec;
        border-color: #e91e63;
    }

    /* Node Content */
    .node-title {
        font-weight: 600;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .node-content {
        margin-bottom: 8px;
        padding: 8px;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 4px;
        font-size: 12px;
    }

    .node-description {
        font-size: 12px;
        color: #666;
    }

    .code-text {
        font-family: 'Courier New', monospace;
        color: #d63384;
    }

    .action-type {
        color: #4caf50;
        font-weight: 500;
    }

    /* Handles */
    .react-flow__handle {
        background: #555;
    }

    .react-flow__handle.connect-above {
        border-top: 5px solid white;
    }

    .react-flow__handle.connect-below {
        border-bottom: 5px solid white;
    }

    /* Edge Styles */
    .react-flow__edge-path {
        stroke: #999;
        stroke-width: 2;
    }

    /* Node Handles Wrapper */
    .node-handles {
        display: flex;
        justify-content: space-between;
        margin-top: 8px;
    }

    /* Properties Panel */
    .flow-properties .card {
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .flow-properties .card-header {
        background-color: #f8f9fa;
        padding: 12px 15px;
        border-bottom: 1px solid #dee2e6;
    }

    .flow-properties .card-header h6 {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
    }

    .flow-properties .card-body {
        padding: 15px;
    }

    .flow-properties .form-label {
        margin-bottom: 6px;
        color: #495057;
    }

    /* Toolbar */
    .flow-toolbar {
        z-index: 10;
    }

    .flow-actions {
        z-index: 10;
    }
</style>
@endsection
