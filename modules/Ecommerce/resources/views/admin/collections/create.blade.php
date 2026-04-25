@extends('layouts.theme')

@section('title', 'Nueva coleccion')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Nueva coleccion'])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('ecommerce.collections.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug <span class="text-danger">*</span></label>
                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" required>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripcion</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Publicado</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('ecommerce.collections.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar coleccion</button>
                </div>
            </form>
        </div>
    </div>
@endsection
