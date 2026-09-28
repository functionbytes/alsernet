{{-- Shared modal for "Probar" (saved case) and "Probar borrador" (case form) results. --}}
<div class="modal fade" id="test-results-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('helpdeskaiprompts::ai-prompts.modal_test_draft_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="test-results-loading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div id="test-results-empty" class="text-muted text-center py-4 d-none">
                    {{ __('helpdeskaiprompts::ai-prompts.no_test_questions') }}
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle d-none" id="test-results-table">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('helpdeskaiprompts::ai-prompts.th_question') }}</th>
                                <th>{{ __('helpdeskaiprompts::ai-prompts.th_answer') }}</th>
                                <th>{{ __('helpdeskaiprompts::ai-prompts.th_tools') }}</th>
                                <th class="text-center">{{ __('helpdeskaiprompts::ai-prompts.th_result') }}</th>
                            </tr>
                        </thead>
                        <tbody id="test-results-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">{{ __('helpdeskaiprompts::ai-prompts.cancel') }}</button>
            </div>
        </div>
    </div>
</div>
