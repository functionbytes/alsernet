@extends('layouts.theme')

@section('title', 'Editar categoria')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Editar categoria'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion de la categoria</h5>
                        <small class="text-muted">Modifica los datos de la categoria.</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $category->name) }}"
                                   required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Enlace permanente</label>
                            <div class="p-3 bg-light rounded border">
                                <small class="text-muted d-block mb-1">Vista actual</small>
                                <a href="{{ url('categorias/' . $category->slug) }}" target="_blank" class="text-break small">
                                    {{ url('categorias/' . $category->slug) }}
                                </a>
                            </div>
                            <input type="hidden" name="slug" value="{{ old('slug', $category->slug) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Padre</label>
                            <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                <option value="">Nada</option>
                                @foreach($parents as $id => $name)
                                    <option value="{{ $id }}" {{ old('parent_id', $category->parent_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="4">{{ old('description', $category->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 80px;">

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Publicar</h6>
                            <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar categoria</button>
                            <a href="{{ route('ecommerce.categories.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Estado</h6>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="published" {{ old('status', $category->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="draft" {{ old('status', $category->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Ajustes</h6>
                            <div class="mb-3">
                                <label class="form-label">Orden</label>
                                <input type="number" name="order"
                                       class="form-control @error('order') is-invalid @enderror"
                                       value="{{ old('order', $category->order) }}"
                                       min="0">
                                @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                       id="is_featured" {{ old('is_featured', $category->is_featured) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">Destacado</label>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Detalles</h6>
                            <dl class="row mb-0 small">
                                <dt class="col-5 text-muted fw-normal">Creado</dt>
                                <dd class="col-7 mb-2">{{ $category->created_at->translatedFormat('j M, Y') }}</dd>
                                <dt class="col-5 text-muted fw-normal">Actualizado</dt>
                                <dd class="col-7 mb-0">{{ $category->updated_at->translatedFormat('j M, Y') }}</dd>
                            </dl>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
@endsection
