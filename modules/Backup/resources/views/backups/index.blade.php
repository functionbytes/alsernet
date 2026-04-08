@extends('layouts.theme')

@section('title', 'Administrador de backups')

@section('content')

    @include('core::components.card', ['title' => 'Administrador de backups'])

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Columna izquierda: tabla principal --}}
        <div class="col-lg-8">
            <div class="card">

                {{-- Header --}}
                <div class="card-header p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Administrador de backups</h5>
                            <p class="small mb-0 text-muted">Crea, descarga y elimina copias de seguridad de la aplicación y base de datos</p>
                        </div>
                        <div class="dropdown">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown">
                                Acciones
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('settings.backups.create') }}">Crear copia</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('settings.backup.schedules.index') }}">Copias programadas</a></li>
                                <li><a class="dropdown-item" href="{{ route('settings.backups.setup') }}">Configuración del sistema</a></li>
                                <li><a class="dropdown-item" href="{{ route('settings.backups.guide') }}">Guía de configuración</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Total de backups</h6>
                                    <h4 class="mb-1 fw-bold" id="backupCount">—</h4>
                                    <small class="text-muted">Copias almacenadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Tamaño total</h6>
                                    <h4 class="mb-1 fw-bold" id="totalSize">—</h4>
                                    <small class="text-muted" id="latestBackupInfo">Espacio utilizado</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre del archivo</th>
                                    <th>Tamaño</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($backups as $backup)
                                    <tr>
                                        <td class="fw-semibold">{{ $backup['name'] }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $backup['size'] }}</span>
                                        </td>
                                        <td>{{ $backup['date'] }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.backups.download', $backup['name']) }}">
                                                            Descargar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.backups.destroy', $backup['name']) }}"
                                                           data-title="Eliminar: {{ $backup['name'] }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-box-archive fs-7"></i>
                                                </div>
                                                <h6 class="mb-1">No hay backups disponibles</h6>
                                                <p class="text-muted mb-3">Crea una copia de seguridad para proteger tu aplicación</p>
                                                <a href="{{ route('settings.backups.create') }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-plus me-1"></i> Crear copia
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        {{-- Columna derecha: sidebar informativo --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Información importante</h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">¿Qué incluyen?</h6>
                    <p class="text-muted mb-3">Los backups incluyen los archivos de la aplicación y la base de datos.</p>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-1">¿Qué se excluye?</h6>
                    <p class="text-muted mb-0">Se excluyen automáticamente <code>vendor</code>, <code>node_modules</code>, <code>.git</code>, <code>.env</code> y logs del servidor.</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Almacenamiento</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        Las copias se guardan en:
                    </p>
                    <code class="small">storage/app/{{ config('app.name') }}</code>
                    <hr class="my-3">
                    <p class="text-muted mb-0">
                        Los backups más antiguos se eliminan automáticamente después de <strong>7 días</strong>.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Consejos de uso</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted  mb-0">
                        <li class="mb-2">Descarga y guarda los backups importantes en un lugar seguro fuera del servidor.</li>
                        <li class="mb-2">Realiza copias de seguridad de forma regular, al menos semanalmente.</li>
                        <li class="mb-0">Los backups con todos los componentes son los más completos.</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $.get('{{ route('settings.backups.status') }}', function (data) {
        if (data.success) {
            $('#backupCount').text(data.count);
            $('#totalSize').text(data.total_size);
            if (data.latest) {
                $('#latestBackupInfo').text('Último: ' + data.latest.name + ' — ' + data.latest.date);
            }
        }
    });

    $(document).on('click', '[data-bs-target="#delete-modal"]', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });
});
</script>
@endpush
