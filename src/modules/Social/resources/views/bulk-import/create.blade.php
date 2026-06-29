@extends('layouts.theme')

@section('title', 'Nueva Importación Masiva')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.bulk-import.index') }}">Importación Masiva</a></li>
                <li class="breadcrumb-item active">Nueva Importación</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-upload me-2"></i>Nueva Importación Masiva
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.social.bulk-import.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <!-- CSV File -->
                            <div class="mb-4">
                                <label for="file" class="form-label fw-semibold">
                                    Archivo CSV <span class="text-danger">*</span>
                                </label>
                                <input type="file"
                                       class="form-control @error('file') is-invalid @enderror"
                                       id="file"
                                       name="file"
                                       accept=".csv"
                                       required>
                                @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Solo archivos CSV (máximo 5MB)</small>
                            </div>

                            <!-- Instructions -->
                            <div class="alert alert-info" role="alert">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>Formato del archivo CSV
                                </h6>
                                <p class="mb-2">El archivo CSV debe contener las siguientes columnas:</p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered bg-white mb-3">
                                        <thead>
                                            <tr>
                                                <th>Columna</th>
                                                <th>Descripción</th>
                                                <th>Requerido</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>content</code></td>
                                                <td>Texto de la publicación</td>
                                                <td><span class="badge bg-danger">Sí</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>social_account_id</code></td>
                                                <td>ID de la cuenta social</td>
                                                <td><span class="badge bg-secondary">No</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>campaign_id</code></td>
                                                <td>ID de la campaña</td>
                                                <td><span class="badge bg-secondary">No</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>type</code></td>
                                                <td>Tipo: text, image, video, link</td>
                                                <td><span class="badge bg-secondary">No</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>scheduled_at</code></td>
                                                <td>Fecha programación (YYYY-MM-DD HH:MM:SS)</td>
                                                <td><span class="badge bg-secondary">No</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>status</code></td>
                                                <td>Estado: draft, scheduled, published</td>
                                                <td><span class="badge bg-secondary">No</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="mb-0"><strong>Ejemplo de CSV:</strong></p>
                                <pre class="bg-dark text-white p-3 rounded mt-2"><code>content,social_account_id,campaign_id,type,scheduled_at,status
"Mi primera publicación",1,5,text,"2025-01-15 10:00:00",scheduled
"Segunda publicación con imagen",2,5,image,"2025-01-16 14:30:00",draft
"Publicación simple",,,,draft</code></pre>
                            </div>

                            <!-- Download Template -->
                            <div class="mb-4">
                                <a href="data:text/csv;charset=utf-8,content%2Csocial_account_id%2Ccampaign_id%2Ctype%2Cscheduled_at%2Cstatus%0A%22Ejemplo%20de%20publicaci%C3%B3n%22%2C1%2C%2Ctext%2C%2Cdraft"
                                   download="plantilla_importacion.csv"
                                   class="btn btn-light">
                                    <i class="fas fa-download me-1"></i>
                                    Descargar Plantilla CSV
                                </a>
                            </div>

                            <!-- Warning Alert -->
                            <div class="alert alert-warning d-flex align-items-start mb-4" role="alert">
                                <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                                <div>
                                    <strong>Importante:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>El archivo será procesado en segundo plano</li>
                                        <li>Recibirás una notificación cuando termine</li>
                                        <li>Las filas con errores serán registradas</li>
                                        <li>Asegúrate de que el archivo CSV esté correctamente formateado</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.social.bulk-import.index') }}" class="btn btn-light">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i> Iniciar Importación
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Show file name when selected
    document.getElementById('file').addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            const fileName = e.target.files[0].name;
            const fileSize = (e.target.files[0].size / 1024 / 1024).toFixed(2);

            // Check file size
            if (e.target.files[0].size > 5 * 1024 * 1024) {
                alert('El archivo es muy grande. El tamaño máximo es 5MB.');
                e.target.value = '';
                return;
            }

            console.log(`Archivo seleccionado: ${fileName} (${fileSize} MB)`);
        }
    });
</script>
@endpush
