/**
 * Thin wrapper around AutoSuggest.js that standardises the
 * PersonalizationTagCatalog item shape across plain input/textarea editors.
 *
 * Item shape (from PHP service):
 *   { key, label, description, snippet, group }
 *
 * Usage (plain input / textarea / contenteditable):
 *   PersonalizationAutoSuggest.mount({
 *       selector: '[data-subject-autosuggest]',
 *       items: <array>,
 *   });
 */
(function (global) {
    'use strict';

    function mount(options) {
        options = options || {};
        var items = Array.isArray(options.items) ? options.items : [];

        if (!global.AutoSuggest || !options.selector) {
            return null;
        }

        var instance = global.AutoSuggest.create({
            trigger: options.trigger || '{' + '{',
            selector: options.selector,
            maxResults: options.maxResults || 8,
            ariaLabel: options.ariaLabel || 'Personalization tag suggestions',
            getTitle:   function (item) { return item.label || item.key || ''; },
            getText:    function (item) { return item.snippet || ''; },
            getPreview: function (item) { return item.description || item.snippet || ''; },
        });

        instance.setArrayDataSource(items);
        instance.mount();
        return instance;
    }

    global.PersonalizationAutoSuggest = {
        mount: mount,
    };
})(typeof window !== 'undefined' ? window : globalThis);
