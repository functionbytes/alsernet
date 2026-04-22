<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $widthClass = match ($field->width) {
    'half' => 'col-md-6',
    'third' => 'col-md-4',
    'quarter' => 'col-md-3',
    default => 'col-12',
};
$inputId = $formId.'-'.$field->key;
$labelPos = $field->label_position ?? 'top';
$isFloating = $floatingLabel || $labelPos === 'floating';
$conditionAttr = ! empty($field->conditions) ? 'data-conditions="'.htmlspecialchars(json_encode($field->conditions)).'"' : '';
$autoPopulate = $field->auto_populate_param
    ? 'data-auto-populate="'.htmlspecialchars($field->auto_populate_param, ENT_QUOTES).'"'
    : '';
$locale = app()->getLocale();
$fieldLabel = $field->localizedLabel($locale);
$fieldPlaceholder = $field->localizedPlaceholder($locale);
$fieldConsentText = $field->localizedConsentText($locale);
$fieldI18n = [
    'es' => [
        'select_placeholder' => '-- Seleccionar --',
        'nps_low' => 'Nada probable', 'nps_high' => 'Muy probable',
        'clear' => 'Borrar', 'calculating' => 'Calculado automáticamente',
        'chars' => 'caracteres', 'file_max' => 'Máx.', 'file_allowed' => 'Permitidos:',
        'street' => 'Calle / Dirección', 'number' => 'Número',
        'city' => 'Ciudad', 'postal' => 'CP', 'country' => 'País',
    ],
    'pt' => [
        'select_placeholder' => '-- Selecionar --',
        'nps_low' => 'Nada provável', 'nps_high' => 'Muito provável',
        'clear' => 'Limpar', 'calculating' => 'Calculado automaticamente',
        'chars' => 'caracteres', 'file_max' => 'Máx.', 'file_allowed' => 'Permitidos:',
        'street' => 'Rua / Morada', 'number' => 'Número',
        'city' => 'Cidade', 'postal' => 'CP', 'country' => 'País',
    ],
    'en' => [
        'select_placeholder' => '-- Select --',
        'nps_low' => 'Not at all likely', 'nps_high' => 'Extremely likely',
        'clear' => 'Clear', 'calculating' => 'Calculated automatically',
        'chars' => 'characters', 'file_max' => 'Max.', 'file_allowed' => 'Allowed:',
        'street' => 'Street / Address', 'number' => 'Number',
        'city' => 'City', 'postal' => 'Postcode', 'country' => 'Country',
    ],
    'fr' => [
        'select_placeholder' => '-- Sélectionner --',
        'nps_low' => 'Pas du tout probable', 'nps_high' => 'Très probable',
        'clear' => 'Effacer', 'calculating' => 'Calculé automatiquement',
        'chars' => 'caractères', 'file_max' => 'Max.', 'file_allowed' => 'Autorisés :',
        'street' => 'Rue / Adresse', 'number' => 'Numéro',
        'city' => 'Ville', 'postal' => 'Code postal', 'country' => 'Pays',
    ],
][$locale] ?? [
    'select_placeholder' => '-- Seleccionar --',
    'nps_low' => 'Nada probable', 'nps_high' => 'Muy probable',
    'clear' => 'Borrar', 'calculating' => 'Calculado automáticamente',
    'chars' => 'caracteres', 'file_max' => 'Máx.', 'file_allowed' => 'Permitidos:',
    'street' => 'Calle / Dirección', 'number' => 'Número',
    'city' => 'Ciudad', 'postal' => 'CP', 'country' => 'País',
];
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php switch ($field->type) {
    case 'section_header': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <h5 class="forms-section-header mt-2 mb-1"><?php echo e($fieldLabel); ?></h5>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->default_value) { ?><p class="text-muted"><?php echo e($field->default_value); ?></p><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <hr class="mt-1">
    </div>
    <?php break; ?>

    <?php case 'html_block': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php echo clean_html($field->html_content); ?>

    </div>
    <?php break; ?>

    <?php case 'divider': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>><hr></div>
    <?php break; ?>

    <?php case 'spacer': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?> style="height: <?php echo e($field->min_value ?? 20); ?>px"></div>
    <?php break; ?>

    <?php case 'hidden': ?>
    <input type="hidden" name="<?php echo e($field->key); ?>" value="<?php echo e($field->default_value); ?>" <?php echo $autoPopulate; ?>>
    <?php break; ?>

    <?php case 'textarea': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! $isFloating && $labelPos !== 'hidden') { ?>
        <label for="<?php echo e($inputId); ?>" class="form-label"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <textarea
            id="<?php echo e($inputId); ?>"
            name="<?php echo e($field->key); ?>"
            class="form-control"
            placeholder="<?php echo e($fieldPlaceholder); ?>"
            data-form-field-id="<?php echo e($field->id); ?>"
            data-form-field-label="<?php echo e($fieldLabel); ?>"
            data-form-field-help="<?php echo e($field->help_text); ?>"
            data-form-field-required="<?php echo e($field->is_required ? '1' : '0'); ?>"
            <?php echo e($field->is_required ? 'required' : ''); ?>

            <?php if ($field->max_value) { ?> maxlength="<?php echo e((int) $field->max_value); ?>" <?php } ?>
            rows="4"
            <?php echo $autoPopulate; ?>

        ><?php echo e($field->default_value); ?></textarea>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mt-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->show_char_counter) { ?>
        <small class="forms-char-counter text-muted" data-target="<?php echo e($inputId); ?>">0<?php echo e($field->max_value ? '/'.(int) $field->max_value : ''); ?> <?php echo e($fieldI18n['chars']); ?></small>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="invalid-feedback"></div>
    </div>
    <?php break; ?>

    <?php case 'select': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label for="<?php echo e($inputId); ?>" class="form-label"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <select id="<?php echo e($inputId); ?>" name="<?php echo e($field->key); ?>" class="form-select" <?php echo e($field->is_required ? 'required' : ''); ?>

            data-form-field-id="<?php echo e($field->id); ?>"
            data-form-field-label="<?php echo e($fieldLabel); ?>"
            data-form-field-help="<?php echo e($field->help_text); ?>"
            data-form-field-required="<?php echo e($field->is_required ? '1' : '0'); ?>"
            data-form-field-placeholder="<?php echo e($fieldPlaceholder); ?>">
            <option value=""><?php echo e($fieldPlaceholder ?: $fieldI18n['select_placeholder']); ?></option>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $field->localizedOptions($locale);
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $option) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
            <option value="<?php echo e($option['value']); ?>" <?php echo e($field->default_value == $option['value'] ? 'selected' : ''); ?>><?php echo e($option['label']); ?></option>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </select>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mt-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="invalid-feedback"></div>
    </div>
    <?php break; ?>

    <?php case 'radio': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label class="form-label d-block"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $field->localizedOptions($locale);
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $option) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
        <div class="form-check">
            <input type="radio" id="<?php echo e($inputId); ?>-<?php echo e($loop->index); ?>" name="<?php echo e($field->key); ?>" value="<?php echo e($option['value']); ?>" class="form-check-input"
                <?php if ($loop->first) { ?>
                data-form-field-id="<?php echo e($field->id); ?>"
                data-form-field-label="<?php echo e($fieldLabel); ?>"
                data-form-field-help="<?php echo e($field->help_text); ?>"
                data-form-field-required="<?php echo e($field->is_required ? '1' : '0'); ?>"
                <?php } ?>
                <?php if (! empty($option['icon'])) { ?> data-icon="<?php echo e($option['icon']); ?>" <?php } ?>
                <?php if (! empty($option['description'])) { ?> data-description="<?php echo e($option['description']); ?>" <?php } ?>
                <?php echo e($field->is_required ? 'required' : ''); ?> <?php echo e($field->default_value == $option['value'] ? 'checked' : ''); ?>>
            <label class="form-check-label" for="<?php echo e($inputId); ?>-<?php echo e($loop->index); ?>"><?php echo e($option['label']); ?></label>
        </div>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        <div class="invalid-feedback d-block" id="<?php echo e($inputId); ?>-error"></div>
    </div>
    <?php break; ?>

    <?php case 'checkbox': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label class="form-label d-block"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $field->localizedOptions($locale);
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $option) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
        <div class="form-check">
            <input type="checkbox" id="<?php echo e($inputId); ?>-<?php echo e($loop->index); ?>" name="<?php echo e($field->key); ?>[]" value="<?php echo e($option['value']); ?>" class="form-check-input"
                <?php if ($loop->first) { ?>
                data-form-field-id="<?php echo e($field->id); ?>"
                data-form-field-label="<?php echo e($fieldLabel); ?>"
                data-form-field-help="<?php echo e($field->help_text); ?>"
                data-form-field-required="<?php echo e($field->is_required ? '1' : '0'); ?>"
                <?php } ?>>
            <label class="form-check-label" for="<?php echo e($inputId); ?>-<?php echo e($loop->index); ?>"><?php echo e($option['label']); ?></label>
        </div>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mt-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="invalid-feedback d-block" id="<?php echo e($inputId); ?>-error"></div>
    </div>
    <?php break; ?>

    <?php case 'file': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label for="<?php echo e($inputId); ?>" class="form-label"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <input type="file" id="<?php echo e($inputId); ?>" name="<?php echo e($field->key); ?>" class="form-control" <?php echo e($field->is_required ? 'required' : ''); ?>>
        <small class="text-muted"><?php echo e($fieldI18n['file_max']); ?> <?php echo e(config('forms.max_file_size_mb', 10)); ?>MB. <?php echo e($fieldI18n['file_allowed']); ?> <?php echo e(implode(', ', config('forms.allowed_file_extensions', []))); ?></small>
        <div class="invalid-feedback"></div>
    </div>
    <?php break; ?>

    <?php case 'rating': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label class="form-label d-block"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="forms-rating" data-field="<?php echo e($field->key); ?>" data-max="<?php echo e((int) ($field->max_value ?? 5)); ?>">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php for ($i = 1; $i <= ($field->max_value ?? 5); $i++) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
            <i class="far fa-star forms-star" data-value="<?php echo e($i); ?>"></i>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
        <input type="hidden" name="<?php echo e($field->key); ?>" id="<?php echo e($inputId); ?>" value="" <?php echo e($field->is_required ? 'required' : ''); ?>>
        <div class="invalid-feedback d-block" id="<?php echo e($inputId); ?>-error"></div>
    </div>
    <?php break; ?>

    <?php case 'nps': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label class="form-label d-block"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="forms-nps d-flex gap-1 flex-wrap">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php for ($i = 0; $i <= 10; $i++) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
            <button type="button" class="btn btn-sm forms-nps-btn <?php echo e($i <= 6 ? 'btn-outline-danger' : ($i <= 8 ? 'btn-outline-warning' : 'btn-outline-success')); ?>" data-value="<?php echo e($i); ?>" data-field="<?php echo e($field->key); ?>"><?php echo e($i); ?></button>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted"><?php echo e($fieldI18n['nps_low']); ?></small>
            <small class="text-muted"><?php echo e($fieldI18n['nps_high']); ?></small>
        </div>
        <input type="hidden" name="<?php echo e($field->key); ?>" id="<?php echo e($inputId); ?>" value="" <?php echo e($field->is_required ? 'required' : ''); ?>>
        <div class="invalid-feedback d-block" id="<?php echo e($inputId); ?>-error"></div>
    </div>
    <?php break; ?>

    <?php case 'slider': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label for="<?php echo e($inputId); ?>" class="form-label"><?php echo e($fieldLabel); ?>: <span id="<?php echo e($inputId); ?>-val"><?php echo e($field->default_value ?? $field->min_value ?? 0); ?></span><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <input type="range" id="<?php echo e($inputId); ?>" name="<?php echo e($field->key); ?>" class="form-range"
            min="<?php echo e($field->min_value ?? 0); ?>"
            max="<?php echo e($field->max_value ?? 100); ?>"
            step="<?php echo e($field->step_value ?? 1); ?>"
            value="<?php echo e($field->default_value ?? $field->min_value ?? 0); ?>"
            <?php echo e($field->is_required ? 'required' : ''); ?>

            oninput="document.getElementById('<?php echo e($inputId); ?>-val').textContent = this.value">
        <div class="d-flex justify-content-between">
            <small class="text-muted"><?php echo e($field->min_value ?? 0); ?></small>
            <small class="text-muted"><?php echo e($field->max_value ?? 100); ?></small>
        </div>
    </div>
    <?php break; ?>

    <?php case 'signature': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label class="form-label d-block"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="forms-signature-wrapper border rounded" style="background:#fff">
            <canvas id="<?php echo e($inputId); ?>-canvas" class="forms-signature-canvas d-block" width="400" height="150" data-field="<?php echo e($field->key); ?>"></canvas>
            <div class="d-flex justify-content-end p-1 border-top">
                <button type="button" class="btn btn-xs btn-outline-secondary forms-signature-clear" data-target="<?php echo e($inputId); ?>-canvas">
                    <i class="fas fa-eraser me-1"></i> <?php echo e($fieldI18n['clear']); ?>

                </button>
            </div>
        </div>
        <input type="hidden" name="<?php echo e($field->key); ?>" id="<?php echo e($inputId); ?>" value="" <?php echo e($field->is_required ? 'required' : ''); ?>>
        <div class="invalid-feedback d-block" id="<?php echo e($inputId); ?>-error"></div>
    </div>
    <?php break; ?>

    <?php case 'consent': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <div class="form-check">
            <input type="checkbox" id="<?php echo e($inputId); ?>" name="<?php echo e($field->key); ?>" value="1" class="form-check-input"
                data-form-field-id="<?php echo e($field->id); ?>"
                data-form-field-label="<?php echo e($fieldLabel); ?>"
                data-form-field-help="<?php echo e($field->help_text); ?>"
                data-form-field-required="<?php echo e($field->is_required ? '1' : '0'); ?>"
                data-form-field-placeholder=""
                <?php echo e($field->is_required ? 'required' : ''); ?>>
            <label class="form-check-label" for="<?php echo e($inputId); ?>"><?php echo clean($fieldConsentText); ?></label>
        </div>
        <div class="invalid-feedback"></div>
    </div>
    <?php break; ?>

    <?php case 'newsletter_consent': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <div class="form-check">
            <input type="checkbox" id="<?php echo e($inputId); ?>" name="<?php echo e($field->key); ?>" value="1" class="form-check-input"
                data-form-field-id="<?php echo e($field->id); ?>"
                data-form-field-label="<?php echo e($fieldLabel); ?>"
                data-form-field-help="<?php echo e($field->help_text); ?>"
                data-form-field-required="<?php echo e($field->is_required ? '1' : '0'); ?>"
                data-form-field-placeholder="">
            <label class="form-check-label" for="<?php echo e($inputId); ?>"><?php echo e($fieldConsentText); ?></label>
        </div>
    </div>
    <?php break; ?>

    <?php case 'likert': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label class="form-label d-block fw-semibold"><?php echo e($fieldLabel); ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-2"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php $likertOptions = $field->options ?? [['value' => '1', 'label' => 'Muy en desacuerdo'], ['value' => '2', 'label' => 'En desacuerdo'], ['value' => '3', 'label' => 'Neutral'], ['value' => '4', 'label' => 'De acuerdo'], ['value' => '5', 'label' => 'Muy de acuerdo']]; ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered forms-likert">
                <thead>
                    <tr>
                        <th></th>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $likertOptions;
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $opt) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?><th class="text-center small"><?php echo e($opt['label']); ?></th><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $field->likert_rows ?? [];
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $idx => $row) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <tr>
                        <td class="small"><?php echo e(is_array($row) ? $row['label'] : $row); ?></td>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $likertOptions;
            $__env->addLoop($__currentLoopData);
            foreach ($__currentLoopData as $opt) {
                $__env->incrementLoopIndices();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                        <td class="text-center">
                            <input type="radio" name="<?php echo e($field->key); ?>[<?php echo e($idx); ?>]" value="<?php echo e($opt['value']); ?>" class="form-check-input" <?php echo e($field->is_required ? 'required' : ''); ?>>
                        </td>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    </tr>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php break; ?>

    <?php case 'address': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label class="form-label d-block"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="row g-2">
            <div class="col-8"><input type="text" name="<?php echo e($field->key); ?>[street]" class="form-control form-control-sm" placeholder="<?php echo e($fieldI18n['street']); ?>" <?php echo e($field->is_required ? 'required' : ''); ?>></div>
            <div class="col-4"><input type="text" name="<?php echo e($field->key); ?>[number]" class="form-control form-control-sm" placeholder="<?php echo e($fieldI18n['number']); ?>"></div>
            <div class="col-6"><input type="text" name="<?php echo e($field->key); ?>[city]" class="form-control form-control-sm" placeholder="<?php echo e($fieldI18n['city']); ?>" <?php echo e($field->is_required ? 'required' : ''); ?>></div>
            <div class="col-3"><input type="text" name="<?php echo e($field->key); ?>[postal_code]" class="form-control form-control-sm" placeholder="<?php echo e($fieldI18n['postal']); ?>"></div>
            <div class="col-3"><input type="text" name="<?php echo e($field->key); ?>[country]" class="form-control form-control-sm" placeholder="<?php echo e($fieldI18n['country']); ?>"></div>
        </div>
    </div>
    <?php break; ?>

    <?php case 'image_choice': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label class="form-label d-block"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="d-flex flex-wrap gap-2 forms-image-choice" data-field="<?php echo e($field->key); ?>">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $field->localizedOptions($locale);
        $__env->addLoop($__currentLoopData);
        foreach ($__currentLoopData as $option) {
            $__env->incrementLoopIndices();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
            <div class="forms-image-choice-item text-center" data-value="<?php echo e($option['value']); ?>" style="cursor:pointer;border:2px solid #dee2e6;border-radius:8px;padding:8px;width:120px">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($option['image'])) { ?><img src="<?php echo e($option['image']); ?>" class="img-fluid mb-1" style="height:60px;object-fit:cover"><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <small><?php echo e($option['label']); ?></small>
            </div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
        <input type="hidden" name="<?php echo e($field->key); ?>" id="<?php echo e($inputId); ?>" value="" <?php echo e($field->is_required ? 'required' : ''); ?>>
        <div class="invalid-feedback d-block" id="<?php echo e($inputId); ?>-error"></div>
    </div>
    <?php break; ?>

    <?php case 'calculation': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label for="<?php echo e($inputId); ?>" class="form-label"><?php echo e($fieldLabel); ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <input type="text" id="<?php echo e($inputId); ?>" name="<?php echo e($field->key); ?>" class="form-control forms-calculation" readonly placeholder="<?php echo e($fieldI18n['calculating']); ?>" data-formula="<?php echo e($field->formula); ?>" value="<?php echo e($field->default_value); ?>">
    </div>
    <?php break; ?>

    <?php case 'rich_text': ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
        <label for="<?php echo e($inputId); ?>" class="form-label"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mb-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <textarea id="<?php echo e($inputId); ?>" name="<?php echo e($field->key); ?>" class="form-control forms-rich-text" rows="5" <?php echo e($field->is_required ? 'required' : ''); ?>><?php echo e($field->default_value); ?></textarea>
        <div class="invalid-feedback"></div>
    </div>
    <?php break; ?>

    <?php case 'color_picker': ?>
    <div class="<?php echo e($widthClass ?? 'col-12'); ?>" <?php echo $conditionAttr ?? ''; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (($labelPos ?? 'top') !== 'hidden') { ?>
            <label for="<?php echo e($inputId); ?>" class="form-label">
                <?php echo e($fieldLabel); ?>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            </label>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="d-flex align-items-center gap-2">
            <input type="color"
                id="<?php echo e($inputId); ?>"
                name="<?php echo e($field->key); ?>"
                class="form-control form-control-color"
                value="<?php echo e($field->default_value ?: '#000000'); ?>"
                <?php echo e($field->is_required ? 'required' : ''); ?>>
            <span class="text-muted color-picker-value"><?php echo e($field->default_value ?: '#000000'); ?></span>
        </div>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?>
            <div class="form-text"><?php echo e($field->help_text); ?></div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <div class="invalid-feedback"></div>
    </div>
    <?php break; ?>

    <?php default: ?>
    
    <?php
        $inputType = match ($field->type) {
            'text' => 'text',
            'email' => 'email',
            'tel' => 'tel',
            'number' => 'number',
            'url' => 'url',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'datetime-local',
            default => 'text',
        };
        ?>
    <div class="<?php echo e($widthClass); ?>" <?php echo $conditionAttr; ?>>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($isFloating) { ?>
        <div class="form-floating">
            <input type="<?php echo e($inputType); ?>" id="<?php echo e($inputId); ?>" name="<?php echo e($field->key); ?>"
                class="form-control"
                placeholder="<?php echo e($fieldPlaceholder ?? $fieldLabel); ?>"
                value="<?php echo e($field->default_value); ?>"
                data-form-field-id="<?php echo e($field->id); ?>"
                data-form-field-label="<?php echo e($fieldLabel); ?>"
                data-form-field-help="<?php echo e($field->help_text); ?>"
                data-form-field-required="<?php echo e($field->is_required ? '1' : '0'); ?>"
                <?php echo e($field->is_required ? 'required' : ''); ?>

                <?php if ($field->min_value !== null) { ?> min="<?php echo e($field->min_value); ?>" <?php } ?>
                <?php if ($field->max_value !== null) { ?> max="<?php echo e($field->max_value); ?>" <?php } ?>
                <?php echo $autoPopulate; ?>>
            <label for="<?php echo e($inputId); ?>"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><div class="form-text"><?php echo e($field->help_text); ?></div><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <div class="invalid-feedback"></div>
        </div>
        <?php } else { ?>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($labelPos !== 'hidden') { ?>
            <label for="<?php echo e($inputId); ?>" class="form-label"><?php echo e($fieldLabel); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?><span class="text-danger ms-1">*</span><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?></label>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <input type="<?php echo e($inputType); ?>" id="<?php echo e($inputId); ?>" name="<?php echo e($field->key); ?>"
                class="form-control"
                placeholder="<?php echo e($fieldPlaceholder); ?>"
                value="<?php echo e($field->default_value); ?>"
                data-form-field-id="<?php echo e($field->id); ?>"
                data-form-field-label="<?php echo e($fieldLabel); ?>"
                data-form-field-help="<?php echo e($field->help_text); ?>"
                data-form-field-required="<?php echo e($field->is_required ? '1' : '0'); ?>"
                <?php echo e($field->is_required ? 'required' : ''); ?>

                <?php if ($field->min_value !== null) { ?> min="<?php echo e($field->min_value); ?>" <?php } ?>
                <?php if ($field->max_value !== null) { ?> max="<?php echo e($field->max_value); ?>" <?php } ?>
                <?php if ($field->show_char_counter && $field->max_value) { ?> maxlength="<?php echo e((int) $field->max_value); ?>" <?php } ?>
                <?php echo $autoPopulate; ?>>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->help_text) { ?><small class="text-muted d-block mt-1"><?php echo e($field->help_text); ?></small><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->show_char_counter) { ?>
            <small class="forms-char-counter text-muted" data-target="<?php echo e($inputId); ?>">0<?php echo e($field->max_value ? '/'.(int) $field->max_value : ''); ?> <?php echo e($fieldI18n['chars']); ?></small>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <div class="invalid-feedback"></div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    </div>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Forms/resources/views/public/partials/field.blade.php ENDPATH**/ ?>