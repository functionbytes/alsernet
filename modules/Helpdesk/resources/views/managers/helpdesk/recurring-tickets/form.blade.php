@extends('layouts.helpdesk')

@section('title', ($recurringTicket ? 'Editar' : 'Nuevo') . ' ticket recurrente - Helpdesk')

@section('content')
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-3">
                        {{ $recurringTicket ? 'Editar ticket recurrente' : 'Nuevo ticket recurrente' }}
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('recurring-tickets.index') }}">Tickets recurrentes</a>
                            </li>
                            <li class="breadcrumb-item active">
                                {{ $recurringTicket ? 'Editar' : 'Nuevo' }}
                            </li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('recurring-tickets.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ $recurringTicket ? route('recurring-tickets.update', $recurringTicket) : route('recurring-tickets.store') }}"
          method="POST">
        @csrf
        @if($recurringTicket)
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Configuracion del ticket
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                Nombre del schedule <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name"
                                   value="{{ old('name', $recurringTicket?->name) }}"
                                   placeholder="Ej. Revision mensual de servidores" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Subject --}}
                        <div class="mb-3">
                            <label for="subject" class="form-label fw-semibold">
                                Asunto del ticket <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                   id="subject" name="subject"
                                   value="{{ old('subject', $recurringTicket?->subject) }}"
                                   placeholder="Asunto que tendra cada ticket generado" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Descripcion</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="5"
                                      placeholder="Descripcion que tendra cada ticket generado...">{{ old('description', $recurringTicket?->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Frequency --}}
                        <div class="mb-3">
                            <label for="frequency" class="form-label fw-semibold">
                                Frecuencia <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('frequency') is-invalid @enderror"
                                    id="frequency" name="frequency" required>
                                <option value="">Seleccionar frecuencia...</option>
                                @foreach(['daily' => 'Diario', 'weekly' => 'Semanal', 'monthly' => 'Mensual', 'custom' => 'Personalizado (cron)'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('frequency', $recurringTicket?->frequency) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('frequency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Cron Expression (only for custom) --}}
                        <div class="mb-3" id="cron-expression-group"
                             style="{{ old('frequency', $recurringTicket?->frequency) === 'custom' ? '' : 'display:none' }}">
                            <label for="cron_expression" class="form-label fw-semibold">Expresion cron</label>
                            <input type="text" class="form-control font-monospace @error('cron_expression') is-invalid @enderror"
                                   id="cron_expression" name="cron_expression"
                                   value="{{ old('cron_expression', $recurringTicket?->cron_expression) }}"
                                   placeholder="*/15 * * * *">
                            <div class="form-text">Formato cron estandar: minuto hora dia mes dia_semana</div>
                            @error('cron_expression')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Next Run At --}}
                        <div class="mb-3">
                            <label for="next_run_at" class="form-label fw-semibold">Primera ejecucion</label>
                            <input type="datetime-local" class="form-control @error('next_run_at') is-invalid @enderror"
                                   id="next_run_at" name="next_run_at"
                                   value="{{ old('next_run_at', $recurringTicket?->next_run_at?->format('Y-m-d\TH:i')) }}">
                            <div class="form-text">Deja en blanco para ejecutar en el proximo ciclo del scheduler.</div>
                            @error('next_run_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Configuracion
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Category --}}
                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-semibold">Categoria</label>
                            <select class="form-select @error('category_id') is-invalid @enderror"
                                    id="category_id" name="category_id">
                                <option value="">Sin categoria</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id', $recurringTicket?->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Priority --}}
                        <div class="mb-3">
                            <label for="priority_id" class="form-label fw-semibold">Prioridad</label>
                            <select class="form-select @error('priority_id') is-invalid @enderror"
                                    id="priority_id" name="priority_id">
                                <option value="">Sin prioridad</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->id }}"
                                        {{ old('priority_id', $recurringTicket?->priority_id) == $priority->id ? 'selected' : '' }}>
                                        {{ $priority->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('priority_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Assignee --}}
                        <div class="mb-3">
                            <label for="assignee_id" class="form-label fw-semibold">Agente asignado</label>
                            <select class="form-select @error('assignee_id') is-invalid @enderror"
                                    id="assignee_id" name="assignee_id">
                                <option value="">Sin asignar</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}"
                                        {{ old('assignee_id', $recurringTicket?->assignee_id) == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assignee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Active --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $recurringTicket?->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Schedule activo</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        {{ $recurringTicket ? 'Actualizar schedule' : 'Crear schedule' }}
                    </button>
                    <a href="{{ route('recurring-tickets.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.getElementById('frequency').addEventListener('change', function () {
        const cronGroup = document.getElementById('cron-expression-group');
        cronGroup.style.display = this.value === 'custom' ? '' : 'none';
    });
</script>
@endpush
