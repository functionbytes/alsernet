@extends('layouts.theme')

@section('title', 'Instalar nuevo módulo')

@section('content')

    @include('core::components.card', ['title' => 'Instalar nuevo módulo'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">
                <form action="{{ route('settings.modules.install') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card">
                        <div class="card-body">

                            <h6 class="fw-bold text-dark mb-1">Archivo del módulo</h6>
                            <p class="text-muted mb-3">Carga un archivo ZIP que contenga un módulo compatible con el sistema.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">
                                        Archivo ZIP <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" id="module_file" name="module_file" accept=".zip"
                                           class="form-control @error('module_file') is-invalid @enderror" required>
                                    <small class="text-muted d-block mt-1">Solo archivos .zip · Debe contener module.json · Máximo 50 MB</small>
                                    @error('module_file')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <hr class="my-0">

                        {{-- Estructura esperada --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Estructura esperada del módulo</h6>
                            <p class="text-muted mb-3">El archivo ZIP debe contener la siguiente estructura de directorios:</p>
                            <div class="alert alert-info mb-0 p-4">
                                <pre class="mb-0 small"><code>ModuleName/
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

                        <hr class="my-0">

                        {{-- module.json --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Contenido mínimo de module.json</h6>
                            <p class="text-muted mb-3">El archivo <code>module.json</code> es requerido para instalar un módulo correctamente.</p>
                            <div class="alert alert-info mb-0 p-4 position-relative">
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-success">Requerido</span>
                                </div>
                                <pre class="mb-0 small"><code>{
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

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                Instalar módulo
                            </button>
                        </div>
                    </div>

                </form>
            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Módulos instalados</h6>
                        <p class="text-muted mb-3">Consulta y gestiona los módulos actualmente instalados en el sistema.</p>
                        <a href="{{ route('settings.modules.index') }}" class="btn btn-outline-secondary w-100">
                            Volver a módulos
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">¿Necesitas crear un módulo?</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Puedes crear un nuevo módulo usando el comando de Artisan:</p>
                        <div class="alert alert-info mb-3">
                            <code>php artisan module:make YourModuleName</code>
                        </div>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Para más información, consulta la
                            <a href="{{ route('settings.modules.index') }}" class="text-decoration-none">lista de módulos instalados</a>.
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

