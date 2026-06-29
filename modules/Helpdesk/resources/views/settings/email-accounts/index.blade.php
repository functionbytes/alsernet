@extends('layouts.theme')

@section('title', 'Cuentas de email')

@section('page_header')
    @include('core::components.card', ['title' => 'Cuentas de email'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Cuentas de email configuradas</h5>
                        <p class="small mb-0 text-muted">Gestiona las cuentas IMAP/SMTP para recibir y enviar correos desde el helpdesk</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.email-accounts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva cuenta
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Cuentas registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Habilitadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con IMAP</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['with_imap']) }}</h4>
                                <small class="text-muted">Con recepcion configurada</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.email-accounts.index') }}">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por nombre o email..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk.email-accounts.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($accounts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th class="text-center">IMAP</th>
                                    <th class="text-center">SMTP</th>
                                    <th>Ultima sincronizacion</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accounts as $account)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $account->name }}</span>
                                        </td>
                                        <td>
                                            <small>{{ $account->email }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($account->hasImap())
                                                <span class="badge bg-success-subtle text-success">Configurado</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($account->hasSmtp())
                                                <span class="badge bg-success-subtle text-success">Configurado</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $account->last_sync_at ? $account->last_sync_at->diffForHumans() : 'Nunca' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($account->is_active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.email-accounts.edit', $account->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item test-connection" href="#"
                                                           data-url="{{ route('settings.helpdesk.email-accounts.test', $account->id) }}">
                                                            Probar conexion
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk.email-accounts.destroy', $account->id) }}"
                                                           data-title="Eliminar cuenta: {{ $account->name }}">
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
                        <i class="fas fa-envelope fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search'))
                                No se encontraron resultados
                            @else
                                No hay cuentas de email configuradas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Conecta una cuenta de email para gestionar conversaciones por correo
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk.email-accounts.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk.email-accounts.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva cuenta
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($accounts->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $accounts->firstItem() }} - {{ $accounts->lastItem() }} de {{ $accounts->total() }}
                        </div>
                        <div>
                            {{ $accounts->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    $(document).on('click', '.test-connection', function (e) {
        e.preventDefault();
        const $btn = $(this);
        $.ajax({
            url: $btn.data('url'),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message ?? 'No se pudo conectar.';
                toastr.error(msg, 'Error de conexion');
            }
        });
    });
});
</script>
@endpush
