{{--
    One "test_questions" repeater row. Reused for server-rendered rows (real
    index) and the hidden <template> (literal token __INDEX__, replaced by JS
    when a new row is cloned) — every name/id/data-target below must use the
    same $i token consistently so the JS string-replace covers all of them.
--}}
<div class="border rounded p-3 ai-test-question-row">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <label class="form-label mb-0 flex-fill">
            {{ __('helpdeskaiprompts::ai-prompts.field_tq_question') }}
            <input type="text" name="test_questions[{{ $i }}][question]" class="form-control form-control-sm mt-1"
                   maxlength="500" value="{{ $tq['question'] ?? '' }}">
        </label>
        <button type="button" class="btn btn-light btn-sm ai-remove-row ms-2" aria-label="{{ __('helpdeskaiprompts::ai-prompts.remove') }}"><i class="fas fa-xmark"></i></button>
    </div>

    <div class="row g-2 mb-2">
        <div class="col-md-8">
            <label class="form-label small mb-1">{{ __('helpdeskaiprompts::ai-prompts.field_tq_expect_tools') }}</label>
            <div class="d-flex flex-wrap gap-2">
                @foreach($tools as $tool => $toolDescription)
                    <div class="form-check form-check-inline m-0">
                        <input class="form-check-input" type="checkbox" name="test_questions[{{ $i }}][expect_tools][]" value="{{ $tool }}"
                               id="tq-{{ $i }}-tool-{{ $tool }}" @checked(in_array($tool, (array) ($tq['expect_tools'] ?? []), true))>
                        <label class="form-check-label small" for="tq-{{ $i }}-tool-{{ $tool }}">{{ $tool }}</label>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1" for="tq-{{ $i }}-escalate">{{ __('helpdeskaiprompts::ai-prompts.field_tq_expect_escalate') }}</label>
            @php $expectEscalate = $tq['expect_escalate'] ?? null; @endphp
            <select name="test_questions[{{ $i }}][expect_escalate]" id="tq-{{ $i }}-escalate" class="form-select form-select-sm">
                <option value="" @selected($expectEscalate === null)>{{ __('helpdeskaiprompts::ai-prompts.expect_escalate_indifferent') }}</option>
                <option value="1" @selected($expectEscalate === true)>{{ __('helpdeskaiprompts::ai-prompts.expect_escalate_yes') }}</option>
                <option value="0" @selected($expectEscalate === false)>{{ __('helpdeskaiprompts::ai-prompts.expect_escalate_no') }}</option>
            </select>
        </div>
    </div>

    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label small mb-1" for="tq-{{ $i }}-must-add">{{ __('helpdeskaiprompts::ai-prompts.field_tq_must_contain') }}</label>
            <div class="ai-tags mb-1" id="tq-{{ $i }}-must-tags" data-name="test_questions[{{ $i }}][must_contain]">
                @foreach((array) ($tq['must_contain'] ?? []) as $needle)
                    <span class="badge bg-light-secondary text-dark ai-tag">{{ $needle }}<input type="hidden" name="test_questions[{{ $i }}][must_contain][]" value="{{ $needle }}"><button type="button" class="ai-tag-remove">&times;</button></span>
                @endforeach
            </div>
            <input type="text" id="tq-{{ $i }}-must-add" class="form-control form-control-sm ai-tag-add" data-target="#tq-{{ $i }}-must-tags">
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-1" for="tq-{{ $i }}-must-not-add">{{ __('helpdeskaiprompts::ai-prompts.field_tq_must_not_contain') }}</label>
            <div class="ai-tags mb-1" id="tq-{{ $i }}-must-not-tags" data-name="test_questions[{{ $i }}][must_not_contain]">
                @foreach((array) ($tq['must_not_contain'] ?? []) as $needle)
                    <span class="badge bg-light-secondary text-dark ai-tag">{{ $needle }}<input type="hidden" name="test_questions[{{ $i }}][must_not_contain][]" value="{{ $needle }}"><button type="button" class="ai-tag-remove">&times;</button></span>
                @endforeach
            </div>
            <input type="text" id="tq-{{ $i }}-must-not-add" class="form-control form-control-sm ai-tag-add" data-target="#tq-{{ $i }}-must-not-tags">
        </div>
    </div>
</div>
