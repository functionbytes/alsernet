@extends('layouts.theme')

@section('title', 'Nueva regla de asignación')

@section('content')

    @include('core::components.card', ['title' => 'Nueva regla de asignación'])

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">
                <form action="{{ route('settings.attention.routing-rules.store') }}" method="POST">
                    @csrf

                    <div class="card">

                        {{-- Sección: Identificación --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Identificación</h6>
                            <p class="text-muted mb-3">Nombre descriptivo y configuración general de la regla.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}"
                                           placeholder="Ej: Quejas a soporte" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Prioridad
                                        <span class="text-muted fw-normal">(1 = alta)</span>
                                    </label>
                                    <input type="number" name="priority"
                                           class="form-control @error('priority') is-invalid @enderror"
                                           value="{{ old('priority', 10) }}" min="1" max="100">
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Estado</label>
                                    <select name="is_active" class="form-select select2">
                                        <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Sección: Condiciones --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Condiciones</h6>
                            <p class="text-muted mb-3">Filtra por tipo, categoría o sede. Dejar en blanco aplica a cualquier valor de ese criterio.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Tipo de PQRSF</label>
                                    <select name="condition_type_id" class="form-select select2">
                                        <option value="">— Cualquier tipo —</option>
                                        @foreach($types as $type)
                                            <option value="{{ $type->id }}"
                                                {{ old('condition_type_id') == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Categoría</label>
                                    <select name="condition_category_id" class="form-select select2">
                                        <option value="">— Cualquier categoría —</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('condition_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Sede</label>
                                    <select name="condition_sede_id" class="form-select select2">
                                        <option value="">— Cualquier sede —</option>
                                        @foreach($sedes as $sede)
                                            <option value="{{ $sede->id }}"
                                                {{ old('condition_sede_id') == $sede->id ? 'selected' : '' }}>
                                                {{ $sede->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-0">

                        {{-- Sección: Acciones --}}
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-1">Acciones</h6>
                            <p class="text-muted mb-3">Define a quién se asignará la PQRSF cuando la regla coincida. Al menos una debe estar definida.</p>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Asignar a usuario</label>
                                    <select name="assign_to_user_id" class="form-select select2">
                                        <option value="">— Sin asignar —</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}"
                                                {{ old('assign_to_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Asignar a departamento</label>
                                    <select name="assign_to_department_id" class="form-select select2">
                                        <option value="">— Sin asignar —</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ old('assign_to_department_id') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                Crear regla
                            </button>
                            <a href="{{ route('settings.attention.routing-rules.index') }}" class="btn btn-light border w-100">
                                Cancelar
                            </a>
                        </div>

                    </div>
                </form>
            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Ver todas las reglas</h6>
                        <p class="text-muted mb-3">Consulta y gestiona las reglas de asignación ya configuradas en el sistema.</p>
                        <a href="{{ route('settings.attention.routing-rules.index') }}" class="btn btn-outline-secondary w-100">
                            Ver listado
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">¿Cómo funcionan las reglas?</h6>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-semibold mb-2">Prioridad</h6>
                        <p class="text-muted mb-3">Las reglas se evalúan en orden de prioridad (1 = más alta). La primera regla activa que coincida se aplica y las demás se ignoran.</p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Condiciones</h6>
                        <p class="text-muted mb-3">Filtra por tipo de PQRSF, categoría o sede. Dejar un campo en blanco significa que aplica a cualquier valor de ese criterio.</p>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">Acciones</h6>
                        <p class="text-muted mb-0">Define a quién se asignará la PQRSF cuando la regla coincida. Puedes asignar a un usuario, a un departamento, o a ambos.</p>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
});
</script>
@endpush
