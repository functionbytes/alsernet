@extends('layouts.theme')

@section('title', 'Gestión de almacenamiento')

@section('page_header')
    @include('core::components.card', ['title' => 'Gestión de almacenamiento'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Discos de almacenamiento</h5>
                        <p class="small mb-0 text-muted">Gestiona los discos de almacenamiento personalizados del sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.storage.create') }}" class="btn btn-primary">
                            Nuevo disco
                        </a>
                    </div>
                </div>
            </div>

            {{-- Statistics — only shown when there are disks to report --}}
            @if ($statistics['total_disks'] > 0)
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $statistics['total_disks'] }}</h4>
                                        <small class="text-muted">Total discos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Base de datos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $statistics['custom_db'] }}</h4>
                                        <small class="text-muted">En base de datos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title  mb-2">Configuración</h6>
                                        <h4 class="mb-1 fw-bold">{{ $statistics['custom_config'] }}</h4>
                                        <small class="text-muted">De configuración</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Amazon S3</h6>
                                        <h4 class="mb-1 fw-bold">{{ $statistics['driver_counts']['s3'] }}</h4>
                                        <small class="text-muted">Discos S3</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
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
                            @foreach($storageData['custom_disks'] as $disk)
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
                                            <span class="badge bg-light text-warning">Config</span>
                                        @else
                                            <span class="badge bg-light text-success">BD</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($disk['driver'] === 'local')
                                            <small class="text-muted">
                                                <code class="text-muted">{{ Str::limit($disk['root'] ?? 'N/A', 35) }}</code>
                                            </small>
                                        @elseif(in_array($disk['driver'], ['ftp', 'sftp']))
                                            <small class="text-muted">
                                                {{ $disk['host'] ?? 'N/A' }}:{{ $disk['port'] ?? 'default' }}
                                            </small>
                                        @elseif($disk['driver'] === 's3')
                                            <small class="text-muted">
                                                {{ $disk['bucket'] ?? 'N/A' }} ({{ $disk['region'] ?? 'N/A' }})
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.storage.edit', $disk['name']) }}">
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-hdd fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay discos personalizados</h6>
                            <p class="text-muted mb-3">Comienza creando tu primer disco de almacenamiento personalizado.</p>
                            <a href="{{ route('settings.storage.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Crear ahora
                            </a>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Delete Form --}}
    <form id="deleteDiskForm" method="POST" action="{{ route('settings.storage.destroy') }}" style="display: none;">
        @csrf
        @method('DELETE')
        <input type="hidden" name="disk_name" id="delete_disk_name">
    </form>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            @if (session('success'))
            toastr.success(@json(session('success')), 'Éxito');
            @endif

            @if (session('error'))
            toastr.error(@json(session('error')), 'Error');
            @endif

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
