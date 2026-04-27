@extends('layouts.theme')

@section('title', 'Nuevo grupo de especificaciones')

@section('content')
    @include('core::components.card', ['title' => 'Nuevo grupo de especificaciones'])

    <form action="{{ route('ecommerce.specification-groups.store') }}" method="POST">
        @csrf
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion del grupo</h5>
                        <small class="text-muted">Complete la informacion requerida.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
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
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar grupo</button>
                        <a href="{{ route('ecommerce.specification-groups.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection
