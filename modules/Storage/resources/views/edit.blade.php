@extends('layouts.theme')

@section('page_title', 'Editar disco de almacenamiento')

@section('content')

    @include('core::components.card', ['title' => 'Editar disco de almacenamiento'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">

                <form method="POST" action="{{ route('settings.storage.update-disk', $diskName) }}" id="formEdit">
                    @csrf
                    @method('PATCH')

                    <div class="card">

                        {{-- Información del disco --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Información del disco</h6>
                            <p class="text-muted mb-3">
                                @if($isFromConfig)
                                    Este disco está definido en <code class="text-warning">config/filesystems.php</code>. Los cambios crearán una copia editable en la base de datos.
                                @else
                                    Este disco está almacenado en la base de datos y es completamente editable.
                                @endif
                            </p>

                            <div class="row g-3">
                                <div class="col-sm-12">
                                    <label class="form-label fw-semibold">
                                        Nombre del disco <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $disk['name']) }}"
                                           placeholder="Ej: network_shared" required>
                                    <small class="text-muted d-block mt-1">Sin espacios, solo letras, números y guiones bajos</small>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-sm-12">
                                    <label class="form-label fw-semibold">
                                        Tipo de almacenamiento <span class="text-danger">*</span>
                                    </label>
                                    <select class="select2 form-select @error('driver') is-invalid @enderror"
                                            id="driver" name="driver" required>
                                        <option value="">Selecciona un tipo</option>
                                        @foreach($driverOptions as $driverKey => $driverLabel)
                                            <option value="{{ $driverKey }}" {{ old('driver', $disk['driver']) == $driverKey ? 'selected' : '' }}>
                                                {{ $driverLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('driver')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Configuración específica --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Configuración específica</h6>
                            <p class="text-muted mb-3">Parámetros de conexión según el tipo de almacenamiento seleccionado.</p>

                            {{-- Local Driver Fields --}}
                            <div id="localFields" class="driver-fields" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">
                                            Ubicación del almacenamiento <span class="text-danger">*</span>
                                        </label>
                                        <select class="select2 form-select @error('storage_type') is-invalid @enderror" name="storage_type">
                                            <option value="public" {{ old('storage_type', $disk['storage_type'] ?? 'public') === 'public' ? 'selected' : '' }}>
                                                Público — Accesible desde el navegador
                                            </option>
                                            <option value="private" {{ old('storage_type', $disk['storage_type'] ?? '') === 'private' ? 'selected' : '' }}>
                                                Privado — Solo accesible por la aplicación
                                            </option>
                                        </select>
                                        @error('storage_type')
                                            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <div class="alert alert-info mb-0">
                                            <strong>Ubicación actual</strong>
                                            <ul class="mb-0 mt-2">
                                                <li><strong>Ruta:</strong> <code>{{ $disk['root'] ?? 'N/A' }}</code></li>
                                                @if(!empty($disk['url']))
                                                    <li><strong>URL:</strong> <code>{{ $disk['url'] }}</code></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- FTP Driver Fields --}}
                            <div id="ftpFields" class="driver-fields row g-3" style="display: none;">
                                @include('storage::partials.remote-fields', ['driver' => 'ftp', 'disk' => $disk])
                            </div>

                            {{-- SFTP Driver Fields --}}
                            <div id="sftpFields" class="driver-fields row g-3" style="display: none;">
                                @include('storage::partials.remote-fields', ['driver' => 'sftp', 'disk' => $disk])
                            </div>

                            {{-- S3 Driver Fields --}}
                            <div id="s3Fields" class="driver-fields" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-sm-12">
                                        <label class="form-label fw-semibold">Bucket <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('bucket') is-invalid @enderror"
                                               name="bucket" value="{{ old('bucket', $disk['bucket'] ?? '') }}"
                                               placeholder="mi-bucket">
                                        @error('bucket')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label fw-semibold">Región <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('region') is-invalid @enderror"
                                               name="region" value="{{ old('region', $disk['region'] ?? '') }}"
                                               placeholder="us-east-1">
                                        <small class="text-muted d-block mt-1">Región de AWS donde está el bucket</small>
                                        @error('region')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label fw-semibold">Access Key ID <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('key') is-invalid @enderror"
                                               name="key" value="{{ old('key') }}"
                                               placeholder="Dejar en blanco para mantener el actual">
                                        <small class="text-muted d-block mt-1">Dejar vacío para mantener el Access Key ID actual</small>
                                        @error('key')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label fw-semibold">Secret Access Key</label>
                                        <input type="password" class="form-control @error('secret') is-invalid @enderror"
                                               name="secret" value="{{ old('secret') }}"
                                               placeholder="Dejar en blanco para mantener la actual">
                                        <small class="text-muted d-block mt-1">Dejar vacío para mantener el secret actual</small>
                                        @error('secret')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar cambios
                            </button>
                        </div>

                    </div>

                </form>

            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Origen</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Disco</span>
                            <strong>{{ $disk['name'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Driver</span>
                            <strong>{{ strtoupper($disk['driver'] ?? '—') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Origen</span>
                            @if($isFromConfig)
                                <strong>config/filesystems.php</strong>
                            @else
                                <strong>Base de datos</strong>
                            @endif
                        </div>
                    </div>
                </div>

                @if(!$isFromConfig)
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">Eliminar disco</h6>
                            <p class="text-muted mb-3">Elimina este disco permanentemente. Esta acción no se puede deshacer.</p>
                            <button type="button" class="btn btn-outline-info w-100" id="deleteBtn">
                                Eliminar disco
                            </button>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Cancelar</h6>
                        <p class="text-muted mb-3">Descartar cambios y volver al listado de almacenamientos.</p>
                        <a href="{{ route('settings.storage') }}" class="btn btn-secondary w-100">
                            Volver a almacenamiento
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

    @if(!$isFromConfig)
        <form id="deleteDiskForm" method="POST" action="{{ route('settings.storage.destroy') }}" style="display: none;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="disk_name" value="{{ $disk['name'] }}">
        </form>
    @endif

@endsection

@push('scripts')
    @include('storage::partials.driver-toggle')
    <script>
    $(document).ready(function () {
        $('#deleteBtn').on('click', function () {
            if (confirm('¿Estás seguro de que deseas eliminar este disco?\n\nEsta acción no se puede deshacer.')) {
                $('#deleteDiskForm').submit();
            }
        });

        @if (session('success'))
            toastr.success(@json(session('success')), 'Éxito');
        @endif
        @if (session('error'))
            toastr.error(@json(session('error')), 'Error');
        @endif
    });
    </script>
@endpush
