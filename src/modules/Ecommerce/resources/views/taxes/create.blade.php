@extends('layouts.theme')

@section('title', 'Nuevo impuesto')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Nuevo impuesto'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.taxes.store') }}" method="POST">
        @csrf
        <div class="row g-4 align-items-start">

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Informacion del impuesto</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Titulo</label>
                            <input type="text" name="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Porcentaje <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="percentage" step="0.01" min="0" max="100"
                                    class="form-control @error('percentage') is-invalid @enderror"
                                    value="{{ old('percentage') }}" required>
                                <span class="input-group-text">%</span>
                            </div>
                            @error('percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Prioridad</label>
                            <input type="number" name="priority" min="0"
                                class="form-control @error('priority') is-invalid @enderror"
                                value="{{ old('priority', 0) }}">
                            <div class="form-text">Menor numero = mayor prioridad al aplicar impuestos.</div>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">

                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Publicar</h6>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Guardar impuesto</button>
                            <a href="{{ route('ecommerce.taxes.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Estado</h6>
                        </div>
                        <div class="card-body">
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
@endsection
