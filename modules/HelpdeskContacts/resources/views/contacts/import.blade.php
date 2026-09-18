@extends('layouts.theme')

@section('title', 'Importar contactos')

@section('page_header')
    @include('core::components.card', ['title' => 'Importar contactos'])
@endsection

@section('content')
<div class="row g-3">

    <div class="col-12 col-lg-8">
        <div class="card">
            <form action="{{ route('contacts.import.process') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Subir archivo CSV</h5>
                    <small class="text-muted">Importa múltiples contactos de una sola vez desde un archivo CSV.</small>
                </div>

                <div class="card-body">
                    @include('core::components.alerts')

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="file" class="form-label fw-semibold">
                            Archivo CSV <span class="text-danger">*</span>
                        </label>
                        <input
                            type="file"
                            id="file"
                            name="file"
                            class="form-control @error('file') is-invalid @enderror"
                            accept=".csv,.txt"
                        >
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Formatos aceptados: CSV o TXT. Tamaño máximo: 5 MB.</div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-upload me-1"></i> Importar contactos
                    </button>
                    <a href="{{ route('contacts.index') }}" class="btn btn-light w-100">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="fas fa-circle-info me-1 text-primary"></i>
                    Formato del CSV
                </h6>
                <p class="card-text text-muted small">
                    El archivo debe incluir una fila de encabezado con los nombres de columna.
                    Se aceptan los siguientes nombres (en inglés o español):
                </p>
                <table class="table table-sm table-bordered small">
                    <thead class="table-light">
                        <tr>
                            <th>Columna (EN)</th>
                            <th>Columna (ES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>name</td><td>nombre</td></tr>
                        <tr><td>email</td><td>correo</td></tr>
                        <tr><td>phone</td><td>telefono</td></tr>
                        <tr><td>whatsapp_phone</td><td>whatsapp</td></tr>
                    </tbody>
                </table>
                <p class="card-text text-muted small mb-1">
                    <strong>Lógica de importación:</strong>
                </p>
                <ul class="text-muted small ps-3">
                    <li>Si el email coincide con un contacto existente, se actualizan nombre, teléfono y WhatsApp.</li>
                    <li>Si no hay coincidencia, se crea un contacto nuevo.</li>
                    <li>Las filas sin nombre ni email se omiten.</li>
                    <li><code>whatsapp_phone</code> debe tener formato internacional válido (ej. <code>+34600000001</code>); si no, se importa el contacto igual pero sin ese número, y solo entonces se habilita "Enviar plantilla".</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title mb-2">
                    <i class="fas fa-download me-1 text-secondary"></i>
                    Plantilla de ejemplo
                </h6>
                <p class="card-text text-muted small">Estructura mínima del CSV:</p>
                <pre class="bg-light rounded p-2 small mb-0">name,email,phone,whatsapp_phone
Juan García,juan@ejemplo.com,+34 600 000 001,+34600000001
Ana López,ana@ejemplo.com,,</pre>
            </div>
        </div>
    </div>

</div>
@endsection
