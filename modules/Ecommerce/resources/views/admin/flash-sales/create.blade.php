@extends('layouts.theme')

@section('title', 'Nueva venta flash')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Nueva venta flash'])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('ecommerce.flash-sales.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha inicio <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha fin <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
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
                    <a href="{{ route('ecommerce.flash-sales.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar venta flash</button>
                </div>
            </form>
        </div>
    </div>
@endsection
