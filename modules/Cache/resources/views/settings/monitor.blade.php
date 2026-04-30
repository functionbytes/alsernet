@extends('layouts.theme')

@section('title', 'Monitor de cache')

@section('page_header')
    @include('core::components.card', ['title' => 'Monitor de cache'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Monitor de cache</h5>
                        <p class="small mb-0 text-muted">Estado de Redis y herramientas para limpiar el cache del sistema</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="javascript:void(0)" id="btn-refresh-redis">Actualizar estado</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('settings.cache.index') }}">Volver a configuracion</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Redis status --}}
            <div class="card-body border-bottom p-4">
                <h6 class="fw-semibold mb-3">Estado de Redis</h6>
                <div id="redis-stats-content">
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-spinner fa-spin me-1"></i> Cargando...
                    </div>
                </div>
            </div>

            {{-- Flush --}}
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-1">Limpiar cache</h6>
                <p class="small text-muted mb-3">Invalida entradas de cache especificas sin necesidad de reiniciar el sistema</p>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-danger btn-flush" data-type="all">Limpiar todo</button>
                    <button type="button" class="btn btn-outline-warning btn-flush" data-type="menu">Limpiar menu</button>
                    <button type="button" class="btn btn-outline-warning btn-flush" data-type="settings">Limpiar configuracion</button>
                    @if(\Modules\System\Helpers\ModuleStatusHelper::isModuleEnabled('Page'))
                        <button type="button" class="btn btn-outline-warning btn-flush" data-type="pages">Limpiar paginas</button>
                    @endif
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function loadRedisStats() {
        $.get('{{ route('settings.cache.redis-stats') }}')
            .done(function (data) {
                $('#redis-stats-content').html(renderRedisStats(data));
            })
            .fail(function () {
                $('#redis-stats-content').html('<div class="alert alert-danger mb-0">No se pudo obtener el estado de Redis.</div>');
            });
    }

    function renderRedisStats(data) {
        if (!data.connected) {
            return '<div class="alert alert-danger mb-0">Desconectado: ' + (data.error || '') + '</div>';
        }

        const hitRateColor = data.hit_rate >= 80 ? 'success' : (data.hit_rate >= 50 ? 'warning' : 'danger');

        return `
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="p-3 border rounded text-center">
                        <span class="badge bg-success mb-2">Conectado</span>
                        <div class="small text-muted">Estado</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 border rounded text-center">
                        <div class="fw-bold fs-5">${data.used_memory}</div>
                        <div class="small text-muted">Memoria usada</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 border rounded text-center">
                        <div class="fw-bold fs-5">${data.connected_clients}</div>
                        <div class="small text-muted">Clientes conectados</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 border rounded text-center">
                        <div class="fw-bold fs-5">${data.uptime_days} dias</div>
                        <div class="small text-muted">Tiempo activo</div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold mb-1">Hit rate: ${data.hit_rate}%</label>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-${hitRateColor}" style="width: ${data.hit_rate}%"></div>
                    </div>
                    <small class="text-muted">${data.keyspace_hits.toLocaleString()} aciertos / ${data.keyspace_misses.toLocaleString()} fallos</small>
                </div>
            </div>`;
    }

    $('#btn-refresh-redis').on('click', loadRedisStats);

    $(document).on('click', '.btn-flush', function () {
        const type = $(this).data('type');
        const labels = {
            all: 'todo el cache',
            menu: 'cache del menu',
            settings: 'cache de configuracion',
            pages: 'cache de paginas',
        };

        if (!confirm('¿Confirmas que quieres limpiar ' + (labels[type] || type) + '?')) {
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: '{{ route('settings.cache.flush') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { type: type },
        })
            .done(function () {
                toastr.success('Cache limpiado correctamente');
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al limpiar el cache');
            })
            .always(function () {
                btn.prop('disabled', false);
            });
    });

    loadRedisStats();
})();
</script>
@endpush
