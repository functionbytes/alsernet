@extends('layouts.theme')

@section('title', 'Importar categorias')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Importar categorias'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-3 border-bottom">
            <h5 class="mb-0 fw-bold">Importar categorias</h5>
            <small class="text-muted">Sube un archivo Excel o CSV para crear o actualizar categorias de productos en masa</small>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <form action="{{ route('ecommerce.categories.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Archivo <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Formatos aceptados: .xlsx, .xls, .csv — Maximo 10MB</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Importar</button>
                            <a href="{{ route('ecommerce.categories.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">Formato del archivo</h6>
                            <p class="small mb-2">El archivo debe contener las siguientes columnas en la primera fila (encabezados):</p>
                            <ul class="small mb-2">
                                <li><strong>nombre</strong> — Nombre de la categoria (requerido)</li>
                                <li><strong>descripcion</strong> — Descripcion (opcional)</li>
                                <li><strong>orden</strong> — Orden de aparicion (opcional)</li>
                            </ul>
                            <p class="small text-muted mb-0">Las categorias existentes con el mismo nombre seran actualizadas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
