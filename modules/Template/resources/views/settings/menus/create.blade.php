@extends('layouts.theme')

@section('title', 'Crear menu')

@section('content')

    @include('core::components.card', ['title' => 'Crear menu'])

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form action="{{ route('settings.menus.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Crear menú</h5>
                                <p class="mb-0 text-muted small">Completa la información para registrar un nuevo menú de navegación.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        Nombre del menú <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name" value="{{ old('name') }}" required
                                           class="form-control @error('name') is-invalid @enderror"
                                           placeholder="Ej: Menu principal">
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        Slug <small class="text-muted">(se genera automaticamente si se deja vacio)</small>
                                    </label>
                                    <input type="text" name="slug" value="{{ old('slug') }}"
                                           class="form-control @error('slug') is-invalid @enderror"
                                           placeholder="menu-principal">
                                    @error('slug')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Ubicacion</label>
                                    <select name="location" class="form-select">
                                        <option value="">Seleccionar ubicacion</option>
                                        @foreach($locations as $key => $label)
                                            <option value="{{ $key }}" {{ old('location') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('location')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="checkbox" name="status" value="1" id="status"
                                               {{ old('status', true) ? 'checked' : '' }} class="form-check-input">
                                        <label class="form-check-label" for="status">Activo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer border-top">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Crear menú</button>
                            <a href="{{ route('settings.menus.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
