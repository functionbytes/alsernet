<div class="card analytics-widget-referrers">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-external-link-alt me-2"></i>Fuentes de Tráfico
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
            <div class="referrers-list">
                <!-- Data will be loaded here -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const widget = document.querySelector('.analytics-widget-referrers');
    const loadingEl = widget.querySelector('.widget-loading');
    const errorEl = widget.querySelector('.widget-error');
    const listEl = widget.querySelector('.referrers-list');

    const sourceIcons = {
        'google': 'fab fa-google',
        'facebook': 'fab fa-facebook',
        'twitter': 'fab fa-twitter',
        'linkedin': 'fab fa-linkedin',
        'instagram': 'fab fa-instagram',
        'youtube': 'fab fa-youtube',
        'direct': 'fas fa-arrow-right',
        'email': 'fas fa-envelope',
        'referral': 'fas fa-link',
        'organic': 'fas fa-search',
        'social': 'fas fa-share-alt',
        'default': 'fas fa-globe'
    };

    function loadData(range = 'last_7_days') {
        loadingEl.style.display = 'block';
        errorEl.style.display = 'none';

        fetch(`{{ route('api.analytics.top-referrers') }}?range=${range}`)
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    renderReferrers(data.data);
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

    function renderReferrers(referrers) {
        if (!referrers || referrers.length === 0) {
            listEl.innerHTML = '<p class="text-center text-muted">No hay datos disponibles</p>';
            return;
        }

        // Calculate total
        const total = referrers.reduce((sum, r) => sum + r.views, 0);

        listEl.innerHTML = referrers.map((referrer, index) => {
            const percentage = ((referrer.views / total) * 100).toFixed(1);
            const icon = getSourceIcon(referrer.source);
            const color = getSourceColor(index);

            return `
                <div class="referrer-item mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper me-3">
                                <i class="${icon} fa-2x" style="color: ${color}"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">${escapeHtml(referrer.source)}</h6>
                                <small class="text-muted">${getSourceType(referrer.source)}</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0 fw-bold">${formatNumber(referrer.views)}</h6>
                            <small class="text-muted">${percentage}% del total</small>
                        </div>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar" role="progressbar"
                             style="width: ${percentage}%; background-color: ${color};"
                             aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function getSourceIcon(source) {
        const lowerSource = source.toLowerCase();
        for (const key in sourceIcons) {
            if (lowerSource.includes(key)) {
                return sourceIcons[key];
            }
        }
        return sourceIcons['default'];
    }

    function getSourceType(source) {
        const lowerSource = source.toLowerCase();
        if (lowerSource === 'direct' || lowerSource === '(direct)') return 'Tráfico Directo';
        if (lowerSource.includes('google')) return 'Motor de Búsqueda';
        if (lowerSource.includes('facebook') || lowerSource.includes('twitter') || lowerSource.includes('instagram')) return 'Red Social';
        if (lowerSource.includes('email')) return 'Email Marketing';
        return 'Referencia Externa';
    }

    function getSourceColor(index) {
        const colors = [
            '#4285F4', '#34A853', '#FBBC05', '#EA4335',
            '#8E24AA', '#FF6D00', '#00ACC1', '#43A047',
            '#5E35B1', '#C0CA33'
        ];
        return colors[index % colors.length];
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
