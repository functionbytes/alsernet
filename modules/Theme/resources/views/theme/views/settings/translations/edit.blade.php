@extends('layouts.theme')

@section('content')

    @include('core::components.card', [
        'title' => 'Editar Traducciones',
        'subtitle' => $locale_label . ' - ' . $file_label
    ])

    <style>
        .field-input,
        .field-textarea {
            font-size: 13px;
            border: 1.5px solid #e0e0e0;
            transition: all 0.2s ease;
        }

        .field-input:focus,
        .field-textarea:focus {
            border-color: #90bb13;
            background-color: #fafbfc;
            box-shadow: 0 0 0 3px rgba(144, 187, 19, 0.08);
        }

        .field-key {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .translation-field {
            padding: 1rem;
            background: #ffffff;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .translation-field:hover {
            background-color: #fafbfc;
            border-left: 3px solid #90bb13;
            padding-left: calc(1rem - 3px);
        }

        .section-group {
            border-left: 4px solid #90bb13 !important;
        }

        .section-group .card-header {
            border-bottom-color: #e0e0e0 !important;
        }

        .section-content {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
        }
    </style>

    <form method="POST" action="{{ route('manager.backups.translations.update', [$locale, $file]) }}"
          id="translationForm">
        @csrf
        @method('PATCH')

        <div class="widget-content searchable-container list">

            <!-- Toolbar -->
            <div class="d-flex gap-2 mb-3" style="flex-wrap: wrap; align-items: center;">
                <a href="{{ route('manager.backups.translations.index') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save me-1"></i> Guardar
                </button>
            </div>

            <!-- Info Card with search -->
            <div class="card card-body mb-3">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg-6">
                        <div>
                            <h6 class="mb-1 fw-bold">
                                <i class="fas fa-globe me-2 text-primary"></i>{{ $locale_label }}
                            </h6>
                            <small class="text-muted">
                                <code>{{ $file_label }} ({{ $file }}.php)</code>
                            </small>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                            <input
                                    type="text"
                                    id="searchInput"
                                    class="form-control border-start-0"
                                    placeholder="Buscar claves..."
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert info -->
            <div class="alert alert-info bg-info-subtle border-0 mb-4" role="alert">
                <i class="fas fa-circle-info me-2"></i>
                <strong>Total de claves:</strong> {{ countKeys($content) }} -
                <strong>Idioma:</strong> {{ $locale_label }}
            </div>

            <!-- Translation Sections -->
            @include('theme.views.backups.translations.partials.translation-fields-modern', [
                'data' => $content,
                'baseData' => $baseContent ?? [],
                'prefix' => ''
            ])

        </div>

        <!-- Submit Footer -->
        <div class="d-flex gap-2 mt-4" style="flex-wrap: wrap;">
            <a href="{{ route('manager.backups.translations.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-times me-1"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-save me-1"></i> Guardar Cambios
            </button>
        </div>

    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const sectionGroups = document.querySelectorAll('.section-group');

            if (searchInput) {
                searchInput.addEventListener('input', function (e) {
                    const query = e.target.value.toLowerCase();

                    sectionGroups.forEach(section => {
                        let visibleFields = 0;

                        const fields = section.querySelectorAll('.translation-field');
                        fields.forEach(field => {
                            const label = field.querySelector('label') ? field.querySelector('label').textContent.toLowerCase() : '';
                            const key = field.querySelector('.field-key') ? field.querySelector('.field-key').textContent.toLowerCase() : '';
                            const inputs = field.querySelectorAll('textarea, input[type="text"]');
                            let value = '';
                            inputs.forEach(input => {
                                value += input.value.toLowerCase() + ' ';
                            });

                            const matches = label.includes(query) || key.includes(query) || value.includes(query);

                            field.style.display = matches ? '' : 'none';
                            if (matches) visibleFields++;
                        });

                        section.style.display = visibleFields > 0 ? '' : 'none';
                    });
                });
            }

            // Track original form state
            const form = document.getElementById('translationForm');
            const originalState = {};

            if (form) {
                // Store initial values
                const inputs = form.querySelectorAll('input[type="text"]:not(#searchInput), textarea');
                inputs.forEach(field => {
                    originalState[field.name] = field.value;
                });

                // Form submission validation - check for actual changes
                form.addEventListener('submit', function (e) {
                    let hasChanges = false;

                    inputs.forEach(field => {
                        if (field.value !== originalState[field.name]) {
                            hasChanges = true;
                        }
                    });

                    if (!hasChanges) {
                        e.preventDefault();
                        alert('No hay cambios para guardar. Por favor, modifica al menos un campo.');
                        return false;
                    }
                });

                // Enable/disable submit button based on changes
                inputs.forEach(field => {
                    field.addEventListener('input', function () {
                        let hasChanges = false;
                        inputs.forEach(f => {
                            if (f.value !== originalState[f.name]) {
                                hasChanges = true;
                            }
                        });

                        // Update button states
                        const submitButtons = form.querySelectorAll('button[type="submit"]');
                        submitButtons.forEach(btn => {
                            btn.disabled = !hasChanges;
                            if (hasChanges) {
                                btn.classList.add('btn-primary');
                                btn.classList.remove('btn-secondary');
                            } else {
                                btn.classList.remove('btn-primary');
                                btn.classList.add('btn-secondary');
                            }
                        });
                    });
                });

                // Initial button state (disabled)
                const submitButtons = form.querySelectorAll('button[type="submit"]');
                submitButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('btn-secondary');
                    btn.classList.remove('btn-primary');
                });
            }
        });

        function countKeys(obj, count = 0) {
            for (let key in obj) {
                if (obj.hasOwnProperty(key)) {
                    if (typeof obj[key] === 'object') {
                        count = countKeys(obj[key], count);
                    } else {
                        count++;
                    }
                }
            }
            return count;
        }
    </script>

@endsection

@php
    function countKeys($array) {
        $count = 0;
        array_walk_recursive($array, function($item) use (&$count) {
            $count++;
        });
        return $count;
    }
@endphp
