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

                                    <div class="ms-auto d-flex gap-2 align-items-center">
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


                            <!-- Summary Stats with Cards -->
                            <div class="row mb-4 g-3">
                                <div class="col-md-6 col-lg-3">
                                    <div class="card bg-light-secondary stat-card  h-100  ">
                                        <div class="card-body position-relative">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-2">Total de tablas</h6>
                                                    <h2 >{{ count($tables) }}</h2>
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
                                                    <h2 id="totalRecords">{{ array_sum(array_column($tables, 'records')) }}</h2>
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
                                                    <h2 id="selectedCount">0</h2>
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
                                                    <h2  id="recordsToDelete">0</h2>
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
                                    <button type="submit" class="btn btn-primary w-100 mb-1" id="cleanupBtn" disabled>
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
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content ">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">
                        Confirmación de limpieza
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"  aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        <strong>Esta acción es irreversible.</strong> Se eliminarán todos los registros de las siguientes tablas:
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
                    <button type="button" class="btn btn-primary w-100 mb-1" id="confirmCleanupBtn" disabled>
                        Sí, limpiar ahora
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">
                        Limpieza completada
                    </h5>
                </div>
                <div class="modal-body">
                    <p id="successMessage" class="mb-2"></p>
                    <p class="text-muted mb-0">
                        Los registros de las tablas seleccionadas han sido eliminados permanentemente.
                        Esta operación no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">
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

@endsection

@push('scripts')
    <script>
        $(function () {
            var confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));

            function updateCounts() {
                var selected = $('.table-checkbox:checked');
                var recordsToDelete = 0;

                selected.each(function () {
                    recordsToDelete += parseInt($(this).closest('.table-row').data('records'));
                });

                $('#selectedCount').text(selected.length);
                $('#recordsToDelete').text(recordsToDelete.toLocaleString());
                $('#cleanupBtn').prop('disabled', selected.length === 0);
                $('#selectAllCheckbox').prop('checked', selected.length === $('.table-checkbox').length);
            }

            $('#selectAllBtn').on('click', function () {
                $('.table-checkbox').prop('checked', true);
                updateCounts();
            });

            $('#deselectAllBtn').on('click', function () {
                $('.table-checkbox').prop('checked', false);
                updateCounts();
            });

            $('#selectAllCheckbox').on('change', function () {
                var checked = $(this).is(':checked');
                $('.table-row:visible .table-checkbox').prop('checked', checked);
                updateCounts();
            });

            $(document).on('change', '.table-checkbox', updateCounts);

            $('#tableSearch').on('input', function () {
                var term = $(this).val().toLowerCase();
                $('.table-row').each(function () {
                    $(this).toggle($(this).data('table-name').toLowerCase().indexOf(term) !== -1);
                });
            });

            $('#cleanupForm').on('submit', function (e) {
                e.preventDefault();

                var selectedTables = [];
                var totalRecords = 0;

                $('.table-checkbox:checked').each(function () {
                    var records = parseInt($(this).closest('.table-row').data('records'));
                    selectedTables.push({ name: $(this).val(), records: records });
                    totalRecords += records;
                });

                var $list = $('#tablesToDeleteList').empty();
                $.each(selectedTables, function (i, table) {
                    $list.append('<li><strong>' + table.name + '</strong> - <span class="text-muted">' + table.records + ' registros</span></li>');
                });

                $('#totalToDelete').text(totalRecords.toLocaleString());
                $('#confirmCheckbox').prop('checked', false);
                $('#confirmCleanupBtn').prop('disabled', true);

                confirmationModal.show();
            });

            $('#confirmCheckbox').on('change', function () {
                $('#confirmCleanupBtn').prop('disabled', !$(this).is(':checked'));
            });

            $('#confirmCleanupBtn').on('click', function () {
                var tables = $('.table-checkbox:checked').map(function () { return $(this).val(); }).get();

                $('#confirmCleanupBtn').prop('disabled', true).html('Limpiando...');

                $.ajax({
                    url: '{{ route("settings.database.cleanup.truncate") }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    contentType: 'application/json',
                    data: JSON.stringify({ tables: tables }),
                    success: function (data) {
                        confirmationModal.hide();
                        $('#successMessage').text(data.message);
                        successModal.show();

                        setTimeout(function () {
                            location.reload();
                        }, 3000);
                    },
                    error: function (xhr) {
                        confirmationModal.hide();
                        var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error en la solicitud';
                        toastr.error(msg);
                    },
                    complete: function () {
                        $('#confirmCleanupBtn').prop('disabled', false).html('Sí, limpiar ahora');
                    }
                });
            });

            updateCounts();
        });
    </script>
@endpush
