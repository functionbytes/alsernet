@extends('layouts.theme')

@section('title', 'Etiquetas de producto')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Etiquetas de producto'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Etiquetas de producto</h5>
                        <p class="small mb-0 text-muted">Administra las etiquetas que se muestran en los productos</p>
                    </div>
                    <a href="{{ route('ecommerce.product-labels.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva etiqueta
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($labels->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Color de fondo</th>
                                    <th>Color de texto</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($labels as $label)
                                    <tr>
                                        <td>
                                            <span class="badge rounded-pill" style="background-color: {{ $label->color ?? '#666666' }}; color: {{ $label->text_color ?? '#ffffff' }}">
                                                {{ $label->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="rounded border" style="display:inline-block;width:20px;height:20px;background-color:{{ $label->color ?? '#666666' }}"></span>
                                                <small class="text-muted">{{ $label->color ?? '—' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="rounded border" style="display:inline-block;width:20px;height:20px;background-color:{{ $label->text_color ?? '#ffffff' }}"></span>
                                                <small class="text-muted">{{ $label->text_color ?? '—' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $label->status === 'published' ? 'success' : 'secondary' }}">
                                                {{ $label->status === 'published' ? 'Publicado' : 'Borrador' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.product-labels.edit', $label) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.product-labels.destroy', $label) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta etiqueta?')">Eliminar</button>
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
                        <i class="fas fa-tags fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay etiquetas de producto</h5>
                        <a href="{{ route('ecommerce.product-labels.create') }}" class="btn btn-primary">Crear primera etiqueta</a>
                    </div>
                @endif
            </div>

            @if($labels->hasPages())
                <div class="card-footer">{{ $labels->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
