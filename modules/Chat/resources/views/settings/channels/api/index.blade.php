@extends('layouts.theme')

@section('title', 'Canales API')

@section('content')

    @include('core::components.card', ['title' => 'Canales API'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- API Channels Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Canales API</h5>
                        <p class="small mb-0 text-muted">Gestiona integraciones personalizadas usando REST API</p>
                    </div>
                    <div>
                        <a href="{{ route('settings.chat.channels.api.create') }}" class="btn btn-primary">
                            Nuevo canal
                        </a>
                    </div>
                </div>
            </div>

            <!-- API Channels List -->
            <div class="card-body">
                @if($apiChannels->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Los canales API permiten integrar sistemas externos mediante REST API y webhooks personalizados
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Identificador</th>
                                    <th class="text-center">Webhook</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($apiChannels as $api)
                                    <tr>
                                        <td>
                                            <strong>{{ $api->inbox->name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $api->identifier }}</code>
                                        </td>
                                        <td class="text-center">
                                            @if($api->webhook_url)
                                                <span class="badge bg-info-subtle text-info" title="{{ $api->webhook_url }}">
                                                    Configurado
                                                </span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">
                                                    Sin configurar
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($api->active)
                                                <span class="badge bg-success-subtle text-success">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.chat.channels.api.show', $api) }}">
                                                            Ver
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.chat.channels.api.edit', $api) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.chat.channels.api.destroy', $api) }}"
                                                           data-title="Eliminar canal: {{ $api->inbox->name ?? $api->identifier }}">
                                                            Eliminar
                                                        </a>
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
                                <i class="fa fa-code fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay canales API para mostrar</h6>
                            <p class="text-muted mb-3">
                                Añade tu primer canal API para comenzar a integrar sistemas externos
                            </p>
                            <a href="{{ route('settings.chat.channels.api.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i> Crear primer canal
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Delete modal functionality
    $('.delete-btn').on('click', function() {
        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
