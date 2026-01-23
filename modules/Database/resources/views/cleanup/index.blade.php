@extends('layouts.theme')

@section('content')
    <div class="container-fluid">

        @include('core::components.card', ['title' => 'Limpieza de Base de Datos'])

        @include('core::components.alerts')

        <!-- Warning Alert -->
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="alert alert-dismissible fade show border-0 bg-warning-subtle" role="alert">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa fa-triangle-exclamation text-warning fs-9"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading fw-bold text-warning mb-0">
                                ¡Atención! Realiza una copia de seguridad completa antes de continuar
                            </h6>
                            <p class="mb-0 text-warning">
                                Esta operación eliminará permanentemente todos los registros de las tablas
                                seleccionadas. Una vez ejecutada, <strong>no podrás recuperar los datos
                                    eliminados</strong>. Se recomienda crear un backup completo de la base de datos
                                antes de proceder.
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>

        <form id="cleanupForm">
            @csrf
            <div class="row">
                <div class="col-lg-12">
                    <div class="card ">
                        <div class="card-body">

                            <div class="mb-4">
                                <div class="d-flex no-block align-items-center">
                                    <div>
                                        <h5 class="m-0">Selecciona las tablas a limpiar</h5>
                                        <p class="card-subtitle m-0">
                                            Aquí puedes crear, descargar y eliminar backups de tu aplicación y base de
                                            datos.
                                        </p>
                                    </div>


                                    <div class="ms-auto d-flex gap-2">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div class="btn-group" role="group">
                                                <button type="button" id="selectAllBtn"
                                                        class="btn btn-sm btn-outline-secondary">
                                                    Todos
                                                </button>
                                                <button type="button" id="deselectAllBtn"
                                                        class="btn btn-sm btn-outline-secondary">
                                                    Ninguno
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>


                            <!-- Summary Stats with Cards -->
                            <div class="row mb-4 g-3">
                                <div class="col-md-6 col-lg-3">
                                    <div class="card bg-light-secondary stat-card  h-100  ">
                                        <div class="card-body position-relative">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-2">Total de tablas</h6>
                                                    <h2 class="text-success"
                                                        style="font-weight: 700;">{{ count($tables) }}</h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card bg-light-secondary stat-card  h-100  ">
                                        <div class="card-body position-relative">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-2">Registros totales</h6>
                                                    <h2 class="text-success"
                                                        id="totalRecords">{{ array_sum(array_column($tables, 'records')) }}</h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card bg-light-secondary stat-card  h-100  ">
                                        <div class="card-body position-relative">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-2">Seleccionadas</h6>
                                                    <h2 class="text-success" id="selectedCount">0</h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card bg-light-secondary stat-card  h-100  ">
                                        <div class="card-body position-relative">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-2">A eliminar</h6>
                                                    <h2 class="text-success" id="recordsToDelete">0</h2>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Search/Filter -->
                            <div class="mb-4">
                                <input type="text" class="form-control" id="tableSearch" placeholder="Buscar tabla...">
                            </div>

                            <!-- Tables List -->
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                    <tr>
                                        <th style="width: 50px;">
                                            <input type="checkbox" class="form-check-input" id="selectAllCheckbox">
                                        </th>
                                        <th>Nombre de la tabla</th>
                                        <th style="width: 150px;" class="text-end">Registros</th>
                                    </tr>
                                    </thead>
                                    <tbody id="tablesList">
                                    @foreach($tables as $table)
                                        <tr class="table-row" data-table-name="{{ $table['name'] }}"
                                            data-records="{{ $table['records'] }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input table-checkbox"
                                                       name="tables[]" value="{{ $table['name'] }}">
                                            </td>
                                            <td>
                                                <code class="bg-light-secondary  p-2 rounded small text-black">{{ $table['name'] }}</code>
                                                @if($table['records'] === 0)
                                                    <span class="badge bg-success ms-2">Vacía</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-light-secondary  text-dark table-record-count">{{ $table['records'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row">
                                <div class="border-top pt-1 mt-4 pt-2">
                                    <button type="submit" class="btn btn-primary w-100 mb-2" id="cleanupBtn" disabled>
                                        Limpiar tablas seleccionadas
                                    </button>
                                    <a href="{{ route('settings.database.index') }}"
                                       class="btn btn-secondary w-100 ">
                                        Cancelar
                                    </a>
                                </div>
                            </div>

                            @if(empty($tables))
                                <div class="alert alert-info" role="alert">
                                    <i class="fa fa-circle-info"></i> No hay tablas disponibles.
                                </div>
                            @endif

                        </div>


                    </div>
                </div>


            </div>

        </form>

    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmationModalLabel">
                        <i class="fa fa-triangle-exclamation"></i> Confirmación de limpieza
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger mb-3">
                        <strong>Esta acción es irreversible.</strong> Se eliminarán todos los registros de las
                        siguientes tablas:
                    </p>
                    <div class="bg-light-secondary p-3 rounded mb-3" style="max-height: 300px; overflow-y: auto;">
                        <ul id="tablesToDeleteList" class="mb-0">
                        </ul>
                    </div>
                    <p class="mb-3">
                        Total de registros a eliminar: <strong id="totalToDelete" class="text-danger">0</strong>
                    </p>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmCheckbox">
                        <label class="form-check-label" for="confirmCheckbox">
                            Entiendo que esto eliminará todos los datos y que no podrá ser reversible
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-xmark"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmCleanupBtn" disabled>
                        <i class="fa fa-trash"></i> Sí, limpiar ahora
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fa fa-circle-check"></i> Limpieza completada
                    </h5>
                </div>
                <div class="modal-body">
                    <p id="successMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .card {
            border-radius: 8px;
        }

        .card-header {
            border-bottom: 1px solid #e3e6f0;
            border-radius: 8px 8px 0 0;
        }

        code {
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            color: #e83e8c;
            font-size: 0.85rem;
        }

        .table-row:hover {
            background-color: #f8f9fa;
        }

        .table-record-count {
            font-weight: 600;
        }

        #tablesList {
            max-height: 600px;
            overflow-y: auto;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('cleanupForm');
            const cleanupBtn = document.getElementById('cleanupBtn');
            const selectAllBtn = document.getElementById('selectAllBtn');
            const deselectAllBtn = document.getElementById('deselectAllBtn');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const tableCheckboxes = document.querySelectorAll('.table-checkbox');
            const tableSearch = document.getElementById('tableSearch');
            const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            const confirmCheckbox = document.getElementById('confirmCheckbox');
            const confirmCleanupBtn = document.getElementById('confirmCleanupBtn');

            // Update counts when checkbox changes
            function updateCounts() {
                const selectedCount = document.querySelectorAll('.table-checkbox:checked').length;
                let recordsToDelete = 0;

                document.querySelectorAll('.table-checkbox:checked').forEach(checkbox => {
                    const row = checkbox.closest('.table-row');
                    recordsToDelete += parseInt(row.getAttribute('data-records'));
                });

                document.getElementById('selectedCount').textContent = selectedCount;
                document.getElementById('recordsToDelete').textContent = recordsToDelete.toLocaleString();
                cleanupBtn.disabled = selectedCount === 0;
                selectAllCheckbox.checked = selectedCount === tableCheckboxes.length;
            }

            // Select all tables
            selectAllBtn.addEventListener('click', function () {
                tableCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateCounts();
            });

            // Deselect all tables
            deselectAllBtn.addEventListener('click', function () {
                tableCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateCounts();
            });

            // Select all via checkbox
            selectAllCheckbox.addEventListener('change', function () {
                tableCheckboxes.forEach(checkbox => {
                    if (!checkbox.closest('.table-row').style.display === 'none') {
                        checkbox.checked = this.checked;
                    }
                });
                updateCounts();
            });

            // Update counts on individual checkbox change
            tableCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCounts);
            });

            // Search/Filter functionality
            tableSearch.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('.table-row');

                rows.forEach(row => {
                    const tableName = row.getAttribute('data-table-name').toLowerCase();
                    if (tableName.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // Form submission
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const selectedTables = [];
                let totalRecords = 0;

                document.querySelectorAll('.table-checkbox:checked').forEach(checkbox => {
                    const row = checkbox.closest('.table-row');
                    selectedTables.push({
                        name: checkbox.value,
                        records: parseInt(row.getAttribute('data-records'))
                    });
                    totalRecords += parseInt(row.getAttribute('data-records'));
                });

                if (selectedTables.length === 0) {
                    alert('Por favor selecciona al menos una tabla');
                    return;
                }

                // Populate confirmation modal
                const tableList = document.getElementById('tablesToDeleteList');
                tableList.innerHTML = '';
                selectedTables.forEach(table => {
                    const li = document.createElement('li');
                    li.innerHTML = `<strong>${table.name}</strong> - <span class="text-muted">${table.records} registros</span>`;
                    tableList.appendChild(li);
                });

                document.getElementById('totalToDelete').textContent = totalRecords.toLocaleString();
                confirmCheckbox.checked = false;
                confirmCleanupBtn.disabled = true;

                confirmationModal.show();
            });

            // Confirmation checkbox
            confirmCheckbox.addEventListener('change', function () {
                confirmCleanupBtn.disabled = !this.checked;
            });

            // Confirm cleanup
            confirmCleanupBtn.addEventListener('click', function () {
                const tablesToTruncate = Array.from(document.querySelectorAll('.table-checkbox:checked')).map(cb => cb.value);

                confirmCleanupBtn.disabled = true;
                confirmCleanupBtn.innerHTML = '<i class="fa fa-spinner animate-spin"></i> Limpiando...';

                fetch('{{ route('settings.database.cleanup.truncate") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tables: tablesToTruncate
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        confirmationModal.hide();

                        if (data.success) {
                            document.getElementById('successMessage').textContent = data.message;
                            successModal.show();

                            // Reset form
                            setTimeout(() => {
                                form.reset();
                                updateCounts();
                            }, 2000);
                        } else {
                            alert('Error: ' + data.message);
                            if (data.errors) {
                                console.error('Detailed errors:', data.errors);
                            }
                        }
                    })
                    .catch(error => {
                        confirmationModal.hide();
                        alert('Error en la solicitud: ' + error.message);
                    })
                    .finally(() => {
                        confirmCleanupBtn.disabled = false;
                        confirmCleanupBtn.innerHTML = '<i class="fa fa-trash"></i> Sí, limpiar ahora';
                    });
            });

            // Initialize counts
            updateCounts();
        });
    </script>
@endsection
