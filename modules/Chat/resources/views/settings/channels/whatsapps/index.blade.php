@extends('layouts.theme')

@section('title', 'Canales de WhatsApp')

@section('content')

    @include('core::components.card', ['title' => 'Canales de WhatsApp'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- WhatsApp Channels Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Canales de WhatsApp</h5>
                        <p class="small mb-0 text-muted">Gestiona tus canales de WhatsApp conectados mediante diferentes proveedores</p>
                    </div>
                    <div>
                        <a href="{{ route('settings.chat.channels.whatsapps.create') }}" class="btn btn-primary">
                            Nuevo canal
                        </a>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Channels List -->
            <div class="card-body">
                @if($whatsapps->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Los canales de WhatsApp permiten recibir y enviar mensajes mediante proveedores como Evolution API, Cloud API y más
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Número teléfono</th>
                                    <th>Proveedor</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($whatsapps as $whatsapp)
                                    <tr>
                                        <td>
                                            <strong>{{ $whatsapp->inbox->name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $whatsapp->phone_number }}</code>
                                        </td>
                                        <td>
                                            @php
                                                $providerLabels = [
                                                    'evolution_api' => 'Evolution API',
                                                    'cloud_api' => 'Cloud API',
                                                    'whatsapp_cloud' => 'Cloud API',
                                                    '360dialog' => '360Dialog',
                                                    'twilio' => 'Twilio',
                                                ];
                                                $providerLabel = $providerLabels[$whatsapp->provider] ?? ucfirst($whatsapp->provider);
                                            @endphp
                                            <span class="badge bg-info-subtle text-info">{{ $providerLabel }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($whatsapp->isEvolutionApi())
                                                <span class="badge bg-success-subtle text-success" data-status-badge="{{ $whatsapp->id }}">
                                                    Conectado
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">
                                                    Activo
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
                                                           href="{{ route('settings.chat.channels.whatsapps.show', $whatsapp) }}">
                                                            Ver
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.chat.channels.whatsapps.edit', $whatsapp) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    @if($whatsapp->isEvolutionApi())
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('settings.chat.channels.whatsapps.settings', $whatsapp) }}">
                                                                Configuración avanzada
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.chat.channels.whatsapps.destroy', $whatsapp) }}"
                                                           data-title="Eliminar canal: {{ $whatsapp->inbox->name ?? $whatsapp->phone_number }}">
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
                                <i class="fab fa-whatsapp fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay canales de WhatsApp para mostrar</h6>
                            <p class="text-muted mb-3">
                                Crea tu primer canal para empezar a recibir mensajes de WhatsApp
                            </p>
                            <a href="{{ route('settings.chat.channels.whatsapps.create') }}" class="btn btn-sm btn-primary">
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
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
