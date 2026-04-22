{{-- Content tab --}}
<form method="POST" action="{{ route('manager.helpdesk-campaigns.update', $campaign) }}" id="content-form">
    @csrf
    @method('PUT')

    <h6 class="fw-semibold mb-1 border-bottom pb-2">Bloques de contenido</h6>
    <p class="text-muted small mb-3">Componentes visuales que forman el cuerpo de la campana</p>

    @php $content = old('content', $campaign->content ?? []); @endphp

    @if(empty($content))
        <div class="alert alert-info mb-3" id="empty-state">
            Sin contenido aun. Haz clic en "Agregar bloque" para comenzar a disenar la campana.
        </div>
    @endif

    <div id="content-blocks-container" class="mb-3">
        @foreach($content as $index => $block)
            @include('helpdeskcampaigns::managers.campaigns.partials.content-block', ['block' => $block, 'index' => $index])
        @endforeach
    </div>

    <button type="button" class="btn btn-outline-secondary btn-sm mb-4" onclick="addContentBlock()">
        <i class="fas fa-plus me-1"></i> Agregar bloque
    </button>

    <div class="d-flex justify-content-between border-top pt-3">
        <a href="{{ route('manager.helpdesk-campaigns.edit', ['campaign' => $campaign, 'tab' => 'general']) }}" class="btn btn-light">
            Anterior: General
        </a>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Guardar contenido</button>
            <a href="{{ route('manager.helpdesk-campaigns.edit', ['campaign' => $campaign, 'tab' => 'appearance']) }}" class="btn btn-light">
                Siguiente: Apariencia
            </a>
        </div>
    </div>
</form>

{{-- Block template --}}
<template id="content-block-template">
    <div class="card mb-2 content-block" data-block-index="">
        <div class="card-body p-3">
            <div class="d-flex align-items-start gap-3">
                <div class="flex-grow-1">
                    <div class="mb-2">
                        <label class="form-label small mb-1">Tipo de bloque</label>
                        <select class="form-select form-select-sm block-type" name="content[][type]" onchange="updateBlockFields(this)">
                            <option value="text">Texto</option>
                            <option value="heading">Encabezado</option>
                            <option value="button">Boton</option>
                            <option value="image">Imagen</option>
                            <option value="html">HTML personalizado</option>
                        </select>
                    </div>
                    <div class="block-fields"></div>
                </div>
                <button type="button" class="btn btn-sm btn-light-danger flex-shrink-0" onclick="removeBlock(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
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
    const block = clone.querySelector('.content-block');

    block.dataset.blockIndex = blockCounter;
    block.querySelectorAll('[name^="content[]"]').forEach(input => {
        input.name = input.name.replace('[]', `[${blockCounter}]`);
    });

    container.appendChild(clone);
    updateBlockFields(container.lastElementChild.querySelector('.block-type'));

    const emptyState = document.getElementById('empty-state');
    if (emptyState) { emptyState.remove(); }

    blockCounter++;
}

function removeBlock(btn) {
    btn.closest('.content-block').remove();

    const container = document.getElementById('content-blocks-container');
    if (container.children.length === 0 && !document.getElementById('empty-state')) {
        container.insertAdjacentHTML('beforebegin',
            '<div class="alert alert-info mb-3" id="empty-state">Sin contenido aun. Haz clic en "Agregar bloque" para comenzar.</div>'
        );
    }
}

function updateBlockFields(select) {
    const block = select.closest('.content-block');
    const index = block.dataset.blockIndex;
    const type = select.value;

    const templates = {
        text: `<label class="form-label small mb-1">Texto</label>
               <textarea class="form-control form-control-sm" name="content[${index}][value]" rows="3" placeholder="Ingrese el texto aqui..."></textarea>`,
        heading: `<div class="row g-2">
                    <div class="col-8"><label class="form-label small mb-1">Titulo</label>
                    <input type="text" class="form-control form-control-sm" name="content[${index}][value]" placeholder="Titulo"></div>
                    <div class="col-4"><label class="form-label small mb-1">Nivel</label>
                    <select class="form-select form-select-sm" name="content[${index}][level]">
                        <option value="h1">H1</option><option value="h2" selected>H2</option>
                        <option value="h3">H3</option><option value="h4">H4</option>
                    </select></div></div>`,
        button: `<div class="row g-2">
                    <div class="col-6"><label class="form-label small mb-1">Texto del boton</label>
                    <input type="text" class="form-control form-control-sm" name="content[${index}][label]" placeholder="Haz clic aqui"></div>
                    <div class="col-6"><label class="form-label small mb-1">URL</label>
                    <input type="url" class="form-control form-control-sm" name="content[${index}][url]" placeholder="https://"></div>
                    <div class="col-12"><label class="form-label small mb-1">Estilo</label>
                    <select class="form-select form-select-sm" name="content[${index}][style]">
                        <option value="primary">Primario</option><option value="secondary">Secundario</option>
                        <option value="success">Exito</option><option value="danger">Peligro</option>
                    </select></div></div>`,
        image: `<div class="mb-2"><label class="form-label small mb-1">URL de imagen</label>
                <input type="url" class="form-control form-control-sm" name="content[${index}][src]" placeholder="https://ejemplo.com/imagen.jpg"></div>
                <div class="mb-2"><label class="form-label small mb-1">Texto alternativo</label>
                <input type="text" class="form-control form-control-sm" name="content[${index}][alt]" placeholder="Descripcion de la imagen"></div>`,
        html: `<label class="form-label small mb-1">HTML personalizado</label>
               <textarea class="form-control form-control-sm font-monospace" name="content[${index}][html]" rows="4" placeholder="<div>...</div>"></textarea>
               <small class="text-muted">Solo para usuarios avanzados</small>`,
    };

    block.querySelector('.block-fields').innerHTML = templates[type] ?? '';
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#content-blocks-container .content-block').forEach((block, index) => {
        block.dataset.blockIndex = index;
        const typeSelect = block.querySelector('.block-type');
        if (typeSelect) { updateBlockFields(typeSelect); }
    });
});
</script>
@endpush
