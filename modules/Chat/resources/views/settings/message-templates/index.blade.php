@extends('layouts.theme')

@section('title', 'Plantillas de mensaje')

@section('content')

    @include('core::components.card', ['title' => 'Plantillas de mensaje'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de mensaje</h5>
                        <p class="small mb-0 text-muted">Plantillas de mensaje rápido para conversaciones con sustitución de variables</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search') || request('category'))
                            <a href="{{ route('settings.chat.message.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.chat.message.create') }}" class="btn btn-primary">
                            Nueva plantilla
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.chat.message.index') }}">
                    <div class="row align-items-end g-3">
                        <div class="col-md-4">
                            <label for="category" class="col-form-label">Categoría</label>
                            <select name="category" id="category" class="form-control select2">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="search" class="col-form-label">Buscar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" id="search" class="form-control"
                                       placeholder="Buscar por nombre, contenido o atajo..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
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
                                <th width="30%">Nombre / Contenido</th>
                                <th width="15%">Atajo</th>
                                <th width="15%">Categoría</th>
                                <th width="10%" class="text-center">Tipo</th>
                                <th width="10%" class="text-center">Uso</th>
                                <th width="12%">Última vez usado</th>
                                <th width="8%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($templates as $template)
                                <tr>
                                    <td>
                                        <div>
                                            <a href="{{ route('settings.chat.message.edit', $template) }}" class="text-decoration-none">
                                                <strong>{{ $template->name }}</strong>
                                            </a>
                                            <small class="d-block text-muted">{{ Str::limit($template->content, 60) }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($template->shortcut)
                                            <code class="bg-light px-2 py-1 rounded">/{{ $template->shortcut }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($template->category)
                                            <span class="badge bg-info-subtle text-info">
                                                {{ $categories[$template->category] ?? $template->category }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($template->is_public)
                                            <span class="badge bg-success-subtle text-success">Pública</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">Privada</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info">{{ number_format($template->usage_count) }}</span>
                                    </td>
                                    <td>
                                        @if($template->last_used_at)
                                            <small class="text-muted">
                                                {{ $template->last_used_at->diffForHumans() }}
                                            </small>
                                        @else
                                            <span class="text-muted">Nunca</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.message.show', $template) }}">
                                                        Ver detalles
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.message.edit', $template) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button"
                                                        class="dropdown-item text-success delete-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#delete-modal"
                                                        data-url="{{ route('settings.chat.message.destroy', $template) }}"
                                                        data-title="Eliminar plantilla: {{ $template->name }}">
                                                        Eliminar
                                                    </button>
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
                                <i class="fas fa-comment-dots fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay plantillas de mensaje para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('category'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primera plantilla de mensaje para agilizar las respuestas en conversaciones
                                @endif
                            </p>
                            @if(!request('search') && !request('category'))
                                <a href="{{ route('settings.chat.message.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primera plantilla
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

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
            // Delete modal functionality
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const deleteUrl = $(this).data('url');
                const deleteTitle = $(this).data('title');

                $('#delete-modal .modal-title').text(deleteTitle);
                $('#delete-form').attr('action', deleteUrl);

                // Show the modal explicitly
                const deleteModal = new bootstrap.Modal(document.getElementById('delete-modal'));
                deleteModal.show();
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
