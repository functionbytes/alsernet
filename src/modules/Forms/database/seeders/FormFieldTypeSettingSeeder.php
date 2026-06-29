<?php

namespace Modules\Forms\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Forms\Models\FormFieldTypeSetting;

class FormFieldTypeSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->fieldTypes() as $data) {
            FormFieldTypeSetting::updateOrCreate(
                ['type' => $data['type']],
                $data
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function fieldTypes(): array
    {
        return [
            // Básicos (group_order=1)
            [
                'type' => 'text', 'group_name' => 'Básicos', 'group_order' => 1,
                'label' => 'Texto', 'icon' => 'fas fa-font', 'sort_order' => 1,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="text" class="form-control {css_class}" name="{name}" id="{id}" placeholder="{placeholder}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'textarea', 'group_name' => 'Básicos', 'group_order' => 1,
                'label' => 'Largo', 'icon' => 'fas fa-align-left', 'sort_order' => 2,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <textarea class="form-control {css_class}" name="{name}" id="{id}" rows="4" placeholder="{placeholder}" {required}></textarea>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'email', 'group_name' => 'Básicos', 'group_order' => 1,
                'label' => 'Email', 'icon' => 'fas fa-at', 'sort_order' => 3,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="email" class="form-control {css_class}" name="{name}" id="{id}" placeholder="{placeholder}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'phone', 'group_name' => 'Básicos', 'group_order' => 1,
                'label' => 'Teléfono', 'icon' => 'fas fa-phone', 'sort_order' => 4,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="tel" class="form-control {css_class}" name="{name}" id="{id}" placeholder="{placeholder}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'number', 'group_name' => 'Básicos', 'group_order' => 1,
                'label' => 'Número', 'icon' => 'fas fa-hashtag', 'sort_order' => 5,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="number" class="form-control {css_class}" name="{name}" id="{id}" placeholder="{placeholder}" min="{min}" max="{max}" step="{step}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'date', 'group_name' => 'Básicos', 'group_order' => 1,
                'label' => 'Fecha', 'icon' => 'fas fa-calendar-alt', 'sort_order' => 6,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="date" class="form-control {css_class}" name="{name}" id="{id}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'time', 'group_name' => 'Básicos', 'group_order' => 1,
                'label' => 'Hora', 'icon' => 'fas fa-clock', 'sort_order' => 7,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="time" class="form-control {css_class}" name="{name}" id="{id}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'url', 'group_name' => 'Básicos', 'group_order' => 1,
                'label' => 'URL', 'icon' => 'fas fa-link', 'sort_order' => 8,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="url" class="form-control {css_class}" name="{name}" id="{id}" placeholder="{placeholder}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],

            // Selección (group_order=2)
            [
                'type' => 'select', 'group_name' => 'Selección', 'group_order' => 2,
                'label' => 'Select', 'icon' => 'fas fa-caret-square-down', 'sort_order' => 1,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <select class="form-select {css_class}" name="{name}" id="{id}" {required}>'."\n".'        <option value="">Selecciona una opción...</option>'."\n".'        {options}'."\n".'    </select>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'radio', 'group_name' => 'Selección', 'group_order' => 2,
                'label' => 'Radio', 'icon' => 'far fa-dot-circle', 'sort_order' => 2,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold d-block">{label}</label>'."\n".'    {options_radio}'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'checkbox', 'group_name' => 'Selección', 'group_order' => 2,
                'label' => 'Checkbox', 'icon' => 'far fa-check-square', 'sort_order' => 3,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold d-block">{label}</label>'."\n".'    {options_checkbox}'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'image_choice', 'group_name' => 'Selección', 'group_order' => 2,
                'label' => 'Imagen', 'icon' => 'far fa-image', 'sort_order' => 4,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold d-block">{label}</label>'."\n".'    <div class="d-flex flex-wrap gap-2">'."\n".'        {options_image}'."\n".'    </div>'."\n".'    {help_text}'."\n".'</div>',
            ],

            // Avanzados (group_order=3)
            [
                'type' => 'file', 'group_name' => 'Avanzados', 'group_order' => 3,
                'label' => 'Archivo', 'icon' => 'fas fa-paperclip', 'sort_order' => 1,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="file" class="form-control {css_class}" name="{name}" id="{id}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'rating', 'group_name' => 'Avanzados', 'group_order' => 3,
                'label' => 'Rating', 'icon' => 'fas fa-star', 'sort_order' => 2,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold d-block">{label}</label>'."\n".'    <div class="rating-stars {css_class}" data-name="{name}" data-max="{max}">'."\n".'        {stars}'."\n".'    </div>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'slider', 'group_name' => 'Avanzados', 'group_order' => 3,
                'label' => 'Slider', 'icon' => 'fas fa-sliders-h', 'sort_order' => 3,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}: <span id="{id}_val">{default}</span></label>'."\n".'    <input type="range" class="form-range {css_class}" name="{name}" id="{id}" min="{min}" max="{max}" step="{step}" value="{default}" oninput="document.getElementById(\'{id}_val\').textContent=this.value">'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'nps', 'group_name' => 'Avanzados', 'group_order' => 3,
                'label' => 'NPS', 'icon' => 'fas fa-chart-bar', 'sort_order' => 4,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold d-block">{label}</label>'."\n".'    <div class="d-flex gap-1 flex-wrap nps-scale {css_class}">'."\n".'        {nps_options}'."\n".'    </div>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'likert', 'group_name' => 'Avanzados', 'group_order' => 3,
                'label' => 'Likert', 'icon' => 'fas fa-table', 'sort_order' => 5,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <div class="table-responsive">'."\n".'        <table class="table table-bordered {css_class}">'."\n".'            <thead>{likert_headers}</thead>'."\n".'            <tbody>{likert_rows}</tbody>'."\n".'        </table>'."\n".'    </div>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'signature', 'group_name' => 'Avanzados', 'group_order' => 3,
                'label' => 'Firma', 'icon' => 'fas fa-signature', 'sort_order' => 6,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold d-block">{label}</label>'."\n".'    <canvas class="border rounded {css_class}" id="{id}" width="400" height="150" style="touch-action:none;"></canvas>'."\n".'    <input type="hidden" name="{name}" id="{id}_data">'."\n".'    <div class="mt-1"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById(\'{id}\').getContext(\'2d\').clearRect(0,0,400,150)">Limpiar</button></div>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'calculation', 'group_name' => 'Avanzados', 'group_order' => 3,
                'label' => 'Cálculo', 'icon' => 'fas fa-calculator', 'sort_order' => 7,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="text" class="form-control {css_class}" name="{name}" id="{id}" readonly data-formula="{formula}">'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'address', 'group_name' => 'Avanzados', 'group_order' => 3,
                'label' => 'Dirección', 'icon' => 'fas fa-map-marker-alt', 'sort_order' => 8,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="text" class="form-control {css_class} mb-2" name="{name}[street]" placeholder="Calle y número">'."\n".'    <div class="row g-2">'."\n".'        <div class="col-6"><input type="text" class="form-control" name="{name}[city]" placeholder="Ciudad"></div>'."\n".'        <div class="col-6"><input type="text" class="form-control" name="{name}[zip]" placeholder="Código postal"></div>'."\n".'    </div>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'color_picker', 'group_name' => 'Avanzados', 'group_order' => 3,
                'label' => 'Color', 'icon' => 'fas fa-palette', 'sort_order' => 9,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="color" class="form-control form-control-color {css_class}" name="{name}" id="{id}" value="{default}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],

            // Layout (group_order=4)
            [
                'type' => 'section_header', 'group_name' => 'Layout', 'group_order' => 4,
                'label' => 'Sección', 'icon' => 'fas fa-heading', 'sort_order' => 1,
                'custom_html' => '<div class="mb-4 mt-2">'."\n".'    <h5 class="fw-bold {css_class}">{title}</h5>'."\n".'    {description}'."\n".'    <hr class="mt-2">'."\n".'</div>',
            ],
            [
                'type' => 'html_block', 'group_name' => 'Layout', 'group_order' => 4,
                'label' => 'HTML', 'icon' => 'fas fa-code', 'sort_order' => 2,
                'custom_html' => '<div class="mb-3 {css_class}">'."\n".'    {html_content}'."\n".'</div>',
            ],
            [
                'type' => 'divider', 'group_name' => 'Layout', 'group_order' => 4,
                'label' => 'Divisor', 'icon' => 'fas fa-minus', 'sort_order' => 3,
                'custom_html' => '<hr class="my-4 {css_class}">',
            ],
            [
                'type' => 'spacer', 'group_name' => 'Layout', 'group_order' => 4,
                'label' => 'Espacio', 'icon' => 'fas fa-arrows-alt-v', 'sort_order' => 4,
                'custom_html' => '<div class="my-3 {css_class}"></div>',
            ],

            // Legal (group_order=5)
            [
                'type' => 'consent', 'group_name' => 'Legal', 'group_order' => 5,
                'label' => 'Consent.', 'icon' => 'fas fa-user-check', 'sort_order' => 1,
                'custom_html' => '<div class="mb-3">'."\n".'    <div class="form-check">'."\n".'        <input type="checkbox" class="form-check-input {css_class}" name="{name}" id="{id}" {required}>'."\n".'        <label class="form-check-label" for="{id}">{consent_text}</label>'."\n".'    </div>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'newsletter', 'group_name' => 'Legal', 'group_order' => 5,
                'label' => 'Newsletter', 'icon' => 'fas fa-newspaper', 'sort_order' => 2,
                'custom_html' => '<div class="mb-3">'."\n".'    <div class="form-check">'."\n".'        <input type="checkbox" class="form-check-input {css_class}" name="{name}" id="{id}">'."\n".'        <label class="form-check-label" for="{id}">{label}</label>'."\n".'    </div>'."\n".'    {help_text}'."\n".'</div>',
            ],
            [
                'type' => 'hidden', 'group_name' => 'Legal', 'group_order' => 5,
                'label' => 'Oculto', 'icon' => 'fas fa-eye-slash', 'sort_order' => 3,
                'custom_html' => '<input type="hidden" name="{name}" id="{id}" value="{default_value}">',
            ],
            [
                'type' => 'password', 'group_name' => 'Legal', 'group_order' => 5,
                'label' => 'Contraseña', 'icon' => 'fas fa-key', 'sort_order' => 4,
                'custom_html' => '<div class="mb-3">'."\n".'    <label class="form-label fw-semibold">{label}</label>'."\n".'    <input type="password" class="form-control {css_class}" name="{name}" id="{id}" placeholder="{placeholder}" {required}>'."\n".'    {help_text}'."\n".'</div>',
            ],
        ];
    }
}
