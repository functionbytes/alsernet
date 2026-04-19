@extends('layouts.theme')

@section('title', 'Optimización')

@section('content')
    @include('core::components.card', ['title' => 'Optimización'])

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
                            <div class="alert alert-info border-0 mb-0">
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
                                    ['id' => 'minify_inline_styles','icon' => 'fa-file-code',      'label' => 'Minificar estilos en línea',      'desc' => 'Minifica el contenido de los bloques <style>'],
                                    ['id' => 'minify_inline_scripts','icon' => 'fa-code',          'label' => 'Minificar scripts en línea',      'desc' => 'Minifica el contenido de los bloques <script>'],
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

            {{-- ── Ejecutar comandos de optimización ────────────────────── --}}
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fa-solid fa-terminal me-2 text-muted"></i>Herramientas de optimización
                    </h5>
                    <p class="text-muted small mb-3">
                        Ejecuta tareas pesadas que mejoran PageSpeed. Se corren de forma síncrona —
                        pueden tardar varios segundos en sitios grandes.
                    </p>

                    {{-- Botón orquestador: corre toda la secuencia de optimización --}}
                    <div class="alert alert-primary d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <strong><i class="fa-solid fa-rocket me-2"></i>Ejecutar toda la optimización</strong>
                            <div class="small text-muted">
                                enable-all → minify theme → webp → srcset → audit-a11y --fix → purge-cache
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="run-all-slug" class="form-control form-control-sm"
                                   value="caixilhariablanco" style="width:180px"
                                   placeholder="slug del theme">
                            <button type="button" id="btn-run-all" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-play me-1"></i>Ejecutar todo
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        @foreach ($commands as $signature => $meta)
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 d-flex flex-column">
                                    <div class="mb-2">
                                        <strong>{{ $meta['label'] }}</strong>
                                        <small class="text-muted d-block mt-1">{{ $meta['description'] }}</small>
                                        <code class="small text-muted">{{ $signature }}</code>
                                    </div>

                                    <div class="mt-2 mb-2 flex-grow-1">
                                        @foreach ($meta['params'] as $param)
                                            @if ($param === 'ttl')
                                                <label class="form-label small mb-1">TTL (segundos)</label>
                                                <input type="number" class="form-control form-control-sm mb-2"
                                                       data-cmd-param="{{ $param }}" value="3600" min="60">
                                            @elseif ($param === 'quality')
                                                <label class="form-label small mb-1">Calidad WebP (0-100)</label>
                                                <input type="number" class="form-control form-control-sm mb-2"
                                                       data-cmd-param="{{ $param }}" value="82" min="0" max="100">
                                            @elseif ($param === 'limit')
                                                <label class="form-label small mb-1">Límite (0 = todos)</label>
                                                <input type="number" class="form-control form-control-sm mb-2"
                                                       data-cmd-param="{{ $param }}" value="0" min="0">
                                            @elseif ($param === 'slug')
                                                <label class="form-label small mb-1">Slug del theme</label>
                                                <input type="text" class="form-control form-control-sm mb-2"
                                                       data-cmd-param="{{ $param }}" value="caixilhariablanco">
                                            @elseif ($param === 'fix')
                                                <div class="form-check mb-2">
                                                    <input type="checkbox" class="form-check-input"
                                                           id="fix-{{ \Illuminate\Support\Str::slug($signature) }}"
                                                           data-cmd-param="{{ $param }}" value="1">
                                                    <label class="form-check-label small"
                                                           for="fix-{{ \Illuminate\Support\Str::slug($signature) }}">
                                                        Aplicar correcciones automáticas (--fix)
                                                    </label>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>

                                    <button type="button"
                                            class="btn btn-sm btn-primary run-command-btn align-self-start"
                                            data-command="{{ $signature }}">
                                        <i class="fa-solid fa-play me-1"></i>Ejecutar
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="command-output-wrap" class="mt-3 d-none">
                        <label class="form-label small mb-1">Salida del comando</label>
                        <pre id="command-output" class="bg-dark text-light p-3 rounded small"
                             style="max-height:360px; overflow:auto; white-space:pre-wrap;"></pre>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    $('#enabled').on('change', function () {
        $('#optimize-settings').toggleClass('d-none', !$(this).is(':checked'));
    });

    $('#response_cache').on('change', function () {
        $('#response-cache-ttl').toggleClass('d-none', $(this).val() !== '1');
    });

    // ── Ejecutar comandos de optimización ──
    $('.run-command-btn').on('click', function () {
        var $btn = $(this);
        var $card = $btn.closest('.border');
        var command = $btn.data('command');

        // Recolectar parámetros del card.
        var payload = { command: command, _token: '{{ csrf_token() }}' };
        $card.find('[data-cmd-param]').each(function () {
            var $el = $(this);
            var name = $el.data('cmd-param');
            if ($el.is(':checkbox')) {
                if ($el.is(':checked')) { payload[name] = '1'; }
            } else {
                var val = String($el.val() || '').trim();
                if (val !== '') { payload[name] = val; }
            }
        });

        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Ejecutando…');

        $.ajax({
            url: '{{ route("settings.optimize.run-command") }}',
            method: 'POST',
            data: payload,
        })
        .done(function (res) {
            $('#command-output-wrap').removeClass('d-none');
            var header = '$ php artisan ' + res.command + ' (' + res.elapsed_ms + ' ms)\n'
                       + '─'.repeat(60) + '\n';
            $('#command-output').text(header + (res.output || '').trim());
        })
        .fail(function (xhr) {
            $('#command-output-wrap').removeClass('d-none');
            var msg = xhr.responseJSON?.message || 'Error al ejecutar el comando';
            $('#command-output').text('[ERROR] ' + msg);
        })
        .always(function () {
            $btn.prop('disabled', false).html(originalHtml);
        });
    });

    // ── Botón "Ejecutar toda la optimización" ──
    $('#btn-run-all').on('click', function () {
        var $btn = $(this);
        var slug = String($('#run-all-slug').val() || '').trim();
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Ejecutando…');

        $.ajax({
            url: '{{ route("settings.optimize.run-all") }}',
            method: 'POST',
            timeout: 300000, // 5 min — convert-webp puede tardar en catálogos grandes
            data: { _token: '{{ csrf_token() }}', slug: slug },
        })
        .done(function (res) {
            $('#command-output-wrap').removeClass('d-none');
            var total = res.total_elapsed_ms || 0;
            var out = '$ Optimización completa en ' + (total/1000).toFixed(1) + ' s\n';
            out += '─'.repeat(60) + '\n';
            (res.steps || []).forEach(function (s, i) {
                out += '\n[' + (i+1) + '/' + res.steps.length + '] ' + s.command;
                if (s.skipped) { out += '  (omitido: ' + s.reason + ')\n'; return; }
                if (s.error) { out += '  [ERROR] ' + s.error + '\n'; return; }
                out += '  (' + s.elapsed_ms + ' ms)\n';
                if (s.output) { out += s.output + '\n'; }
            });
            $('#command-output').text(out);
        })
        .fail(function (xhr) {
            $('#command-output-wrap').removeClass('d-none');
            $('#command-output').text('[ERROR] ' + (xhr.responseJSON?.message || 'Error al ejecutar la secuencia'));
        })
        .always(function () {
            $btn.prop('disabled', false).html(originalHtml);
        });
    });
});
</script>
@endpush
