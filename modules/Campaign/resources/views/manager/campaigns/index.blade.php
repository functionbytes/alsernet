@extends('layouts.theme')

@section('title', 'Campañas')

@section('content')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Campañas de email</h5>
                    <p class="small mb-0 text-muted">Crea y gestiona tus envíos masivos de email marketing</p>
                </div>
                @hasanypermission('campaigns.manage.all')
                    <a href="{{ route('manager.campaigns.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva campaña
                    </a>
                @endhasanypermission
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('manager.campaigns.index') }}">
                <div class="d-flex gap-3 align-items-stretch">
                    <div class="flex-fill">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" name="q" class="form-control -0 ps-0"
                                   placeholder="Buscar por nombre o asunto…"
                                   value="{{ request('q') }}">
                        </div>
                    </div>
                    <div class="flex-shrink-0" style="min-width: 200px;">
                        <select name="status" class="form-select">
                            <option value="">Todos los estados</option>
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request()->hasAny(['q', 'status']))
                            <a href="{{ route('manager.campaigns.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="card-body">
            @if ($campaigns->isEmpty())
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="mb-3 text-muted">
                            <i class="fas fa-paper-plane fs-2"></i>
                        </div>
                        <h6 class="mb-1">
                            @if(request()->hasAny(['q', 'status']))
                                No se encontraron campañas
                            @else
                                Aún no hay campañas
                            @endif
                        </h6>
                        <p class="text-muted mb-3">
                            @if(request()->hasAny(['q', 'status']))
                                Prueba con otros criterios de búsqueda
                            @else
                                Crea tu primera campaña para empezar a enviar emails
                            @endif
                        </p>
                        @if(!request()->hasAny(['q', 'status']))
                            @hasanypermission('campaigns.manage.all')
                                <a href="{{ route('manager.campaigns.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Nueva campaña
                                </a>
                            @endhasanypermission
                        @endif
                    </div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Asunto</th>
                                <th>Estado</th>
                                <th>Programada</th>
                                <th>Creada</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($campaigns as $c)
                                <tr>
                                    <td>
                                        <a href="{{ route('manager.campaigns.show', $c->uid) }}" class="fw-semibold text-decoration-none">
                                            {{ $c->name }}
                                        </a>
                                    </td>
                                    <td class="text-muted small">{{ Str::limit($c->subject, 60) }}</td>
                                    <td>
                                        @php
                                            $statusColor = match($c->status) {
                                                'done'      => 'success',
                                                'sending'   => 'primary',
                                                'scheduled' => 'info',
                                                'paused'    => 'warning',
                                                'failed'    => 'danger',
                                                default     => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                            {{ $statuses[$c->status] ?? $c->status }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">{{ $c->run_at?->format('d/m/Y H:i') ?: '—' }}</td>
                                    <td class="text-muted small">{{ $c->created_at?->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.campaigns.show', $c->uid) }}">
                                                        Ver detalle
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.campaigns.edit', $c->uid) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.campaigns.recipients', $c->uid) }}">
                                                        Destinatarios
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.campaigns.tracking.log', $c->uid) }}">
                                                        Log de envíos
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item"
                                                            onclick="if(confirm('¿Eliminar la campaña {{ addslashes($c->name) }}?')) { document.getElementById('del-{{ $c->uid }}').submit(); }">
                                                        Eliminar
                                                    </button>
                                                    <form id="del-{{ $c->uid }}" method="post"
                                                          action="{{ route('manager.campaigns.destroy', $c->uid) }}"
                                                          class="d-none">
                                                        @csrf @method('DELETE')
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($campaigns->hasPages())
            <div class="card-footer">
                {{ $campaigns->links() }}
            </div>
        @endif

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success(@json(session('success')));
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')));
    @endif
});
</script>
@endpush
