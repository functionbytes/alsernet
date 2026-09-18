@extends('layouts.theme')

@section('title', 'Sesiones — ' . $chatFlow->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Sesiones del flow'])
@endsection

@section('content')

    @include('core::components.alerts')

    {{-- Breadcrumb info --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('chatflow.index') }}">Chat flows</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('chatflow.edit', $chatFlow) }}">{{ $chatFlow->name }}</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Sesiones</li>
        </ol>
    </nav>

    {{-- Flow summary --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="flex-fill">
                    <h5 class="fw-bold mb-1">{{ $chatFlow->name }}</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        @php
                            $triggerLabel = match($chatFlow->trigger_type) {
                                'conversation_start' => 'Inicio conv.',
                                'keyword'            => 'Keyword',
                                'manual'             => 'Manual',
                                'no_agent'           => 'Sin agente',
                                default              => $chatFlow->trigger_type,
                            };
                            $statusColor = match($chatFlow->status) {
                                'active'   => 'success',
                                'archived' => 'dark',
                                default    => 'secondary',
                            };
                            $statusLabel = match($chatFlow->status) {
                                'active'   => 'Activo',
                                'archived' => 'Archivado',
                                default    => 'Borrador',
                            };
                        @endphp
                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $statusLabel }}</span>
                        <span class="badge bg-info-subtle text-info">{{ $triggerLabel }}</span>
                        @if($chatFlow->inbox)
                            <span class="badge bg-light text-dark border">{{ $chatFlow->inbox->name }}</span>
                        @else
                            <span class="badge bg-light text-dark border">Todos los inboxes</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('chatflow.edit', $chatFlow) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-pen me-1"></i> Editar flow
                </a>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Total sesiones</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($sessions->total()) }}</h4>
                    <small class="text-muted">Registradas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Completadas</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($stats['completed'] ?? 0) }}</h4>
                    <small class="text-muted">Con exito</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Activas</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($stats['active'] ?? 0) }}</h4>
                    <small class="text-muted">En progreso</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-light-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">Abandonadas</h6>
                    <h4 class="mb-1 fw-bold">{{ number_format($stats['abandoned'] ?? 0) }}</h4>
                    <small class="text-muted">Sin completar</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Sessions table --}}
    <div class="card">

        {{-- Filters --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('chatflow.sessions', $chatFlow) }}">
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <select name="session_status" class="form-select w-auto flex-shrink-0">
                        <option value="">Todos los estados</option>
                        <option value="active"      {{ request('session_status') === 'active'      ? 'selected' : '' }}>Activa</option>
                        <option value="completed"   {{ request('session_status') === 'completed'   ? 'selected' : '' }}>Completada</option>
                        <option value="failed"      {{ request('session_status') === 'failed'      ? 'selected' : '' }}>Fallida</option>
                        <option value="transferred" {{ request('session_status') === 'transferred' ? 'selected' : '' }}>Transferida</option>
                        <option value="abandoned"   {{ request('session_status') === 'abandoned'   ? 'selected' : '' }}>Abandonada</option>
                    </select>
                    <button type="submit" class="btn btn-primary flex-shrink-0">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request('session_status'))
                        <a href="{{ route('chatflow.sessions', $chatFlow) }}" class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($sessions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Conversacion</th>
                                <th>Canal</th>
                                <th>Trigger</th>
                                <th>Estado</th>
                                <th>Duracion</th>
                                <th>Inicio</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sessions as $session)
                                @php
                                    $sessionColor = match($session->status) {
                                        'active'      => 'primary',
                                        'completed'   => 'success',
                                        'failed'      => 'danger',
                                        'transferred' => 'warning',
                                        default       => 'secondary',
                                    };
                                    $sessionLabel = match($session->status) {
                                        'active'      => 'Activa',
                                        'completed'   => 'Completada',
                                        'failed'      => 'Fallida',
                                        'transferred' => 'Transferida',
                                        default       => 'Abandonada',
                                    };
                                    $duration = null;
                                    if ($session->started_at && $session->ended_at) {
                                        $seconds  = $session->started_at->diffInSeconds($session->ended_at);
                                        $minutes  = floor($seconds / 60);
                                        $duration = $minutes > 0 ? "{$minutes}m " . ($seconds % 60) . "s" : "{$seconds}s";
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold small">
                                            #{{ $session->conversation_id ?? $session->id }}
                                        </div>
                                        @if($session->contact_name ?? $session->conversation?->contact?->name)
                                            <div class="text-muted small">
                                                {{ $session->contact_name ?? $session->conversation?->contact?->name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $session->channel ?? $session->conversation?->channel ?? '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $session->trigger ?? $chatFlow->trigger_type }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $sessionColor }}-subtle text-{{ $sessionColor }}">
                                            {{ $sessionLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $duration ?? '—' }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ ($session->started_at ?? $session->created_at)?->format('d/m/Y H:i') ?? '—' }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('chatflow.sessions.replay', [$chatFlow, $session]) }}">
                                                        Reproducir
                                                    </a>
                                                </li>
                                                @if($session->conversation_id)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('helpdesk.conversations.show', $session->conversation_id) }}">
                                                            Ver conversación
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">Sin sesiones registradas</h5>
                    <p class="text-muted mb-0">
                        @if(request('session_status'))
                            No hay sesiones con el estado seleccionado
                        @else
                            Aun no se han iniciado conversaciones con este flow
                        @endif
                    </p>
                    @if(request('session_status'))
                        <a href="{{ route('chatflow.sessions', $chatFlow) }}" class="btn btn-secondary mt-3">Limpiar filtros</a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Pagination --}}
        @if($sessions->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Mostrando {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} de {{ $sessions->total() }}
                    </div>
                    <div>
                        {{ $sessions->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        @endif

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success(@json(session('success')), 'Exito');
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')), 'Error');
    @endif
});
</script>
@endpush
