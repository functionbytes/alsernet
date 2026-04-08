@extends('layouts.theme')

@section('title', 'Configuración de Categorías')

@push('styles')
<style>
    :root {
        --primary: #5D87FF;
        --primary-dark: #3E5BDB;
        --success: #13C672;
        --danger: #FA896B;
        --warning: #FEC90F;
        --info: #5DADE2;
        --light-bg: #f8f9fa;
        --card-border: #e0e0e0;
    }

    .settings-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(93, 135, 255, 0.2);
    }

    .settings-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .settings-header p {
        opacity: 0.95;
        margin: 0;
        font-size: 0.95rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border: 1px solid var(--card-border);
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .stat-card h6 {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #999;
        margin-bottom: 0.5rem;
    }

    .stat-card h4 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 0.25rem;
    }

    .stat-card p {
        font-size: 0.85rem;
        color: #999;
        margin: 0;
    }

    .card-header-custom {
        padding: 1.5rem;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
    }

    .card-header-custom h5 {
        margin: 0;
        font-weight: 700;
        color: #333;
    }

    .btn-primary-custom {
        background: var(--primary);
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
        text-decoration: none;
        color: white;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary-custom:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(93, 135, 255, 0.3);
        color: white;
    }
</style>
@endpush

@section('content')

<div class="container-fluid">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('manager.helpdesk.tickets.index') }}">Helpdesk</a>
            </li>
            <li class="breadcrumb-item active">Categorías</li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="settings-header">
        <h2>
            <i class="fas fa-folder-open me-2"></i>
            Categorías de Tickets
        </h2>
        <p>Organiza los tickets por categorías para mejorar la gestión y asignación</p>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <h6 class="text-primary">Total</h6>
            <h4>{{ $stats['total'] }}</h4>
            <p>Categorías configuradas</p>
        </div>
        <div class="stat-card">
            <h6 class="text-success">Activas</h6>
            <h4 class="text-success">{{ $stats['active'] }}</h4>
            <p>Categorías habilitadas</p>
        </div>
        <div class="stat-card">
            <h6 class="text-warning">Inactivas</h6>
            <h4 class="text-warning">{{ $stats['inactive'] }}</h4>
            <p>Categorías deshabilitadas</p>
        </div>
        <div class="stat-card">
            <h6 class="text-info">Con SLA</h6>
            <h4 class="text-info">{{ $stats['with_sla'] }}</h4>
            <p>Con política SLA asignada</p>
        </div>
    </div>

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- System Settings Card -->
        <div class="card shadow-sm" style="border-radius: 12px; border: 1px solid #e0e0e0;">
            <!-- Header Section -->
            <div class="card-header-custom">
                <div>
                    <h5>Categorías disponibles</h5>
                </div>
                <div class="d-flex gap-2">
                    @if(request('search'))
                        <a href="{{ route('manager.helpdesk.backups.tickets.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i> Limpiar búsqueda
                        </a>
                    @endif
                    <a href="{{ route('manager.helpdesk.backups.tickets.categories.create') }}" class="btn-primary-custom">
                        <i class="fas fa-plus"></i> Nueva categoría
                    </a>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom" style="background: #f8f9fa;">
                <form method="GET" action="{{ route('manager.helpdesk.backups.tickets.categories.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-color: #e0e0e0;">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0" placeholder="Buscar por nombre o slug..." value="{{ request('search') }}" style="border-color: #e0e0e0;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100" style="background-color: var(--primary); border: none;">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Categories List -->
            <div class="card-body">
                @if($categories->count() > 0)
                    <div class="alert mb-3" style="background: rgba(93, 135, 255, 0.1); border: 1px solid rgba(93, 135, 255, 0.3); color: #3E5BDB;">
                        <i class="fas fa-hand-pointer me-2"></i>
                        <strong>Consejo:</strong> Arrastra y suelta para reordenar las categorías
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0" id="categoriesTable" style="border-collapse: separate; border-spacing: 0;">
                            <thead style="background: #f8f9fa;">
                            <tr>
                                <th width="5%" style="border: none;"></th>
                                <th width="25%" style="border: none; font-weight: 700; color: #333;">Nombre</th>
                                <th width="15%" style="border: none; font-weight: 700; color: #333;">Slug</th>
                                <th width="15%" style="border: none; font-weight: 700; color: #333;">Política SLA</th>
                                <th width="10%" style="border: none; font-weight: 700; color: #333;">Grupos</th>
                                <th width="15%" style="border: none; font-weight: 700; color: #333;">Descripción</th>
                                <th width="10%" class="text-center" style="border: none; font-weight: 700; color: #333;">Estado</th>
                                <th width="5%" class="text-center" style="border: none; font-weight: 700; color: #333;">Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="categoriesList">
                            @foreach($categories as $category)
                                <tr data-id="{{ $category->id }}" class="sortable-row" style="border-bottom: 1px solid #e0e0e0; transition: all 0.2s ease;">
                                    <td class="drag-handle text-center align-middle" style="border: none;">
                                        <i class="fas fa-grip-vertical text-muted" style="cursor: grab; font-size: 0.9rem;"></i>
                                    </td>
                                    <td style="border: none; vertical-align: middle;">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($category->icon)
                                                <i class="ti {{ $category->icon }}" style="color: {{ $category->color }}; font-size: 20px;"></i>
                                            @else
                                                <div style="width: 24px; height: 24px; background: var(--primary); border-radius: 6px;"></div>
                                            @endif
                                            <div>
                                                <strong style="font-weight: 700; color: #333;">{{ $category->name }}</strong>
                                                @if($category->is_default)
                                                    <span class="badge ms-2" style="background: rgba(93, 135, 255, 0.2); color: #5D87FF; font-weight: 600; font-size: 0.75rem;">Por defecto</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td style="border: none; vertical-align: middle;">
                                        <code style="background: #f0f0f0; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.85rem; color: #666;">{{ $category->slug }}</code>
                                    </td>
                                    <td style="border: none; vertical-align: middle;">
                                        @if($category->default_sla_policy)
                                            <span style="background: rgba(93, 173, 226, 0.2); color: #5DADE2; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">{{ $category->default_sla_policy }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td style="border: none; vertical-align: middle;">
                                        @if($category->groups && count($category->groups) > 0)
                                            <span style="background: #f0f0f0; color: #333; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">{{ count($category->groups) }} grupo(s)</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td style="border: none; vertical-align: middle;">
                                        <small class="text-muted">{{ $category->description ? Str::limit($category->description, 40) : '-' }}</small>
                                    </td>
                                    <td class="text-center" style="border: none; vertical-align: middle;">
                                        <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.categories.toggle', $category->id) }}" class="toggle-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input toggle-checkbox" role="switch"
                                                       {{ $category->active ? 'checked' : '' }}
                                                       onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center" style="border: none; vertical-align: middle;">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.helpdesk.backups.tickets.categories.edit', $category->id) }}">
                                                        <i class="fas fa-edit me-2"></i> Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.categories.destroy', $category->id) }}"
                                                          onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash me-2"></i> Eliminar
                                                        </button>
                                                    </form>
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
                            <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                                <i class="fas fa-folder" style="font-size: 2rem; color: #999;"></i>
                            </div>
                            <h5 style="font-weight: 700; color: #333; margin-bottom: 0.5rem;">No hay categorías para mostrar</h5>
                            <p style="color: #999; margin-bottom: 1.5rem; font-size: 0.95rem;">
                                @if(request('search'))
                                    No se encontraron resultados para <strong>"{{ request('search') }}"</strong>
                                @else
                                    Crea tu primera categoría para organizar los tickets
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('manager.helpdesk.backups.tickets.categories.create') }}" class="btn-primary-custom">
                                    <i class="fas fa-plus"></i> Crear Primera Categoría
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($categories->hasPages())
                <div class="card-footer" style="background: #f8f9fa; border-top: 1px solid #e0e0e0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div style="color: #999; font-size: 0.9rem;">
                            Mostrando <strong style="color: #333;">{{ $categories->firstItem() }}</strong> a <strong style="color: #333;">{{ $categories->lastItem() }}</strong>
                            de <strong style="color: #333;">{{ $categories->total() }}</strong> categorías
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $categories->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
    .sortable-row {
        cursor: move;
    }
    .sortable-row:hover {
        background-color: #f8f9fa;
    }
    .ui-sortable-helper {
        display: table;
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .ui-sortable-placeholder {
        background-color: #e9ecef;
        visibility: visible !important;
        border: 2px dashed #dee2e6;
    }
    .drag-handle {
        cursor: grab;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
</style>

<script>
$(document).ready(function() {
    // Initialize sortable
    $('#categoriesList').sortable({
        handle: '.drag-handle',
        axis: 'y',
        cursor: 'grabbing',
        placeholder: 'ui-sortable-placeholder',
        helper: function(e, tr) {
            var $originals = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function(index) {
                $(this).width($originals.eq(index).width());
            });
            return $helper;
        },
        start: function(e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {
            const ids = [];
            $('#categoriesList tr').each(function() {
                ids.push($(this).data('id'));
            });

            // Save new order
            $.ajax({
                url: '{{ route('manager.helpdesk.backups.tickets.categories.reorder') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                },
                success: function(response) {
                    toastr.success(response.message || 'Orden actualizado exitosamente', 'Éxito');
                },
                error: function(xhr) {
                    toastr.error('Error al actualizar el orden', 'Error');
                    $('#categoriesList').sortable('cancel');
                }
            });
        }
    });

    // Prevent multiple toggle submissions
    $('.toggle-form').on('submit', function() {
        $(this).find('.toggle-checkbox').prop('disabled', true);
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
