@extends('layouts.theme')

@section('title', 'Formularios pre-chat')

@section('page_header')
    @include('core::components.card', ['title' => 'Formularios pre-chat'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Formularios pre-chat</h5>
                        <p class="small mb-0 text-muted">Recopila informacion del cliente antes de iniciar la conversacion</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk-livechat.pre-chat-forms.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo formulario
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
                                <small class="text-muted">Formularios registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Habilitados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Globales</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['global']) }}</h4>
                                <small class="text-muted">Sin bandeja especifica</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk-livechat.pre-chat-forms.index') }}">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk-livechat.pre-chat-forms.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($forms->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Bandeja</th>
                                    <th class="text-center">Campos</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($forms as $form)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $form->name }}</span>
                                        </td>
                                        <td>
                                            @if($form->inbox)
                                                <span class="badge bg-primary-subtle text-primary">{{ $form->inbox->name }}</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Global</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ is_array($form->fields) ? count($form->fields) : 0 }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($form->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk-livechat.pre-chat-forms.edit', $form->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk-livechat.pre-chat-forms.destroy', $form->id) }}"
                                                           data-title="Eliminar formulario: {{ $form->name }}">
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
                        <i class="fas fa-wpforms fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search'))
                                No se encontraron resultados
                            @else
                                No hay formularios pre-chat configurados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search'))
                                No hay resultados para "{{ request('search') }}"
                            @else
                                Crea un formulario para recopilar datos del cliente antes del chat
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('settings.helpdesk-livechat.pre-chat-forms.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk-livechat.pre-chat-forms.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo formulario
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($forms->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $forms->firstItem() }} - {{ $forms->lastItem() }} de {{ $forms->total() }}
                        </div>
                        <div>
                            {{ $forms->appends(request()->input())->links() }}
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
});
</script>
@endpush
