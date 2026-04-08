@extends('layouts.theme')

@php
    $titles = [
        'reviews'   => 'Reseñas',
        'helpdesk'  => 'Tickets de soporte',
        'attention' => 'PQRSF',
        'forms'     => 'Envios de formulario',
    ];
    $title = $titles[$type] ?? ucfirst($type);
@endphp

@section('title', "Importar {$title}")

@section('content')

    @include('core::components.card', ['title' => "Importar {$title}"])

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">
                        <i class="fas fa-file-import me-2"></i>
                        Importar {{ $title }}
                    </h5>
                </div>
                <div class="card-body">

                    <div class="alert alert-info d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle flex-shrink-0"></i>
                        <div>
                            Descarga la plantilla CSV para ver el formato requerido.
                            <a href="{{ route('import.template', $type) }}" class="alert-link ms-1">
                                <i class="fas fa-download me-1"></i>Descargar plantilla
                            </a>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ route('import.store', $type) }}"
                          enctype="multipart/form-data"
                          id="import-form">
                        @csrf

                        <div class="border rounded-3 p-5 text-center mb-3"
                             id="drop-zone"
                             style="border-style:dashed !important;border-color:#dee2e6;cursor:pointer">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3 d-block"></i>
                            <p class="mb-1">Arrastra tu archivo aqui o</p>
                            <label class="btn btn-outline-primary btn-sm">
                                Seleccionar archivo
                                <input type="file" name="file" id="file-input"
                                       accept=".xlsx,.xls,.csv" class="d-none">
                            </label>
                            <p class="text-muted small mt-2 mb-0">
                                Formatos: CSV, XLSX, XLS &mdash; Max. 10 MB
                            </p>
                        </div>

                        <div id="file-preview" class="d-none mb-3">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded">
                                <i class="fas fa-file-excel text-success fa-2x flex-shrink-0"></i>
                                <div class="flex-grow-1 min-w-0">
                                    <div id="file-name" class="fw-semibold text-truncate"></div>
                                    <div id="file-size" class="text-muted small"></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0"
                                        id="btn-remove-file">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="progress d-none mb-3" id="upload-progress" style="height:6px">
                            <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                        </div>

                        @error('file')
                            <div class="alert alert-danger py-2 mb-3">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="btn-import" disabled>
                                <i class="fas fa-upload me-1"></i>Importar
                            </button>
                            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                Volver
                            </a>
                        </div>
                    </form>

                    @if(session('import_skipped') && count(session('import_skipped')) > 0)
                        <div class="mt-4">
                            <h6 class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Registros omitidos ({{ count(session('import_skipped')) }})
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:60px">Fila</th>
                                            <th>Error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('import_skipped') as $i => $skipped)
                                            <tr>
                                                <td class="text-center">{{ $i + 2 }}</td>
                                                <td class="text-danger small">{{ $skipped['error'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    var fileInput = document.getElementById('file-input');
    var dropZone  = document.getElementById('drop-zone');
    var preview   = document.getElementById('file-preview');
    var btnImport = document.getElementById('btn-import');
    var btnRemove = document.getElementById('btn-remove-file');
    var form      = document.getElementById('import-form');
    var progress  = document.getElementById('upload-progress');

    function showFile(file) {
        if (!file) { return; }
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-size').textContent = (file.size / 1024).toFixed(1) + ' KB';
        preview.classList.remove('d-none');
        dropZone.classList.add('d-none');
        btnImport.disabled = false;
    }

    function resetForm() {
        fileInput.value = '';
        preview.classList.add('d-none');
        dropZone.classList.remove('d-none');
        btnImport.disabled = true;
    }

    fileInput.addEventListener('change', function () {
        showFile(fileInput.files[0]);
    });

    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.classList.add('border-primary');
    });

    dropZone.addEventListener('dragleave', function () {
        dropZone.classList.remove('border-primary');
    });

    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.classList.remove('border-primary');
        var file = e.dataTransfer.files[0];
        if (file) {
            fileInput.files = e.dataTransfer.files;
            showFile(file);
        }
    });

    dropZone.addEventListener('click', function () {
        fileInput.click();
    });

    btnRemove.addEventListener('click', function (e) {
        e.stopPropagation();
        resetForm();
    });

    form.addEventListener('submit', function () {
        progress.classList.remove('d-none');
        btnImport.disabled = true;
        btnImport.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Importando...';
    });
}());
</script>
@endpush
