@extends('layouts.theme')

@section('title', 'Bandejas — ' . ($channelLabels[$type] ?? $type))

@push('styles')
<style>.hd-inbox-icon { width: 36px; height: 36px; background: var(--bs-secondary-bg); color: var(--bs-secondary-color); }</style>
@endpush

@section('content')

@include('core::components.alerts')

<div class="card">
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('settings.helpdesk.inboxes.index') }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex-grow-1">
                <h5 class="mb-1 fw-bold">
                    <i class="{{ $channelIcons[$type] ?? 'fas fa-inbox' }} me-2"></i>Bandejas de {{ $channelLabels[$type] ?? $type }}
                </h5>
                <p class="small mb-0 text-muted">Gestiona las bandejas configuradas para este canal</p>
            </div>
            <a href="{{ route('settings.helpdesk.inboxes.create', ['channel' => $type]) }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nueva bandeja
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        @if($inboxes->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted"></i>
                <h6 class="mt-3 mb-1">No hay bandejas configuradas</h6>
                <p class="small text-muted mb-3">Crea tu primera bandeja para empezar a recibir mensajes</p>
                <a href="{{ route('settings.helpdesk.inboxes.create', ['channel' => $type]) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Crear bandeja
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Bandeja</th>
                            <th scope="col">Canal</th>
                            <th scope="col">Asignación por defecto</th>
                            <th scope="col" class="text-center">Conversaciones</th>
                            <th scope="col" class="text-center">Estado</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inboxes as $inbox)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="hd-inbox-icon rounded d-flex align-items-center justify-content-center"
                                             @if($inbox->color) data-color="{{ $inbox->color }}" @endif>
                                            <i class="{{ $inbox->channel_icon ?? $channelIcons[$inbox->channel_type] ?? 'fas fa-inbox' }}"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $inbox->name }}</div>
                                            @if($inbox->description)
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($inbox->description, 60) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="{{ $channelIcons[$inbox->channel_type] ?? 'fas fa-inbox' }} me-1"></i>
                                        {{ $channelLabels[$inbox->channel_type] ?? $inbox->channel_type }}
                                    </span>
                                </td>
                                <td>
                                    @if($inbox->defaultAssignee)
                                        <small><i class="far fa-user me-1"></i>{{ $inbox->defaultAssignee->firstname }} {{ $inbox->defaultAssignee->lastname }}</small>
                                    @elseif($inbox->defaultGroup)
                                        <small><i class="far fa-users me-1"></i>{{ $inbox->defaultGroup->name }}</small>
                                    @else
                                        <small class="text-muted">Sin asignar</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light-secondary">{{ $inbox->conversations_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if($inbox->is_active)
                                        <span class="badge bg-success">Activa</span>
                                    @else
                                        <span class="badge bg-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.helpdesk.inboxes.edit', $inbox) }}">
                                                    Editar
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('settings.helpdesk.inboxes.toggle', $inbox) }}" method="POST" class="d-inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        {{ $inbox->is_active ? 'Desactivar' : 'Activar' }}
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('settings.helpdesk.inboxes.destroy', $inbox) }}" method="POST"
                                                      class="needs-confirm"
                                                      data-confirm-msg="¿Eliminar este inbox? Las conversaciones existentes quedarán sin bandeja asignada.">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item">Eliminar</button>
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
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.hd-inbox-icon[data-color]').each(function () {
        $(this).css({ background: $(this).data('color'), color: '#fff' });
    });
});
</script>
@endpush
