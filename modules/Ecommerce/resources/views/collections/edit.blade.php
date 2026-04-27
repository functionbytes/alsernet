@extends('layouts.theme')

@section('title', 'Editar coleccion')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Editar coleccion'])

    <form action="{{ route('ecommerce.collections.update', $collection) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion de la coleccion</h5>
                        <small class="text-muted">Modifica los datos de la coleccion.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $collection->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Enlace permanente</label>
                            <div class="p-3 bg-light rounded border">
                                <span class="text-muted small">{{ rtrim(url('/'), '/') }}/colecciones/{{ $collection->slug }}</span>
                                <a href="{{ url('colecciones/' . $collection->slug) }}" target="_blank" class="ms-2 small">
                                    Vista <i class="fas fa-external-link-alt fa-xs"></i>
                                </a>
                            </div>
                            <input type="hidden" name="slug" value="{{ old('slug', $collection->slug) }}">
                            @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" rows="4"
                                class="form-control @error('description') is-invalid @enderror">{{ old('description', $collection->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4" style="position: sticky; top: 80px;">
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Publicar</h6>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar coleccion</button>
                        <a href="{{ route('ecommerce.collections.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Estado</h6>
                    </div>
                    <div class="card-body">
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="published" {{ old('status', $collection->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                            <option value="draft" {{ old('status', $collection->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Detalles</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Creado</span>
                            <span class="small">{{ $collection->created_at->translatedFormat('j M, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Actualizado</span>
                            <span class="small">{{ $collection->updated_at->translatedFormat('j M, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection
