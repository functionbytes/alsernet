@extends('layouts.theme')

@section('title', 'Atributos personalizados')

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Atributos personalizados</h5>
                        <p class="small mb-0 text-muted">Define campos personalizados para clientes y conversaciones</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.chat.custom-attributes.index', ['model' => $model]) }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.chat.custom-attributes.create') }}" class="btn btn-primary">
                            Nuevo atributo
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $attributes->total() }}</h4>
                                        <small class="text-muted">Atributos configurados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Clientes</h6>
                                        <h4 class="mb-1 fw-bold">{{ $customerCount }}</h4>
                                        <small class="text-muted">Atributos de clientes</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Conversaciones</h6>
                                        <h4 class="mb-1 fw-bold">{{ $conversationCount }}</h4>
                                        <small class="text-muted">Atributos de conversaciones</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Model Filter -->
            <div class="card-body border-bottom">
                <div class="row align-items-center mb-3">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <a href="{{ route('settings.chat.custom-attributes.index', ['model' => 'contact']) }}"
                               class="btn btn-{{ $model === 'contact' ? 'primary' : 'outline-primary' }}">
                                Clientes
                            </a>
                            <a href="{{ route('settings.chat.custom-attributes.index', ['model' => 'conversation']) }}"
                               class="btn btn-{{ $model === 'conversation' ? 'primary' : 'outline-primary' }}">
                                Conversaciones
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Search Section -->
                <form method="GET" action="{{ route('settings.chat.custom-attributes.index') }}">
                    <input type="hidden" name="model" value="{{ $model }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar atributos..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Attributes List -->
            <div class="card-body">
                @if($attributes->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="20%">Nombre</th>
                                <th width="15%">Clave</th>
                                <th width="15%">Tipo</th>
                                <th width="15%">Modelo</th>
                                <th width="15%">Creado</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($attributes as $attribute)
                                <tr>
                                    <td>
                                        <a href="{{ route('settings.chat.custom-attributes.edit', $attribute) }}" class="text-decoration-none">
                                            <strong>{{ $attribute->attribute_display_name }}</strong>
                                        </a>
                                        @if($attribute->attribute_description)
                                            <small class="d-block text-muted">{{ Str::limit($attribute->attribute_description, 40) }}</small>
                                        @endif
                                    </td>
                                    <td><code>{{ $attribute->attribute_key }}</code></td>
                                    <td>
                                        @php
                                            $types = ['Texto', 'Número', 'Moneda', 'Porcentaje', 'Enlace', 'Fecha', 'Lista', 'Checkbox'];
                                        @endphp
                                        <span class="badge bg-secondary">{{ $types[$attribute->attribute_display_type] ?? 'Desconocido' }}</span>
                                    </td>
                                    <td>
                                        @if($attribute->attribute_model === 0)
                                            <span class="badge bg-info-subtle text-info">Clientes</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Conversaciones</span>
                                        @endif
                                    </td>
                                    <td>{{ $attribute->created_at->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.custom-attributes.edit', $attribute) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button"
                                                        class="dropdown-item text-danger delete-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#delete-modal"
                                                        data-url="{{ route('settings.chat.custom-attributes.destroy', $attribute) }}"
                                                        data-title="Eliminar atributo: {{ $attribute->attribute_display_name }}">
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
                                <i class="fas fa-tags fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay atributos personalizados para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primer atributo personalizado para almacenar información adicional
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.chat.custom-attributes.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer atributo
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($attributes->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $attributes->firstItem() }}</strong> a <strong>{{ $attributes->lastItem() }}</strong>
                            de <strong>{{ $attributes->total() }}</strong> atributos
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $attributes->appends(['model' => $model, 'search' => request('search')])->links() }}
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
