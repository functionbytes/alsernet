/**
 * McBox — AJAX content loader for containers.
 *
 * Replaces legacy `Box` from core/js/box.js.
 * Loads HTML fragments via AJAX into a target container element.
 *
 * Usage:
 *   var sidebar = new Box($('#SideBarContent'));
 *   sidebar.load('/some/url');
 *   sidebar.load('/other/url', function() { console.log('loaded'); });
 *   sidebar.loadHtml('<div>Direct HTML</div>');
 *   sidebar.loading(); // show skeleton
 */
class Box {
    constructor(selector, url, callback) {
        this.box = selector;
        this.url = url;
        this.callback = callback;
        this.data = null;

        // Show initial skeleton
        this._showSkeleton();
    }

    /**
     * Show loading skeleton in the container.
     */
    loading() {
        this._showSkeleton();
    }

    /**
     * Mark content as loaded — init dynamic JS on new content.
     */
    loaded() {
        this._removeSkeleton();
        this._initDynamicJs();
    }

    /**
     * Load content from URL via AJAX.
     * @param {string} [url] - URL to load (optional, uses constructor URL if omitted)
     * @param {Function} [callback] - Called after content is loaded
     */
    load(url, callback) {
        if (typeof url !== 'undefined') this.url = url;
        if (typeof callback !== 'undefined') this.callback = callback;

        this.loading();

        var _this = this;
        $.ajax({
            url: _this.url,
            type: 'GET',
            dataType: 'html',
            data: _this.data || {}
        }).always(function(response) {
            _this.box.html(response);

            if (typeof _this.callback !== 'undefined') {
                _this.callback();
            }

            _this.box.animate({ scrollTop: 0 });
            _this.loaded();
        });
    }

    /**
     * Load content from raw HTML string.
     * @param {string} html
     */
    loadHtml(html) {
        this.box.html(html);
        this.box.animate({ scrollTop: 0 });
        this.loaded();
    }

    // ── Private ────────────────────────────────────────────

    _showSkeleton() {
        this.box.addClass('mc-box-loading');

        // Add shimmer overlay if not present
        if (!this.box.find('.mc-box-skeleton').length) {
            this.box.prepend(
                '<div class="mc-box-skeleton">' +
                    '<div class="mc-skeleton-line mc-skeleton-line-lg"></div>' +
                    '<div class="mc-skeleton-line mc-skeleton-line-md"></div>' +
                    '<div class="mc-skeleton-line mc-skeleton-line-sm"></div>' +
                    '<div class="mc-skeleton-line mc-skeleton-line-md"></div>' +
                    '<div class="mc-skeleton-line mc-skeleton-line-sm"></div>' +
                '</div>'
            );
        }
    }

    _removeSkeleton() {
        this.box.removeClass('mc-box-loading');
        this.box.find('.mc-box-skeleton').remove();
    }

    /**
     * Initialize JS on dynamically loaded content.
     * Handles RichSelect, TagInput, pickadate, numeric inputs, etc.
     */
    _initDynamicJs() {
        var c = this.box;

        // Execute <script> tags (innerHTML ignores them)
        c.find('script').each(function() {
            var oldScript = this;
            var newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        // RichSelect (replaces select2)
        if (window.McRichSelect) {
            c.find('[data-rich-select]').each(function() {
                if (!this._richSelect) this._richSelect = new McRichSelect(this);
            });
        }

        // TagInput (replaces select-tag / select2 tags)
        if (window.McTagInput) {
            c.find('[data-mc-tag-input]').each(function() {
                if (!this.getAttribute('data-mc-tag-initialized')) new McTagInput(this);
            });
        }

        // Pickadate
        if (c.find('.pickadate-control').length && $.fn.pickadate) {
            c.find('.pickadate-control').pickadate({
                format: 'yyyy-mm-dd',
                selectMonths: true,
                selectYears: 160,
            });
        }

        // Datetime picker (AnyTime)
        if (c.find('.pickadatetime').length && $.fn.AnyTime_picker) {
            c.find('.pickadatetime').each(function() {
                var id = '_' + Math.random().toString(36).substr(2, 9);
                $(this).attr('id', id);
                $('#' + id).AnyTime_picker({ format: typeof LANG_ANY_DATETIME_FORMAT !== 'undefined' ? LANG_ANY_DATETIME_FORMAT : '%Z-%m-%d, %H:%i' });
            });
        }

        // Time picker
        if (c.find('.pickatime, .time-selector').length && $.fn.AnyTime_picker) {
            c.find('.pickatime, .time-selector').each(function() {
                var id = '_' + Math.random().toString(36).substr(2, 9);
                $(this).attr('id', id);
                $('#' + id).AnyTime_picker({ format: '%H:%i' });
            });
        }

        // Numeric
        if (c.find('.numeric').length && $.fn.numeric) {
            c.find('.numeric').numeric();
        }

        // jQuery validate
        if (typeof customValidate === 'function') {
            customValidate(c.find('.form-validate-jquery'));
        }
    }
}
