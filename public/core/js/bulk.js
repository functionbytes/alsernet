/**
 * BulkActions — selección masiva con toolbar flotante y contador.
 *
 * Uso:
 *   const bulk = window.BulkActions.init({
 *     checkbox:  '.my-checkbox',
 *     toolbar:   '#my-toolbar',   // id con o sin '#'
 *     selectAll: '#my-select-all' // id con o sin '#'
 *   });
 *   bulk.getIds()   → [1, 2, 3]
 *   bulk.reset()    → desmarca todo
 */
window.BulkActions = (function () {

    function init(options) {
        const checkboxSelector = options.checkbox || '.bulk-checkbox';

        // Resolve toolbar: accept id string with or without '#', fallback to legacy id
        const toolbarId = options.toolbar
            ? options.toolbar.replace(/^#/, '')
            : 'bulk-toolbar';
        const toolbar = document.getElementById(toolbarId);

        // Resolve select-all: accept id string with or without '#', fallback to legacy ids
        const selectAllId = options.selectAll
            ? options.selectAll.replace(/^#/, '')
            : null;
        const selectAllEl = selectAllId
            ? document.getElementById(selectAllId)
            : (document.getElementById('check-all') || document.getElementById('select-all'));

        // Build count elements: toolbar + corresponding modal (bulk-toolbar-X → bulk-X-modal)
        function getCountEls() {
            const group = toolbarId.replace('bulk-toolbar-', '');
            const modalEl = document.getElementById('bulk-' + group + '-modal');
            const els = [
                ...(toolbar  ? Array.from(toolbar.querySelectorAll('[data-bulk-count]'))  : []),
                ...(modalEl  ? Array.from(modalEl.querySelectorAll('[data-bulk-count]'))  : []),
            ];
            return els.length ? els : Array.from(document.querySelectorAll('[data-bulk-count]'));
        }

        function selectedIds() {
            return Array.from(document.querySelectorAll(checkboxSelector + ':checked'))
                .map(el => el.value);
        }

        function updateUI() {
            const count = selectedIds().length;
            getCountEls().forEach(el => { el.textContent = count; });
            if (toolbar) {
                toolbar.classList.toggle('d-none', count === 0);
            }
        }

        function reset() {
            document.querySelectorAll(checkboxSelector).forEach(cb => { cb.checked = false; });
            if (selectAllEl) selectAllEl.checked = false;
            updateUI();
        }

        // Delegation so dynamically added rows work
        document.addEventListener('change', function (e) {
            if (e.target.matches(checkboxSelector)) {
                updateUI();
            }
        });

        if (selectAllEl) {
            selectAllEl.addEventListener('change', function () {
                document.querySelectorAll(checkboxSelector).forEach(cb => {
                    cb.checked = this.checked;
                });
                updateUI();
            });
        }

        return {
            getIds: selectedIds,
            reset:  reset,
        };
    }

    return { init: init };

})();
