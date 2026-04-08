{{-- Content Editor Tab --}}
<form method="POST" action="{{ route('manager.helpdesk.campaigns.update', $campaign) }}" id="content-form">
    @csrf
    @method('PUT')

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="fas fa-layer-group"></i> Bloques de Contenido
            </h5>
            <button type="button" class="btn btn-sm btn-primary" onclick="addContentBlock()">
                <i class="fas fa-plus"></i> Agregar Bloque
            </button>
        </div>

        @php
            $content = old('content', $campaign->content ?? []);
        @endphp

        @if(empty($content))
            <div class="alert alert-info" id="empty-state">
                <i class="fas fa-info-circle"></i>
                <strong>Sin contenido aún</strong><br>
                Haz clic en "Agregar Bloque" para comenzar a diseñar tu campaña.
            </div>
        @endif

        <div id="content-blocks-container" class="mb-3">
            @foreach($content as $index => $block)
                @include('managers.views.helpdesk.campaigns.partials.content-block', [
                    'block' => $block,
                    'index' => $index
                ])
            @endforeach
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between border-top pt-3">
        <a href="{{ route('manager.helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'general']) }}" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Anterior: General
        </a>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="far fa-save"></i> Guardar Contenido
            </button>
            <a href="{{ route('manager.helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'appearance']) }}" class="btn btn-success">
                Siguiente: Apariencia <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</form>

{{-- Block Template (Hidden) --}}
<template id="content-block-template">
    <div class="content-block" data-block-index="">
        <div class="d-flex align-items-start gap-2">
            <div class="block-handle">
                <i class="fas fa-grip-vertical fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="mb-2">
                    <label class="form-label small mb-1">Tipo de Bloque</label>
                    <select class="form-select form-select-sm block-type" name="content[][type]" onchange="updateBlockFields(this)">
                        <option value="text">Texto</option>
                        <option value="heading">Encabezado</option>
                        <option value="button">Botón</option>
                        <option value="image">Imagen</option>
                        <option value="html">HTML Personalizado</option>
                    </select>
                </div>

                <div class="block-fields">
                    {{-- Dynamic fields will be inserted here --}}
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-light-danger" onclick="removeBlock(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</template>

@push('scripts')
<script>
let blockCounter = {{ count($content) }};

function addContentBlock() {
    const template = document.getElementById('content-block-template');
    const clone = template.content.cloneNode(true);
    const container = document.getElementById('content-blocks-container');

    // Update index
    const block = clone.querySelector('.content-block');
    block.dataset.blockIndex = blockCounter;

    // Update name attributes
    block.querySelectorAll('[name^="content[]"]').forEach(input => {
        input.name = input.name.replace('[]', `[${blockCounter}]`);
    });

    container.appendChild(clone);
    updateBlockFields(container.lastElementChild.querySelector('.block-type'));

    // Hide empty state
    const emptyState = document.getElementById('empty-state');
    if (emptyState) emptyState.remove();

    blockCounter++;
}

function removeBlock(btn) {
    if (confirm('¿Eliminar este bloque?')) {
        btn.closest('.content-block').remove();

        // Show empty state if no blocks left
        const container = document.getElementById('content-blocks-container');
        if (container.children.length === 0 && !document.getElementById('empty-state')) {
            container.insertAdjacentHTML('beforebegin', `
                <div class="alert alert-info" id="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <strong>Sin contenido aún</strong><br>
                    Haz clic en "Agregar Bloque" para comenzar a diseñar tu campaña.
                </div>
            `);
        }
    }
}

function updateBlockFields(select) {
    const block = select.closest('.content-block');
    const fieldsContainer = block.querySelector('.block-fields');
    const type = select.value;
    const index = block.dataset.blockIndex;

    let html = '';

    switch (type) {
        case 'text':
            html = `
                <div class="mb-2">
                    <label class="form-label small mb-1">Texto</label>
                    <textarea class="form-control form-control-sm" name="content[${index}][value]" rows="3" placeholder="Ingrese el texto aquí..."></textarea>
                </div>
            `;
            break;

        case 'heading':
            html = `
                <div class="row g-2">
                    <div class="col-8">
                        <label class="form-label small mb-1">Título</label>
                        <input type="text" class="form-control form-control-sm" name="content[${index}][value]" placeholder="Título">
                    </div>
                    <div class="col-4">
                        <label class="form-label small mb-1">Nivel</label>
                        <select class="form-select form-select-sm" name="content[${index}][level]">
                            <option value="h1">H1</option>
                            <option value="h2" selected>H2</option>
                            <option value="h3">H3</option>
                            <option value="h4">H4</option>
                        </select>
                    </div>
                </div>
            `;
            break;

        case 'button':
            html = `
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">Texto del Botón</label>
                        <input type="text" class="form-control form-control-sm" name="content[${index}][label]" placeholder="Haz clic aquí">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">URL</label>
                        <input type="url" class="form-control form-control-sm" name="content[${index}][url]" placeholder="https://">
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1">Estilo</label>
                        <select class="form-select form-select-sm" name="content[${index}][style]">
                            <option value="primary">Primario</option>
                            <option value="secondary">Secundario</option>
                            <option value="success">Éxito</option>
                            <option value="danger">Peligro</option>
                        </select>
                    </div>
                </div>
            `;
            break;

        case 'image':
            html = `
                <div class="mb-2">
                    <label class="form-label small mb-1">URL de Imagen</label>
                    <input type="url" class="form-control form-control-sm" name="content[${index}][src]" placeholder="https://ejemplo.com/imagen.jpg">
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">Texto Alternativo</label>
                    <input type="text" class="form-control form-control-sm" name="content[${index}][alt]" placeholder="Descripción de la imagen">
                </div>
            `;
            break;

        case 'html':
            html = `
                <div class="mb-2">
                    <label class="form-label small mb-1">HTML Personalizado</label>
                    <textarea class="form-control form-control-sm font-monospace" name="content[${index}][html]" rows="4" placeholder="<div>...</div>"></textarea>
                    <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Solo para usuarios avanzados</small>
                </div>
            `;
            break;
    }

    fieldsContainer.innerHTML = html;
}

// Make blocks sortable (simple drag and drop)
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('content-blocks-container');

    // Initialize existing blocks
    container.querySelectorAll('.content-block').forEach((block, index) => {
        block.dataset.blockIndex = index;
        const type = block.querySelector('.block-type').value;
        if (type) updateBlockFields(block.querySelector('.block-type'));
    });
});
</script>
@endpush
