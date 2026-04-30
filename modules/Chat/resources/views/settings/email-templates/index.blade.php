@extends('layouts.theme')

@section('title', 'Plantillas de email')

@section('content')

    @include('core::components.card', ['title' => 'Plantillas de email'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Email Templates Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de email</h5>
                        <p class="small mb-0 text-muted">Gestiona las notificaciones automáticas enviadas por el sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('event_type') || request('active'))
                            <a href="{{ route('settings.chat.email.index') }}" class="btn btn-secondary">
                                Limpiar filtros
                            </a>
                        @endif
                        <a href="{{ route('settings.chat.email.create') }}" class="btn btn-primary">
                            Nueva plantilla
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.chat.email.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-6">
                            <select class="form-control select2 select2" name="event_type">
                                <option value="">Todos los eventos</option>
                                @foreach($eventTypes as $key => $label)
                                    <option value="{{ $key }}" {{ request('event_type') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control select2 select2" name="active" data-minimum-results-for-search="Infinity">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Activa</option>
                                <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactiva</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Templates List -->
            <div class="card-body">
                @if($templates->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Evento</th>
                                    <th>Asunto</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $template->name }}</strong>
                                                @if($template->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($template->description, 60) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ $eventTypes[$template->event_type] ?? $template->event_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($template->subject, 40) }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($template->active)
                                                <span class="badge bg-success-subtle text-success">
                                                    Activa
                                                </span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">
                                                    Inactiva
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
                                                        <a class="dropdown-item" href="{{ route('settings.chat.email.preview', $template) }}" target="_blank">
                                                            Vista previa
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.chat.email.edit', $template) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('settings.chat.email.toggle', $template) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                @if($template->active)
                                                                    Desactivar
                                                                @else
                                                                    Activar
                                                                @endif
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.chat.email.destroy', $template) }}"
                                                           data-title="Eliminar plantilla: {{ $template->name }}">
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
                            <h6 class="mb-1">No hay plantillas de email para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('event_type') || request('active'))
                                    No se encontraron resultados con los filtros aplicados
                                @else
                                    Crea tu primera plantilla para comenzar
                                @endif
                            </p>
                            @if(!request('event_type') && !request('active'))
                                <a href="{{ route('settings.chat.email.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus"></i> Crear primera plantilla
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($templates->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $templates->firstItem() }}</strong> a <strong>{{ $templates->lastItem() }}</strong>
                            de <strong>{{ $templates->total() }}</strong> plantillas
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $templates->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        allowClear: false,
        minimumResultsForSearch: Infinity
    });

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
