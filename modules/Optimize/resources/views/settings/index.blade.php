@extends('layouts.theme')

@section('title', 'Optimización')

@section('page_header')
    @include('core::components.card', ['title' => 'Optimización'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Formulario (izquierda) --}}
            <div class="col-lg-8">
                <form method="POST" action="{{ route('settings.optimize.update') }}">
                    @csrf

                    <div class="card">

                        {{-- Estado del servicio --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Estado del servicio</h6>
                            <p class="text-muted mb-3">Habilita o deshabilita la optimización automática del HTML generado por la aplicación.</p>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="enabled"
                                       name="enabled" value="1"
                                       {{ $get('enabled') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="enabled">
                                    Activar optimización
                                </label>
                            </div>
                            <small class="text-muted d-block mb-3">Cuando está habilitado, el HTML de las páginas públicas se optimiza automáticamente</small>

                            @if($stats['requests'] > 0)
                            <div class="alert alert-info border-0 mb-3">
                                <small>
                                    <strong>Rendimiento:</strong>
                                    {{ number_format($stats['requests']) }} páginas optimizadas
                                    &middot;
                                    @php
                                        $kb = $stats['bytes_saved'] / 1024;
                                        $display = $kb >= 1024
                                            ? number_format($kb / 1024, 2) . ' MB'
                                            : number_format($kb, 1) . ' KB';
                                    @endphp
                                    {{ $display }} ahorrados
                                    <form method="POST" action="{{ route('settings.optimize.reset-stats') }}" class="d-inline ms-2">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">
                                            <i class="fas fa-redo me-1"></i>Reiniciar
                                        </button>
                                    </form>
                                </small>
                            </div>

                            {{-- Gráfico últimos 7 días --}}
                            @if (array_sum($chartRequests) > 0 || array_sum($chartBytesSaved) > 0)
                                <div class="border rounded p-3 mb-3">
                                    <div class="small text-muted mb-2">
                                        <i class="fa-solid fa-chart-line me-1"></i>
                                        Últimos 7 días
                                    </div>
                                    <div class="position-relative has-fixed-height-250">
                                        <canvas id="optimize-chart"></canvas>
                                    </div>
                                </div>
                            @endif
                            @endif
                        </div>

                        <div id="optimize-settings" class="{{ $get('enabled') === '1' ? '' : 'd-none' }}">

                            <hr class="my-0">

                            {{-- Opciones de minificación --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">Opciones de minificación</h6>
                                <p class="text-muted mb-3">Selecciona qué transformaciones aplicar al HTML de las páginas públicas.</p>

                                @php
                                $options = [
                                    ['id' => 'collapse_whitespace', 'icon' => 'fa-compress',      'label' => 'Colapsar espacios en blanco',     'desc' => 'Elimina espacios y saltos de línea innecesarios del HTML'],
                                    ['id' => 'elide_attributes',    'icon' => 'fa-tag',            'label' => 'Eliminar atributos por defecto',  'desc' => 'Elimina valores de atributos HTML que coinciden con el valor por defecto'],
                                    ['id' => 'inline_css',          'icon' => 'fa-paint-brush',    'label' => 'CSS en línea',                    'desc' => 'Mueve los estilos inline a una clase CSS en el head'],
                                    ['id' => 'insert_dns_prefetch', 'icon' => 'fa-server',         'label' => 'Insertar DNS prefetch',           'desc' => 'Inyecta etiquetas de prefetch para acelerar recursos externos'],
                                    ['id' => 'remove_comments',     'icon' => 'fa-comment-slash',  'label' => 'Eliminar comentarios HTML',       'desc' => 'Elimina comentarios del código fuente'],
                                    ['id' => 'remove_quotes',       'icon' => 'fa-quote-left',     'label' => 'Eliminar comillas innecesarias',  'desc' => 'Elimina comillas en atributos HTML cuando es seguro'],
                                    ['id' => 'defer_javascript',    'icon' => 'fa-clock',          'label' => 'Diferir JavaScript',              'desc' => 'Agrega defer a scripts externos para no bloquear renderizado'],
                                    ['id' => 'add_loading_lazy',    'icon' => 'fa-images',         'label' => 'Carga diferida de imágenes',      'desc' => 'Agrega loading="lazy" a imágenes e iframes'],
                                    ['id' => 'add_image_decoding',  'icon' => 'fa-bolt',           'label' => 'Decodificación asíncrona',        'desc' => 'Agrega decoding="async" para decodificar imágenes fuera del hilo principal'],
                                    ['id' => 'inject_font_display', 'icon' => 'fa-font',           'label' => 'Font display swap',               'desc' => 'Agrega font-display:swap a fuentes web para evitar FOIT'],
                                    ['id' => 'inject_critical_preload', 'icon' => 'fa-bolt',       'label' => 'Precarga crítica',                'desc' => 'Inyecta preload para CSS críticos del tema e imagen hero'],
                                    ['id' => 'inject_critical_css', 'icon' => 'fa-file-code',      'label' => 'Critical CSS inline',             'desc' => 'Inyecta CSS crítico inline y carga el resto de forma async'],
                                    ['id' => 'add_image_dimensions','icon' => 'fa-ruler-combined', 'label' => 'Dimensiones de imágenes',         'desc' => 'Agrega width y height a imágenes locales para reducir CLS'],
                                    ['id' => 'minify_inline_styles','icon' => 'fa-file-code',      'label' => 'Minificar estilos en línea',      'desc' => 'Minifica el contenido de los bloques <style>'],
                                    ['id' => 'minify_inline_scripts','icon' => 'fa-code',          'label' => 'Minificar scripts en línea',      'desc' => 'Minifica el contenido de los bloques <script>'],
                                    ['id' => 'rewrite_min_assets',  'icon' => 'fa-compress',       'label' => 'Reescribir a minificados',        'desc' => 'Sirve .min.css y .min.js cuando existen versiones minificadas'],
                                    ['id' => 'link_preload_header', 'icon' => 'fa-link',           'label' => 'Preload HTTP/2',                  'desc' => 'Envía cabeceras Link: rel=preload para assets críticos'],
                                ];
                                @endphp

                                <div class="row g-3">
                                    @foreach($options as $opt)
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0 h-100">
                                            <div class="card-body p-3 d-flex gap-3 align-items-center">
                                                <div class="rounded-circle bg-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">
                                                    <i class="fas {{ $opt['icon'] }} fa-fw text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <label class="fw-semibold mb-0 d-block" for="{{ $opt['id'] }}">{{ $opt['label'] }}</label>
                                                    <small class="text-muted">{{ $opt['desc'] }}</small>
                                                </div>
                                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           name="{{ $opt['id'] }}" id="{{ $opt['id'] }}"
                                                           value="1" {{ $get($opt['id']) === '1' ? 'checked' : '' }}
                                                           style="width:3em;height:1.5em;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-0">

                            {{-- Caché de respuestas --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">Caché de respuestas</h6>
                                <p class="text-muted mb-3">Almacena el HTML optimizado en Redis para no repetir las transformaciones en cada peticion.</p>

                                <label for="response_cache" class="form-label fw-semibold">Activar caché de respuestas</label>
                                <select class="form-select select2" id="response_cache" name="response_cache">
                                    <option value="1" {{ $get('response_cache') === '1' ? 'selected' : '' }}>Habilitado</option>
                                    <option value="0" {{ $get('response_cache') !== '1' ? 'selected' : '' }}>Deshabilitado</option>
                                </select>
                                <small class="text-muted d-block mt-1">Cuando esta habilitado, el HTML se sirve desde caché sin repetir las optimizaciones</small>

                                <div id="response-cache-ttl" class="{{ $get('response_cache') === '1' ? '' : 'd-none' }} mt-3">
                                    <label for="response_cache_ttl" class="form-label fw-semibold">Tiempo de vida del caché (minutos)</label>
                                    <input type="number" name="response_cache_ttl" id="response_cache_ttl"
                                           class="form-control" value="{{ $ttl }}" min="1" max="1440">
                                    <small class="text-muted d-block mt-1">Tiempo que se mantiene el HTML en caché antes de regenerarlo. Maximo: 1440 (24 horas)</small>
                                </div>
                            </div>

                            <hr class="my-0">

                            {{-- Patrones de exclusión --}}
                            <div class="card-body">
                                <h6 class="fw-bold text-dark mb-1">Patrones de exclusión</h6>
                                <p class="text-muted mb-3">Define rutas que no deben ser optimizadas. Un patrón por línea.</p>

                                <textarea name="skip_patterns" id="skip_patterns" rows="4"
                                          class="form-control"
                                          placeholder="admin/*&#10;api/*&#10;perfil/*">{{ $skipPatterns }}</textarea>
                                <small class="text-muted d-block mt-1">Soporta wildcards: <code>admin/*</code>, <code>api/v*</code></small>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar configuración
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Panel informativo (derecha) --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Herramientas de optimización</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">Ejecuta tareas pesadas que mejoran PageSpeed de forma manual. Se corren de forma síncrona y pueden tardar varios segundos en sitios grandes.</p>
                    </div>
                    <div class="card-footer border-top">
                        <a href="{{ route('settings.optimize.tools') }}" class="btn btn-primary w-100">
                            <i class="fa-solid fa-terminal me-2"></i>Abrir herramientas
                        </a>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Qué hace la optimización</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">La optimización procesa el HTML de las páginas públicas aplicando transformaciones que reducen el tamaño del documento:</p>
                        <ul class="text-muted ps-3 mb-0">
                            <li class="mb-2">Elimina espacios y comentarios innecesarios</li>
                            <li class="mb-2">Difiere la carga de scripts e imágenes</li>
                            <li class="mb-2">Minifica CSS y JS inline</li>
                            <li>Agrega DNS prefetch para recursos externos</li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Caché de respuestas</h6>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-semibold mb-2">Cómo funciona</h6>
                        <p class="text-muted mb-3">El HTML optimizado se almacena en Redis. Las siguientes peticiones a la misma URL reciben el HTML cacheado sin repetir el proceso.</p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Cuándo usar</h6>
                        <p class="text-muted mb-0">Ideal para sitios con contenido que cambia poco. No recomendado para páginas con contenido dinámico personalizado por usuario.</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Recomendaciones</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted mb-0">
                            <li class="mb-2">Excluye rutas del panel de administración (<code>admin/*</code>).</li>
                            <li class="mb-2">Excluye rutas de API (<code>api/*</code>).</li>
                            <li class="mb-2">Si usas AJAX en el frontend, verifica que <em>Diferir JavaScript</em> no rompa la funcionalidad.</li>
                            <li>Monitorea las estadísticas para verificar el impacto real.</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    // Chart.js — últimos 7 días (si hay canvas + librería cargada).
    (function initOptimizeChart() {
        var canvas = document.getElementById('optimize-chart');
        if (!canvas) { return; }
        function draw() {
            if (typeof Chart === 'undefined') { setTimeout(draw, 100); return; }
            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels ?? []),
                    datasets: [
                        {
                            label: 'Requests optimizados',
                            data: @json($chartRequests ?? []),
                            backgroundColor: 'rgba(177, 1, 0, .6)',
                            borderColor: '#90bb13',
                            borderWidth: 1,
                            borderRadius: 4,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Ahorro (KB)',
                            data: @json($chartBytesSaved ?? []),
                            type: 'line',
                            borderColor: '#333333',
                            backgroundColor: 'rgba(51, 51, 51, .1)',
                            tension: .3,
                            yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            ticks: { font: { size: 10 } },
                            grid: { drawOnChartArea: true },
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { font: { size: 10 } },
                        },
                        x: {
                            ticks: { font: { size: 10 } },
                            grid: { display: false },
                        },
                    },
                    layout: { padding: { top: 4, right: 4, bottom: 0, left: 4 } },
                },
            });
        }
        draw();
    })();

    $('#enabled').on('change', function () {
        $('#optimize-settings').toggleClass('d-none', !$(this).is(':checked'));
    });

    $('#response_cache').on('change', function () {
        $('#response-cache-ttl').toggleClass('d-none', $(this).val() !== '1');
    });


});
</script>
@endpush
