<div class="modal fade" id="quickSearchModal" tabindex="-1" aria-label="Busqueda rapida" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="input-group border-bottom">
                    <span class="input-group-text bg-white border-0 ps-3"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="quickSearchInput" class="form-control border-0 fs-5 py-3"
                           placeholder="Buscar productos, clientes, ordenes..." autocomplete="off">
                    <button type="button" class="btn-close me-3 align-self-center" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div id="quickSearchResults" style="max-height:60vh;overflow-y:auto">
                    <p class="p-3 text-center text-muted small mb-0">Escribe al menos 2 caracteres para buscar (Cmd+K / Ctrl+K)</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var searchTimeout;
    var searchUrl = '{{ route('ecommerce.quick-search') }}';
    var groupLabels = { products: 'Productos', customers: 'Clientes', orders: 'Ordenes' };

    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            var modal = new bootstrap.Modal(document.getElementById('quickSearchModal'));
            modal.show();
            setTimeout(function () {
                var input = document.getElementById('quickSearchInput');
                if (input) { input.focus(); }
            }, 200);
        }
    });

    document.getElementById('quickSearchModal')?.addEventListener('shown.bs.modal', function () {
        document.getElementById('quickSearchInput')?.focus();
    });

    document.getElementById('quickSearchModal')?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('quickSearchInput').value = '';
        document.getElementById('quickSearchResults').innerHTML =
            '<p class="p-3 text-center text-muted small mb-0">Escribe al menos 2 caracteres para buscar (Cmd+K / Ctrl+K)</p>';
    });

    document.getElementById('quickSearchInput')?.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        var q = this.value.trim();

        if (q.length < 2) {
            document.getElementById('quickSearchResults').innerHTML =
                '<p class="p-3 text-center text-muted small mb-0">Escribe al menos 2 caracteres para buscar (Cmd+K / Ctrl+K)</p>';
            return;
        }

        searchTimeout = setTimeout(function () {
            fetch(searchUrl + '?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var html = '';
                    var hasResults = false;

                    ['products', 'customers', 'orders'].forEach(function (group) {
                        if (!data[group] || !data[group].length) { return; }
                        hasResults = true;
                        html += '<div class="px-3 pt-3 pb-1"><small class="text-uppercase text-muted fw-bold">' + groupLabels[group] + '</small></div>';
                        data[group].forEach(function (item) {
                            html += '<a href="' + item.url + '" class="d-block px-3 py-2 text-decoration-none text-dark border-bottom quick-search-item">'
                                + '<i class="fas fa-arrow-right me-2 text-muted" style="font-size:.75rem"></i>' + item.label
                                + '</a>';
                        });
                    });

                    document.getElementById('quickSearchResults').innerHTML = hasResults
                        ? html
                        : '<p class="p-3 text-center text-muted">Sin resultados para <strong>' + q + '</strong></p>';
                })
                .catch(function () {
                    document.getElementById('quickSearchResults').innerHTML =
                        '<p class="p-3 text-center text-danger">Error al buscar. Intenta nuevamente.</p>';
                });
        }, 250);
    });
}());
</script>
@endpush

@push('css')
<style>
.quick-search-item:hover { background-color: #f8f9fa; }
</style>
@endpush
