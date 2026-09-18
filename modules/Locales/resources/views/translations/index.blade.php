@extends('layouts.theme')

@section('title', 'Traducciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Traducciones'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Traducciones</h5>
                        <p class="small mb-0 text-muted">Gestiona los textos del tema y de cada módulo por idioma</p>
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
            @php
                $modulesCount = $groupsByModule->count();
                $groupsCount = $groupsByModule->sum(fn ($m) => count($m['groups']));
            @endphp
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Idiomas activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $locales->count() }}</h4>
                                <small class="text-muted">Con traducciones disponibles</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Módulos</h6>
                                <h4 class="mb-1 fw-bold">{{ $modulesCount }}</h4>
                                <small class="text-muted">Con archivos de traducción</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Grupos</h6>
                                <h4 class="mb-1 fw-bold">{{ $groupsCount }}</h4>
                                <small class="text-muted">Archivos editables</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter by module --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ request()->url() }}" id="module-filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <select name="module" id="module-filter" class="form-select">
                                <option value="">Todos los módulos</option>
                                @foreach ($groupsByModule as $moduleKey => $moduleData)
                                    <option value="{{ $moduleKey }}" {{ $moduleFilter === $moduleKey ? 'selected' : '' }}>
                                        {{ $moduleData['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            @if ($moduleFilter !== '')
                                <a href="{{ request()->url() }}" class="btn btn-outline-secondary" title="Limpiar filtro">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Groups by module --}}
            <div class="card-body">
                @if ($locales->isEmpty())
                    <div class="text-center py-5">
                        <h6 class="mb-1">No hay idiomas activos</h6>
                        <p class="text-muted mb-3">Activa al menos un idioma para gestionar traducciones</p>
                        <a href="{{ route('locales.index') }}" class="btn btn-sm btn-primary">Ver idiomas</a>
                    </div>
                @elseif ($groupsByModule->isEmpty())
                    <div class="text-center py-5">
                        <h6 class="mb-1">No se encontraron traducciones</h6>
                        <p class="text-muted mb-0">No hay archivos de traducción disponibles para este filtro.</p>
                    </div>
                @else
                    @foreach ($groupsByModule as $moduleKey => $moduleData)
                        <div class="mb-4">
                            <h6 class="fw-bold mb-2 text-uppercase small text-muted">
                                {{ $moduleData['label'] }}
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:25%">Archivo</th>
                                            <th>Idioma</th>
                                            <th style="width:15%">Código</th>
                                            <th style="width:12%">Formato</th>
                                            <th style="width:8%" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($moduleData['groups'] as $group)
                                            @foreach ($locales as $locale)
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark border font-monospace">
                                                            {{ $group['file'] }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold">{{ $locale->name }}</div>
                                                        <small class="text-muted">{{ $locale->native_name }}</small>
                                                    </td>
                                                    <td><code>{{ $locale->code }}</code></td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border text-uppercase">
                                                            {{ $group['format'] }}
                                                        </span>
                                                    </td>
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
                        </div>
                    @endforeach
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

    $('#module-filter').select2({ width: '100%' }).on('change', function () {
        $('#module-filter-form').submit();
    });
});
</script>
@endpush
