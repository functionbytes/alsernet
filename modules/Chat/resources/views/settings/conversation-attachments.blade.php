@extends('layouts.theme')

@section('title', 'Adjuntos en conversaciones')

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card w-100">

            <form id="attachmentsForm" method="POST" action="{{ route('settings.chat.attachments.update') }}">
                @csrf
                @method('PUT')

                <!-- Header -->
                <div class="card-header p-4 border-bottom border-light">
                    <h5 class="mb-1 fw-bold">Configuración de adjuntos en conversaciones</h5>
                    <p class="small mb-0 text-muted">Define los formatos permitidos, límites de tamaño y disco de almacenamiento para los archivos adjuntos del chat</p>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        {{-- ── Enable / disable ─────────────────────────────────────── --}}
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="enabled" value="0">
                                <input type="checkbox" name="enabled" class="form-check-input" id="enabled" value="1"
                                       {{ $settings['enabled'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="enabled">
                                    <strong>Permitir adjuntos en conversaciones</strong>
                                    <small class="d-block text-muted mt-1">Cuando está desactivado, los agentes no podrán adjuntar archivos en el chat</small>
                                </label>
                            </div>
                        </div>

                        {{-- ── Limits ────────────────────────────────────────────────── --}}
                        <div class="col-12" id="attachmentsConfig">
                            <hr class="mt-0 mb-4">
                            <h5 class="mb-1">Límites</h5>
                            <p class="text-muted small mb-3">Restricciones de tamaño y cantidad por mensaje</p>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="max_file_size_mb" class="form-label fw-semibold">
                                        Tamaño máximo por archivo (MB) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" name="max_file_size_mb" id="max_file_size_mb"
                                           class="form-control @error('max_file_size_mb') is-invalid @enderror"
                                           value="{{ old('max_file_size_mb', $settings['max_file_size_mb']) }}"
                                           min="1" max="100" required>
                                    <small class="text-muted">Entre 1 y 100 MB</small>
                                    @error('max_file_size_mb')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="max_attachments" class="form-label fw-semibold">
                                        Número máximo de adjuntos por mensaje <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" name="max_attachments" id="max_attachments"
                                           class="form-control @error('max_attachments') is-invalid @enderror"
                                           value="{{ old('max_attachments', $settings['max_attachments']) }}"
                                           min="1" max="20" required>
                                    <small class="text-muted">Entre 1 y 20 archivos</small>
                                    @error('max_attachments')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- ── Allowed types ─────────────────────────────────────────── --}}
                        <div class="col-12">
                            <hr class="mt-0 mb-4">
                            <h5 class="mb-1">Formatos permitidos</h5>
                            <p class="text-muted small mb-3">Selecciona los tipos de archivo que los agentes pueden adjuntar</p>

                            @php
                                $typeGroups = [
                                    'Imágenes' => [
                                        'image/jpeg'    => 'JPEG (.jpg)',
                                        'image/png'     => 'PNG (.png)',
                                        'image/gif'     => 'GIF (.gif)',
                                        'image/webp'    => 'WebP (.webp)',
                                        'image/svg+xml' => 'SVG (.svg)',
                                    ],
                                    'Documentos' => [
                                        'application/pdf'                                                                 => 'PDF (.pdf)',
                                        'application/msword'                                                              => 'Word (.doc)',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'         => 'Word (.docx)',
                                        'application/vnd.ms-excel'                                                        => 'Excel (.xls)',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'               => 'Excel (.xlsx)',
                                        'text/plain'                                                                      => 'Texto plano (.txt)',
                                        'text/csv'                                                                        => 'CSV (.csv)',
                                    ],
                                    'Audio / Video' => [
                                        'audio/mpeg'  => 'MP3 (.mp3)',
                                        'audio/ogg'   => 'OGG Audio (.ogg)',
                                        'audio/webm'  => 'WebM Audio (.webm)',
                                        'video/mp4'   => 'MP4 (.mp4)',
                                        'video/webm'  => 'WebM Video (.webm)',
                                    ],
                                    'Archivos comprimidos' => [
                                        'application/zip'              => 'ZIP (.zip)',
                                        'application/x-rar-compressed' => 'RAR (.rar)',
                                        'application/x-7z-compressed'  => '7-Zip (.7z)',
                                    ],
                                ];
                                $selectedTypes = $settings['allowed_types'] ?? [];
                            @endphp

                            <div class="row g-3">
                                @foreach($typeGroups as $groupLabel => $types)
                                    <div class="col-12 col-md-6">
                                        <div class="border rounded-2 p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong class="small text-uppercase text-muted">{{ $groupLabel }}</strong>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-primary toggle-group"
                                                        data-group="{{ Str::slug($groupLabel) }}">
                                                    Seleccionar todos
                                                </button>
                                            </div>
                                            @foreach($types as $mimeType => $label)
                                                <div class="form-check mb-1" data-group-item="{{ Str::slug($groupLabel) }}">
                                                    <input type="checkbox" name="allowed_types[]" value="{{ $mimeType }}"
                                                           class="form-check-input mime-checkbox"
                                                           id="type_{{ md5($mimeType) }}"
                                                           {{ in_array($mimeType, $selectedTypes) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="type_{{ md5($mimeType) }}">
                                                        {{ $label }}
                                                        <code class="text-muted" style="font-size:.75em">{{ $mimeType }}</code>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ── Storage disk ──────────────────────────────────────────── --}}
                        <div class="col-12">
                            <hr class="mt-0 mb-4">
                            <h5 class="mb-1">Disco de almacenamiento</h5>
                            <p class="text-muted small mb-3">Selecciona dónde se guardarán los archivos adjuntos de las conversaciones</p>

                            <div class="row g-3">
                                <div class="col-lg-8">
                                    <label for="storage_disk" class="form-label fw-semibold">
                                        Disco predeterminado <span class="text-danger">*</span>
                                    </label>
                                    <select name="storage_disk" id="storage_disk"
                                            class="form-control select2 select2 @error('storage_disk') is-invalid @enderror" required>
                                        <option value="">Selecciona un disco</option>
                                        @foreach($disksMeta as $name => $disk)
                                            <option value="{{ $name }}"
                                                    {{ old('storage_disk', $settings['storage_disk']) === $name ? 'selected' : '' }}
                                                    data-driver="{{ $disk['driver'] }}"
                                                    data-root="{{ $disk['root'] }}"
                                                    data-url="{{ $disk['url'] }}"
                                                    data-description="{{ $disk['description'] }}">
                                                {{ $disk['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('storage_disk')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label fw-semibold">&nbsp;</label>
                                    <button type="button" class="btn btn-outline-primary w-100" id="testConnectionBtn">
                                        Probar conexión
                                    </button>
                                    <div id="testResult" class="mt-2" style="display:none;"></div>
                                </div>
                            </div>
                        </div>

                        {{-- ── Disk info ─────────────────────────────────────────────── --}}
                        <div class="col-12" id="diskInfoSection" style="display:none;">
                            <h6 class="mb-1 fw-bold">Información del disco seleccionado</h6>
                            <p class="text-muted small mb-3">Detalles de configuración del disco actual.</p>

                            <div class="border rounded p-3 bg-light">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <strong class="d-block text-muted small mb-1">Driver:</strong>
                                        <span id="info-driver" class="fw-semibold">-</span>
                                    </div>
                                    <div class="col-12">
                                        <strong class="d-block text-muted small mb-1">Ruta raíz:</strong>
                                        <span id="info-root" class="fw-semibold text-break">-</span>
                                    </div>
                                    <div class="col-12">
                                        <strong class="d-block text-muted small mb-1">URL:</strong>
                                        <span id="info-url" class="fw-semibold text-break">-</span>
                                    </div>
                                    <div class="col-12">
                                        <strong class="d-block text-muted small mb-1">Descripción:</strong>
                                        <p id="info-description" class="mb-0">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ── Storage stats ─────────────────────────────────────────── --}}
                        <div class="col-12" id="storageStatsSection" style="display:none;">
                            <h6 class="mb-1 fw-bold">Estadísticas de uso</h6>
                            <p class="text-muted small mb-3">Información de espacio disponible en el disco seleccionado.</p>

                            <div class="border rounded p-3">
                                <div id="storage-stats-content" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ── .env docs ─────────────────────────────────────────────── --}}
                        <div class="col-12">
                            <hr class="mt-0 mb-4">
                            <h6 class="mb-1 fw-bold">Configuración en .env</h6>
                            <p class="text-muted small mb-3">
                                Para agregar o modificar discos de almacenamiento, edita el archivo <code>config/filesystems.php</code> y las variables de entorno en <code>.env</code>
                            </p>
                            <div class="alert alert-info mb-0">
                                <strong><i class="fas fa-info-circle me-2"></i>Ejemplo de configuración de carpeta compartida en red:</strong>
                                <pre class="mb-0 mt-2 bg-white border rounded p-2"><code># En .env
NETWORK_SHARED_PATH=/mnt/red_compartida/documentos
NETWORK_SHARED_URL=${APP_URL}/network
# O en Windows:
NETWORK_SHARED_PATH=Z:/documentos
NETWORK_SHARED_URL=${APP_URL}/network</code></pre>
                            </div>
                        </div>

                        {{-- ── History ───────────────────────────────────────────────── --}}
                        <div class="col-12" id="historySection" style="display:none;">
                            <hr class="mt-0 mb-4">
                            <h6 class="mb-1 fw-bold">Historial de cambios</h6>
                            <p class="text-muted small mb-3">Últimos cambios en la configuración de almacenamiento.</p>
                            <div id="history-content" class="border rounded p-3">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>{{-- /row --}}
                </div>{{-- /card-body --}}

                <!-- Footer -->
                <div class="card-footer p-4 border-top d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary w-100" id="saveBtn" disabled>
                        Guardar cambios
                    </button>
                    <a href="{{ route('settings.chat.configurations.global') }}" class="btn btn-secondary w-100">
                        Volver
                    </a>
                </div>

            </form>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const form = $('#attachmentsForm');
    const saveBtn = $('#saveBtn');
    let originalFormData = form.serialize();

    // ── Dirty detection ──────────────────────────────────────────────────────
    function checkDirty() {
        saveBtn.prop('disabled', form.serialize() === originalFormData);
    }
    form.on('change input', 'input, select, textarea', checkDirty);

    // ── Enable toggle ────────────────────────────────────────────────────────
    $('#enabled').on('change', function () {
        $('#attachmentsConfig').toggle(this.checked);
        checkDirty();
    });
    if (!$('#enabled').is(':checked')) $('#attachmentsConfig').hide();

    // ── Select all per group ─────────────────────────────────────────────────
    $(document).on('click', '.toggle-group', function () {
        const group = $(this).data('group');
        const boxes = $('[data-group-item="' + group + '"] .mime-checkbox');
        const allChecked = boxes.filter(':checked').length === boxes.length;
        boxes.prop('checked', !allChecked);
        $(this).text(allChecked ? 'Seleccionar todos' : 'Deseleccionar todos');
        checkDirty();
    });

    // ── Disk select ──────────────────────────────────────────────────────────
    $('#storage_disk').on('change', function () {
        const $opt = $(this).find('option:selected');
        const disk = $(this).val();

        if (!disk) {
            $('#diskInfoSection, #storageStatsSection, #historySection').hide();
            return;
        }

        $('#diskInfoSection').show();
        $('#storageStatsSection').show();
        $('#historySection').show();

        $('#info-driver').text($opt.data('driver') || 'N/A');
        $('#info-root').text($opt.data('root') || 'N/A');
        $('#info-url').text($opt.data('url') || 'N/A');
        $('#info-description').text($opt.data('description') || 'N/A');

        loadStats(disk);
        loadHistory();
        checkDirty();
    });

    // ── Stats AJAX ───────────────────────────────────────────────────────────
    function loadStats(disk) {
        const $content = $('#storage-stats-content');
        $content.html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>');

        $.get('{{ route("settings.chat.attachments.disk-stats", ":disk") }}'.replace(':disk', disk))
            .done(function (data) {
                if (!data.success) { $content.html('<div class="alert alert-warning mb-0">No se pudieron cargar las estadísticas</div>'); return; }
                const s = data.stats;
                let progressClass = 'bg-success';
                if (s.percent > 80) progressClass = 'bg-danger';
                else if (s.percent > 60) progressClass = 'bg-warning';

                $content.html(
                    '<div class="row g-3">' +
                        '<div class="col-md-3 text-center"><strong class="d-block text-muted small mb-1">Espacio total</strong><span class="fw-bold h6">' + s.total + '</span></div>' +
                        '<div class="col-md-3 text-center"><strong class="d-block text-muted small mb-1">Espacio usado</strong><span class="fw-bold h6">' + s.used + '</span></div>' +
                        '<div class="col-md-3 text-center"><strong class="d-block text-muted small mb-1">Espacio libre</strong><span class="fw-bold h6">' + s.free + '</span></div>' +
                        '<div class="col-md-3 text-center"><strong class="d-block text-muted small mb-1">% Usado</strong><span class="fw-bold h6">' + (s.percent !== null ? s.percent + '%' : 'N/A') + '</span></div>' +
                    '</div>' +
                    (s.percent !== null
                        ? '<div class="mt-3"><div class="progress" style="height:25px"><div class="progress-bar ' + progressClass + '" role="progressbar" style="width:' + s.percent + '%" aria-valuenow="' + s.percent + '" aria-valuemin="0" aria-valuemax="100">' + s.percent + '%</div></div></div>'
                        : '')
                );
            })
            .fail(function () {
                $content.html('<div class="alert alert-warning mb-0">No se pudo cargar las estadísticas</div>');
            });
    }

    // ── History AJAX ─────────────────────────────────────────────────────────
    function loadHistory() {
        const $content = $('#history-content');
        $content.html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>');

        $.get('{{ route("settings.chat.attachments.history") }}')
            .done(function (data) {
                if (!data.success || !data.history.length) {
                    $content.html('<p class="text-muted text-center mb-0">No hay cambios registrados</p>');
                    return;
                }
                const colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning'];
                let html = '';
                $.each(data.history, function (i, r) {
                    const initials = r.user.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                    const border = i < data.history.length - 1 ? ' border-bottom' : '';
                    html +=
                        '<div class="d-flex flex-row comment-row' + border + ' p-3 gap-3">' +
                            '<div><span class="rounded-circle ' + colors[i % 4] + ' text-white d-flex align-items-center justify-content-center" style="width:50px;height:50px;font-weight:600">' + initials + '</span></div>' +
                            '<div class="comment-text w-100">' +
                                '<h6 class="fw-medium mb-1">' + r.user + '</h6>' +
                                '<p class="mb-1 fs-2 text-muted">Cambió el disco de almacenamiento a <strong>' + (r.new_disk || 'N/A') + '</strong>' + (r.old_disk ? ' (anterior: ' + r.old_disk + ')' : '') + '</p>' +
                                '<div class="comment-footer mt-2 d-flex align-items-center justify-content-between">' +
                                    '<span class="badge bg-success-subtle text-success">Actualizada</span>' +
                                    '<span class="text-muted fw-normal fs-2">' + r.created_at_relative + '</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                });
                $content.html(html);
            })
            .fail(function () {
                $content.html('<p class="text-muted text-center mb-0">No se pudo cargar el historial</p>');
            });
    }

    // ── Test connection ──────────────────────────────────────────────────────
    $('#testConnectionBtn').on('click', function () {
        const disk = $('#storage_disk').val();
        if (!disk) { alert('Por favor selecciona un disco primero'); return; }

        const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Probando...');

        $.ajax({
            url: '{{ route("settings.documents.configurations.storage.test") }}',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({ disk_name: disk }),
        })
        .done(function (data) {
            const cls = data.success ? 'alert-success' : 'alert-danger';
            const icon = data.success ? 'fa-check-circle' : 'fa-exclamation-circle';
            $('#testResult').show().html(
                '<div class="alert ' + cls + ' mb-0"><i class="fas ' + icon + ' me-2"></i><strong>' + (data.success ? 'Conexión exitosa' : 'Error en la conexión') + '</strong><br>' + data.message + '<small class="d-block mt-1">Tiempo de respuesta: ' + data.response_time + 'ms</small></div>'
            );
        })
        .fail(function (xhr) {
            $('#testResult').show().html('<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle me-2"></i>Error: ' + xhr.statusText + '</div>');
        })
        .always(function () {
            $btn.prop('disabled', false).html('Probar conexión');
        });
    });

    // ── Init: trigger disk change if already selected ────────────────────────
    if ($('#storage_disk').val()) {
        $('#storage_disk').trigger('change');
    }

    // ── Submit state ─────────────────────────────────────────────────────────
    form.on('submit', function () {
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');
    });

    // ── Toastr ───────────────────────────────────────────────────────────────
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Configuración actualizada');
        setTimeout(function () { originalFormData = form.serialize(); checkDirty(); }, 100);
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

@push('css')
<style>
    .comment-row { transition: background-color .2s ease; }
    .comment-row:hover { background-color: #f8f9fa; }
</style>
@endpush
