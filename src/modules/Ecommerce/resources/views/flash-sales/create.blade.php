@extends('layouts.theme')

@section('title', 'Nueva venta flash')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Nueva venta flash'])
@endsection

@section('content')
    <form action="{{ route('ecommerce.flash-sales.store') }}" method="POST">
        @csrf
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion de la promocion</h5>
                        <small class="text-muted">Complete los campos requeridos para crear la venta flash.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fecha de inicio <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="start_date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date') }}" required>
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de fin <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="end_date"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date') }}" required>
                                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
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
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar promocion</button>
                        <a href="{{ route('ecommerce.flash-sales.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Estado</h6>
                    </div>
                    <div class="card-body">
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Publicado</option>
                            <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Borrador</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection
