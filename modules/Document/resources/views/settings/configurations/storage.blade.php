@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Almacenamiento'])

    <!-- Mensajes de estado -->
    @if ($message = session('success'))
        <div class="alert bg-light-secondary text-black alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> Por favor, corrige los siguientes errores:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('settings.documents.configurations.storage.update') }}" method="POST" class="needs-validation" novalidate>
        @csrf

        <!-- Campo oculto para razón de cambio -->
        <input type="hidden" id="change_reason" name="reason" value=""
>

        <div class="card">
            <div class="card-body">

                <!-- Selección de disco de almacenamiento -->
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">
                        Disco de almacenamiento para documentos
                    </h6>
                    <p class="text-muted small mb-3">
                        Selecciona el disco donde se guardarán los documentos subidos por los clientes. Los discos se configuran en el archivo .env del sistema.
                    </p>

                    <div class="row g-3">
                        <div class="col-lg-8">
                            <label class="form-label fw-bold">Disco predeterminado</label>
                            <select class="form-select select2" id="default_storage_disk" name="default_storage_disk" required>
                                <option value="">Selecciona un disco</option>
                                @foreach($storageSettings['available_disks'] as $diskName => $disk)
                                    <option value="{{ $diskName }}"
                                            {{ $storageSettings['current_disk'] === $diskName ? 'selected' : '' }}
                                            data-driver="{{ $disk['driver'] }}"
                                            data-root="{{ $disk['root'] }}"
                                            data-url="{{ $disk['url'] ?? 'N/A' }}">
                                        {{ ucfirst($diskName) }} ({{ $disk['driver'] }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-2">
                                Los archivos se organizarán en: /documentos/{numero_orden}/{uid_documento}/
                            </small>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label fw-bold">&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary w-100" id="test-connection-btn">
                                Probar
                            </button>
                            <div id="test-result" class="mt-2" style="display: none;"></div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Información del disco seleccionado -->
                <div class="mb-4" id="diskInfo" style="display: none;">
                    <h6 class="mb-1 fw-bold text-dark">
                        Información del disco seleccionado
                    </h6>
                    <p class="text-muted small mb-3">
                        Detalles de configuración del disco actual.
                    </p>

                    <div class="border rounded p-3 bg-light">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <strong class="d-block text-muted small mb-1">Driver:</strong>
                                <span id="info-driver" class="fw-semibold">-</span>
                            </div>
                            <div class="col-md-12">
                                <strong class="d-block text-muted small mb-1">Ruta raíz:</strong>
                                <span id="info-root" class="fw-semibold text-break">-</span>
                            </div>
                            <div class="col-md-12">
                                <strong class="d-block text-muted small mb-1">URL:</strong>
                                <span id="info-url" class="fw-semibold text-break">-</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <strong class="d-block text-muted small mb-1">Descripción:</strong>
                            <p id="info-description" class="mb-0">-</p>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Estadísticas de Almacenamiento -->
                <div class="mb-4" id="storageStatsSection" style="display: none;">
                    <h6 class="mb-1 fw-bold text-dark">
                       Estadísticas de uso
                    </h6>
                    <p class="text-muted small mb-3">
                        Información de espacio disponible en el disco seleccionado.
                    </p>

                    <div class="border rounded p-3">
                        <div id="storage-stats-content" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Configuración en .env -->
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">
                        Configuración en .env
                    </h6>
                    <p class="text-muted small mb-3">
                        Para agregar o modificar discos de almacenamiento, edita el archivo <code>config/filesystems.php</code> y las variables de entorno en <code>.env</code>
                    </p>

                    <div class="alert alert-info mb-0" role="alert">
                        <strong><i class="fas fa-info-circle me-2"></i>Ejemplo de configuración de carpeta compartida en red:</strong>
                        <pre class="mb-0 mt-2 bg-white border rounded p-2"><code># En .env
NETWORK_SHARED_PATH=/mnt/red_compartida/documentos
NETWORK_SHARED_URL=${APP_URL}/network
# O en Windows:
NETWORK_SHARED_PATH=Z:/documentos
NETWORK_SHARED_URL=${APP_URL}/network</code>
                        </pre>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Historial de cambios -->
                <div class="mb-4" id="historySection" style="display: none;">
                    <h6 class="mb-1 fw-bold text-dark">
                        Historial de cambios
                    </h6>
                    <p class="text-muted small mb-3">
                        Últimos cambios en la configuración de almacenamiento.
                    </p>

                    <div id="history-content" class="border rounded p-3">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón de guardar -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary w-100">
                        Guardar
                    </button>
                </div>

            </div>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Actualizar información, estadísticas e historial al cambiar disco
            $('#default_storage_disk').on('change', function() {
                const $selectedOption = $(this).find('option:selected');
                const diskName = $(this).val();

                if (diskName) {
                    $('#diskInfo').show();
                    $('#storageStatsSection').show();
                    $('#historySection').show();

                    $('#info-driver').text($selectedOption.data('driver') || 'N/A');
                    $('#info-root').text($selectedOption.data('root') || 'N/A');
                    $('#info-url').text($selectedOption.data('url') || 'N/A');

                    // Buscar la descripción en el array original
                    @foreach($storageSettings['available_disks'] as $diskName => $disk)
                        if (diskName === '{{ $diskName }}') {
                            $('#info-description').text('{{ $disk['description'] }}');
                        }
                    @endforeach

                    // Cargar estadísticas
                    loadStorageStats(diskName);
                    // Cargar historial
                    loadStorageHistory();
                } else {
                    $('#diskInfo').hide();
                    $('#storageStatsSection').hide();
                    $('#historySection').hide();
                }
            });

            // Evento para probar conexión
            $('#test-connection-btn').on('click', function(e) {
                e.preventDefault();
                const diskName = $('#default_storage_disk').val();

                if (!diskName) {
                    alert('Por favor selecciona un disco primero');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Probando...');

                $.ajax({
                    url: '{{ route("settings.documents.configurations.storage.test") }}',
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: JSON.stringify({ disk_name: diskName }),
                    success: function(data) {
                        $('#test-result').show();

                        if (data.success) {
                            $('#test-result').html(`
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Conexión exitosa</strong><br>
                                    ${data.message}
                                    <small class="d-block mt-1">Tiempo de respuesta: ${data.response_time}ms</small>
                                </div>
                            `);
                        } else {
                            $('#test-result').html(`
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Error en la conexión</strong><br>
                                    ${data.message}
                                    <small class="d-block mt-1">Tiempo de respuesta: ${data.response_time}ms</small>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr) {
                        $('#test-result').show().html(`
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Error: ${xhr.statusText}
                            </div>
                        `);
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html('Probar');
                    }
                });
            });

            // Función para cargar estadísticas
            function loadStorageStats(diskName) {
                const $statsContent = $('#storage-stats-content');

                $.ajax({
                    url: '{{ route("settings.documents.configurations.storage.stats", ":disk") }}'.replace(':disk', diskName),
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            const stats = data.stats;
                            let progressClass = 'bg-success';
                            if (stats.percent > 80) progressClass = 'bg-primary';
                            else if (stats.percent > 60) progressClass = 'bg-warning';

                            const html = `
                                <div class="row g-3">
                                    <div class="col-md-3 text-center">
                                        <strong class="d-block text-muted small mb-1">Espacio total</strong>
                                        <span class="fw-bold h6">${stats.total}</span>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <strong class="d-block text-muted small mb-1">Espacio usado</strong>
                                        <span class="fw-bold h6">${stats.used}</span>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <strong class="d-block text-muted small mb-1">Espacio libre</strong>
                                        <span class="fw-bold h6">${stats.free}</span>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <strong class="d-block text-muted small mb-1">% Usado</strong>
                                        <span class="fw-bold h6">${stats.percent || 'N/A'}%</span>
                                    </div>
                                </div>
                                ${stats.percent !== null ? `
                                    <div class="mt-3">
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar ${progressClass}" role="progressbar"
                                                 style="width: ${stats.percent}%" aria-valuenow="${stats.percent}" aria-valuemin="0" aria-valuemax="100">
                                                ${stats.percent}%
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                            `;
                            $statsContent.html(html);
                        }
                    },
                    error: function() {
                        $statsContent.html('<div class="alert alert-warning mb-0">No se pudo cargar las estadísticas</div>');
                    }
                });
            }

            // Función para cargar historial
            function loadStorageHistory() {
                const $historyContent = $('#history-content');

                $.ajax({
                    url: '{{ route("settings.documents.configurations.storage.history") }}',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.history.length > 0) {
                            let html = '';
                            $.each(data.history, function(index, record) {
                                // Determinar badge según acción
                                let badgeClass = 'bg-info-subtle text-info';
                                let badgeText = record.action_label;

                                if (record.action_label === 'Actualizada') {
                                    badgeClass = 'bg-success-subtle text-success';
                                } else if (record.action_label === 'Eliminada') {
                                    badgeClass = 'bg-danger-subtle text-danger';
                                }

                                // Obtener iniciales del usuario para avatar placeholder
                                const userName = record.user || 'Sistema';
                                const initials = userName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                                const avatarColor = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning'][index % 4];

                                html += `
                                    <div class="d-flex flex-row comment-row ${index < data.history.length - 1 ? 'border-bottom' : ''} p-3 gap-3">
                                        <div>
                                            <span class="rounded-circle ${avatarColor} text-white d-flex align-items-center justify-content-center"
                                                  style="width: 50px; height: 50px; font-weight: 600;">
                                                ${initials}
                                            </span>
                                        </div>
                                        <div class="comment-text w-100">
                                            <h6 class="fw-medium mb-1">${userName}</h6>
                                            <p class="mb-1 fs-2 text-muted">
                                                Cambió el disco de almacenamiento a <strong>${record.new_disk}</strong>
                                                ${record.old_disk ? ` (anterior: ${record.old_disk})` : ''}
                                            </p>
                                            ${record.reason ? `<p class="mb-1 fs-2 text-muted fst-italic">Razón: ${record.reason}</p>` : ''}
                                            <div class="comment-footer mt-2">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="badge ${badgeClass}">${badgeText}</span>
                                                    <span class="text-muted fw-normal fs-2">${record.created_at_relative}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            $historyContent.html(html);
                        } else {
                            $historyContent.html('<p class="text-muted text-center mb-0">No hay cambios registrados</p>');
                        }
                    },
                    error: function() {
                        $historyContent.html('<p class="text-muted text-center mb-0">No se pudo cargar el historial</p>');
                    }
                });
            }

            // Cargar información del disco actual al cargar
            if ($('#default_storage_disk').val()) {
                $('#default_storage_disk').trigger('change');
            }
        });
    </script>
@endpush

@push('css')
    <style>
        .comment-row {
            transition: background-color 0.2s ease;
        }

        .comment-row:hover {
            background-color: #f8f9fa;
        }
    </style>
@endpush
