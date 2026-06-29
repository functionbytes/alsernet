@php
    $typeIcons = [
        'text'               => 'fas fa-font',
        'textarea'           => 'fas fa-align-left',
        'email'              => 'fas fa-at',
        'phone'              => 'fas fa-phone',
        'number'             => 'fas fa-hashtag',
        'date'               => 'fas fa-calendar-alt',
        'time'               => 'fas fa-clock',
        'url'                => 'fas fa-link',
        'select'             => 'fas fa-caret-square-down',
        'radio'              => 'far fa-dot-circle',
        'checkbox'           => 'far fa-check-square',
        'image_choice'       => 'far fa-image',
        'file'               => 'fas fa-paperclip',
        'rating'             => 'fas fa-star',
        'slider'             => 'fas fa-sliders-h',
        'nps'                => 'fas fa-chart-bar',
        'likert'             => 'fas fa-table',
        'signature'          => 'fas fa-signature',
        'calculation'        => 'fas fa-calculator',
        'address'            => 'fas fa-map-marker-alt',
        'section_header'     => 'fas fa-heading',
        'html_block'         => 'fas fa-code',
        'divider'            => 'fas fa-minus',
        'spacer'             => 'fas fa-arrows-alt-v',
        'consent'            => 'fas fa-user-check',
        'newsletter_consent' => 'fas fa-newspaper',
        'hidden'             => 'far fa-eye-slash',
        'password'           => 'fas fa-key',
        'color_picker'       => 'fas fa-palette',
    ];

    $accentClasses = [
        'text'               => 'field-accent-basic',
        'email'              => 'field-accent-basic',
        'phone'              => 'field-accent-basic',
        'textarea'           => 'field-accent-basic',
        'number'             => 'field-accent-basic',
        'date'               => 'field-accent-basic',
        'time'               => 'field-accent-basic',
        'url'                => 'field-accent-basic',
        'password'           => 'field-accent-basic',
        'select'             => 'field-accent-select',
        'radio'              => 'field-accent-select',
        'checkbox'           => 'field-accent-select',
        'image_choice'       => 'field-accent-select',
        'file'               => 'field-accent-advanced',
        'rating'             => 'field-accent-advanced',
        'slider'             => 'field-accent-advanced',
        'nps'                => 'field-accent-advanced',
        'likert'             => 'field-accent-advanced',
        'signature'          => 'field-accent-advanced',
        'calculation'        => 'field-accent-advanced',
        'address'            => 'field-accent-advanced',
        'color_picker'       => 'field-accent-advanced',
        'section_header'     => 'field-accent-layout',
        'html_block'         => 'field-accent-layout',
        'divider'            => 'field-accent-layout',
        'spacer'             => 'field-accent-layout',
        'hidden'             => 'field-accent-layout',
        'consent'            => 'field-accent-legal',
        'newsletter_consent' => 'field-accent-legal',
    ];

    $widthLabels = [
        'full'    => '100%',
        'half'    => '50%',
        'third'   => '33%',
        'quarter' => '25%',
    ];

    $icon        = $typeIcons[$field->type] ?? 'fas fa-question';
    $accentClass = $accentClasses[$field->type] ?? '';
@endphp

<tr class="field-item {{ $accentClass }}"
    id="field-item-{{ $field->id }}"
    data-id="{{ $field->id }}">

    {{-- Drag handle --}}
    <td class="col-drag text-center">
        <span class="drag-handle" title="Arrastrar para reordenar">
            <i class="fas fa-grip-vertical"></i>
        </span>
    </td>

    {{-- Campo: label + type below --}}
    <td class="col-label">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted flex-shrink-0" title="{{ $field->type }}">
                <i class="{{ $icon }}"></i>
            </span>
            <div class="min-width-0">
                <div class="fw-semibold small text-truncate">{{ $field->label }}</div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <code class="field-type-badge text-muted">{{ $field->type }}</code>
                    @if ($field->is_required)
                        <span class="badge bg-light-danger text-danger field-type-badge">Req.</span>
                    @endif
                    @if ($field->step_number)
                        <span class="badge bg-light text-secondary field-type-badge">P{{ $field->step_number }}</span>
                    @endif
                </div>
            </div>
        </div>
    </td>

    {{-- Key --}}
    <td class="col-key">
        <span class="badge bg-light text-secondary field-type-badge field-key-badge text-truncate d-inline-block">{{ $field->name }}</span>
    </td>

    {{-- Actions --}}
    <td class="col-actions text-center">
        <div class="dropdown field-actions">
            <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-ellipsis-vertical"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item btn-edit-field" href="javascript:void(0)"
                       data-id="{{ $field->id }}">
                        Editar
                    </a>
                </li>
                <li>
                    <a class="dropdown-item btn-duplicate-field" href="javascript:void(0)"
                       data-field-id="{{ $field->id }}"
                       data-url="{{ route('settings.forms.fields.duplicate', [$form, $field]) }}">
                        Duplicar
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item btn-delete-field" href="javascript:void(0)"
                       data-id="{{ $field->id }}"
                       data-label="{{ $field->label }}">
                        Eliminar
                    </a>
                </li>
            </ul>
        </div>
    </td>

</tr>
