$(function () {

    /**
     * Initialize select2 on all <select> elements.
     * - Skips elements with data-no-select2 (opt-out).
     * - Skips elements already initialized, UNLESS $forceModal=true (modal re-init).
     * - Selects inside hidden modals are skipped on page load and initialized on shown.bs.modal.
     * - Selects inside modals always get dropdownParent + width:100%.
     *
     * @param {jQuery}  $context     Scope to search within (defaults to document).
     * @param {boolean} $forceModal  When true, destroy+reinit selects already initialized inside a modal.
     */
    function initSelect2($context, forceModal) {
        $context = $context || $(document);

        $context.find('select:not([data-no-select2])').each(function () {
            var $select = $(this);
            var $modal  = $select.closest('.modal');

            // Inside a modal
            if ($modal.length) {
                // On page load skip hidden modals — width would be 0/auto
                if (!forceModal && !$modal.hasClass('show')) {
                    return;
                }
                // Destroy existing instance so we can re-init with correct width
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({ dropdownParent: $modal, width: '100%' });
                return;
            }

            // Outside modal — skip if already initialized
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            $select.select2();
        });
    }

    // Initial page load (skips selects inside hidden modals)
    initSelect2();

    // Re-init after AJAX (dynamically injected selects)
    $(document).ajaxComplete(function () {
        initSelect2();
    });

    // When a modal opens: destroy+reinit its selects with correct width
    $(document).on('shown.bs.modal', '.modal', function () {
        initSelect2($(this), true);
    });

});
