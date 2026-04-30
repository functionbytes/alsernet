@extends('layouts.theme')

@section('title', 'Atributos Personalizados')

@section('page_header')
    @include('core::components.card', ['title' => 'Atributos Personalizados'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="mb-0 border-bottom pb-3">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <a href="{{ route('settings.helpdesk.attributes.create') }}" class="btn btn-primary w-100">
                                <i class="fa fa-plus"></i> Nuevo Atributo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <h5 class="mb-1">Filtros de Búsqueda</h5>
                <p class="text-muted mb-3">Filtra los atributos por nombre, formato o permisos</p>
                <form method="GET" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Búsqueda</label>
                            <input type="text" name="search" class="form-control" placeholder="Buscar atributo..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Formato</label>
                            <select name="format" class="form-select select2" id="formatSelect">
                                <option value="">Todos los formatos</option>
                                <option value="text" {{ request('format') === 'text' ? 'selected' : '' }}>Texto</option>
                                <option value="textarea" {{ request('format') === 'textarea' ? 'selected' : '' }}>Área de Texto</option>
                                <option value="number" {{ request('format') === 'number' ? 'selected' : '' }}>Número</option>
                                <option value="switch" {{ request('format') === 'switch' ? 'selected' : '' }}>Interruptor</option>
                                <option value="rating" {{ request('format') === 'rating' ? 'selected' : '' }}>Calificación</option>
                                <option value="select" {{ request('format') === 'select' ? 'selected' : '' }}>Selección</option>
                                <option value="checkboxGroup" {{ request('format') === 'checkboxGroup' ? 'selected' : '' }}>Grupo de Checkboxes</option>
                                <option value="date" {{ request('format') === 'date' ? 'selected' : '' }}>Fecha</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Permisos</label>
                            <select name="permission" class="form-select select2" id="permissionSelect">
                                <option value="">Todos los permisos</option>
                                <option value="userCanView" {{ request('permission') === 'userCanView' ? 'selected' : '' }}>Usuario puede ver</option>
                                <option value="userCanEdit" {{ request('permission') === 'userCanEdit' ? 'selected' : '' }}>Usuario puede editar</option>
                                <option value="agentCanEdit" {{ request('permission') === 'agentCanEdit' ? 'selected' : '' }}>Agente puede editar</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100 d-block">
                                <i class="fa fa-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attributes Table -->
        <div class="card">
            <div class="card-body">
                <h5 class="mb-1">Atributos personalizados</h5>
                <p class="text-muted mb-3">Define campos adicionales para conversaciones, clientes y tickets</p>
                <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Nombre</th>
                            <th style="width: 15%;">Clave</th>
                            <th style="width: 15%;">Formato</th>
                            <th style="width: 15%;">Permiso</th>
                            <th style="width: 10%;" class="text-center">Requerido</th>
                            <th style="width: 10%;" class="text-center">Estado</th>
                            <th style="width: 10%;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attributes as $attribute)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $attribute->name }}</div>
                                    @if($attribute->description)
                                        <small class="text-muted">{{ $attribute->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <code class="text-muted">{{ $attribute->key }}</code>
                                </td>
                                <td>
                                    @php
                                        $formatLabels = [
                                            'text'          => ['label' => 'Texto',          'icon' => 'fas fa-font',           'color' => 'primary'],
                                            'textarea'      => ['label' => 'Área de Texto',  'icon' => 'far fa-rectangle-list', 'color' => 'info'],
                                            'number'        => ['label' => 'Número',         'icon' => 'fas fa-hashtag',        'color' => 'success'],
                                            'switch'        => ['label' => 'Interruptor',    'icon' => 'fas fa-toggle-on',      'color' => 'warning'],
                                            'rating'        => ['label' => 'Calificación',   'icon' => 'fas fa-star',           'color' => 'danger'],
                                            'select'        => ['label' => 'Selección',      'icon' => 'fas fa-list',           'color' => 'secondary'],
                                            'checkboxGroup' => ['label' => 'Checkboxes',     'icon' => 'far fa-square-check',   'color' => 'dark'],
                                            'date'          => ['label' => 'Fecha',          'icon' => 'far fa-calendar',       'color' => 'purple'],
                                        ];
                                        $format = $formatLabels[$attribute->format]
                                            ?? ['label' => $attribute->format, 'icon' => 'far fa-circle-question', 'color' => 'muted'];
                                    @endphp
                                    <span class="badge bg-{{ $format['color'] }}-subtle text-{{ $format['color'] }}">
                                        <i class="{{ $format['icon'] }}"></i> {{ $format['label'] }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $permissionLabels = [
                                            'userCanView' => ['label' => 'Usuario Ver', 'color' => 'info'],
                                            'userCanEdit' => ['label' => 'Usuario editar', 'color' => 'success'],
                                            'agentCanEdit' => ['label' => 'Agente editar', 'color' => 'primary']
                                        ];
                                        $perm = $permissionLabels[$attribute->permission] ?? ['label' => $attribute->permission, 'color' => 'muted'];
                                    @endphp
                                    <span class="badge bg-{{ $perm['color'] }}-subtle text-{{ $perm['color'] }}">
                                        {{ $perm['label'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($attribute->required)
                                        <span class="badge bg-light-subtle text-black">
                                            Sí
                                        </span>
                                    @else
                                        <span class="text-muted">
                                            No
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('settings.helpdesk.attributes.toggle', $attribute->id) }}" class="d-inline toggle-form">
                                        @csrf
                                        @method('PATCH')
                                        <div class="form-check form-switch d-inline-block">
                                            <input type="checkbox" class="form-check-input toggle-checkbox" role="switch"
                                                   {{ $attribute->active ? 'checked' : '' }}
                                                  >
                                        </div>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('settings.helpdesk.attributes.edit', $attribute->id) }}">
                                                   Editar
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="{{ route('settings.helpdesk.attributes.destroy', $attribute->id) }}"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este atributo? Esta acción no se puede deshacer.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-cog fs-1 text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted mb-2">No hay atributos creados</h5>
                                    <p class="text-muted mb-4">Crea tu primer atributo personalizado para extender la funcionalidad</p>
                                    <a href="{{ route('settings.helpdesk.attributes.create') }}" class="btn btn-primary">
                                        <i class="fa fa-plus"></i> Crear primer atributo
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

                <!-- Pagination -->
                @if($attributes->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $attributes->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for filter selects
    $('#formatSelect, #permissionSelect').select2({
        placeholder: 'Selecciona una opción',
        allowClear: true,
        width: '100%'
    });

    // Auto-submit form on select change
    $('#filterForm select').on('change', function() {
        $('#filterForm').submit();
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
