{{-- Content Block Partial --}}
@php
    $blockType = $block['type'] ?? 'text';
    $blockValue = $block['value'] ?? '';
    $blockIndex = $index ?? 0;
@endphp

<div class="content-block" data-block-index="{{ $blockIndex }}">
    <div class="d-flex align-items-start gap-2">
        <div class="block-handle">
            <i class="fas fa-grip-vertical fs-5"></i>
        </div>
        <div class="flex-grow-1">
            <div class="mb-2">
                <label class="form-label small mb-1">Tipo de Bloque</label>
                <select class="form-select form-select-sm block-type"
                        name="content[{{ $blockIndex }}][type]"
                        onchange="updateBlockFields(this)">
                    <option value="text" {{ $blockType === 'text' ? 'selected' : '' }}>Texto</option>
                    <option value="heading" {{ $blockType === 'heading' ? 'selected' : '' }}>Encabezado</option>
                    <option value="button" {{ $blockType === 'button' ? 'selected' : '' }}>Botón</option>
                    <option value="image" {{ $blockType === 'image' ? 'selected' : '' }}>Imagen</option>
                    <option value="html" {{ $blockType === 'html' ? 'selected' : '' }}>HTML Personalizado</option>
                </select>
            </div>

            <div class="block-fields">
                @if($blockType === 'text')
                    <div class="mb-2">
                        <label class="form-label small mb-1">Texto</label>
                        <textarea class="form-control form-control-sm"
                                  name="content[{{ $blockIndex }}][value]"
                                  rows="3"
                                  placeholder="Ingrese el texto aquí...">{{ $blockValue }}</textarea>
                    </div>

                @elseif($blockType === 'heading')
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label small mb-1">Título</label>
                            <input type="text"
                                   class="form-control form-control-sm"
                                   name="content[{{ $blockIndex }}][value]"
                                   value="{{ $blockValue }}"
                                   placeholder="Título">
                        </div>
                        <div class="col-4">
                            <label class="form-label small mb-1">Nivel</label>
                            <select class="form-select form-select-sm" name="content[{{ $blockIndex }}][level]">
                                <option value="h1" {{ ($block['level'] ?? 'h2') === 'h1' ? 'selected' : '' }}>H1</option>
                                <option value="h2" {{ ($block['level'] ?? 'h2') === 'h2' ? 'selected' : '' }}>H2</option>
                                <option value="h3" {{ ($block['level'] ?? 'h2') === 'h3' ? 'selected' : '' }}>H3</option>
                                <option value="h4" {{ ($block['level'] ?? 'h2') === 'h4' ? 'selected' : '' }}>H4</option>
                            </select>
                        </div>
                    </div>

                @elseif($blockType === 'button')
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Texto del Botón</label>
                            <input type="text"
                                   class="form-control form-control-sm"
                                   name="content[{{ $blockIndex }}][label]"
                                   value="{{ $block['label'] ?? '' }}"
                                   placeholder="Haz clic aquí">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">URL</label>
                            <input type="url"
                                   class="form-control form-control-sm"
                                   name="content[{{ $blockIndex }}][url]"
                                   value="{{ $block['url'] ?? '' }}"
                                   placeholder="https://">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">Estilo</label>
                            <select class="form-select form-select-sm" name="content[{{ $blockIndex }}][style]">
                                <option value="primary" {{ ($block['style'] ?? 'primary') === 'primary' ? 'selected' : '' }}>Primario</option>
                                <option value="secondary" {{ ($block['style'] ?? 'primary') === 'secondary' ? 'selected' : '' }}>Secundario</option>
                                <option value="success" {{ ($block['style'] ?? 'primary') === 'success' ? 'selected' : '' }}>Éxito</option>
                                <option value="danger" {{ ($block['style'] ?? 'primary') === 'danger' ? 'selected' : '' }}>Peligro</option>
                            </select>
                        </div>
                    </div>

                @elseif($blockType === 'image')
                    <div class="mb-2">
                        <label class="form-label small mb-1">URL de Imagen</label>
                        <input type="url"
                               class="form-control form-control-sm"
                               name="content[{{ $blockIndex }}][src]"
                               value="{{ $block['src'] ?? '' }}"
                               placeholder="https://ejemplo.com/imagen.jpg">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Texto Alternativo</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               name="content[{{ $blockIndex }}][alt]"
                               value="{{ $block['alt'] ?? '' }}"
                               placeholder="Descripción de la imagen">
                    </div>

                @elseif($blockType === 'html')
                    <div class="mb-2">
                        <label class="form-label small mb-1">HTML Personalizado</label>
                        <textarea class="form-control form-control-sm font-monospace"
                                  name="content[{{ $blockIndex }}][html]"
                                  rows="4"
                                  placeholder="<div>...</div>">{{ $block['html'] ?? '' }}</textarea>
                        <small class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i> Solo para usuarios avanzados
                        </small>
                    </div>
                @endif
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-light-danger" onclick="removeBlock(this)">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</div>
