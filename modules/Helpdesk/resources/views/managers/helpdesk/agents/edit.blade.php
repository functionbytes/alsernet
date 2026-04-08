@extends('layouts.theme')

@section('title', 'Editar agente - ' . ($agent->firstname ?? '') . ' ' . ($agent->lastname ?? ''))

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-edit me-2 text-primary"></i>
                Configuración del agente
            </h4>
            <p class="text-muted mb-0">
                <a href="{{ route('manager.helpdesk.agents.index') }}" class="text-muted text-decoration-none">Agentes</a>
                <i class="fas fa-chevron-right mx-1" style="font-size:.65rem;"></i>
                <a href="{{ route('manager.helpdesk.agents.show', $agent) }}" class="text-muted text-decoration-none">
                    {{ $agent->firstname }} {{ $agent->lastname }}
                </a>
                <i class="fas fa-chevron-right mx-1" style="font-size:.65rem;"></i>
                Editar
            </p>
        </div>
    </div>

    @include('core::components.alerts')

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-sliders-h me-2 text-muted"></i>
                        Disponibilidad y límites
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('manager.helpdesk.agents.update', $agent) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Accepts conversations --}}
                        <div class="mb-4">
                            <label for="accepts_conversations" class="form-label fw-semibold">
                                Disponibilidad
                            </label>
                            <select name="accepts_conversations" id="accepts_conversations"
                                    class="form-select @error('accepts_conversations') is-invalid @enderror">
                                <option value="yes" @selected(($agentSettings->accepts_conversations ?? 'yes') === 'yes')>
                                    Siempre disponible
                                </option>
                                <option value="working_hours" @selected(($agentSettings->accepts_conversations ?? '') === 'working_hours')>
                                    Solo en horario laboral
                                </option>
                                <option value="no" @selected(($agentSettings->accepts_conversations ?? '') === 'no')>
                                    No disponible
                                </option>
                            </select>
                            @error('accepts_conversations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">
                                Controla si el agente recibe nuevas conversaciones asignadas automáticamente.
                            </div>
                        </div>

                        {{-- Max concurrent conversations --}}
                        <div class="mb-4">
                            <label for="max_concurrent_conversations" class="form-label fw-semibold">
                                Máximo de conversaciones simultáneas
                            </label>
                            <input type="number" name="max_concurrent_conversations" id="max_concurrent_conversations"
                                   class="form-control @error('max_concurrent_conversations') is-invalid @enderror"
                                   min="1" max="100"
                                   value="{{ old('max_concurrent_conversations', $agentSettings->max_concurrent_conversations ?? 5) }}">
                            @error('max_concurrent_conversations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">
                                Número máximo de conversaciones abiertas asignadas a este agente al mismo tiempo.
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar cambios
                            </button>
                            <a href="{{ route('manager.helpdesk.agents.show', $agent) }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
