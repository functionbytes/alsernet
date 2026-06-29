@extends('layouts.theme')

@section('title', 'Editar grupo de especificaciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar grupo de especificaciones'])
@endsection

@section('content')
    <form action="{{ route('ecommerce.specification-groups.update', $specificationGroup) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion del grupo</h5>
                        <small class="text-muted">Modifique los campos necesarios.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $specificationGroup->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $specificationGroup->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card" style="top: 80px; position: sticky;">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Publicar</h6>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar grupo</button>
                        <a href="{{ route('ecommerce.specification-groups.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Detalles</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted d-block">Creado</small>
                            <span class="small">{{ $specificationGroup->created_at->translatedFormat('j M, Y') }}</span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Actualizado</small>
                            <span class="small">{{ $specificationGroup->updated_at->translatedFormat('j M, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection
