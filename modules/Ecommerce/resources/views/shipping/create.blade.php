@extends('layouts.theme')

@section('title', 'Nuevo metodo de envio')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Nuevo metodo de envio'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.shipping.store') }}" method="POST">
        @csrf
        <div class="row g-4 align-items-start">

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Informacion del metodo</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Titulo <span class="text-danger">*</span></label>
                            <input type="text" name="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Pais</label>
                            <input type="text" name="country"
                                class="form-control @error('country') is-invalid @enderror"
                                value="{{ old('country') }}"
                                placeholder="Dejar vacio para aplicar a todos">
                            <div class="form-text">Dejar en blanco para aplicar globalmente.</div>
                            @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <button type="submit" class="btn btn-primary w-100 mb-2">Guardar metodo</button>
                            <a href="{{ route('ecommerce.shipping.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Estado</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    id="is_active" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Metodo activo</label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
@endsection
