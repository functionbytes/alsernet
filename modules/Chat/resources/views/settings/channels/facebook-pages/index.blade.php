@extends('layouts.theme')

@section('title', 'Páginas de Facebook')

@section('content')

    @include('core::components.card', ['title' => 'Páginas de Facebook'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Facebook Pages Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Páginas de Facebook</h5>
                        <p class="small mb-0 text-muted">Conecta tus páginas de Facebook para recibir mensajes de Messenger</p>
                    </div>
                    <div>
                        <a href="{{ route('settings.chat.channels.facebook-pages.create') }}" class="btn btn-primary">
                            Conectar página
                        </a>
                    </div>
                </div>
            </div>

            <!-- Facebook Pages List -->
            <div class="card-body">
                @if($pages->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Las páginas conectadas permiten recibir y responder mensajes de Facebook Messenger
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre página</th>
                                    <th>Page ID</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pages as $page)
                                    <tr>
                                        <td>
                                            <strong>{{ $page->page_name }}</strong>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $page->page_id }}</code>
                                        </td>
                                        <td class="text-center">
                                            @if($page->inbox)
                                                <span class="badge bg-success-subtle text-success">
                                                    Conectado
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">
                                                    Desconectado
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
                                                           href="{{ route('settings.chat.channels.facebook-pages.show', $page) }}">
                                                            Ver
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.chat.channels.facebook-pages.edit', $page) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.chat.channels.facebook-pages.destroy', $page) }}"
                                                           data-title="Eliminar página: {{ $page->page_name }}">
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
                                <i class="fab fa-facebook fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay páginas de Facebook conectadas</h6>
                            <p class="text-muted mb-3">
                                Conecta tu página de Facebook para empezar a recibir mensajes de Messenger
                            </p>
                            <a href="{{ route('settings.chat.channels.facebook-pages.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i> Conectar primera página
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
