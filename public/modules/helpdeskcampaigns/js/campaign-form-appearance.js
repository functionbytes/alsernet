/**
 * HelpdeskCampaigns — campaign-form-appearance.js
 * Pestaña "Apariencia" del editor de campaña: salida en vivo del radio de
 * borde y sincronización de los inputs de color heredados.
 *
 * Nota: el bloque de sincronización de color busca los ids `${id}-input` /
 * `${id}-text` (bg-color, text-color, primary-color), pero la vista usa el
 * componente core::components.color-field, que genera ids distintos
 * (`cc-appearance-background-color[-picker]`). Ese bloque ya no encontraba
 * los elementos antes de esta extracción — se conserva el comportamiento
 * (guard `if (colorInput && textDisplay)` evita errores) sin corregirlo,
 * por ser un bug funcional fuera del alcance de este refactor.
 */
$(function () {
    [['bg-color', 'appearance[background_color]'],
     ['text-color', 'appearance[text_color]'],
     ['primary-color', 'appearance[primary_color]']
    ].forEach(([id]) => {
        const colorInput = document.getElementById(`${id}-input`);
        const textDisplay = document.getElementById(`${id}-text`);
        if (colorInput && textDisplay) {
            colorInput.addEventListener('input', () => { textDisplay.value = colorInput.value; });
        }
    });

    const rangeInput = document.getElementById('border-radius-range');
    const rangeOutput = document.getElementById('border-radius-output');
    if (rangeInput && rangeOutput) {
        rangeInput.addEventListener('input', () => { rangeOutput.textContent = rangeInput.value + 'px'; });
    }
});
