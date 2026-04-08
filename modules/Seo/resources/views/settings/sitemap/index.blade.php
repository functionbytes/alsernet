@extends('layouts.theme')

@section('title', 'Sitemap XML')

@section('content')

    @include('core::components.card', ['title' => 'Sitemap XML'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">

                <div class="card">
                    <div class="card-body">

                        {{-- Estado --}}
                        <h6 class="fw-bold text-dark mb-1">Estado del sitemap</h6>
                        <p class="text-muted mb-3">Información actual sobre el archivo sitemap.xml y su caché.</p>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2">Sitemaps</h6>
                                        <h4 class="mb-1 fw-bold">{{ count($sitemaps) }}</h4>
                                        <small class="text-muted">Tipos disponibles</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2">Caché</h6>
                                        <h4 class="mb-1 fw-bold">{{ $cacheEnabled ? 'Activo' : 'Inactivo' }}</h4>
                                        <small class="text-muted">{{ $cacheDuration }} seg duración</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2">Archivo XML</h6>
                                        <h4 class="mb-1 fw-bold">{{ $fileExists ? 'Generado' : 'Pendiente' }}</h4>
                                        <small class="text-muted">public/sitemap.xml</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2">Última generación</h6>
                                        <h4 class="mb-1 fw-bold">
                                            {{ $lastModified ? \Carbon\Carbon::createFromTimestamp($lastModified)->format('d/m/Y') : '—' }}
                                        </h4>
                                        <small class="text-muted">
                                            {{ $lastModified ? \Carbon\Carbon::createFromTimestamp($lastModified)->format('H:i') : 'Nunca generado' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <hr class="my-0">

                    {{-- Lista de sitemaps --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Sitemaps disponibles</h6>
                        <p class="text-muted mb-3">URLs de cada tipo de sitemap generado por el sistema.</p>

                        @foreach($sitemaps as $sitemap)
                            <div class="d-flex align-items-center justify-content-between p-3 border rounded {{ !$loop->last ? 'mb-2' : '' }}">
                                <div>
                                    <div class="fw-semibold">{{ $sitemap['name'] }}</div>
                                    <small class="text-muted">{{ $sitemap['description'] }}</small>
                                    <div><code class="small text-break">{{ $sitemap['url'] }}</code></div>
                                </div>
                                <a href="{{ $sitemap['url'] }}" target="_blank" class="btn btn-outline-secondary btn-sm flex-shrink-0 ms-3">
                                    Ver XML
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-0">

                    {{-- Calculadora de prioridades --}}
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Calculadora de prioridades</h6>
                                <p class="text-muted mb-0">Calcula la prioridad sugerida para cada URL basándose en profundidad, frescura y popularidad.</p>
                            </div>
                            <button type="button" id="calculate-priorities-btn" class="btn btn-outline-primary btn-sm flex-shrink-0 ms-3">
                                Calcular
                            </button>
                        </div>

                        <div id="priorities-loading" class="text-center py-4" style="display:none;">
                            <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
                            <p class="text-muted mb-0">Calculando prioridades...</p>
                        </div>

                        <div id="priorities-result" style="display:none;">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>URL</th>
                                            <th class="text-center" width="120">Prioridad</th>
                                        </tr>
                                    </thead>
                                    <tbody id="priorities-table"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Verificar URLs --}}
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Verificar URLs del sitemap</h6>
                                <p class="text-muted mb-0">Comprueba que las URLs estáticas del sitemap responden correctamente.</p>
                            </div>
                            <button type="button" id="verify-urls-btn" class="btn btn-outline-primary btn-sm flex-shrink-0 ms-3">
                                Verificar
                            </button>
                        </div>

                        <div id="verify-urls-loading" class="text-center py-4" style="display:none;">
                            <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
                            <p class="text-muted mb-0">Verificando URLs...</p>
                        </div>

                        <div id="verify-urls-result" style="display:none;">
                            <div class="row g-3 mb-3" id="verify-summary-cards"></div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>URL</th>
                                            <th>Estado</th>
                                            <th>Fuente</th>
                                        </tr>
                                    </thead>
                                    <tbody id="verify-urls-table"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Regenerar sitemap</h6>
                        <p class="text-muted mb-3">Regenera el archivo sitemap.xml con el contenido actual del sitio.</p>
                        <form method="POST" action="{{ route('setting.seo.sitemap.generate') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100"
                                    onclick="return confirm('¿Regenerar el sitemap?')">
                                Regenerar sitemap
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Limpiar caché</h6>
                        <p class="text-muted mb-3">Limpia la caché del sitemap. Se volverá a cachear automáticamente al acceder.</p>
                        <form method="POST" action="{{ route('setting.seo.sitemap.clear-cache') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100"
                                    onclick="return confirm('¿Limpiar la caché del sitemap?')">
                                Limpiar caché
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">URLs estáticas</h6>
                        <p class="text-muted mb-3">Administra las URLs estáticas que se incluyen en el sitemap.</p>
                        <a href="{{ route('setting.seo.static-urls.index') }}" class="btn btn-outline-secondary w-100">
                            Gestionar URLs estáticas
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Dashboard SEO</h6>
                        <p class="text-muted mb-3">Vuelve al panel principal de SEO.</p>
                        <a href="{{ route('setting.seo.dashboard') }}" class="btn btn-outline-secondary w-100">
                            Ir al dashboard
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
(function () {
    // Calculate priorities
    $('#calculate-priorities-btn').on('click', function () {
        $(this).html('<span class="spinner-border spinner-border-sm me-1"></span>Calculando...').prop('disabled', true);
        $('#priorities-loading').show();
        $('#priorities-result').hide();

        $.ajax({
            url: '{{ route("setting.seo.sitemap.calculate-priorities") }}',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                var priorities = res.priorities;

                if (!priorities || priorities.length === 0) {
                    $('#priorities-table').html('<tr><td colspan="2" class="text-center text-muted py-4">No hay URLs con canonical_url configurado.</td></tr>');
                    $('#priorities-result').show();
                    return;
                }

                var rows = priorities.map(function (item) {
                    var badgeClass = item.priority >= 0.8 ? 'bg-success-subtle text-success'
                        : item.priority >= 0.5 ? 'bg-warning-subtle text-warning'
                        : 'bg-secondary-subtle text-secondary';

                    return '<tr>' +
                        '<td><code class="small text-break">' + item.url + '</code></td>' +
                        '<td class="text-center"><span class="badge ' + badgeClass + ' fs-6">' + item.priority.toFixed(1) + '</span></td>' +
                        '</tr>';
                }).join('');

                $('#priorities-table').html(rows);
                $('#priorities-result').show();
            },
            error: function () {
                toastr.error('Error al calcular las prioridades.');
            },
            complete: function () {
                $('#priorities-loading').hide();
                $('#calculate-priorities-btn').html('Calcular').prop('disabled', false);
            },
        });
    });

    // Verify URLs
    $('#verify-urls-btn').on('click', function () {
        $(this).html('<span class="spinner-border spinner-border-sm me-1"></span>Verificando...').prop('disabled', true);
        $('#verify-urls-loading').show();
        $('#verify-urls-result').hide();

        $.ajax({
            url: '{{ route("setting.seo.sitemap.verify-urls") }}',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (!res.status) return;

                var s = res.summary;
                $('#verify-summary-cards').html(
                    '<div class="col-md-4"><div class="card bg-light-secondary text-center py-3 h-100"><div class="fs-4 fw-bold">' + s.total + '</div><small class="text-muted">URLs verificadas</small></div></div>' +
                    '<div class="col-md-4"><div class="card bg-light-secondary text-center py-3 h-100"><div class="fs-4 fw-bold text-success">' + s.ok + '</div><small class="text-muted">Correctas</small></div></div>' +
                    '<div class="col-md-4"><div class="card bg-light-secondary text-center py-3 h-100"><div class="fs-4 fw-bold text-danger">' + s.broken + '</div><small class="text-muted">Con errores</small></div></div>'
                );

                var rows = res.results.map(function (item) {
                    var statusBadge = item.ok
                        ? '<span class="badge bg-success-subtle text-success">' + item.status + ' OK</span>'
                        : '<span class="badge bg-danger-subtle text-danger">' + (item.status || 'Error') + '</span>';

                    return '<tr>' +
                        '<td><code class="small text-break">' + item.url + '</code></td>' +
                        '<td>' + statusBadge + '</td>' +
                        '<td><span class="badge bg-light text-dark">' + item.source + '</span></td>' +
                        '</tr>';
                }).join('');

                $('#verify-urls-table').html(rows || '<tr><td colspan="3" class="text-center text-muted py-4">No hay URLs estáticas configuradas.</td></tr>');
                $('#verify-urls-result').show();
            },
            error: function () {
                toastr.error('Error al verificar las URLs.');
            },
            complete: function () {
                $('#verify-urls-loading').hide();
                $('#verify-urls-btn').html('Verificar').prop('disabled', false);
            },
        });
    });
})();
</script>
@endpush
