@extends('layouts.theme')

@section('title', 'Editar grupo: ' . $group->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Editar grupo'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="groupForm" action="{{ route('manager.helpdesk.settings.ticket-groups.update', $group) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar: {{ $group->name }}</h5>
                        <small class="text-muted">Modifica las propiedades del grupo</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre y descripcion visible del grupo</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $group->name) }}"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripcion</label>
                                    <textarea name="description"
                                              class="form-control @error('description') is-invalid @enderror"
                                              rows="3">{{ old('description', $group->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Asignacion de tickets</h6>
                        <p class="text-muted small mb-3">Define como se distribuyen los tickets entre los miembros del grupo</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="assignment_mode" class="form-label">Modo de asignacion <span class="text-danger">*</span></label>
                                    <select class="form-select select2 @error('assignment_mode') is-invalid @enderror" id="assignment_mode" name="assignment_mode" required>
                                        <option value="manual" {{ old('assignment_mode', $group->assignment_mode) == 'manual' ? 'selected' : '' }}>Manual — un agente asigna tickets</option>
                                        <option value="round_robin" {{ old('assignment_mode', $group->assignment_mode) == 'round_robin' ? 'selected' : '' }}>Round robin — rotacion entre miembros</option>
                                        <option value="load_balanced" {{ old('assignment_mode', $group->assignment_mode) == 'load_balanced' ? 'selected' : '' }}>Balanceo de carga — menos tickets abiertos recibe primero</option>
                                    </select>
                                    @error('assignment_mode')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        @include('theme.views.backups.helpdesk.ticket-groups._members', [
                            'members' => collect(old('users', $group->users->pluck('id')->all()))
                                ->map(fn ($id, $i) => [
                                    'id' => (int) $id,
                                    'priority' => old(
                                        'user_priorities.'.$i,
                                        $group->users->firstWhere('id', (int) $id)?->pivot?->conversation_priority ?? 'primary',
                                    ),
                                ])
                                ->all(),
                        ])

                        <h6 class="fw-semibold mb-1">Configuracion</h6>
                        <p class="text-muted small mb-3">Prioridad y disponibilidad del grupo</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="is_default" class="form-label">Grupo por defecto</label>
                                    <select class="form-select select2 @error('is_default') is-invalid @enderror" id="is_default" name="is_default">
                                        <option value="0" {{ old('is_default', $group->is_default ? '1' : '0') == '0' ? 'selected' : '' }}>No — asignacion explicita</option>
                                        <option value="1" {{ old('is_default', $group->is_default ? '1' : '0') == '1' ? 'selected' : '' }}>Si — asignado por defecto a tickets sin grupo</option>
                                    </select>
                                    @error('is_default')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Estado</label>
                                    <select class="form-select select2 @error('is_active') is-invalid @enderror" id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $group->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Activo — disponible para asignar</option>
                                        <option value="0" {{ old('is_active', $group->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactivo — no disponible</option>
                                    </select>
                                    @error('is_active')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-groups.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre los grupos</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Los grupos permiten organizar a los agentes y definir estrategias de asignacion automatica de tickets segun carga de trabajo o rotacion.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas practicas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres que reflejen el area o nivel de soporte</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Round robin es ideal cuando todos los agentes tienen la misma carga</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Balanceo de carga evita sobrecargar a un agente especifico</li>
                        <li class="mb-0"><i class="fas fa-check-circle text-success me-2"></i> Solo un grupo puede ser el predeterminado para nuevos tickets</li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Informacion del registro</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">
                            <span class="fw-semibold">Creado:</span> {{ $group->created_at->format('d/m/Y H:i') }}
                        </li>
                        <li class="mb-0">
                            <span class="fw-semibold">Actualizado:</span> {{ $group->updated_at->format('d/m/Y H:i') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // 'select.select2' y no '.select2': el contenedor que genera select2 hereda
    // esa misma clase, asi que un selector por clase se re-inicializa sobre su
    // propio contenedor. Con el buscador de miembros (que ya se inicializa por
    // id en _members.blade.php) eso dejaba el desplegable en "No results found"
    // pese a tener las opciones cargadas.
    $('select.select2').select2({ width: '100%' });

});
</script>
@endpush
