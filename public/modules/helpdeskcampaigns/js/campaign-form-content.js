/**
 * HelpdeskCampaigns — campaign-form-content.js
 * Pestaña "Contenido" del editor de campaña: agrega/quita bloques y
 * renderiza los campos propios de cada tipo de bloque.
 * Espera `window.HcmContentTab = { blockCounter }` inyectado desde
 * managers/campaigns/tabs/content.blade.php. Las funciones son globales
 * porque se invocan desde atributos onclick/onchange en el HTML.
 */
let blockCounter = (window.HcmContentTab || {}).blockCounter || 0;

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
