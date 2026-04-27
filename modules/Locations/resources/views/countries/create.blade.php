@extends('layouts.theme')

@section('title', 'Nuevo país')

@section('content')

    @include('core::components.card', ['title' => 'Nuevo país'])

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('locations.countries.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo país</h5>
                        <small class="text-muted">Complete la información del país.</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: México, Colombia, España"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Código <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('code') is-invalid @enderror"
                                       name="code"
                                       value="{{ old('code') }}"
                                       placeholder="Ej: MX, US, CO"
                                       maxlength="10">
                                @error('code')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Código telefónico</label>
                                <input type="text"
                                       class="form-control @error('phone_code') is-invalid @enderror"
                                       name="phone_code"
                                       value="{{ old('phone_code') }}"
                                       placeholder="+52">
                                @error('phone_code')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Código de moneda</label>
                                <input type="text"
                                       class="form-control @error('currency_code') is-invalid @enderror"
                                       name="currency_code"
                                       value="{{ old('currency_code') }}"
                                       placeholder="USD, MXN">
                                @error('currency_code')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Símbolo de moneda</label>
                                <input type="text"
                                       class="form-control @error('currency_symbol') is-invalid @enderror"
                                       name="currency_symbol"
                                       value="{{ old('currency_symbol') }}"
                                       placeholder="$, €">
                                @error('currency_symbol')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Bandera (emoji)</label>
                                <input type="text"
                                       class="form-control @error('flag_emoji') is-invalid @enderror"
                                       name="flag_emoji"
                                       value="{{ old('flag_emoji') }}"
                                       placeholder="🇲🇽">
                                @error('flag_emoji')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="is_active">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Orden</label>
                                <input type="number"
                                       class="form-control @error('order') is-invalid @enderror"
                                       name="order"
                                       value="{{ old('order', 0) }}"
                                       min="0">
                                @error('order')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar país</button>
                        <a href="{{ route('locations.countries.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los países</h6>
                    <p class="card-text text-muted">
                        Los países son la base de la jerarquía geográfica. Cada país puede tener múltiples estados o provincias, y estos a su vez ciudades.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Campos requeridos</h6>
                    <p class="card-text text-muted mb-0">
                        Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
