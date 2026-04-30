@extends('layouts.theme')

@section('title', 'Canales de email')

@section('content')

    @include('core::components.card', ['title' => 'Canales de Email'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Email Channels Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Canales de email</h5>
                        <p class="small mb-0 text-muted">Gestiona los canales de correo electrónico para recibir y enviar mensajes</p>
                    </div>
                    <div>
                        <a href="{{ route('settings.chat.channels.emails.create') }}" class="btn btn-primary">
                            Nuevo canal
                        </a>
                    </div>
                </div>
            </div>

            <!-- Email Channels List -->
            <div class="card-body">
                @if($emails->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Los canales de email permiten gestionar conversaciones a través de correo electrónico con soporte IMAP y SMTP
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre bandeja</th>
                                    <th>Email</th>
                                    <th>Proveedor</th>
                                    <th class="text-center">IMAP</th>
                                    <th class="text-center">SMTP</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emails as $inbox)
                                    @php
                                        $email = $inbox->channel;
                                        $providerBadges = [
                                            'google' => 'bg-info-subtle text-info',
                                            'microsoft' => 'bg-primary-subtle text-primary',
                                            'zoho' => 'bg-warning-subtle text-warning',
                                            'custom' => 'bg-secondary-subtle text-secondary',
                                        ];
                                        $providerClass = $providerBadges[$email->provider ?? 'custom'];
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $inbox->name }}</strong>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $email->email }}</code>
                                        </td>
                                        <td>
                                            <span class="badge {{ $providerClass }}">
                                                {{ ucfirst($email->provider ?? 'custom') }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($email->imap_enabled)
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
                                            @if($email->smtp_enabled)
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
                                                           href="{{ route('settings.chat.channels.emails.show', $email) }}">
                                                            Ver
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.chat.channels.emails.edit', $email) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.chat.channels.emails.destroy', $email) }}"
                                                           data-title="Eliminar canal: {{ $inbox->name }}">
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
                                <i class="fa fa-envelope fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay canales de email para mostrar</h6>
                            <p class="text-muted mb-3">
                                Crea tu primer canal de email para comenzar a recibir y enviar mensajes
                            </p>
                            <a href="{{ route('settings.chat.channels.emails.create') }}" class="btn btn-sm btn-primary">
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
