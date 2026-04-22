<div class="card analytics-widget-browsers">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-globe me-2"></i>Navegadores Principales
        </h5>
    </div>
    <div class="card-body">
        <div class="widget-loading text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <div class="widget-error alert alert-danger d-none"></div>

        <div class="widget-content">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="analytics-browsers-chart"></canvas>
                </div>
                <div class="col-md-6">
                    <div class="browsers-list">
                        <!-- Data will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const widget = document.querySelector('.analytics-widget-browsers');
    const loadingEl = widget.querySelector('.widget-loading');
    const errorEl = widget.querySelector('.widget-error');
    const listEl = widget.querySelector('.browsers-list');
    let chart = null;

    const browserColors = {
        'Chrome': '#4285F4',
        'Safari': '#000000',
        'Firefox': '#FF7139',
        'Edge': '#0078D7',
        'Opera': '#FF1B2D',
        'Samsung Internet': '#1428A0',
        'Default': '#6c757d'
    };

    function loadData(range = 'last_7_days') {
        loadingEl.style.display = 'block';
        errorEl.style.display = 'none';

        fetch(`{{ route('api.analytics.top-browsers') }}?range=${range}`)
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderBrowsers(data.data);
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

    function renderBrowsers(browsers) {
        if (!browsers || browsers.length === 0) {
            listEl.innerHTML = '<p class="text-center text-muted">No hay datos disponibles</p>';
            return;
        }

        // Calculate total
        const total = browsers.reduce((sum, b) => sum + b.sessions, 0);

        // Render list
        listEl.innerHTML = browsers.map(browser => {
            const percentage = ((browser.sessions / total) * 100).toFixed(1);
            const color = browserColors[browser.name] || browserColors['Default'];

            return `
                <div class="browser-item mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold">
                            <i class="fas fa-circle fa-xs me-2" style="color: ${color}"></i>
                            ${escapeHtml(browser.name)}
                        </span>
                        <span class="text-muted">${formatNumber(browser.sessions)} (${percentage}%)</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" role="progressbar"
                             style="width: ${percentage}%; background-color: ${color};"
                             aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Render chart
        renderChart(browsers);
    }

    function renderChart(browsers) {
        const ctx = document.getElementById('analytics-browsers-chart');
        if (!ctx) return;

        const labels = browsers.map(b => b.name);
        const data = browsers.map(b => b.sessions);
        const colors = browsers.map(b => browserColors[b.name] || browserColors['Default']);

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = formatNumber(context.parsed);
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('es-ES').format(num);
    }

    // Initial load
    loadData('last_7_days');
})();
</script>
@endpush
