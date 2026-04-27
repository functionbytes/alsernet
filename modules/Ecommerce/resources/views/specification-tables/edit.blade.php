@extends('layouts.theme')

@section('title', 'Editar tabla de especificaciones')

@section('content')
    @include('core::components.card', ['title' => 'Editar tabla de especificaciones'])

    <form action="{{ route('ecommerce.specification-tables.update', $specificationTable) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-4 align-items-start">

            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Informacion de la tabla</h5>
                        <small class="text-muted">Modifique los campos necesarios.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $specificationTable->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $specificationTable->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                @if($allGroups->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header border-bottom p-3">
                            <h5 class="mb-0 fw-bold">Grupos incluidos</h5>
                            <small class="text-muted">Seleccione los grupos de especificaciones a incluir.</small>
                        </div>
                        <div class="card-body">
                            @error('groups')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
                            @php $selectedGroupIds = $specificationTable->groups->pluck('id')->toArray(); @endphp
                            @foreach($allGroups as $group)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="groups[]" value="{{ $group->id }}" id="group_{{ $group->id }}"
                                        {{ in_array($group->id, old('groups', $selectedGroupIds)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="group_{{ $group->id }}">
                                        {{ $group->name }}
                                        <span class="text-muted small">({{ $group->attributes_count ?? $group->attributes()->count() }} atributos)</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-12 col-lg-4">
                <div class="card" style="top: 80px; position: sticky;">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Publicar</h6>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar tabla</button>
                        <a href="{{ route('ecommerce.specification-tables.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Detalles</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted d-block">Creado</small>
                            <span class="small">{{ $specificationTable->created_at->translatedFormat('j M, Y') }}</span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Actualizado</small>
                            <span class="small">{{ $specificationTable->updated_at->translatedFormat('j M, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection
