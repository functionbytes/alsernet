<div class="modal fade" id="bv-agent-actions-modal" tabindex="-1" aria-labelledby="bv-agent-actions-title" aria-hidden="true"
     data-i18n="{{ json_encode([
         'selectAction' => __('helpdeskaiprompts::agent-actions.select_action'),
         'fieldRequired' => __('helpdeskaiprompts::agent-actions.field_required'),
         'writeRequired' => __('helpdeskaiprompts::agent-actions.write_required'),
         'loadError' => __('helpdeskaiprompts::agent-actions.load_error'),
         'runError' => __('helpdeskaiprompts::agent-actions.run_error'),
         'throttled' => __('helpdeskaiprompts::agent-actions.throttled'),
         'running' => __('helpdeskaiprompts::agent-actions.running'),
         'run' => __('helpdeskaiprompts::agent-actions.run'),
         'inserted' => __('helpdeskaiprompts::agent-actions.inserted'),
         'latency' => __('helpdeskaiprompts::agent-actions.latency'),
     ]) }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="bv-agent-actions-title">{{ __('helpdeskaiprompts::agent-actions.title') }}</h5>
                    <small class="text-muted">{{ __('helpdeskaiprompts::agent-actions.subtitle') }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('helpdeskaiprompts::agent-actions.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="text-center text-muted py-4" id="aa-loading"><i class="fas fa-spinner fa-spin"></i></div>
                <div class="alert alert-danger d-none" id="aa-load-error" role="alert"></div>
                <div class="alert alert-warning d-none" id="aa-empty" role="alert">{{ __('helpdeskaiprompts::agent-actions.no_actions') }}</div>

                <form id="aa-form" class="d-none" novalidate autocomplete="off">
                    <div class="alert alert-warning d-none" id="aa-no-customer" role="alert">{{ __('helpdeskaiprompts::agent-actions.no_customer') }}</div>

                    <div class="mb-3">
                        <label class="form-label" for="aa-action">{{ __('helpdeskaiprompts::agent-actions.action') }}</label>
                        <select class="form-select" id="aa-action"></select>
                        <div class="form-text" id="aa-description"></div>
                    </div>

                    <div class="mb-3 d-none" id="aa-identity-ok">
                        <span class="badge bg-success"><i class="fas fa-circle-check me-1"></i>{{ __('helpdeskaiprompts::agent-actions.identity_verified') }}</span>
                    </div>
                    <div class="form-check mb-3 d-none" id="aa-identity-box">
                        <input class="form-check-input" type="checkbox" id="aa-identity">
                        <label class="form-check-label" for="aa-identity">{{ __('helpdeskaiprompts::agent-actions.identity_confirm') }}</label>
                        <div class="form-text">{{ __('helpdeskaiprompts::agent-actions.identity_hint') }}</div>
                    </div>

                    <div class="row g-3" id="aa-params"></div>

                    <div class="alert alert-warning mt-3 mb-0 d-none" id="aa-write-box">
                        <div class="fw-bold mb-2"><i class="fas fa-triangle-exclamation me-1"></i>{{ __('helpdeskaiprompts::agent-actions.write_badge') }}</div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="aa-write">
                            <label class="form-check-label" for="aa-write">{{ __('helpdeskaiprompts::agent-actions.write_confirm') }}</label>
                        </div>
                    </div>
                </form>

                <div class="mt-3 d-none" id="aa-result" aria-live="polite">
                    <h6 class="fw-bold mb-2 border-bottom pb-2">{{ __('helpdeskaiprompts::agent-actions.result') }} <span class="badge ms-1" id="aa-status"></span></h6>
                    <div class="table-responsive d-none" id="aa-result-table">
                        <table class="table table-sm align-middle mb-0"><tbody id="aa-result-rows"></tbody></table>
                    </div>
                    <p class="mb-0 d-none" id="aa-result-text"></p>
                    <small class="text-muted d-block mt-2" id="aa-latency"></small>
                </div>
            </div>
            <div class="modal-footer flex-column align-items-stretch">
                <button type="button" class="btn btn-primary w-100 mb-2" id="aa-run" disabled>{{ __('helpdeskaiprompts::agent-actions.run') }}</button>
                <button type="button" class="btn btn-success w-100 mb-2 d-none" id="aa-insert">{{ __('helpdeskaiprompts::agent-actions.insert') }}</button>
                <button type="button" class="btn btn-light w-100 m-0" data-bs-dismiss="modal">{{ __('helpdeskaiprompts::agent-actions.close') }}</button>
            </div>
        </div>
    </div>
</div>
