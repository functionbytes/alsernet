@php $t = fn (string $key) => __('helpdeskaiprompts::ai-prompts.actions.'.$key); @endphp
<h6 class="fw-semibold mb-3">{{ $t('section_bridge') }}</h6>
<div class="row g-3 mb-4">
    <div class="col-12">
        <label class="form-label" for="a-bridge-action">{{ $t('field_bridge_action') }} <span class="text-danger">*</span></label>
        <select name="config[action]" id="a-bridge-action" class="form-select">
            <option value="">{{ $t('field_bridge_action_placeholder') }}</option>
            @foreach(['read' => 'group_read', 'write' => 'group_write'] as $mode => $groupKey)
                <optgroup label="{{ $t($groupKey) }}">
                    @foreach($allowlist->where('mode', $mode) as $name => $spec)
                        <option value="{{ $name }}" @selected($form['bridge_action'] === $name)>{{ $name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <small class="text-muted d-block" id="bridge-spec-hint">{{ $t('field_bridge_action_help') }}</small>
        <div class="invalid-feedback" data-error="config.action"></div>
    </div>
    <div class="col-12">
        <label class="form-label" for="a-payload">{{ $t('field_payload') }}</label>
        <textarea name="config[payload]" id="a-payload" class="form-control font-monospace small" rows="6" spellcheck="false"
                  placeholder='{"order_id": "@{{order.id}}"}'>{{ $form['payload'] }}</textarea>
        <small class="text-muted">{{ $t('field_payload_help') }}</small>
        <div class="invalid-feedback" data-error="config.payload"></div>
    </div>
</div>
