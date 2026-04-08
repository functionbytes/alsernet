@php
    $widthClass = match($field->width) {
        'half' => 'col-md-6',
        'third' => 'col-md-4',
        'quarter' => 'col-md-3',
        default => 'col-12',
    };
    $inputId = $formId . '-' . $field->key;
    $labelPos = $field->label_position ?? 'top';
    $isFloating = $floatingLabel || $labelPos === 'floating';
    $conditionAttr = !empty($field->conditions) ? 'data-conditions="' . htmlspecialchars(json_encode($field->conditions)) . '"' : '';
    $autoPopulate = $field->auto_populate_param
        ? 'data-auto-populate="' . htmlspecialchars($field->auto_populate_param, ENT_QUOTES) . '"'
        : '';
@endphp

@switch($field->type)
    @case('section_header')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        <h5 class="forms-section-header mt-2 mb-1">{{ $field->label }}</h5>
        @if($field->default_value)<p class="text-muted">{{ $field->default_value }}</p>@endif
        <hr class="mt-1">
    </div>
    @break

    @case('html_block')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        {!! clean_html($field->html_content) !!}
    </div>
    @break

    @case('divider')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}><hr></div>
    @break

    @case('spacer')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!} style="height: {{ $field->min_value ?? 20 }}px"></div>
    @break

    @case('hidden')
    <input type="hidden" name="{{ $field->key }}" value="{{ $field->default_value }}" {!! $autoPopulate !!}>
    @break

    @case('textarea')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if(!$isFloating && $labelPos !== 'hidden')
        <label for="{{ $inputId }}" class="form-label">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <textarea
            id="{{ $inputId }}"
            name="{{ $field->key }}"
            class="form-control"
            placeholder="{{ $field->placeholder }}"
            {{ $field->is_required ? 'required' : '' }}
            @if($field->max_value) maxlength="{{ (int)$field->max_value }}" @endif
            rows="4"
            {!! $autoPopulate !!}
        >{{ $field->default_value }}</textarea>
        @if($field->show_char_counter)
        <small class="forms-char-counter text-muted" data-target="{{ $inputId }}">0{{ $field->max_value ? '/' . (int)$field->max_value : '' }} caracteres</small>
        @endif
        <div class="invalid-feedback"></div>
    </div>
    @break

    @case('select')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label for="{{ $inputId }}" class="form-label">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <select id="{{ $inputId }}" name="{{ $field->key }}" class="form-select" {{ $field->is_required ? 'required' : '' }}>
            <option value="">{{ $field->placeholder ?: '-- Seleccionar --' }}</option>
            @foreach($field->options ?? [] as $option)
            <option value="{{ $option['value'] }}" {{ $field->default_value == $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>
    @break

    @case('radio')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label class="form-label d-block">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        @foreach($field->options ?? [] as $option)
        <div class="form-check">
            <input type="radio" id="{{ $inputId }}-{{ $loop->index }}" name="{{ $field->key }}" value="{{ $option['value'] }}" class="form-check-input" {{ $field->is_required ? 'required' : '' }} {{ $field->default_value == $option['value'] ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $inputId }}-{{ $loop->index }}">{{ $option['label'] }}</label>
        </div>
        @endforeach
        <div class="invalid-feedback d-block" id="{{ $inputId }}-error"></div>
    </div>
    @break

    @case('checkbox')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label class="form-label d-block">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        @foreach($field->options ?? [] as $option)
        <div class="form-check">
            <input type="checkbox" id="{{ $inputId }}-{{ $loop->index }}" name="{{ $field->key }}[]" value="{{ $option['value'] }}" class="form-check-input">
            <label class="form-check-label" for="{{ $inputId }}-{{ $loop->index }}">{{ $option['label'] }}</label>
        </div>
        @endforeach
        <div class="invalid-feedback d-block" id="{{ $inputId }}-error"></div>
    </div>
    @break

    @case('file')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label for="{{ $inputId }}" class="form-label">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <input type="file" id="{{ $inputId }}" name="{{ $field->key }}" class="form-control" {{ $field->is_required ? 'required' : '' }}>
        <small class="text-muted">Máx. {{ config('forms.max_file_size_mb', 10) }}MB. Permitidos: {{ implode(', ', config('forms.allowed_file_extensions', [])) }}</small>
        <div class="invalid-feedback"></div>
    </div>
    @break

    @case('rating')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label class="form-label d-block">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <div class="forms-rating" data-field="{{ $field->key }}" data-max="{{ (int)($field->max_value ?? 5) }}">
            @for($i = 1; $i <= ($field->max_value ?? 5); $i++)
            <i class="far fa-star forms-star" data-value="{{ $i }}"></i>
            @endfor
        </div>
        <input type="hidden" name="{{ $field->key }}" id="{{ $inputId }}" value="" {{ $field->is_required ? 'required' : '' }}>
        <div class="invalid-feedback d-block" id="{{ $inputId }}-error"></div>
    </div>
    @break

    @case('nps')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label class="form-label d-block">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <div class="forms-nps d-flex gap-1 flex-wrap">
            @for($i = 0; $i <= 10; $i++)
            <button type="button" class="btn btn-sm forms-nps-btn {{ $i <= 6 ? 'btn-outline-danger' : ($i <= 8 ? 'btn-outline-warning' : 'btn-outline-success') }}" data-value="{{ $i }}" data-field="{{ $field->key }}">{{ $i }}</button>
            @endfor
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">Nada probable</small>
            <small class="text-muted">Muy probable</small>
        </div>
        <input type="hidden" name="{{ $field->key }}" id="{{ $inputId }}" value="" {{ $field->is_required ? 'required' : '' }}>
        <div class="invalid-feedback d-block" id="{{ $inputId }}-error"></div>
    </div>
    @break

    @case('slider')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label for="{{ $inputId }}" class="form-label">{{ $field->label }}: <span id="{{ $inputId }}-val">{{ $field->default_value ?? $field->min_value ?? 0 }}</span>@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <input type="range" id="{{ $inputId }}" name="{{ $field->key }}" class="form-range"
            min="{{ $field->min_value ?? 0 }}"
            max="{{ $field->max_value ?? 100 }}"
            step="{{ $field->step_value ?? 1 }}"
            value="{{ $field->default_value ?? $field->min_value ?? 0 }}"
            {{ $field->is_required ? 'required' : '' }}
            oninput="document.getElementById('{{ $inputId }}-val').textContent = this.value">
        <div class="d-flex justify-content-between">
            <small class="text-muted">{{ $field->min_value ?? 0 }}</small>
            <small class="text-muted">{{ $field->max_value ?? 100 }}</small>
        </div>
    </div>
    @break

    @case('signature')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label class="form-label d-block">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <div class="forms-signature-wrapper border rounded" style="background:#fff">
            <canvas id="{{ $inputId }}-canvas" class="forms-signature-canvas d-block" width="400" height="150" data-field="{{ $field->key }}"></canvas>
            <div class="d-flex justify-content-end p-1 border-top">
                <button type="button" class="btn btn-xs btn-outline-secondary forms-signature-clear" data-target="{{ $inputId }}-canvas">
                    <i class="fas fa-eraser me-1"></i> Borrar
                </button>
            </div>
        </div>
        <input type="hidden" name="{{ $field->key }}" id="{{ $inputId }}" value="" {{ $field->is_required ? 'required' : '' }}>
        <div class="invalid-feedback d-block" id="{{ $inputId }}-error"></div>
    </div>
    @break

    @case('consent')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        <div class="form-check">
            <input type="checkbox" id="{{ $inputId }}" name="{{ $field->key }}" value="1" class="form-check-input" {{ $field->is_required ? 'required' : '' }}>
            <label class="form-check-label" for="{{ $inputId }}">{!! clean($field->consent_text ?? $field->label) !!}</label>
        </div>
        <div class="invalid-feedback"></div>
    </div>
    @break

    @case('newsletter_consent')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        <div class="form-check">
            <input type="checkbox" id="{{ $inputId }}" name="{{ $field->key }}" value="1" class="form-check-input">
            <label class="form-check-label" for="{{ $inputId }}">{{ $field->consent_text ?? $field->label }}</label>
        </div>
    </div>
    @break

    @case('likert')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label class="form-label d-block fw-semibold">{{ $field->label }}</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-2">{{ $field->help_text }}</small>@endif
        @php $likertOptions = $field->options ?? [['value'=>'1','label'=>'Muy en desacuerdo'],['value'=>'2','label'=>'En desacuerdo'],['value'=>'3','label'=>'Neutral'],['value'=>'4','label'=>'De acuerdo'],['value'=>'5','label'=>'Muy de acuerdo']]; @endphp
        <div class="table-responsive">
            <table class="table table-sm table-bordered forms-likert">
                <thead>
                    <tr>
                        <th></th>
                        @foreach($likertOptions as $opt)<th class="text-center small">{{ $opt['label'] }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($field->likert_rows ?? [] as $idx => $row)
                    <tr>
                        <td class="small">{{ is_array($row) ? $row['label'] : $row }}</td>
                        @foreach($likertOptions as $opt)
                        <td class="text-center">
                            <input type="radio" name="{{ $field->key }}[{{ $idx }}]" value="{{ $opt['value'] }}" class="form-check-input" {{ $field->is_required ? 'required' : '' }}>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @break

    @case('address')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label class="form-label d-block">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <div class="row g-2">
            <div class="col-8"><input type="text" name="{{ $field->key }}[street]" class="form-control form-control-sm" placeholder="Calle / Dirección" {{ $field->is_required ? 'required' : '' }}></div>
            <div class="col-4"><input type="text" name="{{ $field->key }}[number]" class="form-control form-control-sm" placeholder="Número"></div>
            <div class="col-6"><input type="text" name="{{ $field->key }}[city]" class="form-control form-control-sm" placeholder="Ciudad" {{ $field->is_required ? 'required' : '' }}></div>
            <div class="col-3"><input type="text" name="{{ $field->key }}[postal_code]" class="form-control form-control-sm" placeholder="CP"></div>
            <div class="col-3"><input type="text" name="{{ $field->key }}[country]" class="form-control form-control-sm" placeholder="País"></div>
        </div>
    </div>
    @break

    @case('image_choice')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label class="form-label d-block">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <div class="d-flex flex-wrap gap-2 forms-image-choice" data-field="{{ $field->key }}">
            @foreach($field->options ?? [] as $option)
            <div class="forms-image-choice-item text-center" data-value="{{ $option['value'] }}" style="cursor:pointer;border:2px solid #dee2e6;border-radius:8px;padding:8px;width:120px">
                @if(!empty($option['image']))<img src="{{ $option['image'] }}" class="img-fluid mb-1" style="height:60px;object-fit:cover">@endif
                <small>{{ $option['label'] }}</small>
            </div>
            @endforeach
        </div>
        <input type="hidden" name="{{ $field->key }}" id="{{ $inputId }}" value="" {{ $field->is_required ? 'required' : '' }}>
        <div class="invalid-feedback d-block" id="{{ $inputId }}-error"></div>
    </div>
    @break

    @case('calculation')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label for="{{ $inputId }}" class="form-label">{{ $field->label }}</label>
        @endif
        <input type="text" id="{{ $inputId }}" name="{{ $field->key }}" class="form-control forms-calculation" readonly placeholder="Calculado automáticamente" data-formula="{{ $field->formula }}" value="{{ $field->default_value }}">
    </div>
    @break

    @case('rich_text')
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($labelPos !== 'hidden')
        <label for="{{ $inputId }}" class="form-label">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
        @endif
        @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
        <textarea id="{{ $inputId }}" name="{{ $field->key }}" class="form-control forms-rich-text" rows="5" {{ $field->is_required ? 'required' : '' }}>{{ $field->default_value }}</textarea>
        <div class="invalid-feedback"></div>
    </div>
    @break

    @case('color_picker')
    <div class="{{ $widthClass ?? 'col-12' }}" {!! $conditionAttr ?? '' !!}>
        @if(($labelPos ?? 'top') !== 'hidden')
            <label for="{{ $inputId }}" class="form-label">
                {{ $field->label }}
                @if($field->is_required)<span class="text-danger ms-1">*</span>@endif
            </label>
        @endif
        <div class="d-flex align-items-center gap-2">
            <input type="color"
                id="{{ $inputId }}"
                name="{{ $field->key }}"
                class="form-control form-control-color"
                value="{{ $field->default_value ?: '#000000' }}"
                {{ $field->is_required ? 'required' : '' }}>
            <span class="text-muted color-picker-value">{{ $field->default_value ?: '#000000' }}</span>
        </div>
        @if($field->help_text)
            <div class="form-text">{{ $field->help_text }}</div>
        @endif
        <div class="invalid-feedback"></div>
    </div>
    @break

    @default
    {{-- text, email, tel, number, url, date, time, datetime --}}
    @php
        $inputType = match($field->type) {
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
    @endphp
    <div class="{{ $widthClass }}" {!! $conditionAttr !!}>
        @if($isFloating)
        <div class="form-floating">
            <input type="{{ $inputType }}" id="{{ $inputId }}" name="{{ $field->key }}"
                class="form-control"
                placeholder="{{ $field->placeholder ?? $field->label }}"
                value="{{ $field->default_value }}"
                {{ $field->is_required ? 'required' : '' }}
                @if($field->min_value !== null) min="{{ $field->min_value }}" @endif
                @if($field->max_value !== null) max="{{ $field->max_value }}" @endif
                {!! $autoPopulate !!}>
            <label for="{{ $inputId }}">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
            @if($field->help_text)<div class="form-text">{{ $field->help_text }}</div>@endif
            <div class="invalid-feedback"></div>
        </div>
        @else
            @if($labelPos !== 'hidden')
            <label for="{{ $inputId }}" class="form-label">{{ $field->label }}@if($field->is_required)<span class="text-danger ms-1">*</span>@endif</label>
            @endif
            @if($field->help_text)<small class="text-muted d-block mb-1">{{ $field->help_text }}</small>@endif
            <input type="{{ $inputType }}" id="{{ $inputId }}" name="{{ $field->key }}"
                class="form-control"
                placeholder="{{ $field->placeholder }}"
                value="{{ $field->default_value }}"
                {{ $field->is_required ? 'required' : '' }}
                @if($field->min_value !== null) min="{{ $field->min_value }}" @endif
                @if($field->max_value !== null) max="{{ $field->max_value }}" @endif
                @if($field->show_char_counter && $field->max_value) maxlength="{{ (int)$field->max_value }}" @endif
                {!! $autoPopulate !!}>
            @if($field->show_char_counter)
            <small class="forms-char-counter text-muted" data-target="{{ $inputId }}">0{{ $field->max_value ? '/' . (int)$field->max_value : '' }} caracteres</small>
            @endif
            <div class="invalid-feedback"></div>
        @endif
    </div>
@endswitch
