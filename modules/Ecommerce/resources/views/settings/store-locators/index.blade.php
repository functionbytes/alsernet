@extends('layouts.theme')

@section('title', 'Localizadores de tiendas')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Localizadores de tiendas'])
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold">Localizadores de tiendas</h6>
                    <div class="form-text mb-0">Todas las listas de sus cadenas, tiendas principales, sucursales, etc.</div>
                </div>
                <a href="{{ route('settings.ecommerce.store-locators.create') }}" class="btn btn-primary btn-sm">
                    Agregar nuevo
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Correo electrónico</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th class="text-center">Es primario</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($storeLocators as $storeLocator)
                            <tr>
                                <td class="fw-semibold">{{ $storeLocator->name }}</td>
                                <td>
                                    @if($storeLocator->email)
                                        <a href="mailto:{{ $storeLocator->email }}" class="text-primary">{{ $storeLocator->email }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $storeLocator->phone ?? '—' }}</td>
                                <td>{{ $storeLocator->address ?? '—' }}</td>
                                <td class="text-center">
                                    @if($storeLocator->is_primary)
                                        <span class="text-success fw-semibold">Sí</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="{{ route('settings.ecommerce.store-locators.edit', $storeLocator) }}" class="dropdown-item">Editar</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('settings.ecommerce.store-locators.destroy', $storeLocator) }}" method="POST"
                                                    onsubmit="return confirm('¿Eliminar esta tienda?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item">Eliminar</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No hay tiendas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
