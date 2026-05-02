@extends('layouts.theme')

@section('title', 'Campos personalizados')

@section('page_header')
    @include('core::components.card', ['title' => 'Campos personalizados'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Campos configurados</h5>
                        <p class="small mb-0 text-muted">Atributos adicionales para clientes y conversaciones</p>
                    </div>
                    <a href="{{ route('settings.helpdesk.custom-fields.create') }}" class="btn btn-primary">
                        Nuevo campo
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Campos definidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-info mb-2">Clientes</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['customers'] }}</h4>
                                <small class="text-muted">Campos de cliente</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2" style="color: #6f42c1;">Conversaciones</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['conversations'] }}</h4>
                                <small class="text-muted">Campos de conversacion</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.custom-fields.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                    placeholder="Buscar por etiqueta o clave..."
                                    value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select name="entity_type" class="form-select">
                                <option value="">Todas las entidades</option>
                                <option value="customer" @selected(request('entity_type') === 'customer')>Cliente</option>
                                <option value="conversation" @selected(request('entity_type') === 'conversation')>Conversacion</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($fields->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Etiqueta</th>
                                    <th>Entidad</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Requerido</th>
                                    <th class="text-center">Orden</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fields as $field)
                                    <tr>
                                        <td>
                                            <strong>{{ $field->label }}</strong>
                                            <div><code class="small text-muted">{{ $field->key }}</code></div>
                                        </td>
                                        <td>
                                            @if($field->entity_type === 'customer')
                                                <span class="badge bg-info-subtle text-info">Cliente</span>
                                            @else
                                                <span class="badge bg-purple-subtle text-purple" style="background-color: #f3eeff; color: #6f42c1;">Conversacion</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ \Modules\Helpdesk\Models\CustomField::TYPES[$field->type] ?? $field->type }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($field->is_required)
                                                <i class="fas fa-circle-check text-success"></i>
                                            @else
                                                <i class="fas fa-circle-xmark text-muted"></i>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted">{{ $field->order ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.custom-fields.edit', $field) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $field->id }}"
                                                            data-url="{{ route('settings.helpdesk.custom-fields.destroy', $field) }}"
                                                            data-name="{{ $field->label }}">
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
                                <i class="fas fa-list-check fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay campos personalizados</h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('entity_type'))
                                    No se encontraron resultados para los filtros aplicados
                                @else
                                    Crea campos adicionales para clientes y conversaciones
                                @endif
                            </p>
                            @unless(request('search') || request('entity_type'))
                                <a href="{{ route('settings.helpdesk.custom-fields.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer campo
                                </a>
                            @endunless
                        </div>
                    </div>
                @endif
            </div>

            @if($fields->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $fields->firstItem() }}</strong> a <strong>{{ $fields->lastItem() }}</strong>
                            de <strong>{{ $fields->total() }}</strong> campos
                        </div>
                        {{ $fields->appends(request()->input())->links() }}
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
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#deleteForm').attr('action', url);
        $('#deleteItemName').text(name);
        $('#deleteModal').modal('show');
    });

    @if(session('success'))
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
