@extends('layouts.theme')

@section('title', 'Shortcodes')

@section('page_header')
    @include('core::components.card', ['title' => 'Shortcodes'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Shortcodes</h5>
                        <p class="small mb-0 text-muted">Componentes dinamicos que puedes insertar en cualquier contenido</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.shortcode.tester') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-vial me-1"></i> Tester visual
                        </a>
                        <a href="{{ route('settings.shortcode.reference') }}" class="btn btn-outline-primary">
                            <i class="fas fa-book me-1"></i> Referencia completa
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total registrados</h6>
                                        <h4 class="mb-1 fw-bold">{{ count($shortcodes) }}</h4>
                                        <small class="text-muted">Shortcodes disponibles</small>
                                    </div>
                                    <div class="text-primary opacity-50">
                                        <i class="fas fa-code fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Handlers PHP</h6>
                                        <h4 class="mb-1 fw-bold">{{ count($shortcodes) - count($dbShortcodes) }}</h4>
                                        <small class="text-muted">Definidos en codigo</small>
                                    </div>
                                    <div class="text-info opacity-50">
                                        <i class="fas fa-file-code fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">En base de datos</h6>
                                        <h4 class="mb-1 fw-bold">{{ count($dbShortcodes) }}</h4>
                                        <small class="text-muted">Definidos en BD</small>
                                    </div>
                                    <div class="text-success opacity-50">
                                        <i class="fas fa-database fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($topUsage))
                <!-- Top de uso -->
                <div class="card-body border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-1 fw-bold">Top 10 shortcodes más usados</h6>
                            <p class="text-muted small mb-0">Contador acumulado de compilaciones (cache miss únicamente).</p>
                        </div>
                        @can('shortcode.manage')
                            <button type="button" id="btnResetStats" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-rotate-left me-1"></i> Resetear
                            </button>
                        @endcan
                    </div>

                    @php
                        $max = max($topUsage) ?: 1;
                    @endphp

                    <div class="row g-2">
                        @foreach($topUsage as $name => $count)
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2">
                                    <code class="flex-shrink-0" style="min-width: 140px;">[{{ $name }}]</code>
                                    <div class="progress flex-grow-1" style="height: 18px;">
                                        <div class="progress-bar bg-primary-subtle text-primary fw-semibold"
                                             role="progressbar"
                                             style="width: {{ round(($count / $max) * 100) }}%;">
                                            {{ $count }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Tabla de shortcodes -->
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Listado de shortcodes</h6>
                    <p class="text-muted mb-0">Usalos en el contenido con la sintaxis <code>[nombre]...[/nombre]</code></p>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fas fa-circle-info mt-1 text-info"></i>
                        <small>Copia la sintaxis y pegala en cualquier campo de contenido del sistema</small>
                    </div>
                </div>

                @if(count($shortcodes) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Shortcode</th>
                                    <th>Tipo</th>
                                    <th>Sintaxis</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shortcodes as $name)
                                    @php $dbId = $dbShortcodes[$name] ?? null; @endphp
                                    <tr>
                                        <td class="ps-3">
                                            <code class="fs-6 text-primary">[{{ $name }}]</code>
                                        </td>
                                        <td>
                                            @if($dbId)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="fas fa-database me-1"></i>Base de datos
                                                </span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">
                                                    <i class="fas fa-code me-1"></i>Handler PHP
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="small text-secondary">[{{ $name }}][/{{ $name }}]</code>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false"
                                                        title="Acciones">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item copy-btn"
                                                                data-syntax="[{{ $name }}][/{{ $name }}]">
                                                            Copiar sintaxis
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item copy-btn"
                                                                data-syntax="[{{ $name }} /]">
                                                            Copiar auto-cierre
                                                        </button>
                                                    </li>
                                                    @if($dbId && Route::has('settings.shortcodes.edit'))
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a href="{{ route('settings.shortcodes.edit', $dbId) }}"
                                                               class="dropdown-item">
                                                                Editar
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-code fa-4x text-muted opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay shortcodes registrados</h5>
                        <p class="text-muted mb-0">Registra shortcodes en tu aplicacion para verlos aqui</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Preview interactivo -->
        <div class="card mt-3">
            <div class="card-header p-4 border-bottom border-light">
                <h5 class="mb-1 fw-bold">Preview interactivo</h5>
                <p class="small mb-0 text-muted">Escribe shortcodes y ve el resultado compilado en tiempo real</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Entrada</label>
                        <textarea id="previewInput"
                                  class="form-control font-monospace"
                                  rows="10"
                                  placeholder='Prueba: [alert type="success"]Funciona[/alert]'>[alert type="success"]Funciona[/alert] [badge type="primary"]Preview[/badge]</textarea>
                        <small class="text-muted">Se compila al dejar de escribir (debounce 400ms).</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Resultado</label>
                        <div id="previewOutput"
                             class="border rounded p-3 bg-light"
                             style="min-height: 260px;">
                            <span class="text-muted small">El resultado compilado aparecerá aquí.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ejemplos en vivo -->
        <div class="card mt-3">
            <div class="card-header p-4 border-bottom border-light">
                <h5 class="mb-1 fw-bold">Ejemplos en vivo</h5>
                <p class="small mb-0 text-muted">Shortcodes renderizados en tiempo real</p>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="examplesTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-buttons" data-bs-toggle="tab" data-bs-target="#pane-buttons" type="button" role="tab">
                            Botones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-alerts" data-bs-toggle="tab" data-bs-target="#pane-alerts" type="button" role="tab">
                            Alertas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-badges" data-bs-toggle="tab" data-bs-target="#pane-badges" type="button" role="tab">
                            Badges
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-quote" data-bs-toggle="tab" data-bs-target="#pane-quote" type="button" role="tab">
                            Cita
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-complex" data-bs-toggle="tab" data-bs-target="#pane-complex" type="button" role="tab">
                            Complejo
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="examplesTabContent">
                    <!-- Botones -->
                    <div class="tab-pane fade show active" id="pane-buttons" role="tabpanel">
                        <div class="bg-light rounded p-3 mb-2">
                            @shortcode('[button url="#" class="primary"]Primario[/button]')
                            @shortcode('[button url="#" class="success"]Exito[/button]')
                            @shortcode('[button url="#" class="danger"]Peligro[/button]')
                        </div>
                        <pre class="p-2 bg-dark text-light rounded small mb-0"><code>[button url="#" class="primary"]Texto[/button]</code></pre>
                    </div>

                    <!-- Alertas -->
                    <div class="tab-pane fade" id="pane-alerts" role="tabpanel">
                        <div class="bg-light rounded p-3 mb-2">
                            @shortcode('[alert type="success"]Operacion exitosa[/alert]')
                            @shortcode('[alert type="warning"]Atencion requerida[/alert]')
                        </div>
                        <pre class="p-2 bg-dark text-light rounded small mb-0"><code>[alert type="success"]Mensaje[/alert]</code></pre>
                    </div>

                    <!-- Badges -->
                    <div class="tab-pane fade" id="pane-badges" role="tabpanel">
                        <div class="bg-light rounded p-3 mb-2">
                            @shortcode('[badge type="primary"]Principal[/badge]')
                            @shortcode('[badge type="success"]Activo[/badge]')
                            @shortcode('[badge type="info" pill="true"]Pill[/badge]')
                        </div>
                        <pre class="p-2 bg-dark text-light rounded small mb-0"><code>[badge type="primary" pill="true"]Texto[/badge]</code></pre>
                    </div>

                    <!-- Cita -->
                    <div class="tab-pane fade" id="pane-quote" role="tabpanel">
                        <div class="bg-light rounded p-3 mb-2">
                            @shortcode('[quote author="Albert Einstein"]La imaginacion es mas importante que el conocimiento.[/quote]')
                        </div>
                        <pre class="p-2 bg-dark text-light rounded small mb-0"><code>[quote author="Autor"]Texto[/quote]</code></pre>
                    </div>

                    <!-- Complejo -->
                    <div class="tab-pane fade" id="pane-complex" role="tabpanel">
                        <div class="bg-light rounded p-3 mb-2">
                            @shortcode('[columns count="3" gap="3"][column][card title="Basico"]Plan basico.[/card][/column][column][card title="Profesional"]Plan profesional.[/card][/column][column][card title="Empresa"]Plan empresa.[/card][/column][/columns]')
                        </div>
                        <pre class="p-2 bg-dark text-light rounded small mb-0"><code>[columns count="3"][column][card title="..."]...[/card][/column][/columns]</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.copy-btn', function() {
    var text = $(this).data('syntax');
    if (! text) { return; }

    navigator.clipboard.writeText(text).then(function() {
        if (typeof toastr !== 'undefined') {
            toastr.success('Sintaxis copiada: ' + text);
        }
    }).catch(function() {
        if (typeof toastr !== 'undefined') {
            toastr.error('No se pudo copiar al portapapeles.');
        }
    });
});

// Preview interactivo con debounce
(function () {
    var $input = $('#previewInput');
    var $output = $('#previewOutput');
    if (!$input.length) return;

    var timer = null;
    var inflight = null;

    function compile() {
        var value = $input.val() || '';
        if (value === '') {
            $output.html('<span class="text-muted small">El resultado compilado aparecerá aquí.</span>');
            return;
        }

        if (inflight) { inflight.abort(); }

        inflight = $.ajax({
            url: '{{ route("settings.shortcode.preview") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: { content: value }
        }).done(function (resp) {
            $output.html(resp.compiled || '');
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') return;
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error al compilar';
            $output.html('<span class="text-danger small">' + msg + '</span>');
        });
    }

    $input.on('input', function () {
        clearTimeout(timer);
        timer = setTimeout(compile, 400);
    });

    compile(); // render inicial
})();

// Reset stats
$(document).on('click', '#btnResetStats', function () {
    if (! confirm('¿Resetear todos los contadores de uso?')) return;

    $.ajax({
        url: '{{ route("settings.shortcode.stats.reset") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    }).done(function () {
        if (typeof toastr !== 'undefined') {
            toastr.success('Estadísticas reseteadas.');
        }
        setTimeout(function () { location.reload(); }, 800);
    }).fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo resetear.';
        if (typeof toastr !== 'undefined') { toastr.error(msg); }
    });
});
</script>
@endpush
