@if(\App\Helpers\ModuleStatusHelper::isModuleEnabled('Page'))
    <div class="card-body border-bottom p-4">
        <h6 class="fw-bold mb-1">Estadísticas de caché de páginas</h6>
        <p class="small text-muted mb-3">Resumen del rendimiento del caché para las páginas del sitio</p>

        <div id="page-cache-stats-container">
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <small class="text-muted d-block mt-2">Cargando estadísticas...</small>
            </div>
        </div>

        <div class="row mt-3 g-2">
            <div class="col-12">
                <a href="{{ route('pages.cache.dashboard') }}" class="btn btn-sm btn-outline-info w-100">
                    <i class="fas fa-chart-line me-1"></i> Ver Dashboard Completo
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(document).ready(function() {
        // Load page cache stats
        $.ajax({
            url: '{{ route('settings.cache.stats') }}',
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(stats) {
                const html = `
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="bg-light p-2 rounded">
                                <small class="text-muted d-block">Hit Ratio</small>
                                <h5 class="mb-0 fw-bold">${stats.hit_ratio}%</h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-2 rounded">
                                <small class="text-muted d-block">Páginas Cacheadas</small>
                                <h5 class="mb-0 fw-bold">${stats.cached_pages_count}</h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-2 rounded">
                                <small class="text-muted d-block">Hits</small>
                                <h5 class="mb-0 fw-bold">${stats.total_hits}</h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-2 rounded">
                                <small class="text-muted d-block">Tamaño</small>
                                <h5 class="mb-0 fw-bold">${formatBytes(stats.size_bytes)}</h5>
                            </div>
                        </div>
                    </div>
                `;
                $('#page-cache-stats-container').html(html);
            },
            error: function() {
                $('#page-cache-stats-container').html('<small class="text-muted">Error al cargar estadísticas</small>');
            }
        });

        function formatBytes(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = Math.max(bytes, 0);
            const pow = Math.min(
                Math.floor(size ? Math.log(size) / Math.log(1024) : 0),
                units.length - 1
            );
            size /= Math.pow(1024, pow);
            return Math.round(size * 100) / 100 + ' ' + units[pow];
        }
    });
    </script>
    @endpush
@endif
