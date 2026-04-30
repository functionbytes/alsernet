@extends('layouts.theme')

@section('title', 'Cuentas de Instagram')

@section('content')

    @include('core::components.card', ['title' => 'Cuentas de Instagram'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Instagram Accounts Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Cuentas de Instagram</h5>
                        <p class="small mb-0 text-muted">Conecta cuentas de Instagram Business mediante páginas de Facebook</p>
                    </div>
                    <div>
                        <a href="{{ route('settings.chat.channels.instagrams.create') }}" class="btn btn-primary">
                            Conectar cuenta
                        </a>
                    </div>
                </div>
            </div>

            <!-- Instagram Accounts List -->
            <div class="card-body">
                @if($instagrams->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Las cuentas conectadas permiten recibir y responder mensajes directos de Instagram
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre cuenta</th>
                                    <th>Username</th>
                                    <th class="text-center">Estado token</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($instagrams as $instagram)
                                    <tr>
                                        <td>
                                            <strong>{{ $instagram->inbox->name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $instagram->username ?? 'N/A' }}</code>
                                        </td>
                                        <td class="text-center">
                                            @if($instagram->isTokenExpired())
                                                <span class="badge bg-danger-subtle text-danger">
                                                    Expirado
                                                </span>
                                            @elseif($instagram->needsTokenRefresh())
                                                <span class="badge bg-warning-subtle text-warning">
                                                    Por expirar
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">
                                                    Válido
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
                                                           href="{{ route('settings.chat.channels.instagrams.show', $instagram) }}">
                                                            Ver
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.chat.channels.instagrams.edit', $instagram) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.chat.channels.instagrams.destroy', $instagram) }}"
                                                           data-title="Eliminar cuenta: {{ $instagram->inbox->name ?? $instagram->username }}">
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
                                <i class="fab fa-instagram fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay cuentas de Instagram conectadas</h6>
                            <p class="text-muted mb-3">
                                Conecta una cuenta de Instagram Business mediante una página de Facebook
                            </p>
                            <a href="{{ route('settings.chat.channels.instagrams.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i> Conectar primera cuenta
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
