@extends('layouts.theme')

@section('page_title', 'Variables de Email')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Variables de email',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Variables de email', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Variables de email</h5>
                        <p class="small mb-0 text-muted">Gestiona variables dinámicas que se sustituyen automáticamente en plantillas y componentes</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('mailers.templates.index') }}" class="btn btn-secondary">
                            Ver plantillas
                        </a>
                        <a href="{{ route('mailers.variables.create') }}" class="btn btn-primary">
                            Nueva variable
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">¿Qué son las variables de email?</h6>
                            <p class="mb-0">
                                Las variables son marcadores de posición como <code>&#123;&#123;customer_name&#125;&#125;</code> que se reemplazan automáticamente con datos reales
                                cuando se envía un email. Por ejemplo, <strong>&#123;&#123;customer_name&#125;&#125;</strong> se convierte en el nombre del cliente.
                                Define variables personalizadas para cada módulo del sistema.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if (session('success'))
                <div class="card-body border-bottom">
                    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-check fs-4 me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="card-body border-bottom">
                    <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-triangle-exclamation fs-4 me-2"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('mailers.variables.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label for="search" class="form-label fw-semibold">Búsqueda</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por clave o nombre..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="module" class="form-label fw-semibold">Módulo</label>
                            <select id="module" name="module" class="form-select">
                                <option value="">Todos los módulos</option>
                                <option value="core" @selected(request('module') === 'core')>Core</option>
                                <option value="documents" @selected(request('module') === 'documents')>Documentos</option>
                                <option value="orders" @selected(request('module') === 'orders')>Pedidos</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="category" class="form-label fw-semibold">Categoría</label>
                            <select id="category" name="category" class="form-select">
                                <option value="">Todas las categorías</option>
                                <option value="system" @selected(request('category') === 'system')>Sistema</option>
                                <option value="customer" @selected(request('category') === 'customer')>Cliente</option>
                                <option value="order" @selected(request('category') === 'order')>Pedido</option>
                                <option value="document" @selected(request('category') === 'document')>Documento</option>
                                <option value="general" @selected(request('category') === 'general')>General</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-magnifying-glass me-2"></i>Buscar
                            </button>
                            @if (request('search') || request('module') || request('category'))
                                <a href="{{ route('mailers.variables.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Variables Table --}}
            @if ($variables->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Clave</th>
                                <th width="20%">Nombre</th>
                                <th width="12%">Categoría</th>
                                <th width="12%">Módulo</th>
                                <th width="10%">Tipo</th>
                                <th width="8%">Estado</th>
                                <th width="18%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($variables as $variable)
                                @php
                                    $isSystem = $variable->is_system;
                                @endphp
                                <tr>
                                    <td>
                                        <div>
                                            <code class="text-primary d-block">{{ $variable->key }}</code>
                                            @if ($isSystem)
                                                <span class="badge bg-warning-subtle text-warning mt-1">
                                                    <i class="fa fa-lock"></i> Sistema
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="d-block">{{ $variable->name }}</strong>
                                        @if ($variable->description)
                                            <small class="text-muted d-block">{{ Str::limit($variable->description, 40) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($variable->category)
                                            @case('system')
                                                <span class="badge bg-primary-subtle text-primary">Sistema</span>
                                                @break
                                            @case('customer')
                                                <span class="badge bg-info-subtle text-info">Cliente</span>
                                                @break
                                            @case('order')
                                                <span class="badge bg-success-subtle text-success">Pedido</span>
                                                @break
                                            @case('document')
                                                <span class="badge bg-warning-subtle text-warning">Documento</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary-subtle text-secondary">General</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @switch($variable->module)
                                            @case('documents')
                                                <span class="badge bg-info-subtle text-info">Documentos</span>
                                                @break
                                            @case('orders')
                                                <span class="badge bg-success-subtle text-success">Pedidos</span>
                                                @break
                                            @default
                                                <span class="badge bg-primary-subtle text-primary">Core</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if ($isSystem)
                                            <span class="badge bg-warning-subtle text-warning">Protegido</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Personalizado</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-status" type="checkbox"
                                                @checked($variable->is_enabled)
                                                data-variable-id="{{ $variable->id }}"
                                                data-url="{{ route('mailers.variables.toggle-status', $variable) }}"
                                                title="{{ $variable->is_enabled ? 'Activa' : 'Inactiva' }}">
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('mailers.variables.edit', $variable) }}">
                                                        Editar variable
                                                    </a>
                                                </li>
                                                @if (!$isSystem)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST"
                                                              action="{{ route('mailers.variables.destroy', $variable) }}"
                                                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta variable?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                Eliminar variable
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay variables</h5>
                    <p class="text-muted mb-4">
                        @if (request('search') || request('module') || request('category'))
                            No se encontraron resultados con los filtros aplicados.
                        @else
                            Comienza creando tu primera variable de email para usar en plantillas.
                        @endif
                    </p>
                    @if (request('search') || request('module') || request('category'))
                        <a href="{{ route('mailers.variables.index') }}" class="btn btn-secondary">
                            Ver todas
                        </a>
                    @else
                        <a href="{{ route('mailers.variables.create') }}" class="btn btn-primary">
                            + Crear ahora
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            @if($variables->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $variables->firstItem() }} - {{ $variables->lastItem() }} de {{ $variables->total() }} variables
                        </div>
                        <div>
                            {{ $variables->links() }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- Categories Info Section --}}
            <div class="card-body border-top">
                <h5 class="fw-bold mb-1">Categorías de variables</h5>
                <p class="text-muted mb-4">
                    Las variables se organizan en categorías según el tipo de dato que representan.
                    Esto facilita su búsqueda y organización en plantillas de email.
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-light-subtle d-flex align-items-center justify-content-center me-3"
                                         style="width: 48px; height: 48px;">
                                        <i class="fa fa-cog text-primary"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">Sistema</h6>
                                </div>
                                <p class="text-muted mb-2 small">
                                    Variables globales del sistema como nombre de la empresa, URL, información de contacto.
                                </p>
                                <code class="small text-primary">&#123;&#123;company_name&#125;&#125;</code>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-light-subtle d-flex align-items-center justify-content-center me-3"
                                         style="width: 48px; height: 48px;">
                                        <i class="fa fa-user text-info"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">Cliente</h6>
                                </div>
                                <p class="text-muted mb-2 small">
                                    Datos del cliente destinatario del email como nombre, email, dirección.
                                </p>
                                <code class="small text-info">&#123;&#123;customer_name&#125;&#125;</code>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-light-subtle d-flex align-items-center justify-content-center me-3"
                                         style="width: 48px; height: 48px;">
                                        <i class="fa fa-file-alt text-warning"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">Documento/Pedido</h6>
                                </div>
                                <p class="text-muted mb-2 small">
                                    Información específica de documentos, pedidos y transacciones.
                                </p>
                                <code class="small text-warning">&#123;&#123;order_number&#125;&#125;</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-status').forEach(toggle => {
            toggle.addEventListener('change', async function() {
                const url = this.dataset.url;
                const variableId = this.dataset.variableId;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Show success message
                        console.log(data.message);
                    } else {
                        // Revert the toggle if failed
                        this.checked = !this.checked;
                        console.error(data.message);
                    }
                } catch (error) {
                    this.checked = !this.checked;
                    console.error('Error:', error);
                }
            });
        });
    });
</script>
@endpush

@endsection
