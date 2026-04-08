@extends('layouts.theme')

@section('title', 'Mantenimiento de caché')

@section('content')

    @include('core::components.card', ['title' => 'Mantenimiento de caché'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">
                <div class="card">

                    {{-- Caché de aplicación --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Caché de aplicación</h6>
                        <p class="text-muted mb-3">Limpia los datos almacenados en caché cuando los cambios no se reflejan correctamente.</p>

                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold">Caché del sistema</div>
                                    <small class="text-muted">Elimina datos almacenados temporalmente. Ejecuta cuando no veas cambios después de actualizar datos.</small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm btn-clear-cache"
                                        data-url="{{ route('settings.system.cache.clear-cache') }}"
                                        data-title="Caché del sistema">
                                    Ejecutar
                                </button>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold">Vistas compiladas</div>
                                    <small class="text-muted">Regenera las plantillas Blade compiladas. Necesario si modificaste archivos de vista manualmente.</small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm btn-clear-cache"
                                        data-url="{{ route('settings.system.cache.clear-view-cache') }}"
                                        data-title="Vistas compiladas">
                                    Ejecutar
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Configuración y rutas --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Configuración y rutas</h6>
                        <p class="text-muted mb-3">Gestiona la caché de archivos de configuración y definiciones de rutas del sistema.</p>

                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold">Limpiar caché de configuración</div>
                                    <small class="text-muted">Elimina la configuración cacheada. Ejecuta después de modificar archivos <code>.env</code> o <code>config/</code>.</small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm btn-clear-cache"
                                        data-url="{{ route('settings.system.cache.clear-config-cache') }}"
                                        data-title="Caché de configuración">
                                    Ejecutar
                                </button>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold">Cachear configuración</div>
                                    <small class="text-muted">Compila todos los archivos de configuración en uno solo para mejorar el rendimiento en producción.</small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm btn-clear-cache"
                                        data-url="{{ route('settings.system.cache.cache-config') }}"
                                        data-title="Cachear configuración">
                                    Ejecutar
                                </button>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold">Limpiar caché de rutas</div>
                                    <small class="text-muted">Elimina las rutas cacheadas. Necesario después de agregar o modificar rutas.</small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm btn-clear-cache"
                                        data-url="{{ route('settings.system.cache.clear-route-cache') }}"
                                        data-title="Caché de rutas">
                                    Ejecutar
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Optimización --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Optimización</h6>
                        <p class="text-muted mb-3">Operaciones avanzadas de mantenimiento y regeneración del sistema.</p>

                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold">Limpiar optimización</div>
                                    <small class="text-muted">Elimina todos los archivos de optimización generados por el framework.</small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm btn-clear-cache"
                                        data-url="{{ route('settings.system.cache.clear-optimization') }}"
                                        data-title="Optimización">
                                    Ejecutar
                                </button>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold">Composer dump-autoload</div>
                                    <small class="text-muted">Regenera el mapa de autoload de Composer. Necesario al agregar nuevas clases o mover archivos.</small>
                                </div>
                                <button class="btn btn-outline-primary btn-sm btn-clear-cache"
                                        data-url="{{ route('settings.system.cache.composer-dump-autoload') }}"
                                        data-title="Composer dump-autoload">
                                    Ejecutar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button class="btn btn-primary w-100" id="execute-all-btn"
                                data-url="{{ route('settings.system.cache.execute-all') }}">
                            Ejecutar todos los comandos
                        </button>
                    </div>

                </div>
            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Ejecución rápida</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Ejecuta todos los comandos de limpieza en secuencia con un solo clic desde el botón "Ejecutar todos los comandos".</p>

                        <div class="alert alert-warning border-0 mb-0 py-2">
                            <small>
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Puede causar una breve lentitud mientras se regeneran las cachés.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Cuándo limpiar caché</h6>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-semibold mb-2">Después de actualizar</h6>
                        <p class="text-muted mb-3">Siempre limpia la caché después de una actualización del sistema o de modificar archivos de configuración.</p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Cambios no visibles</h6>
                        <p class="text-muted mb-3">Si realizaste cambios que no se reflejan en el sitio, ejecuta "Caché del sistema" y "Vistas compiladas".</p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Nuevas rutas o clases</h6>
                        <p class="text-muted mb-0">Al agregar nuevas funcionalidades, ejecuta "Limpiar caché de rutas" y "Composer dump-autoload".</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Comandos equivalentes</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><code class="small">php artisan cache:clear</code></li>
                            <li class="mb-2"><code class="small">php artisan view:clear</code></li>
                            <li class="mb-2"><code class="small">php artisan config:clear</code></li>
                            <li class="mb-2"><code class="small">php artisan config:cache</code></li>
                            <li class="mb-2"><code class="small">php artisan route:clear</code></li>
                            <li class="mb-2"><code class="small">php artisan optimize:clear</code></li>
                            <li><code class="small">composer dump-autoload</code></li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var CSRF = $('meta[name="csrf-token"]').attr('content');

    $('.btn-clear-cache').on('click', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        var title = $btn.data('title');
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            success: function (data) {
                $btn.prop('disabled', false).html(originalText);
                if (data.success) {
                    toastr.success(data.message, title);
                } else {
                    toastr.error(data.message, title);
                }
            },
            error: function () {
                $btn.prop('disabled', false).html(originalText);
                toastr.error('Error al ejecutar el comando.', title);
            }
        });
    });

    $('#execute-all-btn').on('click', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Ejecutando...');

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            success: function (data) {
                $btn.prop('disabled', false).html(originalText);

                if (data.success) {
                    toastr.success(data.message, 'Ejecución completa');
                } else {
                    toastr.warning(data.message, 'Ejecución parcial');
                }

                if (data.results) {
                    var names = {
                        'cache_clear': 'Caché del sistema',
                        'view_clear': 'Vistas compiladas',
                        'config_clear': 'Config. limpiada',
                        'config_cache': 'Config. cacheada',
                        'route_clear': 'Rutas limpiadas',
                        'optimize_clear': 'Optimización',
                        'composer_dump_autoload': 'Composer autoload'
                    };

                    $.each(data.results, function (key, result) {
                        var label = names[key] || key;
                        if (result.success) {
                            toastr.info(result.message, label);
                        } else {
                            toastr.error(result.message, label);
                        }
                    });
                }
            },
            error: function () {
                $btn.prop('disabled', false).html(originalText);
                toastr.error('Error al ejecutar los comandos.', 'Error');
            }
        });
    });
});
</script>
@endpush
