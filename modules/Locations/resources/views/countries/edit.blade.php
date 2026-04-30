@extends('layouts.theme')

@section('title', 'Editar país')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar país'])
@endsection

@section('content')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('locations.countries.update', $country) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $country->flag_emoji }} {{ $country->name }}</h5>
                        <small class="text-muted">Actualiza la información del país.</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name', $country->name) }}"
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
                                       value="{{ old('code', $country->code) }}"
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
                                       value="{{ old('phone_code', $country->phone_code) }}"
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
                                       value="{{ old('currency_code', $country->currency_code) }}"
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
                                       value="{{ old('currency_symbol', $country->currency_symbol) }}"
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
                                       value="{{ old('flag_emoji', $country->flag_emoji) }}"
                                       placeholder="🇲🇽">
                                @error('flag_emoji')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="is_active">
                                    <option value="1" {{ old('is_active', $country->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('is_active', $country->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Orden</label>
                                <input type="number"
                                       class="form-control @error('order') is-invalid @enderror"
                                       name="order"
                                       value="{{ old('order', $country->order) }}"
                                       min="0">
                                @error('order')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Actualizar país</button>
                        <a href="{{ route('locations.countries.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Información del registro</h6>
                    <dl class="mb-0">
                        <dt class="small text-muted">ID</dt>
                        <dd class="mb-2">#{{ $country->id }}</dd>
                        <dt class="small text-muted">Creado</dt>
                        <dd class="mb-2">{{ $country->created_at->format('d/m/Y H:i') }}</dd>
                        <dt class="small text-muted">Última actualización</dt>
                        <dd class="mb-0">{{ $country->updated_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

    </div>

@endsection
