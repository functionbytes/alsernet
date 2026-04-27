@extends('layouts.theme')

@section('title', 'Páginas legales')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Páginas legales'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Páginas legales</h5>
                        <p class="small mb-0 text-muted">Administra las páginas de información legal de la tienda</p>
                    </div>
                    <a href="{{ route('ecommerce.legal-pages.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva página
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($legalPages->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Slug</th>
                                    <th>Título</th>
                                    <th>Estado</th>
                                    <th>Actualizado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($legalPages as $legalPage)
                                    <tr>
                                        <td><code class="small">{{ $legalPage->slug }}</code></td>
                                        <td>
                                            <a href="{{ route('ecommerce.legal-pages.edit', $legalPage) }}" class="text-decoration-none fw-semibold">
                                                {{ $legalPage->title }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($legalPage->is_published)
                                                <span class="badge bg-success">Publicado</span>
                                            @else
                                                <span class="badge bg-secondary">Borrador</span>
                                            @endif
                                        </td>
                                        <td><small class="text-muted">{{ $legalPage->updated_at->format('d/m/Y H:i') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('ecommerce.legal-pages.edit', $legalPage) }}">Editar</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('shop.legal.show', $legalPage->slug) }}" target="_blank">Ver en tienda</a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.legal-pages.destroy', $legalPage) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta página legal?')">Eliminar</button>
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
                        <i class="fas fa-file-contract fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay páginas legales</h5>
                        <a href="{{ route('ecommerce.legal-pages.create') }}" class="btn btn-primary">Crear primera página</a>
                    </div>
                @endif
            </div>

            @if($legalPages->hasPages())
                <div class="card-footer">{{ $legalPages->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
