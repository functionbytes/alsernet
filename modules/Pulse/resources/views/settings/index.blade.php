@extends('layouts.theme')

@section('title', 'Configuración de Pulse')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de Pulse'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form method="POST" action="{{ route('settings.pulse.update') }}">
        @csrf

        <div class="card">
            <div class="card-header">
                <h5 class="mb-1 fw-bold">Configuración de pulse</h5>
                <p class="small mb-0 text-muted">Administra el monitoreo en tiempo real de tu aplicación: rendimiento, errores, colas y uso por usuario.</p>
            </div>

            {{-- General Settings --}}
            <div class="card-body border-top">
                <h6 class="mb-1 fw-bold">Configuración general</h6>
                <p class="small text-muted">Configura los parámetros principales de Pulse</p>
                <div class="row g-3">
                    {{-- Path --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ruta del dashboard</label>
                        <input type="text" class="form-control" name="path" value="{{ $settings['path'] }}" required>
                        <small class="text-muted">Ruta donde estará disponible el dashboard de Pulse (ej: /pulse)</small>
                    </div>

                    {{-- Domain --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Dominio (opcional)</label>
                        <input type="text" class="form-control" name="domain" value="{{ $settings['domain'] }}" placeholder="ej: pulse.example.com">
                        <small class="text-muted">Subdominio donde estará disponible el dashboard</small>
                    </div>

                    {{-- Server Name --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre del servidor</label>
                        <input type="text" class="form-control" name="server_name" value="{{ $settings['server_name'] }}" required>
                        <small class="text-muted">Identificador del servidor para las métricas</small>
                    </div>

                    {{-- Server Directories --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Directorios a monitorear</label>
                        <input type="text" class="form-control" name="server_directories" value="{{ $settings['server_directories'] }}" required>
                        <small class="text-muted">Rutas separadas por dos puntos (ej: /:/var/www)</small>
                    </div>
                </div>
            </div>

            {{-- Storage & Ingest --}}
            <div class="card-body border-top">
                <h6 class="mb-1 fw-bold">Almacenamiento e ingesta</h6>
                <p class="small text-muted">Configura cómo se almacenan y procesan los datos</p>
                <div class="row g-3">
                    {{-- Ingest Driver --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Driver de ingesta</label>
                        <select class="form-control select2" name="ingest_driver" required>
                            <option value="storage" {{ $settings['ingest_driver'] === 'storage' ? 'selected' : '' }}>Almacenamiento</option>
                            <option value="redis" {{ $settings['ingest_driver'] === 'redis' ? 'selected' : '' }}>Redis</option>
                        </select>
                        <small class="text-muted">Método para capturar y procesar datos</small>
                    </div>

                    {{-- Ingest Buffer --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Buffer de ingesta</label>
                        <input type="number" class="form-control" name="ingest_buffer" value="{{ $settings['ingest_buffer'] }}" min="100" max="50000" required>
                        <small class="text-muted">Tamaño máximo del buffer (100-50000)</small>
                    </div>

                    {{-- Storage Driver --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Driver de almacenamiento</label>
                        <select class="form-control select2" name="storage_driver" required>
                            <option value="database" {{ $settings['storage_driver'] === 'database' ? 'selected' : '' }}>Base de datos</option>
                            <option value="redis" {{ $settings['storage_driver'] === 'redis' ? 'selected' : '' }}>Redis</option>
                        </select>
                        <small class="text-muted">Dónde se almacenan los datos de Pulse</small>
                    </div>

                    {{-- Storage Keep --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Retención de datos</label>
                        <input type="text" class="form-control" name="storage_keep" value="{{ $settings['storage_keep'] }}" required>
                        <small class="text-muted">Cuánto tiempo mantener los datos (ej: 7 days, 30 days)</small>
                    </div>

                    {{-- Ingest Keep --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Retención de ingesta</label>
                        <input type="text" class="form-control" name="ingest_keep" value="{{ $settings['ingest_keep'] }}" required>
                        <small class="text-muted">Cuánto tiempo mantener datos en tránsito</small>
                    </div>
                </div>
            </div>

            {{-- Thresholds --}}
            <div class="card-body border-top">
                <h6 class="mb-1 fw-bold">Umbrales de rendimiento</h6>
                <p class="small text-muted">Configurar qué se considera "lento" (en milisegundos)</p>
                <div class="row g-3">
                    {{-- Slow Jobs Threshold --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Umbral de trabajos lentos (ms)</label>
                        <input type="number" class="form-control" name="slow_jobs_threshold" value="{{ $settings['slow_jobs_threshold'] }}" min="100" max="60000" required>
                        <small class="text-muted">Tiempo mínimo para considerar un trabajo como lento</small>
                    </div>

                    {{-- Slow Outgoing Requests Threshold --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Umbral de solicitudes salientes (ms)</label>
                        <input type="number" class="form-control" name="slow_outgoing_requests_threshold" value="{{ $settings['slow_outgoing_requests_threshold'] }}" min="100" max="60000" required>
                        <small class="text-muted">Tiempo para solicitudes HTTP externas</small>
                    </div>

                    {{-- Slow Queries Threshold --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Umbral de consultas lentas (ms)</label>
                        <input type="number" class="form-control" name="slow_queries_threshold" value="{{ $settings['slow_queries_threshold'] }}" min="100" max="60000" required>
                        <small class="text-muted">Tiempo para consultas de base de datos</small>
                    </div>

                    {{-- Slow Requests Threshold --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Umbral de solicitudes lentas (ms)</label>
                        <input type="number" class="form-control" name="slow_requests_threshold" value="{{ $settings['slow_requests_threshold'] }}" min="100" max="60000" required>
                        <small class="text-muted">Tiempo para solicitudes HTTP</small>
                    </div>
                </div>
            </div>

            {{-- Recorders --}}
            @php
                $recorderMeta = [
                    'cache_interactions' => [
                        'icon'  => 'fa-database',
                        'color' => 'primary',
                        'desc'  => 'Registra todos los accesos a caché (hits y misses). Útil para detectar claves con baja tasa de acierto y optimizar el rendimiento del caché.',
                    ],
                    'exceptions' => [
                        'icon'  => 'fa-triangle-exclamation',
                        'color' => 'danger',
                        'desc'  => 'Captura excepciones y errores no controlados de la aplicación. Permite ver frecuencia, tipo y ubicación de cada error.',
                    ],
                    'queues' => [
                        'icon'  => 'fa-layer-group',
                        'color' => 'info',
                        'desc'  => 'Monitorea el estado de las colas: jobs en espera, procesados, fallidos y tiempo de procesamiento por cola.',
                    ],
                    'slow_jobs' => [
                        'icon'  => 'fa-briefcase',
                        'color' => 'warning',
                        'desc'  => 'Detecta jobs de cola que superan el umbral de tiempo configurado. Ayuda a identificar tareas en segundo plano con problemas de rendimiento.',
                    ],
                    'slow_outgoing_requests' => [
                        'icon'  => 'fa-arrow-up-right-from-square',
                        'color' => 'warning',
                        'desc'  => 'Registra peticiones HTTP salientes (Guzzle, Http::) que tardan más del umbral. Ideal para detectar APIs externas lentas.',
                    ],
                    'slow_queries' => [
                        'icon'  => 'fa-magnifying-glass',
                        'color' => 'warning',
                        'desc'  => 'Captura consultas SQL que superan el umbral. Incluye la consulta, bindings y ubicación en el código para facilitar la optimización.',
                    ],
                    'slow_requests' => [
                        'icon'  => 'fa-globe',
                        'color' => 'warning',
                        'desc'  => 'Registra solicitudes HTTP entrantes (rutas web/API) que tardan más del umbral configurado. Muestra qué endpoints son los más lentos.',
                    ],
                    'user_jobs' => [
                        'icon'  => 'fa-user-gear',
                        'color' => 'secondary',
                        'desc'  => 'Agrupa los jobs de cola por usuario autenticado. Permite ver qué usuarios generan más carga en segundo plano.',
                    ],
                    'user_requests' => [
                        'icon'  => 'fa-user',
                        'color' => 'secondary',
                        'desc'  => 'Registra solicitudes HTTP agrupadas por usuario. Muestra los usuarios más activos y su consumo de recursos.',
                    ],
                ];
            @endphp

            <div class="card-body border-top">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h6 class="mb-0 fw-bold">Registradores</h6>
                    <span class="badge bg-light-secondary text-dark">
                        {{ collect($recorders)->where('enabled', true)->count() }}/{{ count($recorders) }} activos
                    </span>
                </div>
                <p class="small text-muted mb-3">
                    Activa o desactiva cada tipo de monitoreo. La <strong>frecuencia de muestreo</strong> (0–1) controla
                    qué porcentaje de eventos se registran — valores bajos reducen la carga en producción.
                </p>
                <div class="row g-3">
                    @foreach($recorders as $key => $recorder)
                        @php
                            $meta    = $recorderMeta[$key] ?? ['icon' => 'fa-circle', 'color' => 'secondary', 'desc' => ''];
                            $enabled = $recorder['enabled'];
                        @endphp
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 recorder-card {{ $enabled ? 'border-success-subtle' : 'border-0 bg-light opacity-75' }}"
                                 id="card-{{ $key }}">
                                <div class="card-body p-3 d-flex flex-column gap-2">

                                    {{-- Header: name + toggle --}}
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="fw-semibold" style="font-size:.875rem;">
                                            {{ $recorder['label'] }}
                                        </div>
                                        <div class="form-check form-switch mb-0 flex-shrink-0">
                                            <input class="form-check-input recorder-toggle" type="checkbox" role="switch"
                                                   name="recorder_{{ $key }}_enabled"
                                                   id="recorder_{{ $key }}_enabled"
                                                   data-key="{{ $key }}"
                                                   value="1" {{ $enabled ? 'checked' : '' }}>
                                        </div>
                                    </div>

                                    {{-- Description --}}
                                    <p class="text-muted mb-0" style="font-size:.78rem; line-height:1.4;">
                                        {{ $meta['desc'] }}
                                    </p>

                                    {{-- Sample rate (visible only when enabled) --}}
                                    <div class="recorder-sample mt-auto pt-2 border-top {{ !$enabled ? 'd-none' : '' }}"
                                         id="sample-{{ $key }}">
                                        <input type="number"
                                               class="form-control"
                                               name="recorder_{{ $key }}_sample_rate"
                                               id="rate-{{ $key }}"
                                               value="{{ $recorder['sample_rate'] }}"
                                               min="0" max="1" step="0.05"
                                               placeholder="ej: 1 = 100%, 0.5 = 50%">
                                    </div>

                                    {{-- Hidden input when disabled (sends 0) --}}
                                    <input type="hidden"
                                           class="{{ $enabled ? 'd-none' : '' }}"
                                           id="rate-hidden-{{ $key }}"
                                           name="recorder_{{ $key }}_sample_rate"
                                           value="{{ $recorder['sample_rate'] }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Submit --}}
            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar configuración
                </button>
                <a href="{{ route('pulse.dashboard') }}" class="btn btn-outline-secondary w-100 mb-2">
                    Ver dashboard
                </a>
                <a href="{{ route('settings.panel') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('.select2').select2({ allowClear: false, width: '100%' });

            $('.recorder-toggle').on('change', function () {
                var key     = $(this).data('key');
                var enabled = $(this).is(':checked');
                var $card   = $('#card-' + key);
                var $sample = $('#sample-' + key);
                var $hidden = $('#rate-hidden-' + key);
                $card.toggleClass('border-success-subtle', enabled).toggleClass('border-0 bg-light opacity-75', !enabled);
                $sample.toggleClass('d-none', !enabled);
                $hidden.toggleClass('d-none', enabled);
            });
        });
    </script>
@endpush
