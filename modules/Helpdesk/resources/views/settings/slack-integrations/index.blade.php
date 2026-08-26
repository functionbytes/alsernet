@extends('layouts.theme')

@section('title', 'Integraciones de Slack')

@section('page_header')
    @include('core::components.card', ['title' => 'Integraciones de Slack'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Integraciones configuradas</h5>
                        <p class="small mb-0 text-muted">Recibe notificaciones de Helpdesk directamente en tus canales de Slack</p>
                    </div>
                    <a href="{{ route('settings.helpdesk.slack-integrations.create') }}" class="btn btn-primary">
                        Nueva integracion
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Integraciones configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">Enviando notificaciones</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                <small class="text-muted">Pausadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($integrations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Canal</th>
                                    <th scope="col">Eventos configurados</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($integrations as $integration)
                                    <tr>
                                        <td>
                                            <strong>#{{ $integration->channel_name }}</strong>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach(array_slice($integration->events ?? [], 0, 3) as $event)
                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                        {{ \Modules\Helpdesk\Models\SlackIntegration::AVAILABLE_EVENTS[$event] ?? $event }}
                                                    </span>
                                                @endforeach
                                                @if(count($integration->events ?? []) > 3)
                                                    <span class="badge bg-light text-muted">
                                                        +{{ count($integration->events) - 3 }} mas
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($integration->is_active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.slack-integrations.edit', $integration) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-toggle"
                                                            data-url="{{ route('settings.helpdesk.slack-integrations.toggle', $integration) }}"
                                                            data-active="{{ $integration->is_active ? '1' : '0' }}">
                                                            {{ $integration->is_active ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $integration->id }}"
                                                            data-url="{{ route('settings.helpdesk.slack-integrations.destroy', $integration) }}"
                                                            data-name="#{{ $integration->channel_name }}">
                                                            Eliminar
                                                        </button>
                                                    </li>
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fab fa-slack fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay integraciones de Slack</h6>
                            <p class="text-muted mb-3">Conecta Slack para recibir alertas de SLA, sentimiento y CSAT en tus canales</p>
                            <a href="{{ route('settings.helpdesk.slack-integrations.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Crear primera integracion
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            @if($integrations->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $integrations->firstItem() }}</strong> a <strong>{{ $integrations->lastItem() }}</strong>
                            de <strong>{{ $integrations->total() }}</strong> integraciones
                        </div>
                        {{ $integrations->appends(request()->input())->links() }}
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Toggle form (hidden) --}}
    <form id="toggleForm" method="POST" class="d-none">
        @csrf
        @method('POST')
    </form>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#deleteForm').attr('action', url);
        $('#deleteItemName').text(name);
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.btn-toggle', function () {
        const url = $(this).data('url');
        $('#toggleForm').attr('action', url).submit();
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
