@extends('layouts.theme')

@section('title', 'Editar impuesto')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Editar impuesto'])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('ecommerce.taxes.update', $tax) }}" method="POST">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Titulo</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $tax->title) }}">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Porcentaje <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="percentage" class="form-control @error('percentage') is-invalid @enderror" value="{{ old('percentage', $tax->percentage) }}" required>
                    @error('percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Prioridad</label>
                    <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror" value="{{ old('priority', $tax->priority) }}">
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="published" {{ old('status', $tax->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                        <option value="draft" {{ old('status', $tax->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('ecommerce.taxes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar impuesto</button>
                </div>
            </form>
        </div>
    </div>
@endsection
