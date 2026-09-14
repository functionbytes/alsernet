@forelse($data as $key => $value)
    @if(is_array($value))
        <!-- Grupo de traducción -->
        <div class="translation-group">
            <h6>
                <i class="fas fa-folder me-2"></i>
                {{ ucfirst(str_replace('_', ' ', $key)) }}
            </h6>

            @foreach($value as $subKey => $subValue)
                @if(is_array($subValue))
                    <!-- Sub-grupo -->
                    <div style="margin-left: 20px;">
                        <h6 class="mb-3 mt-3" style="color: #666; font-size: 13px;">
                            <i class="fas fa-file-alt me-2"></i>
                            {{ ucfirst(str_replace('_', ' ', $subKey)) }}
                        </h6>

                        @foreach($subValue as $fieldKey => $fieldValue)
                            @if(!is_array($fieldValue))
                                <div class="form-group mb-3">
                                    <label for="{{ $prefix }}{{ $key }}_{{ $subKey }}_{{ $fieldKey }}" class="form-label">
                                        <strong>{{ ucfirst(str_replace('_', ' ', $fieldKey)) }}</strong>
                                    </label>
                                    @if(is_string($fieldValue) && strlen($fieldValue) > 100)
                                        <textarea
                                            class="form-control"
                                            name="translations[{{ $key }}][{{ $subKey }}][{{ $fieldKey }}]"
                                            id="{{ $prefix }}{{ $key }}_{{ $subKey }}_{{ $fieldKey }}"
                                            @if(in_array($fieldKey, ['name', 'label', 'title']))required @endif
                                        >{{ $fieldValue }}</textarea>
                                    @else
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="translations[{{ $key }}][{{ $subKey }}][{{ $fieldKey }}]"
                                            value="{{ $fieldValue }}"
                                            id="{{ $prefix }}{{ $key }}_{{ $subKey }}_{{ $fieldKey }}"
                                            @if(in_array($fieldKey, ['name', 'label', 'title']))required @endif
                                        />
                                    @endif
                                    <small class="form-text text-muted d-block mt-1">
                                        Clave: <code>{{ $key }}.{{ $subKey }}.{{ $fieldKey }}</code>
                                    </small>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <!-- Campo directo -->
                    <div class="form-group mb-3">
                        <label for="{{ $prefix }}{{ $key }}_{{ $subKey }}" class="form-label">
                            <strong>{{ ucfirst(str_replace('_', ' ', $subKey)) }}</strong>
                        </label>
                        @if(is_string($subValue) && strlen($subValue) > 100)
                            <textarea
                                class="form-control"
                                name="translations[{{ $key }}][{{ $subKey }}]"
                                id="{{ $prefix }}{{ $key }}_{{ $subKey }}"
                                @if(in_array($subKey, ['name', 'label', 'title']))required @endif
                            >{{ $subValue }}</textarea>
                        @else
                            <input
                                type="text"
                                class="form-control"
                                name="translations[{{ $key }}][{{ $subKey }}]"
                                value="{{ $subValue }}"
                                id="{{ $prefix }}{{ $key }}_{{ $subKey }}"
                                @if(in_array($subKey, ['name', 'label', 'title']))required @endif
                            />
                        @endif
                        <small class="form-text text-muted d-block mt-1">
                            Clave: <code>{{ $key }}.{{ $subKey }}</code>
                        </small>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <!-- Campo raíz directo -->
        <div class="form-group mb-3">
            <label for="{{ $prefix }}{{ $key }}" class="form-label">
                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}</strong>
            </label>
            @if(is_string($value) && strlen($value) > 100)
                <textarea
                    class="form-control"
                    name="translations[{{ $key }}]"
                    id="{{ $prefix }}{{ $key }}"
                    @if(in_array($key, ['name', 'label', 'title']))required @endif
                >{{ $value }}</textarea>
            @else
                <input
                    type="text"
                    class="form-control"
                    name="translations[{{ $key }}]"
                    value="{{ $value }}"
                    id="{{ $prefix }}{{ $key }}"
                    @if(in_array($key, ['name', 'label', 'title']))required @endif
                />
            @endif
            <small class="form-text text-muted d-block mt-1">
                Clave: <code>{{ $key }}</code>
            </small>
        </div>
    @endif
@empty
    <div class="alert alert-warning">
        No hay traducciones disponibles para mostrar.
    </div>
@endforelse
