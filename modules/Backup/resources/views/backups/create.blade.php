@extends('layouts.theme')

@section('content')

    <div class="row">
        <div class="col-lg-12d-flex align-items-stretch">

            <div class="card border shadow-none w-100">

                <form method="POST" action="{{ route('settings.backups.store') }}">

                    {{ csrf_field() }}

                    <div class="card-body p-4">
                        <h4 class="card-title">Crear copia</h4>
                        <p class="card-subtitle mb-4">
                            Selecciona qué elementos deseas incluir en tu backup. Puedes hacer backup de los archivos,
                            la base de datos o ambos.
                        </p>

                        @include('core::components.alerts')

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="btn-group" role="group">
                                    <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-secondary">
                                        Todos
                                    </button>
                                    <button type="button" id="deselectAllBtn" class="btn btn-sm btn-outline-secondary">
                                        Ninguno
                                    </button>
                                </div>
                            </div>

                            <!-- Código de la Aplicación -->
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                        <i class="fa fa-code text-dark d-block fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-4 fw-semibold mb-0">Código de la Aplicación</h5>
                                        <p class="mb-0">app/ (Controllers, Models, Providers, etc.)</p>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="backup_types[]"
                                           id="backupAppCode" value="app_code" role="switch" checked>
                                </div>
                            </div>

                            <!-- Configuración -->
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                        <i class="fa fa-gear text-dark d-block fs-2"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-4 fw-semibold mb-0">Configuración</h5>
                                        <p class="mb-0">config/ (Configuración de la aplicación)</p>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="backup_types[]"
                                           id="backupConfig" value="config" role="switch" checked>
                                </div>
                            </div>

                            <!-- Rutas -->
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                        <i class="fa fa-map text-dark d-block fs-2"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-4 fw-semibold mb-0">Rutas</h5>
                                        <p class="mb-0">routes/ (Definición de rutas de la aplicación)</p>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="backup_types[]"
                                           id="backupRoutes" value="routes" role="switch" checked>
                                </div>
                            </div>

                            <!-- Recursos (Vistas, CSS, JS) -->
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                        <i class="fa fa-palette text-dark d-block fs-2"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-4 fw-semibold mb-0">Recursos (Vistas, CSS, JS)</h5>
                                        <p class="mb-0">resources/ (Vistas Blade, assets, idiomas)</p>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="backup_types[]"
                                           id="backupResources" value="resources" role="switch" checked>
                                </div>
                            </div>

                            <!-- Migraciones de BD -->
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                        <i class="fa fa-database text-dark d-block fs-2"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-4 fw-semibold mb-0">Migraciones de BD</h5>
                                        <p class="mb-0">database/migrations (Historial de cambios BD)</p>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="backup_types[]"
                                           id="backupMigrations" value="migrations" role="switch" checked>
                                </div>
                            </div>

                            <!-- Almacenamiento -->
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                        <i class="fa fa-folder text-dark d-block fs-2"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-4 fw-semibold mb-0">Almacenamiento</h5>
                                        <p class="mb-0">storage/app (Archivos cargados, documentos, etc.)</p>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="backup_types[]"
                                           id="backupStorage" value="storage" role="switch" checked>
                                </div>
                            </div>

                            <!-- Base de datos -->
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-bg-light-secondary rounded-1 p-6 d-flex align-items-center justify-content-center">
                                        <i class="fa fa-database text-dark d-block fs-2"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-4 fw-semibold mb-0">Base de datos</h5>
                                        <p class="mb-0">MySQL Dump Completo - Todas las tablas, datos y estructura</p>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="backup_types[]"
                                           id="backupDatabase" value="database" role="switch" checked>
                                </div>
                            </div>

                        </div>

                        <!-- Info Section -->
                        <div class="alert alert-info mt-4">
                            <h6 class="alert-heading">
                                <i class="fa fa-circle-info"></i> Información Importante
                            </h6>
                            <ul class="mb-0 small">
                                <li>El backup se descargará automáticamente una vez se haya generado</li>
                                <li>El tiempo de generación depende del tamaño de tus archivos y base de datos</li>
                                <li>Se recomienda hacer backups regulares (al menos semanalmente)</li>
                                <li>Guarda los backups en un lugar seguro fuera del servidor</li>
                                <li>Los backups con ambas opciones son más seguros</li>
                            </ul>
                        </div>

                        <div class="col-12">
                            <div class="border-top pt-3 mt-4">
                                <button type="submit" class="btn btn-primary px-4 w-100 mb-2">
                                    Crear
                                </button>
                                <a href="{{ route('settings.backups.index') }}"
                                   class="btn btn-secondary px-4 w-100">
                                    Volver
                                </a>
                            </div>
                        </div>

                    </div>

                </form>
            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            const checkboxes = document.querySelectorAll('input[name="backup_types[]"]');
            const submitBtn = form.querySelector('button[type="submit"]');
            const selectAllBtn = document.getElementById('selectAllBtn');
            const deselectAllBtn = document.getElementById('deselectAllBtn');

            // Update submit button state
            function updateSubmitButton() {
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                submitBtn.disabled = !anyChecked;
            }

            // Select all checkboxes
            selectAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateSubmitButton();
            });

            // Deselect all checkboxes
            deselectAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSubmitButton();
            });

            // Form submission validation
            form.addEventListener('submit', function (e) {
                const checked = Array.from(checkboxes).some(cb => cb.checked);
                if (!checked) {
                    e.preventDefault();
                    alert('Por favor, selecciona al menos una opción de backup');
                } else {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner animate-spin"></i> Creando backup...';
                }
            });

            // Update submit button when any checkbox changes
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    updateSubmitButton();
                });
            });

            // Initial button state
            updateSubmitButton();
        });
    </script>

@endsection
