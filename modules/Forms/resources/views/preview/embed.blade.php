@php
    $previewId = 've-form-preview-'.$form->id;
    $theme = $shortcodeConfig['theme'] ?? $form->theme ?? 'default';
    $columns = (int) ($shortcodeConfig['columns'] ?? 1);
    $display = $shortcodeConfig['display'] ?? 'inline';
    $showTitle = (bool) ($shortcodeConfig['show_title'] ?? true);
    $buttonText = $shortcodeConfig['button_text'] ?? $form->submit_button_text ?? 'Enviar';
    $buttonPosition = $form->button_position ?? 'left';
    $buttonSize = $form->button_size ?? 'md';
    $fields = $form->fields;
@endphp

<div class="ve-form-preview ve-form-preview--{{ $theme }} ve-form-preview--{{ $display }}" id="{{ $previewId }}" data-form-id="{{ $form->id }}" role="region" aria-label="{{ $form->name }}">
    @if($showTitle)
        <div class="ve-form-preview__head">
            <h4 class="ve-form-preview__title">{{ $form->name }}</h4>
            @if($form->description)
                <p class="ve-form-preview__desc">{{ $form->description }}</p>
            @endif
        </div>
    @endif

    @if($display === 'popup')
        <div class="ve-form-preview__popup-hint">
            <button type="button" class="btn btn-primary btn-{{ $buttonSize }}" disabled>{{ $buttonText }}</button>
            <small class="d-block mt-2 text-muted">Modo popup: el formulario abrirá un modal al hacer clic.</small>
        </div>
    @else
        <form class="ve-form-preview__form" onsubmit="return false;" aria-label="Preview del formulario">
            <div class="row g-3 ve-form-preview__grid" data-columns="{{ $columns }}">
                @forelse($fields as $field)
                    @php
                        $colClass = match($field->width ?? 'full') {
                            'half' => 'col-md-6',
                            'third' => 'col-md-4',
                            'quarter' => 'col-md-3',
                            default => 'col-12',
                        };
                        if ($columns > 1 && ($field->width ?? 'full') === 'full') {
                            $colClass = 'col-md-'.(12 / $columns);
                        }
                    @endphp

                    <div class="{{ $colClass }}">
                        <div class="ve-form-preview__field" data-type="{{ $field->type }}">
                            @if(! in_array($field->type, ['hidden', 'section_header', 'html_block', 'divider', 'spacer']))
                                <label class="form-label ve-form-preview__label">
                                    {{ $field->label }}
                                    @if($field->is_required)
                                        <span class="text-danger" aria-hidden="true">*</span>
                                    @endif
                                </label>
                            @endif

                            @switch($field->type)
                                @case('textarea')
                                    <textarea class="form-control" rows="3" placeholder="{{ $field->placeholder }}" disabled></textarea>
                                    @break

                                @case('select')
                                    <select class="form-select" disabled>
                                        <option>{{ $field->placeholder ?? 'Seleccionar…' }}</option>
                                        @foreach((array) ($field->options ?? []) as $opt)
                                            <option>{{ is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('checkbox')
                                @case('radio')
                                    @foreach((array) ($field->options ?? []) as $idx => $opt)
                                        <div class="form-check">
                                            <input class="form-check-input" type="{{ $field->type }}" disabled>
                                            <label class="form-check-label">{{ is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt }}</label>
                                        </div>
                                    @endforeach
                                    @break

                                @case('section_header')
                                    <h5 class="ve-form-preview__section-title mt-2">{{ $field->label }}</h5>
                                    @if($field->help_text)
                                        <small class="text-muted">{{ $field->help_text }}</small>
                                    @endif
                                    @break

                                @case('divider')
                                    <hr class="my-2">
                                    @break

                                @case('spacer')
                                    <div class="ve-form-preview__spacer"></div>
                                    @break

                                @case('html_block')
                                    <div class="ve-form-preview__html">{!! strip_tags($field->html_content ?? '', '<p><strong><em><br><ul><ol><li><a>') !!}</div>
                                    @break

                                @case('consent')
                                @case('newsletter_consent')
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" disabled>
                                        <label class="form-check-label">{{ $field->consent_text ?? $field->label }}</label>
                                    </div>
                                    @break

                                @case('rating')
                                    <div class="ve-form-preview__rating" aria-label="Valoración">
                                        @for($i = 0; $i < (int) ($field->max_value ?? 5); $i++)
                                            <i class="far fa-star" aria-hidden="true"></i>
                                        @endfor
                                    </div>
                                    @break

                                @case('file')
                                    <input type="file" class="form-control" disabled>
                                    @break

                                @case('hidden')
                                    <input type="hidden" value="{{ $field->default_value }}">
                                    @break

                                @default
                                    <input type="{{ in_array($field->type, ['email', 'tel', 'number', 'url', 'date', 'time']) ? $field->type : 'text' }}"
                                           class="form-control"
                                           placeholder="{{ $field->placeholder }}"
                                           value="{{ $field->default_value }}"
                                           disabled>
                            @endswitch

                            @if($field->help_text && $field->type !== 'section_header')
                                <small class="form-text text-muted">{{ $field->help_text }}</small>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2" aria-hidden="true"></i>
                        <p class="mb-0">Este formulario todavía no tiene campos visibles.</p>
                    </div>
                @endforelse
            </div>

            @if($fields->isNotEmpty())
                <div class="ve-form-preview__submit text-{{ $buttonPosition === 'full' ? 'center' : $buttonPosition }}">
                    <button type="button" class="btn btn-primary btn-{{ $buttonSize }} {{ $buttonPosition === 'full' ? 'w-100' : '' }}" disabled>{{ $buttonText }}</button>
                </div>
            @endif
        </form>
    @endif
</div>

@once
<style>
.ve-form-preview { padding: 16px; background: #fff; border: 1px dashed #dee2e6; border-radius: 6px; pointer-events: none; }
.ve-form-preview__head { margin-bottom: 12px; }
.ve-form-preview__title { font-size: 18px; font-weight: 600; margin: 0 0 4px; }
.ve-form-preview__desc { color: #6c757d; font-size: 13px; margin: 0; }
.ve-form-preview__field[data-type="section_header"] { margin-top: 8px; }
.ve-form-preview__section-title { font-weight: 600; color: #343a40; border-bottom: 1px solid #eee; padding-bottom: 4px; }
.ve-form-preview__spacer { height: 16px; }
.ve-form-preview__submit { margin-top: 16px; }
.ve-form-preview__rating { color: #f6c343; font-size: 18px; letter-spacing: 2px; }
.ve-form-preview--popup { text-align: center; padding: 24px; }
.ve-form-preview__html { font-size: 13px; }
</style>
@endonce
