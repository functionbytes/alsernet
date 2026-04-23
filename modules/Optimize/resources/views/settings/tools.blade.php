@extends('layouts.theme')

@section('title', 'Herramientas de optimización')

@section('content')
    @include('core::components.card', ['title' => 'Herramientas de optimización'])

    @include('core::components.alerts')

    {{-- Stats globales --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0 p-3">
                        <i class="fa-solid fa-palette text-dark fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">{{ $activeTheme }}</h6>
                        <small class="text-muted">Tema activo + {{ $globalStats['modules'] }} módulos</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0 p-3">
                        <i class="fa-solid fa-file-code text-dark fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">{{ $globalStats['theme_css'] + $globalStats['module_css'] }} CSS / {{ $globalStats['module_js'] }} JS</h6>
                        <small class="text-muted">Assets detectados en total</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0 p-3">
                        <i class="fa-solid fa-weight-hanging text-dark fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">{{ $globalStats['total_size'] }}</h6>
                        <small class="text-muted">Peso total sin minificar</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Assets declarados del tema --}}
    @if(count($declaredAssets) > 0)
        <div class="card mb-3 shadow-sm">
            <div class="card-header border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-dark">Assets declarados en config.php</h6>
                    <span class="badge bg-black">{{ $activeTheme }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($declaredAssets as $name => $path)
                        <div class="col-md-4">
                            <div class="d-flex align-items-center gap-2 border rounded p-2">
                                <i class="fa-solid {{ str_ends_with($path, '.css') ? 'fa-file-code text-primary' : 'fa-brands fa-js text-warning' }} fs-6"></i>
                                <div class="text-truncate">
                                    <small class="fw-semibold d-block text-truncate">{{ $name }}</small>
                                    <code class="small text-muted text-truncate d-block">{{ basename($path) }}</code>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Assets por módulo --}}
    @if(count($moduleAssets) > 0)
        <div class="card mb-3 shadow-sm">
            <div class="card-header border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-dark">Assets por módulo activo</h6>
                    <span class="badge bg-black">{{ count($moduleAssets) }} módulos</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Módulo</th>
                                <th>CSS</th>
                                <th>JS</th>
                                <th>Imágenes</th>
                                <th>Peso total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($moduleAssets as $mod)
                                <tr>
                                    <td class="ps-3">
                                        <span class="fw-semibold">{{ $mod['name'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $mod['css_count'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $mod['js_count'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $mod['image_count'] }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $mod['total_size'] }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <!-- Left Card: Individual Commands -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold text-dark">Comandos individuales</h6>
                        <span class="badge bg-black">Optimización</span>
                    </div>
                </div>

                <div class="card-body pb-0">
                    <p class="text-muted mb-3">
                        Ejecuta tareas específicas de optimización. Cada comando mejora un aspecto diferente del rendimiento del sitio.
                    </p>

                    <div class="alert alert-info alert-sm py-2 px-3 mb-4" role="alert">
                        <strong>Operaciones disponibles</strong>
                    </div>

                    <div class="optimize-operations">
                        @foreach ($commandGroups as $groupName => $groupCommands)
                            <div class="mb-3 pb-2 border-bottom">
                                <h6 class="fw-bold text-dark mb-2 small text-uppercase text-muted">{{ $groupName }}</h6>
                                @foreach ($groupCommands as $signature)
                                    @php $meta = $commands[$signature] ?? null; @endphp
                                    @if($meta)
                                        <div class="d-flex align-items-start justify-content-between mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}"
                                             data-cmd="{{ $signature }}">
                                            <div class="d-flex align-items-start gap-2 flex-grow-1">
                                                <div class="text-bg-light-secondary rounded p-2 d-flex align-items-center justify-content-center flex-shrink-0">
                                                    <i class="fa {{ $meta['icon'] ?? 'fa-terminal' }} text-dark fs-6"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 fw-semibold">{{ $meta['label'] }}</h6>
                                                    <small class="text-muted d-block">{{ $meta['description'] }}</small>
                                                    <code class="small text-muted">{{ $signature }}</code>

                                                    @if(count($meta['params']) > 0)
                                                        <div class="row g-2 mt-2">
                                                            @foreach ($meta['params'] as $param)
                                                                <div class="col-auto">
                                                                    @if ($param === 'ttl')
                                                                        <input type="number" class="form-control form-control-sm"
                                                                               data-cmd-param="{{ $param }}" value="3600" min="60"
                                                                               title="TTL (segundos)">
                                                                    @elseif ($param === 'quality')
                                                                        <input type="number" class="form-control form-control-sm"
                                                                               data-cmd-param="{{ $param }}" value="82" min="0" max="100"
                                                                               title="Calidad WebP (0-100)">
                                                                    @elseif ($param === 'limit')
                                                                        <input type="number" class="form-control form-control-sm"
                                                                               data-cmd-param="{{ $param }}" value="0" min="0"
                                                                               title="Límite (0 = todos)">
                                                                    @elseif ($param === 'slug')
                                                                        <input type="text" class="form-control form-control-sm"
                                                                               data-cmd-param="{{ $param }}" value="{{ $activeTheme }}"
                                                                               title="Slug del theme">
                                                                    @elseif ($param === 'fix')
                                                                        <div class="form-check form-check-sm">
                                                                            <input type="checkbox" class="form-check-input"
                                                                                   id="fix-{{ \Illuminate\Support\Str::slug($signature) }}"
                                                                                   data-cmd-param="{{ $param }}" value="1">
                                                                            <label class="form-check-label small"
                                                                                   for="fix-{{ \Illuminate\Support\Str::slug($signature) }}">
                                                                                --fix
                                                                            </label>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <button class="btn btn-light btn-sm run-command-btn flex-shrink-0 ms-2" type="button"
                                                    data-command="{{ $signature }}" title="Ejecutar">
                                                <i class="fa-solid fa-play"></i>
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Card: Sequence & Output -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold text-dark">Secuencia completa</h6>
                        <span class="badge bg-primary">Orquestador</span>
                    </div>
                </div>

                <div class="card-body pb-0">
                    <p class="text-muted mb-3">
                        Ejecuta toda la secuencia de optimización en orden. Ideal para aplicar todos los cambios de una sola vez sobre el tema <strong>{{ $activeTheme }}</strong>.
                    </p>

                    <div class="alert alert-info alert-sm py-2 px-3 mb-4" role="alert">
                        <strong>Flujo de ejecución</strong>
                    </div>

                    <ul class="list-unstyled ms-3 mb-4">
                        <li class="mb-2">
                            <strong>1. Activar optimizaciones</strong>
                            <br>
                            <small class="text-muted">Habilita los 17 middleware de optimización en la configuración.</small>
                        </li>
                        <li class="mb-2">
                            <strong>2. Generar Critical CSS</strong>
                            <br>
                            <small class="text-muted">Extrae CSS crítico e inyecta <code>critical.css</code> en el head.</small>
                        </li>
                        <li class="mb-2">
                            <strong>3. Minificar assets del tema</strong>
                            <br>
                            <small class="text-muted">Genera .min.css y .min.js solo para los assets declarados en config.php.</small>
                        </li>
                        <li class="mb-2">
                            <strong>4. Minificar assets de módulos</strong>
                            <br>
                            <small class="text-muted">Minifica CSS/JS de todos los módulos activos.</small>
                        </li>
                        <li class="mb-2">
                            <strong>5. Optimizar imágenes del tema</strong>
                            <br>
                            <small class="text-muted">Convierte las imágenes de <code>public/images/</code> a WebP.</small>
                        </li>
                        <li class="mb-2">
                            <strong>6. Optimizar imágenes de módulos</strong>
                            <br>
                            <small class="text-muted">Convierte imágenes de todos los módulos activos a WebP.</small>
                        </li>
                        <li class="mb-2">
                            <strong>7. Convertir imágenes del Media a WebP</strong>
                            <br>
                            <small class="text-muted">Procesa el catálogo de imágenes subidas por el usuario.</small>
                        </li>
                        <li class="mb-2">
                            <strong>8. Generar variantes responsive</strong>
                            <br>
                            <small class="text-muted">Crea versiones -480w, -768w, -1024w, -1920w en formato WebP.</small>
                        </li>
                        <li class="mb-2">
                            <strong>9. Auditar accesibilidad</strong>
                            <br>
                            <small class="text-muted">Detecta y corrige problemas de accesibilidad en el theme.</small>
                        </li>
                        <li class="mb-0">
                            <strong>10. Purgar cachés</strong>
                            <br>
                            <small class="text-muted">Limpia view, route, config, cache y response cache.</small>
                        </li>
                    </ul>

                    <div class="border-top pt-4">
                        <label for="run-all-slug" class="form-label fw-semibold">Slug del theme</label>
                        <input type="text" id="run-all-slug" class="form-control mb-3"
                               value="{{ $activeTheme }}" placeholder="slug del theme">
                        <small class="text-muted d-block mb-3">Theme sobre el que se ejecutarán minify y audit.</small>
                    </div>
                </div>

                <div class="card-footer border-top">
                    <button type="button" id="btn-run-all" class="btn btn-primary w-100">
                        <i class="fa-solid fa-rocket me-2"></i>Ejecutar toda la optimización
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Progreso visual -->
    <div id="run-all-progress" class="mt-3 d-none">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Progreso de optimización</h6>
                <div id="progress-steps" class="mb-3"></div>
                <div class="progress" style="height: 6px;">
                    <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Output Area -->
    <div id="command-output-wrap" class="mt-3 d-none">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label small fw-semibold mb-0">Salida del comando</label>
                    <button type="button" class="btn btn-link btn-sm text-muted" onclick="$('#command-output-wrap').addClass('d-none')">
                        <i class="fa-solid fa-times me-1"></i>Cerrar
                    </button>
                </div>
                <pre id="command-output" class="bg-dark text-light p-3 rounded small mb-0"
                     style="max-height:360px; overflow:auto; white-space:pre-wrap;"></pre>
            </div>
        </div>
    </div>

    {{-- Historial de ejecuciones --}}
    @if(count($executionHistory) > 0)
        <div class="card mt-3 shadow-sm">
            <div class="card-header border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-dark">Historial de ejecuciones</h6>
                    <span class="badge bg-secondary">{{ count($executionHistory) }} entradas</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Comando</th>
                                <th>Fecha</th>
                                <th>Tiempo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($executionHistory as $entry)
                                <tr>
                                    <td class="ps-3">
                                        <code class="small">{{ $entry['command'] }}</code>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $entry['executed_at'] }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $entry['elapsed_ms'] }} ms</small>
                                    </td>
                                    <td>
                                        @if($entry['success'])
                                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Éxito</span>
                                        @else
                                            <span class="badge bg-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>Error</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    function setLoading(loading) {
        $('.run-command-btn, #btn-run-all').prop('disabled', loading);
    }

    function showOutput(header, content) {
        $('#command-output-wrap').removeClass('d-none');
        $('#command-output').text(header + content);
        $('html, body').animate({ scrollTop: $('#command-output-wrap').offset().top - 20 }, 300);
    }

    // ── Ejecutar comandos individuales ──
    $('.run-command-btn').on('click', function () {
        var $btn = $(this);
        var $row = $btn.closest('[data-cmd]');
        var command = $btn.data('command');

        var payload = { command: command, _token: '{{ csrf_token() }}' };
        $row.find('[data-cmd-param]').each(function () {
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
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
        setLoading(true);

        $.ajax({
            url: '{{ route("settings.optimize.run-command") }}',
            method: 'POST',
            data: payload,
        })
        .done(function (res) {
            var header = '$ php artisan ' + res.command + ' (' + res.elapsed_ms + ' ms)\n'
                       + '─'.repeat(60) + '\n';
            showOutput(header, (res.output || '').trim());
            if (typeof toastr !== 'undefined') {
                toastr.success(res.command + ' completado en ' + res.elapsed_ms + ' ms', 'Comando ejecutado');
            }
        })
        .fail(function (xhr) {
            var msg = xhr.responseJSON?.message || 'Error al ejecutar el comando';
            showOutput('[ERROR] ', msg);
            if (typeof toastr !== 'undefined') {
                toastr.error(msg, 'Error');
            }
        })
        .always(function () {
            $btn.prop('disabled', false).html(originalHtml);
            setLoading(false);
        });
    });

    // ── Botón "Ejecutar toda la optimización" con progreso visual ──
    var runAllSteps = [
        { command: 'optimize:enable-all', label: 'Activar optimizaciones', params: {} },
        { command: 'optimize:generate-critical-css', label: 'Generar Critical CSS', params: {} },
        { command: 'optimize:minify-theme-assets', label: 'Minificar assets del tema', params: {} },
        { command: 'optimize:minify-module-assets', label: 'Minificar assets de módulos', params: {} },
        { command: 'optimize:theme-images', label: 'Optimizar imágenes del tema', params: {} },
        { command: 'optimize:optimize-module-images', label: 'Optimizar imágenes de módulos', params: {} },
        { command: 'media:convert-webp', label: 'Convertir Media a WebP', params: {} },
        { command: 'media:generate-srcset', label: 'Generar variantes responsive', params: {} },
        { command: 'theme:audit-a11y', label: 'Auditar accesibilidad', params: {} },
        { command: 'optimize:purge-cache', label: 'Purgar cachés', params: {} },
    ];

    $('#btn-run-all').on('click', function () {
        var $btn = $(this);
        var slug = String($('#run-all-slug').val() || '').trim();
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Ejecutando…');
        setLoading(true);
        $('#run-all-progress').removeClass('d-none');

        var $steps = $('#progress-steps');
        var $bar = $('#progress-bar');
        $steps.empty();

        runAllSteps.forEach(function (step, i) {
            var html = '<div class="d-flex align-items-center gap-2 mb-2 step-item" data-step="' + i + '">' +
                '<span class="step-icon"><i class="fa-regular fa-circle text-muted"></i></span>' +
                '<small class="step-label text-muted">' + (i + 1) + '. ' + step.label + '</small>' +
                '<small class="step-time text-muted ms-auto"></small>' +
                '</div>';
            $steps.append(html);
        });

        var results = [];
        var totalStart = Date.now();

        function updateStep(index, status, time) {
            var $item = $steps.find('[data-step="' + index + '"]');
            var icon = status === 'success' ? '<i class="fa-solid fa-check-circle text-success"></i>' :
                       status === 'error' ? '<i class="fa-solid fa-circle-xmark text-danger"></i>' :
                       '<i class="fa-solid fa-spinner fa-spin text-primary"></i>';
            $item.find('.step-icon').html(icon);
            $item.find('.step-label').removeClass('text-muted fw-semibold').addClass(status === 'running' ? 'fw-semibold' : '');
            if (time) { $item.find('.step-time').text('(' + time + ' ms)'); }
            var pct = Math.round(((index + (status === 'running' ? 0.5 : 1)) / runAllSteps.length) * 100);
            $bar.css('width', pct + '%').attr('aria-valuenow', pct);
        }

        function runStep(index) {
            if (index >= runAllSteps.length) {
                var total = Date.now() - totalStart;
                var out = '$ Optimización completa en ' + (total / 1000).toFixed(1) + ' s\n';
                out += '─'.repeat(60) + '\n';
                results.forEach(function (r, i) {
                    out += '\n[' + (i + 1) + '/' + results.length + '] ' + r.command;
                    if (r.skipped) { out += '  (omitido)\n'; return; }
                    if (r.error) { out += '  [ERROR] ' + r.error + '\n'; return; }
                    out += '  (' + r.elapsed_ms + ' ms)\n';
                    if (r.output) { out += r.output + '\n'; }
                });
                showOutput('', out);
                if (typeof toastr !== 'undefined') {
                    toastr.success('Secuencia completada en ' + (total / 1000).toFixed(1) + ' s', 'Optimización finalizada');
                }
                $btn.prop('disabled', false).html(originalHtml);
                setLoading(false);
                return;
            }

            var step = runAllSteps[index];
            var payload = { command: step.command, _token: '{{ csrf_token() }}' };
            if (step.command === 'optimize:minify-theme-assets' || step.command === 'theme:audit-a11y') {
                payload.slug = slug;
            }
            // quality para media comandos e imágenes
            if (step.command.indexOf('media:') === 0 || step.command.indexOf('optimize:') === 0 && step.command.indexOf('images') !== -1) {
                payload.quality = '82';
            }

            updateStep(index, 'running');

            $.ajax({
                url: '{{ route("settings.optimize.run-command") }}',
                method: 'POST',
                data: payload,
            })
            .done(function (res) {
                results.push({ command: step.command, elapsed_ms: res.elapsed_ms, output: res.output });
                updateStep(index, 'success', res.elapsed_ms);
            })
            .fail(function (xhr) {
                var msg = xhr.responseJSON?.message || 'Error';
                results.push({ command: step.command, error: msg });
                updateStep(index, 'error');
                if (typeof toastr !== 'undefined') { toastr.error(msg, step.label); }
            })
            .always(function () {
                runStep(index + 1);
            });
        }

        runStep(0);
    });
});
</script>
@endpush
