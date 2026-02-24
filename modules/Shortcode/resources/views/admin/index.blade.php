@extends('layouts.theme')

@section('title', 'Shortcodes')

@section('content')
    @include('core::components.card', ['title' => 'Shortcodes'])

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
                    <a href="{{ route('setting.shortcode.reference') }}" class="btn btn-outline-primary">
                        <i class="fas fa-book me-1"></i> Referencia completa
                    </a>
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

            <!-- Tabla de shortcodes -->
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Listado de shortcodes</h6>
                    <p class="text-muted small mb-0">Usalos en el contenido con la sintaxis <code>[nombre]...[/nombre]</code></p>
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
                                            <div class="d-flex align-items-center gap-2">
                                                <code class="small text-secondary">[{{ $name }}][/{{ $name }}]</code>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary copy-btn"
                                                        data-syntax="[{{ $name }}][/{{ $name }}]"
                                                        title="Copiar sintaxis">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($dbId && Route::has('settings.shortcodes.edit'))
                                                <a href="{{ route('settings.shortcodes.edit', $dbId) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Editar shortcode">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                            @else
                                                <span class="badge bg-light text-dark">Handler PHP</span>
                                            @endif
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
                        <p class="text-muted small mb-0">Registra shortcodes en tu aplicacion para verlos aqui</p>
                    </div>
                @endif
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
$(document).ready(function() {
    $(document).on('click', '.copy-btn', function() {
        var $btn = $(this);
        var text = $btn.data('syntax');

        navigator.clipboard.writeText(text).then(function() {
            var $icon = $btn.find('i');
            $icon.removeClass('fa-copy').addClass('fa-check');
            $btn.removeClass('btn-outline-secondary').addClass('btn-outline-success');

            setTimeout(function() {
                $icon.removeClass('fa-check').addClass('fa-copy');
                $btn.removeClass('btn-outline-success').addClass('btn-outline-secondary');
            }, 1500);
        });
    });
});
</script>
@endpush
