@extends('layouts.theme')

@section('title', 'Editar marca')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Editar marca'])
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.brands.update', $brand) }}" method="POST">
        @csrf @method('PUT')

        <div class="row g-4 align-items-start">

            {{-- Columna principal --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Información de la marca</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="brand-name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $brand->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if($brand->slug)
                            <div class="mb-3 p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted fw-semibold text-uppercase" style="letter-spacing:.4px">Enlace permanente</small>
                                    <a href="{{ url('marcas/' . $brand->slug) }}" target="_blank" class="small text-decoration-none">
                                        Vista <i class="fas fa-external-link-alt ms-1"></i>
                                    </a>
                                </div>
                                <div class="mt-1 small text-break">
                                    <span class="text-muted">{{ rtrim(url('/'), '/') }}/marcas/</span><strong>{{ $brand->slug }}</strong>
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                rows="4">{{ old('description', $brand->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sitio web</label>
                            <input type="url" name="website"
                                class="form-control @error('website') is-invalid @enderror"
                                value="{{ old('website', $brand->website) }}"
                                placeholder="https://ejemplo.com">
                            @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Logo (URL)</label>
                            <input type="text" name="logo"
                                class="form-control @error('logo') is-invalid @enderror"
                                value="{{ old('logo', $brand->logo) }}"
                                placeholder="https://ejemplo.com/logo.png">
                            @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">

                    {{-- Publicar --}}
                    <div class="card mb-4">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar marca</button>
                            <a href="{{ route('ecommerce.brands.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    {{-- Estado --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Estado</h6>
                        </div>
                        <div class="card-body">
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="published" {{ old('status', $brand->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="draft" {{ old('status', $brand->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Ajustes --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Ajustes</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Orden</label>
                                <input type="number" name="order"
                                    class="form-control @error('order') is-invalid @enderror"
                                    value="{{ old('order', $brand->order) }}" min="0">
                                @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                    id="is_featured" {{ old('is_featured', $brand->is_featured) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">Destacado</label>
                            </div>
                        </div>
                    </div>

                    {{-- Detalles --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Detalles</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-3 py-2 d-flex justify-content-between small">
                                    <span class="text-muted">Creado</span>
                                    <span>{{ $brand->created_at->translatedFormat('j M, Y') }}</span>
                                </li>
                                <li class="list-group-item px-3 py-2 d-flex justify-content-between small">
                                    <span class="text-muted">Actualizado</span>
                                    <span>{{ $brand->updated_at->translatedFormat('j M, Y') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
@endsection
