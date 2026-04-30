@extends('layouts.theme')

@section('title', 'Macros')

@section('content')

    @include('core::components.card', ['title' => 'Macros'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Macros Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Macros</h5>
                        <p class="small mb-0 text-muted">Automatiza acciones repetitivas en conversaciones</p>
                    </div>
                    <a href="{{ route('settings.chat.macros.create') }}" class="btn btn-primary">
                        Nuevo macro
                    </a>
                </div>
            </div>

            <!-- Macros List -->
            <div class="card-body">
                @if($macros->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th class="text-center">Visibilidad</th>
                                    <th class="text-center">Acciones</th>
                                    <th>Creado por</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($macros as $macro)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $macro->name }}</strong>
                                                @if($macro->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($macro->description, 60) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $visibilityLabels = ['Personal', 'Global', 'Equipo'];
                                                $visibilityColors = ['secondary', 'success', 'info'];
                                            @endphp
                                            <span class="badge bg-{{ $visibilityColors[$macro->visibility] }}-subtle text-{{ $visibilityColors[$macro->visibility] }}">
                                                {{ $visibilityLabels[$macro->visibility] }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ count($macro->actions) }}</span>
                                        </td>
                                        <td>
                                            <div>
                                                <span>{{ $macro->createdBy->name ?? 'Desconocido' }}</span>
                                                <br>
                                                <small class="text-muted">{{ $macro->created_at->diffForHumans() }}</small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($macro->enabled)
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
                                                        <a class="dropdown-item" href="{{ route('settings.chat.macros.show', $macro) }}">
                                                            Ver detalles
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.chat.macros.edit', $macro) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.chat.macros.destroy', $macro) }}"
                                                           data-title="Eliminar macro: {{ $macro->name }}">
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
                                <i class="fa fa-bolt fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay macros para mostrar</h6>
                            <p class="text-muted mb-3">Crea tu primer macro para automatizar acciones repetitivas</p>
                            <a href="{{ route('settings.chat.macros.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i> Crear primer macro
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($macros->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $macros->firstItem() }}</strong> a <strong>{{ $macros->lastItem() }}</strong>
                            de <strong>{{ $macros->total() }}</strong> macros
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $macros->links() }}
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
