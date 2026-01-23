@extends('layouts.theme')

@section('title', 'Instalar nuevo módulo')

@section('content')

<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">
        <div class="card w-100">
            <div class="card-body">
                <div class="d-flex no-block align-items-center mb-4">
                    <h5 class="mb-0">
                        <i class="fas fa-download me-2"></i>Instalar nuevo módulo
                    </h5>
                    <a href="{{ route('settings.modules.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>

                <p class="card-subtitle mb-4">
                    Carga un archivo ZIP que contenga un módulo compatible con el sistema.
                </p>

                {{-- Alert Messages --}}
                @if ($message = Session::get('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ $message }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Upload Form Section --}}
                <form action="{{ route('settings.modules.install') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">
                            Archivo del módulo
                        </label>
                        <div class="custom-file-upload border-3 border-dashed rounded-3 p-5 text-center bg-primary-subtle position-relative"
                             style="cursor: pointer; transition: all 0.3s ease; min-height: 200px;"
                             id="fileDropZone">
                            <input type="file" id="module_file" name="module_file" accept=".zip" class="d-none" required>
                            <div class="upload-placeholder">
                                <div class="mb-3">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mx-auto" style="width: 64px; height: 64px;">
                                        <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                    </div>
                                </div>
                                <h6 class="mb-2">Arrastra tu archivo ZIP aquí</h6>
                                <p class="text-muted mb-3">o haz clic para seleccionar desde tu ordenador</p>
                                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('module_file').click();">
                                    <i class="fas fa-folder-open me-2"></i>Seleccionar archivo
                                </button>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mt-3 text-muted small">
                            <span><i class="fas fa-check-circle text-success me-1"></i>Solo archivos ZIP</span>
                            <span><i class="fas fa-check-circle text-success me-1"></i>Debe contener module.json</span>
                            <span><i class="fas fa-check-circle text-success me-1"></i>Tamaño máximo 50MB</span>
                        </div>
                        @error('module_file')
                            <div class="alert alert-danger mt-3">
                                <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Module Structure Guide --}}
                    <hr class="my-4">

                    <div class="mb-4">
                        <h6 class="mb-3 fw-bold border-bottom pb-2">
                            Estructura esperada del módulo
                        </h6>
                        <p class="text-muted mb-3">El archivo ZIP debe contener la siguiente estructura de directorios:</p>
                        <div class="bg-black text-white p-4 rounded-3">
                            <pre class="mb-0 text-white" style="font-size: 0.875rem; line-height: 1.6;"><code>ModuleName/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Providers/
├── database/
│   └── migrations/
├── resources/
│   └── views/
├── routes/
│   └── web.php
├── module.json (REQUERIDO)
├── composer.json (opcional)
└── README.md (opcional)</code></pre>
                        </div>
                    </div>

                    {{-- Module.json Template --}}
                    <div class="mb-4">
                        <h6 class="mb-3 fw-bold border-bottom pb-2">
                            Contenido mínimo de module.json
                        </h6>
                        <div class="bg-black text-white p-4 rounded-3 position-relative">
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-success">Requerido</span>
                            </div>
                            <pre class="mb-0 text-white" style="font-size: 0.875rem; line-height: 1.6;"><code>{
    "name": "MyModule",
    "alias": "mymodule",
    "description": "Descripción del módulo",
    "version": "1.0.0",
    "priority": 0,
    "providers": [
        "Modules\\MyModule\\Providers\\MyModuleServiceProvider"
    ]
}</code></pre>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-flex gap-2 align-items-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-upload me-2"></i>Instalar módulo
                        </button>
                        <a href="{{ route('settings.modules.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                    </div>
                </form>

                {{-- Instructions Section --}}
                <hr class="my-4">

                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h6 class="mb-3 fw-bold">
                            ¿Necesitas crear un módulo?
                        </h6>
                        <p class="text-muted mb-3">Puedes crear un nuevo módulo usando el comando de Artisan:</p>
                        <div class="bg-black text-white p-3 rounded-3 mb-3">
                            <code class="text-success">php artisan module:make YourModuleName</code>
                        </div>
                        <p class="small text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Para más información sobre cómo desarrollar módulos, consulta la
                            <a href="{{ route('settings.modules.index') }}" class="text-decoration-none">lista de módulos instalados</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // File drag and drop functionality
    const fileDropZone = document.getElementById('fileDropZone');
    const fileInput = document.getElementById('module_file');

    if (fileDropZone && fileInput) {
        // Click to select file (except when clicking the button)
        fileDropZone.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON' && !e.target.closest('button')) {
                fileInput.click();
            }
        });

        // Drag and drop events
        fileDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileDropZone.classList.add('border-primary');
            fileDropZone.classList.remove('border-3');
            fileDropZone.classList.add('border-4');
        });

        fileDropZone.addEventListener('dragleave', () => {
            fileDropZone.classList.remove('border-primary', 'border-4');
            fileDropZone.classList.add('border-3');
        });

        fileDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            fileDropZone.classList.remove('border-primary', 'border-4');
            fileDropZone.classList.add('border-3');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileDisplay(files[0]);
            }
        });

        // Show selected file name
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                updateFileDisplay(e.target.files[0]);
            }
        });

        function updateFileDisplay(file) {
            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB

            fileDropZone.classList.remove('bg-primary-subtle');
            fileDropZone.classList.add('bg-success-subtle', 'border-success');

            fileDropZone.innerHTML = `
                <div class="mb-3">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mx-auto" style="width: 64px; height: 64px;">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
                <h6 class="mb-2 text-success">Archivo seleccionado</h6>
                <p class="mb-1"><strong>${fileName}</strong></p>
                <p class="text-muted small mb-3">Tamaño: ${fileSize} MB</p>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="document.getElementById('module_file').click();">
                    <i class="fas fa-sync-alt me-2"></i>Cambiar archivo
                </button>
            `;
        }
    }
</script>
@endpush
