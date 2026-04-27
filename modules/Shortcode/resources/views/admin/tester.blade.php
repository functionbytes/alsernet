@extends('layouts.theme')

@section('title', 'Tester de shortcodes')

@section('content')
    @include('core::components.card', ['title' => 'Tester de shortcodes'])

    <div class="widget-content searchable-container list">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Tester de shortcodes</h5>
                        <p class="small mb-0 text-muted">Renderiza el ejemplo de cada shortcode registrado. Útil para QA visual tras cambios de estilos.</p>
                    </div>
                    <a href="{{ route('settings.shortcodes.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="alert alert-warning border-0 bg-warning-subtle small">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    Se están compilando {{ count($registered) }} shortcodes con sus ejemplos. Revisa visualmente cualquier regresión.
                </div>

                @foreach($registered as $sc)
                    <div class="tester-entry border rounded mb-3 p-3" data-shortcode="{{ $sc['name'] }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0">
                                <code class="text-primary">[{{ $sc['name'] }}]</code>
                                @if(!empty($sc['deprecated']))
                                    <span class="badge bg-warning-subtle text-warning ms-2">deprecado {{ $sc['deprecated'] }}</span>
                                @endif
                                @if(!empty($sc['alias_of']))
                                    <span class="badge bg-info-subtle text-info ms-2">alias de {{ $sc['alias_of'] }}</span>
                                @endif
                            </h6>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary copy-example"
                                    data-example="{{ $sc['example'] ?? '' }}">
                                <i class="fas fa-copy me-1"></i>Copiar ejemplo
                            </button>
                        </div>

                        @if(!empty($sc['description']))
                            <p class="small text-muted mb-2">{{ $sc['description'] }}</p>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-semibold text-muted mb-1">Fuente</label>
                                <pre class="p-2 bg-dark text-light rounded small mb-0" style="white-space:pre-wrap; word-break:break-all;"><code>{{ $sc['example'] ?? '' }}</code></pre>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-semibold text-muted mb-1">Resultado</label>
                                <div class="border rounded p-2 bg-light tester-output">
                                    {!! shortcode($sc['example'] ?? '') !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.copy-example', function() {
    var text = $(this).data('example');
    if (! text) return;
    navigator.clipboard.writeText(text).then(function() {
        if (typeof toastr !== 'undefined') {
            toastr.success('Ejemplo copiado');
        }
    });
});
</script>
@endpush
