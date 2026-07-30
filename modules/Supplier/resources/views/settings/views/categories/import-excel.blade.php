@extends('layouts.theme')

@section('title', 'Importar excel')

@section('page_header')
    @include('core::components.card', ['title' => 'Importar excel'])
@endsection

@section('content')

    <div class="row">

        {{-- Formulario principal --}}
        <div class="col-lg-8">
            <div class="card">

                <div class="card-header">
                    <h5 class="mb-1">Importar excel</h5>
                    <p class="small mb-0 text-muted">
                        Sube el archivo Excel del ERP para importar la jerarquía de categorías.
                        El sistema procesará los datos según la
                        <a href="{{ route('settings.suppliers.categories.excel.config') }}">configuración activa</a>.
                    </p>
                </div>

                <div class="card-body">

                    {{-- Config activa --}}
                    <div class="border rounded p-3 mb-4" style="background:#f8f9fa;">
                        <p class="small mb-2 fw-semibold text-dark">Configuración activa</p>
                        <div class="row g-2 small text-muted">
                            <div class="col-6 col-md-3">
                                <span class="d-block text-dark fw-semibold">Nivel</span>
                                {{ ucfirst($config['import_level']) }}
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="d-block text-dark fw-semibold">Prefijo eliminado</span>
                                {{ $config['strip_prefix'] ?: '—' }}
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="d-block text-dark fw-semibold">Columna ID</span>
                                <code>{{ $config['col_' . $config['import_level'] . '_id'] ?? '—' }}</code>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="d-block text-dark fw-semibold">Columna nombre</span>
                                <code>{{ $config['col_' . $config['import_level'] . '_name'] ?? '—' }}</code>
                            </div>
                        </div>
                    </div>

                    {{-- Selector de archivo --}}
                    <div class="mb-4">
                        <label class="control-label col-form-label" for="import-file">Archivo Excel</label>
                        <input type="file" class="form-control" id="import-file" accept=".xlsx,.xls,.csv">
                        <small class="text-muted">Formatos aceptados: .xlsx, .xls, .csv — máximo 10 MB.</small>
                    </div>

                    {{-- Progreso --}}
                    <div id="import-progress" class="d-none mb-3">

                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted" id="progress-label">Preparando...</span>
                            <span class="small fw-semibold text-dark" id="progress-percent">0%</span>
                        </div>

                        <div class="progress rounded-pill" style="height:10px;">
                            <div id="progress-bar"
                                 class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 role="progressbar" style="width:0%; transition:width .3s ease;"></div>
                        </div>

                        <div class="border rounded mt-3 p-3" style="background:#f8f9fa;">
                            <div class="row g-0 text-center small">
                                <div class="col-4 border-end">
                                    <span class="d-block fw-bold fs-5 text-dark" id="cnt-total">—</span>
                                    <span class="text-muted">Total filas</span>
                                </div>
                                <div class="col-4 border-end">
                                    <span class="d-block fw-bold fs-5 text-success" id="cnt-imported">0</span>
                                    <span class="text-muted">Importados</span>
                                </div>
                                <div class="col-4">
                                    <span class="d-block fw-bold fs-5 text-warning" id="cnt-skipped">0</span>
                                    <span class="text-muted">Omitidos</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Resultado final --}}
                    <div id="import-result" class="d-none"></div>

                </div>

                <div class="card-footer">
                    <button type="button" id="import-btn" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                        Importar
                    </button>
                    <a href="{{ route('settings.suppliers.categories.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                        Volver a categorías
                    </a>
                </div>

            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Estado actual</h6>
                    <table class="table table-sm small mb-0">
                        <tbody class="text-muted">
                            <tr>
                                <td>Deportes</td>
                                <td class="text-end fw-semibold text-dark">{{ $stats['sports'] }}</td>
                            </tr>
                            <tr>
                                <td>Familias</td>
                                <td class="text-end fw-semibold text-dark">{{ $stats['familias'] }}</td>
                            </tr>
                            <tr>
                                <td>Subfamilias</td>
                                <td class="text-end fw-semibold text-dark">{{ $stats['subfamilias'] }}</td>
                            </tr>
                            <tr>
                                <td>Última importación</td>
                                <td class="text-end fw-semibold text-dark">
                                    {{ $stats['last_import'] ? \Carbon\Carbon::parse($stats['last_import'])->format('d/m/Y H:i') : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Cómo funciona</h6>
                    <ol class="small text-muted mb-0 ps-3">
                        <li class="mb-2">El sistema lee el archivo y localiza las columnas configuradas.</li>
                        <li class="mb-2">Cada fila se procesa como un registro del nivel activo (<strong>{{ ucfirst($config['import_level']) }}</strong>).</li>
                        <li class="mb-2">Si el registro ya existe (mismo ID ERP), se actualiza. Si no, se crea.</li>
                        <li>Los deportes y familias padre se sincronizan automáticamente.</li>
                    </ol>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Configuración</h6>
                    <p class="small text-muted mb-2">
                        Para cambiar el nivel de importación o los nombres de columna visita la página de configuración.
                    </p>
                    <a href="{{ route('settings.suppliers.categories.excel.config') }}" class="btn btn-secondary btn-sm w-100">
                        Configurar excel
                    </a>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {

    $('#import-btn').on('click', function () {
        const file = $('#import-file')[0].files[0];
        if (!file) {
            toastr.warning('Selecciona un archivo antes de importar.', '', { positionClass: 'toast-bottom-right' });
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).text('Importando...');
        $('#import-result').addClass('d-none').html('');
        $('#import-progress').removeClass('d-none');
        $('#progress-bar').css('width', '0%').addClass('progress-bar-animated');
        $('#progress-percent').text('0%');
        $('#progress-label').text('Preparando archivo...');
        $('#cnt-imported, #cnt-skipped, #cnt-total').text('0');

        // Subir el archivo vía fetch y luego leer el stream SSE
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

        fetch('{{ route("settings.suppliers.categories.excel.stream") }}', {
            method: 'POST',
            body: fd,
        }).then(response => {
            if (!response.ok) {
                return response.json().then(d => { throw new Error(d.message ?? 'Error al procesar el archivo.'); });
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            const processChunk = ({ done, value }) => {
                if (done) {
                    $btn.prop('disabled', false).text('Importar');
                    return;
                }

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n\n');
                buffer = lines.pop(); // último fragmento incompleto

                lines.forEach(block => {
                    const eventMatch = block.match(/^event: (\w+)/m);
                    const dataMatch  = block.match(/^data: (.+)/m);
                    if (!eventMatch || !dataMatch) return;

                    const event = eventMatch[1];
                    let data;
                    try { data = JSON.parse(dataMatch[1]); } catch { return; }

                    if (event === 'start') {
                        $('#cnt-total').text(data.total);
                        $('#progress-label').text('Importando ' + data.level + 's...');
                    }

                    if (event === 'progress') {
                        const pct = data.percent + '%';
                        $('#progress-bar').css('width', pct);
                        $('#progress-percent').text(pct);
                        $('#cnt-imported').text(data.imported);
                        $('#cnt-skipped').text(data.skipped);
                        $('#progress-label').text('Procesando fila ' + data.processed + ' de ' + data.total + '...');
                    }

                    if (event === 'done') {
                        $('#progress-bar').css('width', '100%').removeClass('progress-bar-animated');
                        $('#progress-percent').text('100%');
                        $('#progress-label').text('Completado');

                        let html = '<div class="border rounded p-3 mt-3" style="background:#f8f9fa;">';
                        html += '<p class="small fw-semibold text-dark mb-3">Resultado</p>';
                        html += '<div class="row g-2 text-center small mb-3">';
                        html += '<div class="col-4"><div class="border rounded p-2"><span class="d-block fw-bold fs-5 text-dark">' + data.imported + '</span>Importados</div></div>';
                        html += '<div class="col-4"><div class="border rounded p-2"><span class="d-block fw-bold fs-5 ' + (data.skipped > 0 ? 'text-warning' : 'text-dark') + '">' + data.skipped + '</span>Omitidos</div></div>';
                        html += '<div class="col-4"><div class="border rounded p-2"><span class="d-block fw-bold fs-5 ' + (data.errors?.length > 0 ? 'text-danger' : 'text-dark') + '">' + (data.errors?.length ?? 0) + '</span>Errores</div></div>';
                        html += '</div>';
                        html += '<p class="small text-muted mb-0">' + data.message + '</p>';
                        if (data.errors?.length) {
                            html += '<ul class="small text-danger mt-2 mb-0 ps-3">';
                            data.errors.forEach(e => { html += '<li>' + e + '</li>'; });
                            html += '</ul>';
                        }
                        html += '</div>';
                        $('#import-result').removeClass('d-none').html(html);
                        toastr.success(data.message, '', { positionClass: 'toast-bottom-right' });
                        $btn.prop('disabled', false).text('Importar');
                    }

                    if (event === 'error') {
                        $('#import-progress').addClass('d-none');
                        $('#import-result').removeClass('d-none').html(
                            '<div class="border rounded p-3" style="background:#fff5f5;"><p class="small text-danger mb-0">' + data.message + '</p></div>'
                        );
                        toastr.error(data.message, '', { positionClass: 'toast-bottom-right' });
                        $btn.prop('disabled', false).text('Importar');
                    }
                });

                return reader.read().then(processChunk);
            };

            return reader.read().then(processChunk);

        }).catch(err => {
            $('#import-progress').addClass('d-none');
            $('#import-result').removeClass('d-none').html(
                '<div class="border rounded p-3" style="background:#fff5f5;"><p class="small text-danger mb-0">' + err.message + '</p></div>'
            );
            toastr.error(err.message, '', { positionClass: 'toast-bottom-right' });
            $btn.prop('disabled', false).text('Importar');
        });
    });

});
</script>
@endpush
