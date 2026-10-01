{{-- Modal "Probar acción": los campos de los parámetros los genera ai-prompts-actions.js. --}}
@php $t = fn (string $key) => __('helpdeskaiprompts::ai-prompts.actions.'.$key); @endphp

<div class="modal fade" id="action-test-modal" tabindex="-1" aria-labelledby="action-test-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="action-test-form" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="action-test-title">{{ $t('test_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('helpdeskaiprompts::ai-prompts.cancel') }}"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted small" id="action-test-name"></p>

                    <div class="alert alert-warning small d-none" id="action-test-write-warning">
                        <i class="fas fa-triangle-exclamation me-1"></i>{{ $t('test_write_warning') }}
                    </div>

                    <h6 class="fw-semibold mb-2">{{ $t('test_context') }}</h6>
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" class="form-check-input" id="action-test-verified" name="verified" value="1">
                        <label class="form-check-label" for="action-test-verified">{{ $t('test_verified') }}</label>
                    </div>
                    <div class="mb-3 d-none" id="action-test-email-group">
                        <label class="form-label" for="action-test-email">{{ $t('test_customer_email') }}</label>
                        <input type="email" class="form-control" id="action-test-email" name="customer_email" maxlength="190" placeholder="cliente@example.com">
                    </div>

                    <h6 class="fw-semibold mb-2" id="action-test-params-title">{{ $t('test_params') }}</h6>
                    <div id="action-test-params" class="mb-3"></div>
                    <p class="text-muted small mb-3 d-none" id="action-test-no-params">{{ $t('test_no_params') }}</p>

                    <div class="d-none" id="action-test-result" aria-live="polite">
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge" id="action-test-status"></span>
                            <span class="text-muted small"><i class="far fa-clock me-1"></i><span id="action-test-latency"></span> ms</span>
                        </div>
                        <p class="small text-muted mb-1" id="action-test-status-help"></p>
                        <div class="text-muted small mb-1">{{ $t('test_result_content') }}</div>
                        <pre class="border rounded p-3 bg-light small mb-0 ai-prompt-preview" id="action-test-content"></pre>
                    </div>
                </div>

                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2" id="action-test-run">{{ $t('test_run') }}</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">{{ __('helpdeskaiprompts::ai-prompts.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
