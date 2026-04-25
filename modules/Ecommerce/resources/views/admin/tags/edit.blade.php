@extends('layouts.theme')

@section('title', 'Editar etiqueta')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Editar etiqueta'])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('ecommerce.tags.update', $tag) }}" method="POST">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $tag->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripcion</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $tag->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="published" {{ old('status', $tag->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                        <option value="draft" {{ old('status', $tag->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('ecommerce.tags.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar etiqueta</button>
                </div>
            </form>
        </div>
    </div>
@endsection
