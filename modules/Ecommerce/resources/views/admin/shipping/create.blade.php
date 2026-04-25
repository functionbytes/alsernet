@extends('layouts.theme')

@section('title', 'Nuevo metodo de envio')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Nuevo metodo de envio'])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('ecommerce.shipping.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Titulo <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Pais</label>
                    <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country') }}">
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('ecommerce.shipping.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar metodo</button>
                </div>
            </form>
        </div>
    </div>
@endsection
