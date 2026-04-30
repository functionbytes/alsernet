@extends('layouts.theme')

@section('title', 'Widgets web')

@section('content')

    @include('core::components.card', ['title' => 'Widgets Web'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Widget Web Channels Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Widgets web</h5>
                        <p class="small mb-0 text-muted">Gestiona los widgets de chat en vivo para tu sitio web</p>
                    </div>
                    <div>
                        <a href="{{ route('settings.chat.channels.webs.create') }}" class="btn btn-primary">
                            Nuevo widget
                        </a>
                    </div>
                </div>
            </div>

            <!-- Widget List -->
            <div class="card-body">
                @if($widgets->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Los widgets web permiten a tus visitantes iniciar conversaciones directamente desde tu sitio
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>URL del sitio</th>
                                    <th class="text-center">Posición</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($widgets as $widget)
                                    <tr>
                                        <td>
                                            <strong>{{ $widget->inbox->name }}</strong>
                                        </td>
                                        <td>
                                            <a href="{{ $widget->website_url }}" target="_blank" class="text-decoration-none">
                                                {{ Str::limit($widget->website_url, 50) }}
                                                <i class="fa fa-arrow-up-right-from-square small ms-1"></i>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            @if(($widget->widget_position ?? 'right') === 'right')
                                                <span class="badge bg-success-subtle text-success">Derecha</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">Izquierda</span>
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
                                                           href="{{ route('settings.chat.channels.webs.show', $widget) }}">
                                                            Ver
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.chat.channels.webs.edit', $widget) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.chat.channels.webs.destroy', $widget) }}"
                                                           data-title="Eliminar widget: {{ $widget->inbox->name }}">
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
                                <i class="fa fa-comments fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay widgets web para mostrar</h6>
                            <p class="text-muted mb-3">
                                Crea tu primer widget web para empezar a recibir mensajes desde tu sitio
                            </p>
                            <a href="{{ route('settings.chat.channels.webs.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i> Crear primer widget
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
