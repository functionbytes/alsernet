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

    $icon       = $typeIcons[$field->type] ?? 'fas fa-question';
    $isRequired = (bool) $field->is_required;
@endphp

<tr class="field-item {{ $isRequired ? 'field-required' : '' }}"
    id="field-item-{{ $field->id }}"
    data-id="{{ $field->id }}">

    {{-- Drag handle --}}
    <td class="col-drag text-center">
        <span class="drag-handle" title="Arrastrar para reordenar">
            <i class="fas fa-grip-vertical"></i>
        </span>
    </td>

    {{-- Campo: icono + label + type below --}}
    <td class="col-label">
        <div class="d-flex align-items-center gap-2">
            <span class="field-type-icon {{ $isRequired ? 'field-type-icon-required' : '' }} flex-shrink-0" title="{{ $field->type }}">
                <i class="{{ $icon }}"></i>
            </span>
            <div class="min-width-0">
                <div class="fw-semibold small text-truncate">{{ $field->label }}</div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <code class="field-type-badge text-muted">{{ $field->type }}</code>
                    @if ($isRequired)
                        <span class="badge field-required-badge field-type-badge">Requerido</span>
                    @endif
                    @if ($field->step_number)
                        <span class="badge bg-light text-secondary field-type-badge">P{{ $field->step_number }}</span>
                    @endif
                </div>
            </div>
        </div>
    </td>

    {{-- Actions --}}
    <td class="col-actions text-center">
        <div class="dropdown field-actions">
            <a href="javascript:void(0)" class="field-actions-btn" data-bs-toggle="dropdown" aria-expanded="false">
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
