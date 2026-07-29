@extends('layouts.theme')

@section('page_title', 'Gestión de almacenamiento')

@section('content')
    <div class="container-fluid">

        {{-- Breadcrumb Card --}}
        @include('core::components.card', [
            'title' => 'Gestión de almacenamiento',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => url('/home')],
                ['label' => 'Configuración', 'url' => route('manager.backups')],
                ['label' => 'Almacenamiento', 'active' => true]
            ]
        ])

        <div class="widget-content searchable-container list">

            {{-- Main Card --}}
            <div class="card">
                {{-- Header Section --}}
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Discos de almacenamiento</h5>
                            <p class="small mb-0 text-muted">Gestiona los discos de almacenamiento personalizados del sistema</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('manager.backups.storage.create') }}" class="btn btn-primary">
                                Nuevo disco
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Info Section --}}
                <div class="card-body border-bottom">
                    <div class="alert alert-info border-0 mb-0" role="alert">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-start">
                                <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-2">Discos del sistema</h6>
                                    <p class="mb-0">
                                        Los discos core de Laravel (<code>local</code>, <code>public</code>) están configurados en <code>config/filesystems.php</code>.
                                        Aquí solo se gestionan discos personalizados que se almacenan en la base de datos.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alerts --}}
                @if ($errors->any())
                    <div class="card-body border-bottom">
                        <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fa fa-exclamation-circle fs-4 me-2 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading fw-bold mb-2">Errores de Validación</h6>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="card-body border-bottom">
                        <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-check fs-4 me-2"></i>
                                <div>{{ session('success') }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="card-body border-bottom">
                        <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-exclamation-circle fs-4 me-2"></i>
                                <div>{{ session('error') }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                @endif

                {{-- Disks Table --}}
                @if (count($storageData['custom_disks']) > 0)
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th width="25%">Nombre</th>
                                    <th width="15%">Tipo</th>
                                    <th width="12%">Origen</th>
                                    <th width="30%">Configuración</th>
                                    <th width="18%" class="text-center">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($storageData['custom_disks'] as $index => $disk)
                                    @php
                                        $isFromConfig = $disk['from_config'] ?? false;
                                        $iconClass = match($disk['driver']) {
                                            'local' => 'fa-folder text-success',
                                            'ftp' => 'fa-network-wired text-primary',
                                            'sftp' => 'fa-lock text-info',
                                            's3' => 'fa-cloud text-warning',
                                            default => 'fa-hdd text-secondary'
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div>
                                                {{ $disk['name'] }}
                                            </div>
                                        </td>
                                        <td>
                                        <span class="badge bg-info-subtle text-info">
                                            {{ $storageData['driver_options'][$disk['driver']] ?? $disk['driver'] }}
                                        </span>
                                        </td>
                                        <td>
                                            @if($isFromConfig)
                                                <span class="badge bg-warning-subtle text-warning">
                                                <i class="fas fa-cog me-1"></i>Config
                                            </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">
                                                <i class="fas fa-database me-1"></i>BD
                                            </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($disk['driver'] === 'local')
                                                <small class="text-muted">
                                                    <i class="fas fa-folder-open me-1"></i>
                                                    <code class="text-muted">{{ Str::limit($disk['root'] ?? 'N/A', 35) }}</code>
                                                </small>
                                            @elseif(in_array($disk['driver'], ['ftp', 'sftp']))
                                                <small class="text-muted">
                                                    <i class="fas fa-server me-1"></i>
                                                    {{ $disk['host'] ?? 'N/A' }}:{{ $disk['port'] ?? 'default' }}
                                                </small>
                                            @elseif($disk['driver'] === 's3')
                                                <small class="text-muted">
                                                    <i class="fas fa-cloud me-1"></i>
                                                    {{ $disk['bucket'] ?? 'N/A' }} ({{ $disk['region'] ?? 'N/A' }})
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.backups.storage.edit', $index) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    @if(!$isFromConfig)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button type="button" class="dropdown-item delete-disk-btn"
                                                                    data-disk-name="{{ $disk['name'] }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="text-center py-5">
                            <i class="fa fa-hard-drive fa-3x mb-3 text-muted opacity-50"></i>
                            <h5 class="fw-bold mb-2">No hay discos personalizados</h5>
                            <p class="text-muted mb-4">
                                Comienza creando tu primer disco de almacenamiento personalizado.
                            </p>
                            <a href="{{ route('manager.backups.storage.create') }}" class="btn btn-primary">
                                + Crear ahora
                            </a>
                        </div>
                    </div>
                @endif

            </div>

        </div>

        {{-- Delete Form --}}
        <form id="deleteDiskForm" method="POST" action="{{ route('manager.backups.storage.destroy') }}" style="display: none;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="disk_name" id="delete_disk_name">
        </form>

    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Delete disk
            $(document).on('click', '.delete-disk-btn', function(e) {
                e.preventDefault();
                const diskName = $(this).data('disk-name');

                if (confirm(`¿Estás seguro de que deseas eliminar el disco "${diskName}"?\n\nEsta acción no se puede deshacer.`)) {
                    $('#delete_disk_name').val(diskName);
                    $('#deleteDiskForm').submit();
                }
            });
        });
    </script>
@endpush

