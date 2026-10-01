{{-- Entrada de etiquetas. Espera: $id, $name, $values, $placeholder, $errorKey. --}}
<div class="ai-tags mb-1" id="{{ $id }}">
    @foreach($values as $value)
        <span class="badge bg-light-secondary text-dark ai-tag">{{ $value }}<input type="hidden" name="{{ $name }}" value="{{ $value }}"><button type="button" class="ai-tag-remove" aria-label="{{ __('helpdeskaiprompts::ai-prompts.actions.remove') }}">&times;</button></span>
    @endforeach
</div>
<input type="text" class="form-control form-control-sm ai-action-tag-add" data-target="#{{ $id }}" data-name="{{ $name }}"
       placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}">
<div class="invalid-feedback" data-error="{{ $errorKey }}"></div>
