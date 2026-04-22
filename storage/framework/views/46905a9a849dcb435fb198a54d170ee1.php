<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $typeIcons = [
        'text' => 'fas fa-font',
        'textarea' => 'fas fa-align-left',
        'email' => 'fas fa-at',
        'phone' => 'fas fa-phone',
        'number' => 'fas fa-hashtag',
        'date' => 'fas fa-calendar-alt',
        'time' => 'fas fa-clock',
        'url' => 'fas fa-link',
        'select' => 'fas fa-caret-square-down',
        'radio' => 'far fa-dot-circle',
        'checkbox' => 'far fa-check-square',
        'image_choice' => 'far fa-image',
        'file' => 'fas fa-paperclip',
        'rating' => 'fas fa-star',
        'slider' => 'fas fa-sliders-h',
        'nps' => 'fas fa-chart-bar',
        'likert' => 'fas fa-table',
        'signature' => 'fas fa-signature',
        'calculation' => 'fas fa-calculator',
        'address' => 'fas fa-map-marker-alt',
        'section_header' => 'fas fa-heading',
        'html_block' => 'fas fa-code',
        'divider' => 'fas fa-minus',
        'spacer' => 'fas fa-arrows-alt-v',
        'consent' => 'fas fa-user-check',
        'newsletter_consent' => 'fas fa-newspaper',
        'hidden' => 'far fa-eye-slash',
        'password' => 'fas fa-key',
        'color_picker' => 'fas fa-palette',
    ];

$accentClasses = [
    'text' => 'field-accent-basic',
    'email' => 'field-accent-basic',
    'phone' => 'field-accent-basic',
    'textarea' => 'field-accent-basic',
    'number' => 'field-accent-basic',
    'date' => 'field-accent-basic',
    'time' => 'field-accent-basic',
    'url' => 'field-accent-basic',
    'password' => 'field-accent-basic',
    'select' => 'field-accent-select',
    'radio' => 'field-accent-select',
    'checkbox' => 'field-accent-select',
    'image_choice' => 'field-accent-select',
    'file' => 'field-accent-advanced',
    'rating' => 'field-accent-advanced',
    'slider' => 'field-accent-advanced',
    'nps' => 'field-accent-advanced',
    'likert' => 'field-accent-advanced',
    'signature' => 'field-accent-advanced',
    'calculation' => 'field-accent-advanced',
    'address' => 'field-accent-advanced',
    'color_picker' => 'field-accent-advanced',
    'section_header' => 'field-accent-layout',
    'html_block' => 'field-accent-layout',
    'divider' => 'field-accent-layout',
    'spacer' => 'field-accent-layout',
    'hidden' => 'field-accent-layout',
    'consent' => 'field-accent-legal',
    'newsletter_consent' => 'field-accent-legal',
];

$widthLabels = [
    'full' => '100%',
    'half' => '50%',
    'third' => '33%',
    'quarter' => '25%',
];

$icon = $typeIcons[$field->type] ?? 'fas fa-question';
$accentClass = $accentClasses[$field->type] ?? '';
?>

<tr class="field-item <?php echo e($accentClass); ?>"
    id="field-item-<?php echo e($field->id); ?>"
    data-id="<?php echo e($field->id); ?>">

    
    <td class="col-drag text-center">
        <span class="drag-handle" title="Arrastrar para reordenar">
            <i class="fas fa-grip-vertical"></i>
        </span>
    </td>

    
    <td class="col-label">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted flex-shrink-0" title="<?php echo e($field->type); ?>">
                <i class="<?php echo e($icon); ?>"></i>
            </span>
            <div class="min-width-0">
                <div class="fw-semibold small text-truncate"><?php echo e($field->label); ?></div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <code class="field-type-badge text-muted"><?php echo e($field->type); ?></code>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->is_required) { ?>
                        <span class="badge bg-light-danger text-danger field-type-badge">Req.</span>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($field->step_number) { ?>
                        <span class="badge bg-light text-secondary field-type-badge">P<?php echo e($field->step_number); ?></span>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>
            </div>
        </div>
    </td>

    
    <td class="col-key">
        <span class="badge bg-light text-secondary field-type-badge field-key-badge text-truncate d-inline-block"><?php echo e($field->name); ?></span>
    </td>

    
    <td class="col-actions text-center">
        <div class="dropdown field-actions">
            <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-ellipsis-vertical"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item btn-edit-field" href="javascript:void(0)"
                       data-id="<?php echo e($field->id); ?>">
                        Editar
                    </a>
                </li>
                <li>
                    <a class="dropdown-item btn-duplicate-field" href="javascript:void(0)"
                       data-field-id="<?php echo e($field->id); ?>"
                       data-url="<?php echo e(route('settings.forms.fields.duplicate', [$form, $field])); ?>">
                        Duplicar
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item btn-delete-field" href="javascript:void(0)"
                       data-id="<?php echo e($field->id); ?>"
                       data-label="<?php echo e($field->label); ?>">
                        Eliminar
                    </a>
                </li>
            </ul>
        </div>
    </td>

</tr>
<?php /**PATH /Users/developerts/Herd/system/modules/Forms/resources/views/forms/partials/field-item.blade.php ENDPATH**/ ?>