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
    $icon = $typeIcons[$field->type] ?? 'fas fa-question';

    $widthLabels = [
        'full'    => '100%',
        'half'    => '50%',
        'third'   => '33%',
        'quarter' => '25%',
    ];
@endphp

<div class="field-item d-flex align-items-center gap-3 p-3 mb-2"
     id="field-item-{{ $field->id }}"
     data-id="{{ $field->id }}">

    {{-- Handle --}}
    <span class="drag-handle" title="Arrastrar para reordenar">
        <i class="fas fa-grip-vertical"></i>
    </span>

    {{-- Icono tipo --}}
    <span class="text-muted flex-shrink-0" title="{{ $field->type }}">
        <i class="{{ $icon }}"></i>
    </span>

    {{-- Info --}}
    <div class="flex-grow-1" style="min-width:0">
        <div class="fw-semibold text-truncate">{{ $field->label }}</div>
        <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
            <code class="small text-muted field-type-badge">{{ $field->type }}</code>
            <span class="badge bg-light text-secondary field-type-badge">{{ $field->name }}</span>
            @if ($field->is_required)
                <span class="badge bg-light-danger text-danger field-type-badge">Obligatorio</span>
            @endif
            @if ($field->width && $field->width !== 'full')
                <span class="badge bg-light text-muted field-type-badge">
                    {{ $widthLabels[$field->width] ?? $field->width }}
                </span>
            @endif
        </div>
    </div>

    {{-- Acciones --}}
    <div class="d-flex gap-1 flex-shrink-0">
        <button type="button"
                class="btn btn-sm btn-outline-secondary btn-edit-field"
                data-id="{{ $field->id }}"
                title="Editar campo"
                aria-label="Editar campo {{ $field->label }}">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <button type="button"
                class="btn btn-sm btn-outline-info btn-duplicate-field"
                data-field-id="{{ $field->id }}"
                data-url="{{ route('settings.forms.fields.duplicate', [$form, $field]) }}"
                title="Duplicar campo"
                aria-label="Duplicar campo {{ $field->label }}">
            <i class="fas fa-copy"></i>
        </button>
        <button type="button"
                class="btn btn-sm btn-outline-danger btn-delete-field"
                data-id="{{ $field->id }}"
                data-label="{{ $field->label }}"
                title="Eliminar campo"
                aria-label="Eliminar campo {{ $field->label }}">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>

</div>
