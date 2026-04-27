@extends('layouts.theme')

@section('title', 'Importar ubicaciones')

@section('content')

    @include('core::components.card', ['title' => 'Importar ubicaciones'])

    @include('core::components.alerts')

    <div class="row g-3">

        {{-- Import card --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Importar CSV</h5>
                    <small class="text-muted">Carga masiva de países, estados y ciudades desde un archivo CSV</small>
                </div>

                <div class="card-body">
                    <div class="alert alert-info d-flex gap-2 mb-4">
                        <i class="fas fa-circle-info mt-1 flex-shrink-0"></i>
                        <div>
                            <strong>Formato del archivo CSV:</strong>
                            <div class="mt-1">
                                <code>país_código,país_nombre,estado_nombre,estado_código,ciudad_nombre</code>
                            </div>
                            <div class="mt-2 text-muted small">
                                Una fila por ciudad. Si el país o estado no existen se crean automáticamente. La primera fila se trata como encabezado y se omite.
                            </div>
                            <div class="mt-2 small">
                                <strong>Ejemplo:</strong><br>
                                <code>MX,México,Jalisco,JAL,Guadalajara</code><br>
                                <code>CO,Colombia,Antioquia,ANT,Medellín</code>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('locations.import.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Archivo CSV <span class="text-danger">*</span></label>
                            <input type="file"
                                   class="form-control @error('file') is-invalid @enderror"
                                   name="file"
                                   accept=".csv,.txt"
                                   required>
                            <small class="form-text text-muted">Formato: CSV. Tamaño máximo: 10 MB.</small>
                            @error('file')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" id="importBtn" class="btn btn-primary w-100">
                            Importar CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Export + stats card --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Exportar datos</h5>
                    <small class="text-muted">Descarga todos los países, estados y ciudades en formato CSV</small>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-4">
                        El archivo exportado incluye todos los registros activos con el mismo formato requerido para la importación.
                    </p>

                    <a href="{{ route('locations.export') }}" class="btn btn-outline-primary w-100 mb-4">
                        <i class="fas fa-download me-1"></i>
                        Exportar CSV
                    </a>

                    <h6 class="fw-semibold mb-3 border-bottom pb-2">Estadísticas actuales</h6>
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="card bg-light-secondary text-center p-3">
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['countries'] ?? 0) }}</h4>
                                <small class="text-muted">Países</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-light-secondary text-center p-3">
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['states'] ?? 0) }}</h4>
                                <small class="text-muted">Estados</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-light-secondary text-center p-3">
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['cities'] ?? 0) }}</h4>
                                <small class="text-muted">Ciudades</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#importForm').on('submit', function () {
        $('#importBtn').prop('disabled', true).text('Importando...');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
});
</script>
@endpush
