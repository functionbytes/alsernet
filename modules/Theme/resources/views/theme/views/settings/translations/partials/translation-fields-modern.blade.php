@forelse($data as $key => $value)
    @if(is_array($value))
        <!-- Grupo de traducción (sección) -->
        <div class="card mb-4 border-0 shadow-sm section-group">
            <div class="card-header bg-light border-bottom border-primary border-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-cube text-primary me-2"></i>
                        {{ ucfirst(str_replace('_', ' ', $key)) }}
                    </h6>
                    <span class="badge bg-primary">
                        <i class="fas fa-key me-1"></i>{{ count($value) }} claves
                    </span>
                </div>
            </div>

            <div class="card-body section-content bg-white pt-4">
                @foreach($value as $subKey => $subValue)
                    @if(is_array($subValue))
                        <!-- Sub-grupo dentro de la sección -->
                        <div class="mb-4 pb-3 border-bottom">
                            <h6 class="text-muted mb-3 fw-semibold" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                <i class="fas fa-code text-info me-2"></i>
                                {{ ucfirst(str_replace('_', ' ', $subKey)) }}
                            </h6>

                            @foreach($subValue as $fieldKey => $fieldValue)
                                @if(!is_array($fieldValue))
                                    <div class="mb-3 translation-field">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <label class="form-label fw-semibold mb-0">
                                                {{ ucfirst(str_replace('_', ' ', $fieldKey)) }}
                                            </label>
                                            <small class="text-muted">
                                                <code class="field-key bg-light px-2 py-1 rounded">{{ $key }}.{{ $subKey }}.{{ $fieldKey }}</code>
                                            </small>
                                        </div>

                                        @if(is_string($fieldValue) && strlen($fieldValue) > 80)
                                            <textarea
                                                class="form-control field-textarea border rounded-2"
                                                name="translations[{{ $key }}][{{ $subKey }}][{{ $fieldKey }}]"
                                                rows="3"
                                                placeholder="Ingresa la traducción..."
                                            >{{ $fieldValue }}</textarea>
                                        @else
                                            <input
                                                type="text"
                                                class="form-control field-input border rounded-2"
                                                name="translations[{{ $key }}][{{ $subKey }}][{{ $fieldKey }}]"
                                                value="{{ $fieldValue }}"
                                                placeholder="Ingresa la traducción..."
                                            />
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <!-- Campo directo dentro de la sección -->
                        <div class="mb-3 translation-field">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <label class="form-label fw-semibold mb-0">
                                    {{ ucfirst(str_replace('_', ' ', $subKey)) }}
                                </label>
                                <small class="text-muted">
                                    <code class="field-key bg-light px-2 py-1 rounded">{{ $key }}.{{ $subKey }}</code>
                                </small>
                            </div>

                            @if(is_string($subValue) && strlen($subValue) > 80)
                                <textarea
                                    class="form-control field-textarea border rounded-2"
                                    name="translations[{{ $key }}][{{ $subKey }}]"
                                    rows="3"
                                    placeholder="Ingresa la traducción..."
                                >{{ $subValue }}</textarea>
                            @else
                                <input
                                    type="text"
                                    class="form-control field-input border rounded-2"
                                    name="translations[{{ $key }}][{{ $subKey }}]"
                                    value="{{ $subValue }}"
                                    placeholder="Ingresa la traducción..."
                                />
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @else
        <!-- Campo raíz directo -->
        <div class="card mb-4 border-0 shadow-sm section-group">
            <div class="card-header bg-light border-bottom border-primary border-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-cube text-primary me-2"></i>
                    {{ ucfirst(str_replace('_', ' ', $key)) }}
                </h6>
            </div>
            <div class="card-body section-content bg-white">
                <div class="translation-field">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <label class="form-label fw-semibold mb-0">
                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                        </label>
                        <small class="text-muted">
                            <code class="field-key bg-light px-2 py-1 rounded">{{ $key }}</code>
                        </small>
                    </div>

                    @if(is_string($value) && strlen($value) > 80)
                        <textarea
                            class="form-control field-textarea border rounded-2"
                            name="translations[{{ $key }}]"
                            rows="3"
                            placeholder="Ingresa la traducción..."
                        >{{ $value }}</textarea>
                    @else
                        <input
                            type="text"
                            class="form-control field-input border rounded-2"
                            name="translations[{{ $key }}]"
                            value="{{ $value }}"
                            placeholder="Ingresa la traducción..."
                        />
                    @endif
                </div>
            </div>
        </div>
    @endif
@empty
    <div class="text-center py-5 text-muted">
        <i class="fas fa-inbox fs-1 mb-3 d-block"></i>
        <p class="mb-0">No hay traducciones disponibles para mostrar.</p>
    </div>
@endforelse
