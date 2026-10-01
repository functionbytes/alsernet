{{-- Fila del repetidor de parámetros. Espera: $i (índice o __INDEX__), $p (array del parámetro). --}}
@php
    $t = fn (string $key) => __('helpdeskaiprompts::ai-prompts.actions.'.$key);
    $type = $p['type'] ?? 'string';
@endphp
<div class="border rounded p-3 mb-2 ai-param-row" data-index="{{ $i }}">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">{{ $t('param_name') }}</label>
            <input type="text" name="parameters[{{ $i }}][name]" class="form-control form-control-sm" maxlength="32"
                   placeholder="product_id" value="{{ $p['name'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">{{ $t('param_type') }}</label>
            <select name="parameters[{{ $i }}][type]" class="form-select form-select-sm ai-param-type">
                @foreach(['string', 'integer', 'number', 'boolean', 'email', 'enum'] as $option)
                    <option value="{{ $option }}" @selected($type === $option)>{{ $t('param_type_'.$option) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-8 col-md-3">
            <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" role="switch" id="param-required-{{ $i }}"
                       name="parameters[{{ $i }}][required]" value="1" @checked(! empty($p['required']))>
                <label class="form-check-label small" for="param-required-{{ $i }}">{{ $t('param_required') }}</label>
            </div>
        </div>
        <div class="col-4 col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-light ai-param-remove" aria-label="{{ $t('remove') }}">
                <i class="fas fa-trash-can"></i>
            </button>
        </div>
        <div class="col-12">
            <label class="form-label small mb-1">{{ $t('param_description') }}</label>
            <input type="text" name="parameters[{{ $i }}][description]" class="form-control form-control-sm" maxlength="300"
                   value="{{ $p['description'] ?? '' }}">
        </div>
        <div class="col-md-8 ai-param-enum {{ $type === 'enum' ? '' : 'd-none' }}">
            <label class="form-label small mb-1">{{ $t('param_enum') }}</label>
            <input type="text" name="parameters[{{ $i }}][enum_text]" class="form-control form-control-sm"
                   placeholder="{{ $t('param_enum_placeholder') }}" value="{{ $p['enum_text'] ?? '' }}">
        </div>
        <div class="col-md-8 ai-param-string {{ $type === 'string' ? '' : 'd-none' }}">
            <label class="form-label small mb-1">{{ $t('param_pattern') }}</label>
            <input type="text" name="parameters[{{ $i }}][pattern]" class="form-control form-control-sm font-monospace" maxlength="200"
                   placeholder="^[A-Z0-9-]+$" value="{{ $p['pattern'] ?? '' }}">
        </div>
        <div class="col-md-4 ai-param-string {{ $type === 'string' ? '' : 'd-none' }}">
            <label class="form-label small mb-1">{{ $t('param_max_length') }}</label>
            <input type="number" name="parameters[{{ $i }}][max_length]" class="form-control form-control-sm" min="1" max="2000"
                   placeholder="300" value="{{ $p['max_length'] ?? '' }}">
        </div>
    </div>
    <div class="invalid-feedback" data-error="parameters.{{ $i }}"></div>
</div>
