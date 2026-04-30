@extends('layouts.theme')

@section('title', 'Auditoría SEO')

@section('page_header')
    @include('core::components.card', ['title' => 'Auditoría SEO'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">

                <div class="card">
                    <div class="card-body">

                        {{-- Auditar URL específica --}}
                        <h6 class="fw-bold text-dark mb-1">Auditar URL específica</h6>
                        <p class="text-muted mb-3">Analiza cualquier URL pública y obtén su score SEO con detalle de problemas.</p>

                        <div class="input-group mb-3">
                            <input type="url" id="auditUrlInput" class="form-control"
                                   placeholder="https://ejemplo.com/mi-pagina"
                                   value="{{ request()->getSchemeAndHttpHost() }}">
                            <button class="btn btn-outline-primary" id="auditUrlBtn">
                                Auditar
                            </button>
                        </div>
                        <div id="url-audit-result"></div>

                    </div>

                    <hr class="my-0">

                    {{-- Core Web Vitals --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Core Web Vitals</h6>
                        <p class="text-muted mb-3">Analiza rendimiento, LCP, CLS y FCP vía PageSpeed Insights API.</p>

                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <input type="url" id="cwv-url" class="form-control" placeholder="https://tusitio.com/pagina">
                            </div>
                            <div class="col-md-3">
                                <select id="cwv-strategy" class="form-select">
                                    <option value="mobile">Móvil</option>
                                    <option value="desktop">Escritorio</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100" id="btn-cwv">
                                    Analizar
                                </button>
                            </div>
                        </div>
                        <div id="cwv-results" class="d-none">
                            <div class="row g-3" id="cwv-scores"></div>
                            <div class="row g-3 mt-2" id="cwv-metrics"></div>
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Auditoría masiva con progreso --}}
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Auditoría masiva del sitio</h6>
                                <p class="text-muted mb-0">Audita automáticamente todas las páginas y actualiza sus puntuaciones SEO.</p>
                            </div>
                            <button id="btn-bulk-audit" class="btn btn-outline-primary btn-sm flex-shrink-0 ms-3">
                                Iniciar
                            </button>
                        </div>
                        <div id="bulk-audit-progress" class="d-none">
                            <div class="progress mb-2" style="height: 8px;">
                                <div id="audit-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="audit-progress-text" class="text-muted"></small>
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Verificador de links rotos --}}
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Verificador de links rotos</h6>
                                <p class="text-muted mb-0">Verifica que todas las URLs canónicas devuelven respuestas correctas.</p>
                            </div>
                            <button id="btn-check-broken-links" class="btn btn-outline-primary btn-sm flex-shrink-0 ms-3">
                                Verificar
                            </button>
                        </div>
                        <div id="broken-links-progress" class="d-none">
                            <div class="progress mb-2" style="height: 8px;">
                                <div id="broken-links-bar" class="progress-bar bg-warning progress-bar-striped progress-bar-animated"
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="broken-links-text" class="text-muted"></small>
                        </div>
                        <div id="broken-links-results" class="mt-3 d-none"></div>
                    </div>
                </div>

                {{-- Resultados de auditoría masiva --}}
                <div id="bulk-audit-section" class="mt-4">
                    <div class="row mb-3 g-3" id="bulk-summary"></div>
                    <div class="card">
                        <div class="card-body p-0">
                            <div id="bulk-audit-results"></div>
                        </div>
                    </div>
                </div>

                {{-- Resultados de canónicas --}}
                <div id="canonicals-result" class="mt-4 d-none"></div>

            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Auditar todo el sitio</h6>
                        <p class="text-muted mb-3">Revisa los scores SEO de todas las páginas con metadatos registrados.</p>
                        <button class="btn btn-primary w-100" id="auditAllBtn">
                            Auditar todo el sitio
                        </button>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Verificar canónicas</h6>
                        <p class="text-muted mb-3">Comprueba que las URLs canónicas respondan correctamente (HEAD requests).</p>
                        <button class="btn btn-outline-secondary w-100" id="checkCanonicalsBtn">
                            Verificar canónicas
                        </button>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Historial de auditorías</h6>
                        <p class="text-muted mb-3">Consulta el historial de auditorías realizadas y su evolución.</p>
                        <a href="{{ route('settings.seo.audit.history') }}" class="btn btn-outline-secondary w-100">
                            Ver historial
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Dashboard SEO</h6>
                        <p class="text-muted mb-3">Vuelve al panel principal de SEO.</p>
                        <a href="{{ route('settings.seo.dashboard') }}" class="btn btn-outline-secondary w-100">
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
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function scoreColor(score) {
        if (score >= 90) return '#13C672';
        if (score >= 75) return '#b10100';
        if (score >= 60) return '#FEC90F';
        if (score >= 40) return '#FA896B';
        return '#dc3545';
    }

    function gradeBadge(grade, score) {
        const color = scoreColor(score);
        return `<span class="badge rounded-pill fw-bold px-3 py-2" style="background:${color};font-size:1rem;">${grade} ${score}</span>`;
    }

    function issueIcon(status) {
        if (status === 'error') return '<i class="fas fa-times-circle text-danger me-2"></i>';
        if (status === 'warning') return '<i class="fas fa-exclamation-triangle text-warning me-2"></i>';
        return '<i class="fas fa-check-circle text-success me-2"></i>';
    }

    function renderAuditResult(data) {
        const issuesList = data.issues.map(i => `
            <div class="d-flex align-items-start py-2 border-bottom">
                <div class="flex-shrink-0 me-2 mt-1">${issueIcon(i.status)}</div>
                <div>
                    <div class="fw-semibold small">${i.message}</div>
                    ${i.recommendation ? `<div class="text-muted small">${i.recommendation}</div>` : ''}
                </div>
                ${i.weight > 0 ? `<span class="ms-auto badge bg-danger-subtle text-danger small">-${i.weight}pts</span>` : ''}
            </div>`).join('');

        const passedList = data.passed.map(p => `
            <div class="d-flex align-items-center py-1">
                <i class="fas fa-check-circle text-success me-2 small"></i>
                <span class="small">${p.message}</span>
            </div>`).join('');

        return `
            <div class="row g-3 mt-2">
                <div class="col-md-3 text-center">
                    <div class="py-3">
                        <div style="font-size:3rem;font-weight:900;color:${scoreColor(data.score)};">${data.score}</div>
                        <div style="font-size:1.2rem;font-weight:bold;color:${scoreColor(data.score)};">Grado ${data.grade}</div>
                        <small class="text-muted">${data.issues.length} problema(s)</small>
                    </div>
                </div>
                <div class="col-md-9">
                    ${data.issues.length > 0
                        ? `<h6 class="text-danger mb-2">Problemas (${data.issues.length})</h6>${issuesList}`
                        : '<div class="alert alert-success border-0 py-2">Sin problemas detectados</div>'}
                    ${data.passed.length > 0 ? `<div class="mt-3"><h6 class="text-success mb-2">OK (${data.passed.length})</h6>${passedList}</div>` : ''}
                </div>
            </div>`;
    }

    // Broken Links Checker
    (function () {
        var pollInterval = null;

        function stopPolling() {
            if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
        }

        function renderBrokenLinksResults(broken) {
            if (!broken || broken.length === 0) {
                return '<div class="alert alert-success border-0 small">No se encontraron links rotos.</div>';
            }

            var rows = broken.map(function (item) {
                var statusBadge = item.status > 0
                    ? '<span class="badge bg-danger-subtle text-danger">' + item.status + '</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary">Error</span>';
                return '<tr><td class="small">' + (item.title || '(sin título)') + '</td>'
                    + '<td class="small text-muted text-truncate" style="max-width:250px">' + item.url + '</td>'
                    + '<td class="text-center">' + statusBadge + '</td></tr>';
            }).join('');

            return '<div class="table-responsive"><table class="table table-sm table-hover mb-0">'
                + '<thead class="table-light"><tr><th>Título</th><th>URL canónica</th><th class="text-center">Estado</th></tr></thead>'
                + '<tbody>' + rows + '</tbody></table></div>';
        }

        function pollProgress() {
            $.get('{{ route("settings.seo.audit.broken-links-progress") }}', function (data) {
                var checked = data.checked || 0;
                var total = data.total || 0;
                var pct = total > 0 ? Math.round(checked / total * 100) : 0;

                $('#broken-links-bar').css('width', pct + '%');

                if (data.status === 'running') {
                    $('#broken-links-text').text('Verificando... ' + checked + ' de ' + total + ' URLs (' + pct + '%)');
                } else if (data.status === 'completed') {
                    stopPolling();
                    $('#broken-links-bar').removeClass('progress-bar-animated progress-bar-striped bg-warning').addClass('bg-success');
                    $('#broken-links-text').text('Completado: ' + checked + ' de ' + total + ' URLs.');
                    $('#btn-check-broken-links').prop('disabled', false).text('Verificar');
                    $('#broken-links-results').removeClass('d-none').html(renderBrokenLinksResults(data.broken));
                    toastr.success('Verificación de links completada.');
                }
            });
        }

        $('#btn-check-broken-links').on('click', function () {
            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            $('#broken-links-progress').removeClass('d-none');
            $('#broken-links-bar').css('width', '0%').removeClass('bg-success bg-danger').addClass('bg-warning progress-bar-animated progress-bar-striped');
            $('#broken-links-text').text('Iniciando...');
            $('#broken-links-results').addClass('d-none').html('');

            $.ajax({
                url: '{{ route("settings.seo.audit.broken-links-start") }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function () {
                    stopPolling();
                    pollInterval = setInterval(pollProgress, 3000);
                    pollProgress();
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.error || 'No se pudo iniciar la verificación.');
                    $('#btn-check-broken-links').prop('disabled', false).text('Verificar');
                    $('#broken-links-progress').addClass('d-none');
                },
            });
        });
    })();

    // Bulk Audit with Progress
    (function () {
        var pollInterval = null;

        function stopPolling() {
            if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
        }

        function pollProgress() {
            $.get('{{ route("settings.seo.audit.bulk-progress") }}', function (data) {
                var processed = data.processed || 0;
                var total = data.total || 0;
                var pct = total > 0 ? Math.round(processed / total * 100) : 0;

                $('#audit-progress-bar').css('width', pct + '%');

                if (data.status === 'running') {
                    $('#audit-progress-text').text('Auditando... ' + processed + ' de ' + total + ' páginas (' + pct + '%)');
                } else if (data.status === 'completed') {
                    stopPolling();
                    $('#audit-progress-bar').removeClass('progress-bar-animated progress-bar-striped').addClass('bg-success');
                    $('#audit-progress-text').text('Completado: ' + processed + ' de ' + total + ' páginas.');
                    $('#btn-bulk-audit').prop('disabled', false).text('Iniciar');
                    toastr.success('Auditoría masiva completada.');
                } else if (data.status === 'failed') {
                    stopPolling();
                    $('#audit-progress-bar').removeClass('progress-bar-animated progress-bar-striped').addClass('bg-danger');
                    $('#audit-progress-text').text('Error: ' + (data.error || 'Error desconocido'));
                    $('#btn-bulk-audit').prop('disabled', false).text('Iniciar');
                    toastr.error('La auditoría masiva falló.');
                }
            });
        }

        $('#btn-bulk-audit').on('click', function () {
            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            $('#bulk-audit-progress').removeClass('d-none');
            $('#audit-progress-bar').css('width', '0%').removeClass('bg-success bg-danger').addClass('progress-bar-animated progress-bar-striped');
            $('#audit-progress-text').text('Iniciando...');

            $.ajax({
                url: '{{ route("settings.seo.audit.bulk-start") }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function () {
                    stopPolling();
                    pollInterval = setInterval(pollProgress, 3000);
                    pollProgress();
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.error || 'No se pudo iniciar la auditoría.');
                    $('#btn-bulk-audit').prop('disabled', false).text('Iniciar');
                    $('#bulk-audit-progress').addClass('d-none');
                }
            });
        });
    })();

    // URL Audit
    $('#auditUrlBtn').on('click', function () {
        const url = $('#auditUrlInput').val().trim();
        if (!url) return;

        $(this).html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
        $('#url-audit-result').html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div><p class="mt-2 text-muted small">Analizando...</p></div>');

        $.ajax({
            url: '{{ route("settings.seo.audit.url") }}',
            method: 'POST',
            data: { url, _token: csrfToken },
            success: (res) => {
                if (res.status) {
                    $('#url-audit-result').html(renderAuditResult(res.data));
                } else {
                    $('#url-audit-result').html(`<div class="alert alert-danger border-0 small">${res.message}</div>`);
                }
            },
            error: () => $('#url-audit-result').html('<div class="alert alert-danger border-0 small">Error al auditar la URL.</div>'),
            complete: () => $('#auditUrlBtn').html('Auditar').prop('disabled', false),
        });
    });

    // Audit All
    $('#auditAllBtn').on('click', function () {
        $(this).html('<span class="spinner-border spinner-border-sm me-1"></span>Auditando...').prop('disabled', true);
        $('#bulk-audit-section').show();
        $('#bulk-summary').html('<div class="col-12 text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>');
        $('#bulk-audit-results').html('');

        $.get('{{ route("settings.seo.audit.all") }}', (res) => {
            if (!res.status) return;
            const s = res.summary;

            $('#bulk-summary').html(`
                <div class="col-6 col-md-3"><div class="card bg-light-secondary h-100"><div class="card-body text-center"><h4 class="fw-bold mb-1">${s.total}</h4><small class="text-muted">Analizadas</small></div></div></div>
                <div class="col-6 col-md-3"><div class="card bg-light-secondary h-100"><div class="card-body text-center"><h4 class="fw-bold mb-1" style="color:${scoreColor(s.avg_score)}">${s.avg_score}</h4><small class="text-muted">Score promedio</small></div></div></div>
                <div class="col-6 col-md-3"><div class="card bg-light-secondary h-100"><div class="card-body text-center"><h4 class="fw-bold mb-1 text-danger">${s.with_issues}</h4><small class="text-muted">Con problemas</small></div></div></div>
                <div class="col-6 col-md-3"><div class="card bg-light-secondary h-100"><div class="card-body text-center"><h4 class="fw-bold mb-1 text-success">${s.score_a}</h4><small class="text-muted">Grado A</small></div></div></div>
            `);

            const rows = res.data.map(item => `
                <div class="p-3 border-bottom d-flex align-items-center gap-3">
                    <div style="width:50px;text-align:center;">${gradeBadge(item.grade, item.score)}</div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">${item.title}</div>
                        <small class="text-muted badge bg-light text-dark">${item.type}</small>
                    </div>
                    <div class="text-end">
                        ${item.issues_count > 0
                            ? `<span class="badge bg-warning-subtle text-warning">${item.issues_count} problema(s)</span>`
                            : '<span class="badge bg-success-subtle text-success">OK</span>'}
                    </div>
                </div>`).join('');

            $('#bulk-audit-results').html(rows || '<p class="text-muted text-center py-4">No hay registros SEO.</p>');
        }).always(() => $('#auditAllBtn').html('Auditar todo el sitio').prop('disabled', false));
    });

    // Core Web Vitals
    $('#btn-cwv').on('click', function () {
        var url = $('#cwv-url').val();
        if (!url) { toastr.warning('Ingresa una URL'); return; }

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '{{ route("settings.seo.audit.core-web-vitals") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { url: url, strategy: $('#cwv-strategy').val() },
            success: function (data) {
                var scores = [
                    ['Rendimiento', data.performance_score],
                    ['SEO', data.seo_score],
                    ['Accesibilidad', data.accessibility_score],
                    ['Buenas prácticas', data.best_practices_score]
                ];

                var scoresHtml = scores.map(function (item) {
                    var c = item[1] >= 90 ? '#13C672' : (item[1] >= 50 ? '#FEC90F' : '#FA896B');
                    return '<div class="col-md-3"><div class="text-center p-3 border rounded">'
                        + '<div style="font-size:1.8rem;font-weight:700;color:' + c + '">' + item[1] + '</div>'
                        + '<div class="small text-muted">' + item[0] + '</div></div></div>';
                }).join('');

                var metricsHtml = '<div class="col-12"><div class="table-responsive">'
                    + '<table class="table table-sm mb-0">'
                    + '<tr><td><strong>LCP</strong></td><td>' + data.lcp + '</td></tr>'
                    + '<tr><td><strong>TBT</strong></td><td>' + data.fid + '</td></tr>'
                    + '<tr><td><strong>CLS</strong></td><td>' + data.cls + '</td></tr>'
                    + '<tr><td><strong>FCP</strong></td><td>' + data.fcp + '</td></tr>'
                    + '<tr><td><strong>TTFB</strong></td><td>' + data.ttfb + '</td></tr>'
                    + '</table></div></div>';

                $('#cwv-scores').html(scoresHtml);
                $('#cwv-metrics').html(metricsHtml);
                $('#cwv-results').removeClass('d-none');
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.error || 'Error al consultar PageSpeed API.');
            },
            complete: function () {
                $('#btn-cwv').prop('disabled', false).text('Analizar');
            }
        });
    });

    // Check Canonicals
    $('#checkCanonicalsBtn').on('click', function () {
        $(this).html('<span class="spinner-border spinner-border-sm me-1"></span>Verificando...').prop('disabled', true);
        $('#canonicals-result').removeClass('d-none').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>');

        $.get('{{ route("settings.seo.audit.check-canonicals") }}', function (res) {
            if (!res.status) return;
            const s = res.summary;
            let html = `<div class="row g-3 mb-3">
                <div class="col-4"><div class="card bg-light-secondary text-center py-3"><div class="fw-bold">${s.total}</div><small class="text-muted">Total</small></div></div>
                <div class="col-4"><div class="card bg-light-secondary text-center py-3"><div class="fw-bold text-success">${s.ok}</div><small class="text-muted">OK</small></div></div>
                <div class="col-4"><div class="card bg-light-secondary text-center py-3"><div class="fw-bold text-danger">${s.broken}</div><small class="text-muted">Rotas</small></div></div>
            </div>`;

            if (res.results.length === 0) {
                html += '<div class="alert alert-info border-0 small">No hay URLs canónicas definidas.</div>';
            } else {
                html += '<div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Título</th><th>URL canónica</th><th class="text-center">Estado</th></tr></thead><tbody>';
                res.results.forEach(r => {
                    const badge = r.ok
                        ? `<span class="badge bg-success-subtle text-success">${r.status}</span>`
                        : `<span class="badge bg-danger-subtle text-danger">${r.status || 'Error'}</span>`;
                    html += `<tr><td class="small">${r.title}</td><td class="small text-muted text-truncate" style="max-width:200px">${r.canonical_url}</td><td class="text-center">${badge}</td></tr>`;
                });
                html += '</tbody></table></div>';
            }

            $('#canonicals-result').html(html);
        }).always(() => $('#checkCanonicalsBtn').html('Verificar canónicas').prop('disabled', false));
    });
})();
</script>
@endpush
