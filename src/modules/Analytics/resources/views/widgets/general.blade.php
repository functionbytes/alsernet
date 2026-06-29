<div class="card analytics-widget-general">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-chart-line me-2"></i>Estadísticas Generales
        </h5>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" data-range="last_7_days">7 días</button>
            <button type="button" class="btn btn-outline-secondary" data-range="last_30_days">30 días</button>
            <button type="button" class="btn btn-outline-secondary" data-range="this_month">Este mes</button>
        </div>
    </div>
    <div class="card-body">
        <div class="widget-loading text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <div class="widget-error alert alert-danger d-none"></div>

        <div class="widget-content">
            <div class="row g-4">
                <!-- Sessions -->
                <div class="col-md-3">
                    <div class="metric-card bg-primary bg-opacity-10 p-3 rounded">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1 small">Sesiones</h6>
                                <h3 class="mb-0 fw-bold" data-metric="sessions">-</h3>
                            </div>
                            <div class="metric-icon">
                                <i class="fas fa-users fa-2x text-primary opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="col-md-3">
                    <div class="metric-card bg-success bg-opacity-10 p-3 rounded">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1 small">Usuarios</h6>
                                <h3 class="mb-0 fw-bold" data-metric="totalUsers">-</h3>
                            </div>
                            <div class="metric-icon">
                                <i class="fas fa-user fa-2x text-success opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page Views -->
                <div class="col-md-3">
                    <div class="metric-card bg-info bg-opacity-10 p-3 rounded">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1 small">Vistas</h6>
                                <h3 class="mb-0 fw-bold" data-metric="screenPageViews">-</h3>
                            </div>
                            <div class="metric-icon">
                                <i class="fas fa-eye fa-2x text-info opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bounce Rate -->
                <div class="col-md-3">
                    <div class="metric-card bg-warning bg-opacity-10 p-3 rounded">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1 small">Rebote</h6>
                                <h3 class="mb-0 fw-bold" data-metric="bounceRate">-</h3>
                            </div>
                            <div class="metric-icon">
                                <i class="fas fa-percentage fa-2x text-warning opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="mt-4">
                <canvas id="analytics-general-chart"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const widget = document.querySelector('.analytics-widget-general');
    const loadingEl = widget.querySelector('.widget-loading');
    const errorEl = widget.querySelector('.widget-error');
    const contentEl = widget.querySelector('.widget-content');
    let chart = null;

    function loadData(range = 'last_7_days') {
        loadingEl.style.display = 'block';
        errorEl.style.display = 'none';
        contentEl.style.opacity = '0.5';

        fetch(`{{ route('api.analytics.overview') }}?range=${range}`)
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    updateMetrics(data.data.totals);
                    updateChart(data.data.chart_data);
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
                contentEl.style.opacity = '1';
            });
    }

    function updateMetrics(totals) {
        Object.keys(totals).forEach(key => {
            const el = widget.querySelector(`[data-metric="${key}"]`);
            if (el) {
                let value = totals[key];
                if (key === 'bounceRate') {
                    value = (value * 100).toFixed(1) + '%';
                } else {
                    value = new Intl.NumberFormat('es-ES').format(value);
                }
                el.textContent = value;
            }
        });
    }

    function updateChart(chartData) {
        const ctx = document.getElementById('analytics-general-chart');
        if (!ctx) return;

        const labels = chartData.map(row => row[0]);
        const sessions = chartData.map(row => row[1]);
        const pageViews = chartData.map(row => row[2]);

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sesiones',
                        data: sessions,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Vistas de página',
                        data: pageViews,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }

    // Range buttons
    widget.querySelectorAll('[data-range]').forEach(btn => {
        btn.addEventListener('click', function() {
            widget.querySelectorAll('[data-range]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadData(this.dataset.range);
        });
    });

    // Initial load
    widget.querySelector('[data-range="last_7_days"]').classList.add('active');
    loadData('last_7_days');
})();
</script>
@endpush
