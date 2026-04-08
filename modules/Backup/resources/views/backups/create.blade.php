@extends('layouts.theme')

@section('title', 'Crear copia de seguridad')

@section('content')

    @include('core::components.card', ['title' => 'Crear copia de seguridad'])

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Columna izquierda: formulario --}}
        <div class="col-lg-8">
            <form method="POST" action="{{ route('settings.backups.store') }}" id="backupForm">
                @csrf

                <div class="card">

                    <div class="card-header p-4 border-bottom">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1 fw-bold">Componentes a respaldar</h5>
                                <p class="small mb-0 text-muted">Selecciona qué elementos deseas incluir en el backup</p>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" id="selectAllBtn" class="btn btn-outline-secondary">Todos</button>
                                <button type="button" id="deselectAllBtn" class="btn btn-outline-secondary">Ninguno</button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        @php
                            $items = [
                                ['id' => 'backupAppCode',    'value' => 'app_code',    'icon' => 'fas fa-code',        'title' => 'Código de la aplicación', 'desc' => 'app/ (Controllers, Models, Providers, etc.)'],
                                ['id' => 'backupConfig',     'value' => 'config',      'icon' => 'fas fa-gear',        'title' => 'Configuración',            'desc' => 'config/ (Configuración de la aplicación)'],
                                ['id' => 'backupRoutes',     'value' => 'routes',      'icon' => 'fas fa-route',       'title' => 'Rutas',                    'desc' => 'routes/ (Definición de rutas de la aplicación)'],
                                ['id' => 'backupResources',  'value' => 'resources',   'icon' => 'fas fa-palette',     'title' => 'Recursos (Vistas, CSS, JS)', 'desc' => 'resources/ (Vistas Blade, assets, idiomas)'],
                                ['id' => 'backupMigrations', 'value' => 'migrations',  'icon' => 'fas fa-table',       'title' => 'Migraciones de BD',        'desc' => 'database/migrations (Historial de cambios BD)'],
                                ['id' => 'backupStorage',    'value' => 'storage',     'icon' => 'fas fa-folder-open', 'title' => 'Almacenamiento',           'desc' => 'storage/app (Archivos cargados, documentos, etc.)'],
                                ['id' => 'backupDatabase',   'value' => 'database',    'icon' => 'fas fa-database',    'title' => 'Base de datos',            'desc' => 'MySQL Dump completo — todas las tablas, datos y estructura'],
                            ];
                        @endphp

                        @foreach($items as $i => $item)
                            <div class="d-flex align-items-center justify-content-between {{ $i < count($items) - 1 ? 'mb-4' : '' }}">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:40px;height:40px;">
                                        <i class="{{ $item['icon'] }} text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">{{ $item['title'] }}</div>
                                        <div class="text-muted">{{ $item['desc'] }}</div>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0 ms-3">
                                    <input class="form-check-input backup-checkbox" type="checkbox"
                                           name="backup_types[]" id="{{ $item['id'] }}"
                                           value="{{ $item['value'] }}" role="switch" checked>
                                </div>
                            </div>
                        @endforeach

                    </div>

                    <div class="card-footer p-4">
                        <button type="submit" id="submitBtn" class="btn btn-primary w-100 mb-2">
                            Crear backup
                        </button>
                        <a href="{{ route('settings.backups.index') }}" class="btn btn-black w-100">
                            Volver
                        </a>
                    </div>

                </div>

            </form>
        </div>

        {{-- Columna derecha: sidebar informativo --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">¿Qué se respalda?</h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">Archivos del sistema</h6>
                    <p class="text-muted mb-3">Incluye el código fuente, configuración, rutas, vistas, assets, migraciones y archivos de almacenamiento de la aplicación.</p>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-1">Base de datos</h6>
                    <p class="text-muted mb-0">Genera un dump completo de MySQL con todas las tablas, datos y estructura. Es el componente más importante para la recuperación.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Recomendaciones</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted  mb-0">
                        <li class="mb-2">El archivo se descargará automáticamente al terminar la generación.</li>
                        <li class="mb-2">El tiempo depende del tamaño de los archivos y la base de datos.</li>
                        <li class="mb-2">Realiza backups regulares, al menos semanalmente.</li>
                        <li class="mb-2">Guarda las copias en un lugar seguro fuera del servidor.</li>
                        <li class="mb-0">Los backups con todos los componentes son los más completos.</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var $checkboxes = $('.backup-checkbox');
    var $submitBtn  = $('#submitBtn');

    function updateSubmit() {
        $submitBtn.prop('disabled', $checkboxes.filter(':checked').length === 0);
    }

    $('#selectAllBtn').on('click', function (e) {
        e.preventDefault();
        $checkboxes.prop('checked', true);
        updateSubmit();
    });

    $('#deselectAllBtn').on('click', function (e) {
        e.preventDefault();
        $checkboxes.prop('checked', false);
        updateSubmit();
    });

    $checkboxes.on('change', updateSubmit);

    $('#backupForm').on('submit', function (e) {
        if ($checkboxes.filter(':checked').length === 0) {
            e.preventDefault();
            toastr.warning('Selecciona al menos un componente para continuar.', '', { positionClass: 'toast-bottom-right' });
            return;
        }
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Creando backup...');
    });

    updateSubmit();
});
</script>
@endpush
