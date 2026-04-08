@extends('layouts.theme')

@section('title', 'Traducciones')

@section('content')

    @include('core::components.card', ['title' => 'Traducciones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Traducciones del tema</h5>
                        <p class="small mb-0 text-muted">Gestiona los textos del sitio por idioma</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('locales.index') }}">Ver idiomas</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Idiomas activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $locales->count() }}</h4>
                                <small class="text-muted">Con traducciones disponibles</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Grupos</h6>
                                <h4 class="mb-1 fw-bold">{{ count($groups) }}</h4>
                                <small class="text-muted">Archivos de traducción</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Entradas totales</h6>
                                <h4 class="mb-1 fw-bold">{{ $locales->count() * count($groups) }}</h4>
                                <small class="text-muted">Combinaciones idioma × grupo</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if ($locales->isEmpty())
                    <div class="text-center py-5">
                        <h6 class="mb-1">No hay idiomas activos</h6>
                        <p class="text-muted mb-3">Activa al menos un idioma para gestionar traducciones</p>
                        <a href="{{ route('locales.index') }}" class="btn btn-sm btn-primary">Ver idiomas</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Idioma</th>
                                    <th>Grupo</th>
                                    <th>Código</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($locales as $locale)
                                    @foreach ($groups as $group)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fs-5">{{ $locale->flag }}</span>
                                                    <div>
                                                        <div class="fw-semibold">{{ $locale->name }}</div>
                                                        <small class="text-muted">{{ $locale->native_name }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ ucfirst($group['name']) }}</span>
                                            </td>
                                            <td><code>{{ $locale->code }}</code></td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown"
                                                       data-bs-auto-close="true" data-bs-boundary="viewport">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('locales.translations.edit', ['locale' => $locale->code, 'group' => $group['name']]) }}">
                                                                Editar traducciones
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
