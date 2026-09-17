/**
 * HelpdeskCampaigns — campaign-form-appearance.js
 * Pestaña "Apariencia" del editor de campaña: salida en vivo del radio de
 * borde. La sincronización picker↔hex de los 3 selectores de color la
 * resuelve por su cuenta core::components.color-field (initColorFields en
 * su propio @push('scripts')) — no hace falta nada aquí para eso.
 */
$(function () {
    const rangeInput = document.getElementById('border-radius-range');
    const rangeOutput = document.getElementById('border-radius-output');
    if (rangeInput && rangeOutput) {
        rangeInput.addEventListener('input', () => { rangeOutput.textContent = rangeInput.value + 'px'; });
    }
});
