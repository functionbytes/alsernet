<div class="modal fade" id="quality-report-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('helpdeskaiprompts::quality.detail_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small" id="quality-report-summary"></p>
                <div id="quality-report-body"></div>
            </div>
            <div class="modal-footer flex-column">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">{{ __('helpdeskaiprompts::quality.close') }}</button>
            </div>
        </div>
    </div>
</div>
