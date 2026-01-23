@extends('layouts.theme')

@section('content')

    @php
        if (!function_exists('getLocaleLabel')) {
            function getLocaleLabel($locale) {
                return match ($locale) {
                    'es' => 'Español',
                    'en' => 'English',
                    'de' => 'Deutsch',
                    'it' => 'Italiano',
                    'pt' => 'Português',
                    default => strtoupper($locale),
                };
            }
        }
    @endphp

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <div class="card-body">
                    <div class="d-flex no-block align-items-center mb-3">
                        <h5 class="mb-0">Administrador de Traducciones</h5>
                    </div>
                    <p class="card-subtitle mb-3">
                        Este espacio está diseñado para permitirte
                        <mark><code>gestionar</code></mark>
                        todas las traducciones de la plataforma por idioma y archivo. Selecciona un archivo y el idioma
                        correspondiente para editar las traducciones.
                    </p>

                    <!-- Search Bar -->
                    <div class="row mb-3">
                        <div class="col-12 col-md-6">
                            <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                                <input
                                        type="text"
                                        class="form-control border-start-0"
                                        id="searchTranslations"
                                        placeholder="Buscar archivos o idiomas..."
                                        value="{{ $searchQuery }}"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Info Alert -->
                    <div class="alert alert-info bg-info-subtle border-0 mb-3" role="alert">
                        <i class="fas fa-circle-info me-2"></i>
                        <strong>Nota:</strong> Los cambios se guardarán directamente en los archivos de lenguaje del
                        sistema.
                    </div>

                    <!-- Translation Files Table -->
                    @if(empty($translationsByFile))
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-file-code fs-1 mb-3 d-block"></i>
                            <p class="mb-0">No hay archivos de traducción disponibles.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table search-table align-middle text-nowrap mb-0">
                                <thead class="header-item">
                                <tr>
                                    <th class="fw-bold">Archivo</th>
                                    <th class="fw-bold text-center">Claves</th>
                                    <th class="fw-bold text-center">Idiomas</th>
                                    <th class="fw-bold text-end">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($translationsByFile as $file => $fileData)
                                    <tr class="search-items">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-language text-primary fs-5"></i>
                                                <div>
                                                    <span class="fw-semibold d-block">{{ $fileData['label'] }}</span>
                                                    <small class="text-muted"><code>{{ $file }}.php</code></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-key me-1"></i>{{ count($fileData['locales']) > 0 ? $fileData['locales'][0]['count'] : 0 }}
                                    </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                                @foreach($fileData['locales'] as $localeData)
                                                    <span class="badge bg-primary">{{ strtoupper($localeData['locale']) }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm" role="group">
                                                @foreach($fileData['locales'] as $localeData)
                                                    <a href="{{ route('manager.backups.translations.edit', [$localeData['locale'], $file]) }}"
                                                       class="btn btn-outline-primary"
                                                       title="Editar {{ $localeData['locale_label'] }}">
                                                        <i class="fas fa-edit"></i>
                                                        <span class="d-none d-sm-inline ms-1">{{ strtoupper($localeData['locale']) }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Info Section -->
    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <h6 class="mb-3">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                Información
                            </h6>
                            <p class="text-muted small mb-2">
                                Sistema centralizado para gestionar todas las traducciones de la plataforma.
                            </p>
                            <p class="text-muted small mb-0">
                                Las traducciones se almacenan en archivos de lenguaje y pueden ser editadas por idioma y
                                módulo.
                            </p>
                        </div>
                        <div class="col-12 col-md-6">
                            <h6 class="mb-3">
                                <i class="fas fa-globe text-success me-2"></i>
                                Idiomas Soportados
                            </h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($locales as $locale)
                                    <span class="badge bg-secondary">{{ getLocaleLabel($locale) }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchTranslations');
            const tableRows = document.querySelectorAll('tbody tr');

            if (searchInput) {
                searchInput.addEventListener('input', function (e) {
                    const query = e.target.value.toLowerCase();

                    tableRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
                });
            }
        });
    </script>

@endsection
