@extends('layouts.theme')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <div class="card-body">

                    <div class="mb-4">
                        <div class="d-flex no-block align-items-center">
                            <div>
                                <h5 class="m-0">Administrador de backups</h5>
                                <p class="card-subtitle m-0">
                                    Aquí puedes crear, descargar y eliminar backups de tu aplicación y base de datos.
                                </p>
                            </div>

                            <div class="ms-auto d-flex gap-2">
                                <a href="{{ route('settings.backup.schedules.index') }}"
                                   class="btn btn-outline-primary waves-effect waves-light">
                                    Copias programados
                                </a>
                                <a href="{{ route('settings.backups.create') }}"
                                   class="btn btn-primary waves-effect waves-light">
                                    Crear copia
                                </a>
                            </div>
                        </div>
                    </div>

                    @include('core::components.alerts')

                    <!-- Backup Status Cards -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card  bg-light-secondary ">
                                <div class="card-body">
                                    <h6 class="card-title">Total de backups</h6>
                                    <h2 id="backupCount">-</h2>
                                    <small class="text-muted">Cantidad de backups almacenados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card  bg-light-secondary ">
                                <div class="card-body">
                                    <h6 class="card-title">Tamaño total</h6>
                                    <h2 id="totalSize">-</h2>
                                    <small class="text-muted">Espacio utilizado por los backups</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Latest Backup Info -->
                    <div class="row mb-4" id="latestBackupContainer" style="display: none;">
                        <div class="col-lg-12">
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="fa fa-circle-info"></i>
                                <strong>Último Backup:</strong>
                                <span id="latestBackupInfo"></span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                            </div>
                        </div>
                    </div>

                    <!-- Backups Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                            <tr>
                                <th>Nombre del archivo</th>
                                <th>Tamaño</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="backupTableBody">
                            @forelse($backups as $backup)
                                <tr>
                                    <td>
                                        <i class="fa fa-box-archive"></i> {{ $backup['name'] }}
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $backup['size'] }}</span>
                                    </td>
                                    <td>
                                        {{ $backup['date'] }}
                                    </td>
                                    <td>
                                        <div class="dropdown dropstart">
                                            <a href="#" class="text-muted" id="dropdownMenuButton"
                                               data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-3"
                                                       href="{{ route('settings.backups.download', $backup['name']) }}"
                                                       title="Descargar backup">
                                                        Descargar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete"
                                                       data-href="{{ route('settings.backups.destroy', $backup['name']) }}"
                                                       title="Eliminar backup">
                                                        Eliminar
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fa fa-inbox"></i> No hay backups disponibles
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Help Section -->
                    <div class="alert alert-info mt-4">
                        <h6 class="alert-heading">
                            <i class="fa fa-circle-question"></i> Información importante
                        </h6>
                        <ul class="mb-0 small">
                            <li>Los backups incluyen los archivos de la aplicación y la base de datos</li>
                            <li>Se excluyen automáticamente: vendor, node_modules, .git, .env y logs</li>
                            <li>Los backups se almacenan en <code>storage/app/backups</code></li>
                            <li>Los backups más antiguos se eliminan automáticamente después de 7 días</li>
                            <li>Descarga y almacena los backups importantes en un lugar seguro</li>
                        </ul>
                    </div>

                </div>

            </div>

        </div>

    </div>

    @include('core::components.delete')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Load backup status
            fetch('{{ route("settings.backups.status") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('backupCount').textContent = data.count;
                        document.getElementById('totalSize').textContent = data.total_size;

                        if (data.latest) {
                            document.getElementById('latestBackupContainer').style.display = 'block';
                            document.getElementById('latestBackupInfo').textContent =
                                data.latest.name + ' (' + data.latest.size + ') - ' + data.latest.date;
                        }
                    }
                })
                .catch(error => console.error('Error:', error));

            // Modal and Delete functionality
            const deleteModalElement = document.getElementById('delete-modal');
            const deleteModal = new bootstrap.Modal(deleteModalElement);
            const deleteLink = document.getElementById('delete-link');
            let deleteUrl = null;

            // Handle delete link clicks
            const deleteLinks = document.querySelectorAll('.confirm-delete');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();

                    deleteUrl = this.getAttribute('data-href');
                    const filename = deleteUrl.split('/').pop();

                    console.log('Delete URL:', deleteUrl);
                    console.log('Filename:', filename);

                    // Update modal title with filename
                    document.querySelector('#delete-modal .modal-title').textContent = 'Eliminar backup: ' + filename;

                    // Show the modal
                    deleteModal.show();
                });
            });

            // Handle delete link button
            deleteLink.addEventListener('click', function (e) {
                if (deleteUrl) {
                    e.preventDefault();
                    deleteLink.disabled = true;
                    deleteLink.innerHTML = '<i class="fa fa-spinner animate-spin"></i> Eliminando...';

                    console.log('Enviando DELETE a:', deleteUrl);

                    fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                        .then(response => {
                            console.log('Response status:', response.status);
                            console.log('Response headers:', response.headers.get('content-type'));
                            return response.text().then(text => {
                                console.log('Response body:', text);
                                try {
                                    return JSON.parse(text);
                                } catch (e) {
                                    console.error('Failed to parse JSON:', e);
                                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                                }
                            });
                        })
                        .then(data => {
                            deleteModal.hide();
                            deleteLink.disabled = false;
                            deleteLink.innerHTML = 'Confirmar';

                            if (data.success) {
                                // Show success message
                                const alertDiv = document.createElement('div');
                                alertDiv.className = 'alert bg-light-secondary alert-dismissible fade show';
                                alertDiv.innerHTML = `
                                <i class="fa fa-check></i> Copia eliminado correctamente
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            `;

                                // Insert alert at the beginning of card-body
                                const cardBody = document.querySelector('.card-body');
                                cardBody.insertBefore(alertDiv, cardBody.firstChild);

                                // Reload the table after 1.5 seconds
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                alert('Error al eliminar el backup: ' + (data.message || 'Error desconocido'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            deleteLink.disabled = false;
                            deleteLink.innerHTML = 'Confirmar';
                            alert('Error al eliminar el backup');
                        });
                }
            });
        });
    </script>

@endsection
