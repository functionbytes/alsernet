@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $pageTitle }}</h5>
                        <p class="small mb-0 text-muted">Gestiona las categorías de preguntas frecuentes</p>
                    </div>
                    @can('faqs.categories.create')
                        <a href="{{ route('faqs.categories.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva categoría
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card-body p-4">
                <form method="GET" action="{{ route('faqs.categories.index') }}" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Buscar..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search me-1"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Preguntas</th>
                                <th>Orden</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td><span class="fw-semibold">{{ $category->name }}</span></td>
                                    <td>{{ Str::limit($category->description, 60) ?: '-' }}</td>
                                    <td>{{ $category->faqs_count }}</td>
                                    <td>{{ $category->order }}</td>
                                    <td>
                                        @if($category->status->value === 'published')
                                            <span class="badge bg-success-subtle text-success">{{ $category->status->label() }}</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $category->status->label() }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('faqs.categories.update')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('faqs.categories.edit', $category) }}">
                                                            <i class="fas fa-edit me-2 text-primary"></i> Editar
                                                        </a>
                                                    </li>
                                                @endcan
                                                @can('faqs.categories.delete')
                                                    <li>
                                                        <form method="POST" action="{{ route('faqs.categories.destroy', $category) }}" onsubmit="return confirm('¿Eliminar esta categoría?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-trash-alt me-2"></i> Eliminar
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No hay categorías registradas</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $categories->withQueryString()->links() }}
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
