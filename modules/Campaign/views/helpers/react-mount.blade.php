{{--
    React Mount Point Helper

    Usage:
    @include('helpers.react-mount', [
        'id' => 'unique-root-id',
        'component' => 'ComponentName', // Optional: specific React component
        'props' => [
            'key' => 'value',
            // ... data to pass to React
        ]
    ])
--}}

@php
    $mountId = $id ?? 'react-root-' . uniqid();
    $componentName = $component ?? null;
    $mountProps = $props ?? [];
@endphp

<div
    id="{{ $mountId }}"
    data-react-component="{{ $componentName }}"
    data-react-props="{{ json_encode($mountProps) }}"
    class="react-mount-point"
>
    {{-- Placeholder content while React loads --}}
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando editor...</span>
        </div>
        <p class="mt-3 text-muted">Cargando editor de campañas...</p>

        {{-- Fallback message if React doesn't load --}}
        <noscript>
            <div class="alert alert-warning mt-3">
                <i class="fa fa-exclamation-triangle"></i>
                JavaScript es requerido para usar el editor de campañas.
            </div>
        </noscript>
    </div>

    {{-- Development helper: show what would be passed to React --}}
    @if(config('app.debug'))
        <details class="mt-3">
            <summary class="text-muted small cursor-pointer">Debug: React Props</summary>
            <pre class="bg-light p-3 mt-2 small"><code>{{ json_encode($mountProps, JSON_PRETTY_PRINT) }}</code></pre>
        </details>
    @endif
</div>

{{-- Optional: Auto-initialize if React mount script is available --}}
@push('scripts')
<script>
    // This will be picked up by your React mounting logic
    // Example: window.mountReactComponent('{{ $mountId }}', '{{ $componentName }}', props);

    document.addEventListener('DOMContentLoaded', function() {
        const mountPoint = document.getElementById('{{ $mountId }}');
        if (mountPoint && window.mountReactComponents) {
            // Your React initialization logic will handle this
            window.mountReactComponents();
        }
    });
</script>
@endpush
