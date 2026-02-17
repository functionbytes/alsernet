<div class="card analytics-widget-pages">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-file-alt me-2"></i>Páginas Más Visitadas
        </h5>
    </div>
    <div class="card-body">
        <div class="widget-loading text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <div class="widget-error alert alert-danger" style="display: none;"></div>

        <div class="widget-content">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="60%">Título de Página</th>
                            <th width="25%">URL</th>
                            <th width="10%" class="text-end">Vistas</th>
                        </tr>
                    </thead>
                    <tbody class="pages-list">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const widget = document.querySelector('.analytics-widget-pages');
    const loadingEl = widget.querySelector('.widget-loading');
    const errorEl = widget.querySelector('.widget-error');
    const listEl = widget.querySelector('.pages-list');

    function loadData(range = 'last_7_days') {
        loadingEl.style.display = 'block';
        errorEl.style.display = 'none';

        fetch(`{{ route('api.analytics.top-pages') }}?range=${range}`)
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderPages(data.data);
                } else {
                    throw new Error(data.message || 'Error al cargar datos');
                }
            })
            .catch(error => {
                errorEl.textContent = error.message;
                errorEl.style.display = 'block';
            })
            .finally(() => {
                loadingEl.style.display = 'none';
            });
    }

    function renderPages(pages) {
        if (!pages || pages.length === 0) {
            listEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay datos disponibles</td></tr>';
            return;
        }

        listEl.innerHTML = pages.map((page, index) => `
            <tr>
                <td class="text-muted">${index + 1}</td>
                <td>
                    <strong>${escapeHtml(page.title || 'Sin título')}</strong>
                </td>
                <td>
                    <small class="text-muted">
                        <a href="${escapeHtml(page.url)}" target="_blank" class="text-decoration-none">
                            ${truncateUrl(page.url, 40)}
                            <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                        </a>
                    </small>
                </td>
                <td class="text-end">
                    <span class="badge bg-primary">${formatNumber(page.views)}</span>
                </td>
            </tr>
        `).join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function truncateUrl(url, maxLength) {
        if (url.length <= maxLength) return url;
        return url.substring(0, maxLength) + '...';
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('es-ES').format(num);
    }

    // Initial load
    loadData('last_7_days');
})();
</script>
@endpush
