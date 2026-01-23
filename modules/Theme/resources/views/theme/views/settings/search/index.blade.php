@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de búsqueda'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Search Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom ">
                <div>
                    <h5 class="mb-1 fw-bold">Configuración de búsqueda</h5>
                    <p class="small mb-0">Configura el sistema de búsqueda de la aplicación, incluyendo drivers,
                        longitud mínima de búsqueda y módulos donde se permite buscar.</p>
                </div>
            </div>

            <!-- Form -->
            <div class="card-body">
                <form method="POST" action="{{ route('manager.backups.search.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Search Configuration -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Configuración general</h6>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="searchEnabled"
                                                   name="search_enabled"
                                                   value="1" {{ $settings['search_enabled'] ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="searchEnabled">
                                                Habilitar búsqueda
                                            </label>
                                        </div>
                                        <small class="text-muted">Permite realizar búsquedas en la aplicación</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="searchDriver" class="form-label fw-semibold">Driver de
                                            búsqueda</label>
                                        <select class="form-select" id="searchDriver" name="search_driver" required>
                                            <option value="database" {{ $settings['search_driver'] === 'database' ? 'selected' : '' }}>
                                                Base de datos (LIKE)
                                            </option>
                                            <option value="algolia" {{ $settings['search_driver'] === 'algolia' ? 'selected' : '' }}>
                                                Algolia
                                            </option>
                                            <option value="meilisearch" {{ $settings['search_driver'] === 'meilisearch' ? 'selected' : '' }}>
                                                Meilisearch
                                            </option>
                                            <option value="typesense" {{ $settings['search_driver'] === 'typesense' ? 'selected' : '' }}>
                                                Typesense
                                            </option>
                                        </select>
                                        <small class="text-muted">Motor de búsqueda a utilizar</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="minSearchLength" class="form-label fw-semibold">Longitud mínima de
                                            búsqueda</label>
                                        <input type="number" class="form-control" id="minSearchLength"
                                               name="min_search_length" value="{{ $settings['min_search_length'] }}"
                                               min="1" max="10" required>
                                        <small class="text-muted">Número mínimo de caracteres para realizar una
                                            búsqueda</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="searchResultsPerPage" class="form-label fw-semibold">Resultados por
                                            página</label>
                                        <input type="number" class="form-control" id="searchResultsPerPage"
                                               name="search_results_per_page"
                                               value="{{ $settings['search_results_per_page'] }}" min="5" max="100"
                                               required>
                                        <small class="text-muted">Cantidad de resultados a mostrar por página</small>
                                    </div>

                                    <div class="mb-0">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="searchHighlightResults"
                                                   name="search_highlight_results"
                                                   value="1" {{ $settings['search_highlight_results'] ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="searchHighlightResults">
                                                Resaltar resultados
                                            </label>
                                        </div>
                                        <small class="text-muted">Resalta los términos de búsqueda en los
                                            resultados</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search modules -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold">Módulos de búsqueda</h6>
                                    <p class="text-muted small mb-3">Selecciona los módulos donde se permite realizar
                                        búsquedas</p>

                                    @php
                                        $availableModules = [
                                            'users' => 'Usuarios',
                                            'tickets' => 'Tickets',
                                            'inventaries' => 'Productos',
                                            'orders' => 'Pedidos',
                                            'customers' => 'Clientes',
                                            'conversations' => 'Conversaciones',
                                            'campaigns' => 'Campañas',
                                            'documents' => 'Documentos',
                                            'faqs' => 'FAQs',
                                            'events' => 'Eventos',
                                        ];
                                        $selectedModules = $settings['search_modules'] ?? [];
                                    @endphp

                                    @foreach($availableModules as $module => $label)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="module_{{ $module }}"
                                                   name="search_modules[]"
                                                   value="{{ $module }}" {{ in_array($module, $selectedModules) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="module_{{ $module }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach

                                    <div class="alert alert-info border-0 bg-info-subtle text-info mt-3 mb-0">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="ti ti-info-circle fs-5"></i>
                                            <div>
                                                <strong>Nota:</strong> Deseleccionar módulos puede mejorar el
                                                rendimiento de búsqueda reduciendo el alcance.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Guardar configuración</button>
                                <a href="{{ route('manager.backups') }}" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection
