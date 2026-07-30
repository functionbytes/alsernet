@extends('layouts.theme')

@section('title', 'Broadcasts')

@section('page_header')
    @include('core::components.card', ['title' => 'Broadcasts'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Broadcasts</h5>
                    <p class="small mb-0 text-muted">Envios masivos de mensajes a segmentos de contactos</p>
                </div>
                @can('helpdesk.broadcasts.manage')
                    <a href="{{ route('settings.helpdesk.broadcasts.create') }}" class="btn btn-primary">
                        Nuevo broadcast
                    </a>
                @endcan
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-2">Total</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                            <small class="text-muted">Broadcasts creados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-secondary mb-2">Borrador</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['draft']) }}</h4>
                            <small class="text-muted">Pendientes de envio</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-success mb-2">Enviados</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['sent']) }}</h4>
                            <small class="text-muted">Completados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-danger mb-2">Fallidos</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['failed']) }}</h4>
                            <small class="text-muted">Con errores</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-0">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-info mb-2">Destinatarios totales</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['recipients_total']) }}</h4>
                            <small class="text-muted">Acumulado de todos los envios</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('settings.helpdesk.broadcasts.index') }}">
                <div class="row align-items-center g-2">
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
                            <option value="scheduled" @selected(request('status') === 'scheduled')>Programado</option>
                            <option value="sending" @selected(request('status') === 'sending')>Enviando</option>
                            <option value="sent" @selected(request('status') === 'sent')>Enviado</option>
                            <option value="failed" @selected(request('status') === 'failed')>Fallido</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="channel" class="form-select">
                            <option value="">Todos los canales</option>
                            <option value="whatsapp" @selected(request('channel') === 'whatsapp')>WhatsApp</option>
                            <option value="facebook" @selected(request('channel') === 'facebook')>Facebook</option>
                            <option value="instagram" @selected(request('channel') === 'instagram')>Instagram</option>
                            <option value="email" @selected(request('channel') === 'email')>Email</option>
                            <option value="web" @selected(request('channel') === 'web')>Web</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                    @if(request('status') || request('channel'))
                        <div class="col-md-2">
                            <a href="{{ route('settings.helpdesk.broadcasts.index') }}" class="btn btn-light w-100">
                                Limpiar
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($broadcasts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Canal</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-center">Destinatarios / Entregados</th>
                                <th scope="col">Fecha</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($broadcasts as $broadcast)
                                <tr>
                                    <td>
                                        <strong>{{ $broadcast->name }}</strong>
                                    </td>
                                    <td>
                                        @php
                                            $channelColors = [
                                                'whatsapp' => 'success',
                                                'facebook' => 'primary',
                                                'instagram' => 'danger',
                                                'email' => 'info',
                                                'web' => 'secondary',
                                            ];
                                            $channelColor = $channelColors[$broadcast->channel] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $channelColor }}-subtle text-{{ $channelColor }}">
                                            {{ ucfirst($broadcast->channel) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'draft' => 'secondary',
                                                'scheduled' => 'info',
                                                'sending' => 'warning',
                                                'sent' => 'success',
                                                'failed' => 'danger',
                                            ];
                                            $statusLabels = [
                                                'draft' => 'Borrador',
                                                'scheduled' => 'Programado',
                                                'sending' => 'Enviando',
                                                'sent' => 'Enviado',
                                                'failed' => 'Fallido',
                                            ];
                                            $statusColor = $statusColors[$broadcast->status] ?? 'secondary';
                                            $statusLabel = $statusLabels[$broadcast->status] ?? $broadcast->status;
                                        @endphp
                                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ number_format($broadcast->recipients_count ?? 0) }}</span>
                                        <span class="text-muted">/</span>
                                        <span class="text-success">{{ number_format($broadcast->delivered_count ?? 0) }}</span>
                                    </td>
                                    <td>
                                        @if($broadcast->sent_at)
                                            <span class="text-muted small">Enviado {{ $broadcast->sent_at->diffForHumans() }}</span>
                                        @elseif($broadcast->scheduled_at)
                                            <span class="text-muted small">Programado {{ $broadcast->scheduled_at->format('d/m/Y H:i') }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.helpdesk.broadcasts.show', $broadcast) }}">
                                                        Ver detalle
                                                    </a>
                                                </li>
                                                @can('helpdesk.broadcasts.manage')
                                                    @if($broadcast->isDraft())
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item btn-delete"
                                                                data-id="{{ $broadcast->id }}"
                                                                data-url="{{ route('settings.helpdesk.broadcasts.destroy', $broadcast) }}"
                                                                data-name="{{ $broadcast->name }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    @endif
                                                @endcan
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
                            <i class="fas fa-bullhorn fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay broadcasts</h6>
                        <p class="text-muted mb-3">
                            @if(request('status') || request('channel'))
                                No se encontraron resultados para los filtros aplicados
                            @else
                                Crea tu primer broadcast para enviar mensajes masivos a tus contactos
                            @endif
                        </p>
                        @if(! request('status') && ! request('channel'))
                            @can('helpdesk.broadcasts.manage')
                                <a href="{{ route('settings.helpdesk.broadcasts.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer broadcast
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($broadcasts->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $broadcasts->firstItem() }}</strong> a <strong>{{ $broadcasts->lastItem() }}</strong>
                        de <strong>{{ $broadcasts->total() }}</strong> broadcasts
                    </div>
                    {{ $broadcasts->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        $('#delete-form').attr('action', url);
        $('#delete-modal').modal('show');
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
