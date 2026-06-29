(function (global, factory) {
    if (typeof module === 'object' && typeof module.exports === 'object') {
        module.exports = factory(global);
        return;
    }

    global.AutoSuggest = factory(global);
})(typeof globalThis !== 'undefined' ? globalThis : window, function (global) {
    'use strict';

    const DEFAULT_SELECTOR = 'input, textarea, [contenteditable]:not([contenteditable="false"])';
    const DEFAULT_TRIGGER = '//';
    const DEFAULT_MAX_RESULTS = 8;
    const DEFAULT_PREVIEW_LENGTH = 90;
    const DEFAULT_THEME_CLASS = 'auto-suggest-dropdown';
    const STYLE_ID = 'auto-suggest-styles';

    function create(options) {
        return new AutoSuggestInstance(options || {});
    }

    class AutoSuggestInstance {
        constructor(options) {
            this.state = {
                items: Array.isArray(options?.items) ? options.items.slice() : [],
                dropdown: null,
                list: null,
                visible: false,
                activeIndex: 0,
                activeItems: [],
                activeContext: null,
                isComposing: false,
                ignoreSelectionChangeUntil: 0,
                requestId: 0,
                activeAbortController: null,
                mounted: false,
            };

            this.options = buildOptions(options || {});
            this.boundHandlers = {
                focusin: () => {
                    this.handlePossibleUpdate();
                },
                input: () => {
                    this.handlePossibleUpdate();
                },
                click: () => {
                    this.handlePossibleUpdate();
                },
                keydown: (event) => {
                    this.handleKeydown(event);
                },
                selectionchange: () => {
                    this.handleSelectionChange();
                },
                compositionstart: () => {
                    this.state.isComposing = true;
                },
                compositionend: () => {
                    this.state.isComposing = false;
                    this.handlePossibleUpdate();
                },
                scroll: () => {
                    this.repositionDropdown();
                },
                resize: () => {
                    this.repositionDropdown();
                },
            };
        }

        configure(nextOptions) {
            this.options = buildOptions({
                ...this.options,
                ...(nextOptions || {}),
            });

            if (Array.isArray(nextOptions?.items)) {
                this.state.items = nextOptions.items.slice();
            }

            if (this.state.dropdown) {
                this.state.dropdown.className = this.options.themeClass;
                this.state.dropdown.setAttribute('aria-label', this.options.ariaLabel);
            }

            return this;
        }

        mount(selectorOrElements) {
            if (selectorOrElements !== undefined) {
                this.bind(selectorOrElements);
            }

            if (this.state.mounted) {
                return this;
            }

            ensureSharedStyles();
            this.ensureDropdown();
            this.bindGlobalEvents();
            this.state.mounted = true;
            this.handlePossibleUpdate();

            return this;
        }

        bind(selectorOrElements) {
            this.options.targetMatcher = createTargetMatcher(selectorOrElements || this.options.selector || DEFAULT_SELECTOR);
            return this.mount();
        }

        unmount() {
            if (!this.state.mounted) {
                return this;
            }

            document.removeEventListener('focusin', this.boundHandlers.focusin, true);
            document.removeEventListener('input', this.boundHandlers.input, true);
            document.removeEventListener('click', this.boundHandlers.click, true);
            document.removeEventListener('keydown', this.boundHandlers.keydown, true);
            document.removeEventListener('selectionchange', this.boundHandlers.selectionchange, true);
            document.removeEventListener('compositionstart', this.boundHandlers.compositionstart, true);
            document.removeEventListener('compositionend', this.boundHandlers.compositionend, true);
            global.removeEventListener('scroll', this.boundHandlers.scroll, true);
            global.removeEventListener('resize', this.boundHandlers.resize, true);

            if (this.state.activeAbortController) {
                this.state.activeAbortController.abort();
                this.state.activeAbortController = null;
            }

            this.hideDropdown();
            this.state.mounted = false;

            return this;
        }

        destroy() {
            this.unmount();

            if (this.state.dropdown?.parentNode) {
                this.state.dropdown.parentNode.removeChild(this.state.dropdown);
            }

            this.state.dropdown = null;
            this.state.list = null;

            return this;
        }

        setItems(items) {
            return this.applyLocalItems(items);
        }

        setDataSource(dataSource) {
            if (Array.isArray(dataSource)) {
                return this.applyLocalItems(dataSource);
            }

            this.options.dataSource = normalizeDataSource(dataSource, this.options);
            return this;
        }

        setArrayDataSource(items) {
            return this.applyLocalItems(items);
        }

        applyLocalItems(items) {
            this.state.items = Array.isArray(items) ? items.slice() : [];
            this.options.dataSource = null;

            if (this.state.visible) {
                this.handlePossibleUpdate();
            }

            return this;
        }

        setTrigger(trigger) {
            this.options.trigger = normalizeTrigger(trigger);

            if (this.state.visible) {
                this.handlePossibleUpdate();
            }

            return this;
        }

        setSelector(selectorOrElements) {
            this.options.targetMatcher = createTargetMatcher(selectorOrElements || DEFAULT_SELECTOR);
            return this;
        }

        refresh() {
            this.handlePossibleUpdate();
            return this;
        }

        bindGlobalEvents() {
            document.addEventListener('focusin', this.boundHandlers.focusin, true);
            document.addEventListener('input', this.boundHandlers.input, true);
            document.addEventListener('click', this.boundHandlers.click, true);
            document.addEventListener('keydown', this.boundHandlers.keydown, true);
            document.addEventListener('selectionchange', this.boundHandlers.selectionchange, true);
            document.addEventListener('compositionstart', this.boundHandlers.compositionstart, true);
            document.addEventListener('compositionend', this.boundHandlers.compositionend, true);
            global.addEventListener('scroll', this.boundHandlers.scroll, true);
            global.addEventListener('resize', this.boundHandlers.resize, true);
        }

        ensureDropdown() {
            if (this.state.dropdown) {
                return;
            }

            const dropdown = document.createElement('div');
            dropdown.className = this.options.themeClass;
            dropdown.setAttribute('role', 'listbox');
            dropdown.setAttribute('aria-label', this.options.ariaLabel);
            dropdown.style.display = 'none';

            const list = document.createElement('div');
            dropdown.appendChild(list);

            this.state.dropdown = dropdown;
            this.state.list = list;
            document.documentElement.appendChild(dropdown);
        }

        async handlePossibleUpdate() {
            if (this.state.isComposing) {
                return;
            }

            const previousActiveItem = this.state.activeItems[this.state.activeIndex] || null;
            const activeContext = this.getActiveContext();

            if (!activeContext) {
                this.hideDropdown();
                return;
            }

            const requestId = ++this.state.requestId;

            try {
                const items = await this.resolveItems(activeContext);

                if (requestId !== this.state.requestId) {
                    return;
                }

                const results = rankSuggestions(activeContext.keyword, items, this.options).slice(0, this.options.maxResults);

                if (results.length === 0) {
                    this.hideDropdown();
                    return;
                }

                this.state.activeContext = activeContext;
                this.state.activeItems = results;
                this.state.activeIndex = getNextActiveIndex(previousActiveItem, results, this.options.getKey);
                this.renderDropdown();
                this.positionDropdown(activeContext.caretRect, activeContext.element);
                this.showDropdown();
            } catch (error) {
                if (requestId !== this.state.requestId) {
                    return;
                }

                this.hideDropdown();

                if (typeof this.options.onError === 'function') {
                    this.options.onError(error, {
                        keyword: activeContext.keyword,
                        target: activeContext.element,
                    });
                }
            }
        }

        handleSelectionChange() {
            if (this.state.visible && Date.now() < this.state.ignoreSelectionChangeUntil) {
                return;
            }

            this.handlePossibleUpdate();
        }

        handleKeydown(event) {
            if (!this.state.visible) {
                return;
            }

            if (event.key === 'ArrowDown') {
                consumeDropdownKeyboardEvent(event);
                this.state.ignoreSelectionChangeUntil = Date.now() + 120;
                this.setActiveIndex((this.state.activeIndex + 1) % this.state.activeItems.length);
                return;
            }

            if (event.key === 'ArrowUp') {
                consumeDropdownKeyboardEvent(event);
                this.state.ignoreSelectionChangeUntil = Date.now() + 120;
                this.setActiveIndex((this.state.activeIndex - 1 + this.state.activeItems.length) % this.state.activeItems.length);
                return;
            }

            if (event.key === 'Enter' || event.key === 'Tab') {
                consumeDropdownKeyboardEvent(event);
                this.applySuggestion(this.state.activeItems[this.state.activeIndex]);
                return;
            }

            if (event.key === 'Escape') {
                consumeDropdownKeyboardEvent(event);
                this.hideDropdown();
            }
        }

        async resolveItems(context) {
            if (!this.options.dataSource) {
                return this.state.items.slice();
            }

            if (this.state.activeAbortController) {
                this.state.activeAbortController.abort();
            }

            const controller = typeof AbortController === 'function' ? new AbortController() : null;
            this.state.activeAbortController = controller;

            const result = await this.options.dataSource({
                keyword: context.keyword,
                trigger: context.token,
                context,
                target: context.element,
                signal: controller?.signal || null,
                items: this.state.items.slice(),
            });

            return Array.isArray(result) ? result : [];
        }

        getActiveContext() {
            const focusedElement = document.activeElement;

            if (isTextControl(focusedElement) && this.matchesTarget(focusedElement)) {
                return this.getTextControlContext(focusedElement);
            }

            const contentEditable = this.findActiveContentEditable();

            if (contentEditable) {
                return this.getContentEditableContext(contentEditable);
            }

            return null;
        }

        matchesTarget(element) {
            return this.options.targetMatcher(element);
        }

        findActiveContentEditable() {
            const selection = global.getSelection();

            if (!selection || selection.rangeCount === 0) {
                return null;
            }

            const anchorNode = selection.anchorNode;

            if (!anchorNode) {
                return null;
            }

            const anchorElement = anchorNode.nodeType === Node.ELEMENT_NODE
                ? anchorNode
                : anchorNode.parentElement;

            if (!anchorElement) {
                return null;
            }

            const candidate = this.findClosestMatchingContentEditable(anchorElement);
            return candidate?.isContentEditable ? candidate : null;
        }

        findClosestMatchingContentEditable(anchorElement) {
            const selector = this.options.selector;

            if (typeof selector === 'string' && selector.trim() !== '') {
                let current = anchorElement;

                while (current) {
                    if (current instanceof Element && current.matches(selector) && current.isContentEditable) {
                        return current;
                    }

                    current = current.parentElement;
                }

                return null;
            }

            let current = anchorElement;

            while (current) {
                if (current instanceof Element && current.isContentEditable && this.matchesTarget(current)) {
                    return current;
                }

                current = current.parentElement;
            }

            return null;
        }

        getTextControlContext(element) {
            const end = element.selectionStart;

            if (typeof end !== 'number') {
                return null;
            }

            const beforeCaret = element.value.slice(0, end);
            const trigger = extractTrigger(beforeCaret, this.options.trigger);

            if (!trigger) {
                return null;
            }

            return {
                type: 'text-control',
                element,
                keyword: trigger.keyword,
                token: trigger.token,
                start: end - trigger.token.length,
                end,
                caretRect: getTextControlCaretRect(element, end),
            };
        }

        getContentEditableContext(root) {
            const selection = global.getSelection();

            if (!selection || selection.rangeCount === 0 || !selection.isCollapsed) {
                return null;
            }

            const range = selection.getRangeAt(0);

            if (!root.contains(range.endContainer)) {
                return null;
            }

            const beforeRange = range.cloneRange();
            beforeRange.selectNodeContents(root);
            beforeRange.setEnd(range.endContainer, range.endOffset);

            const beforeCaret = beforeRange.toString();
            const trigger = extractTrigger(beforeCaret, this.options.trigger);

            if (!trigger) {
                return null;
            }

            const end = getContentEditableTextOffset(root, range.endContainer, range.endOffset);
            const start = end - trigger.token.length;

            return {
                type: 'contenteditable',
                element: root,
                keyword: trigger.keyword,
                token: trigger.token,
                start,
                end,
                caretRect: getContentEditableCaretRect(range, root),
            };
        }

        applySuggestion(item) {
            if (!item || !this.state.activeContext) {
                this.hideDropdown();
                return;
            }

            const context = this.state.activeContext;
            const plainTextReplacement = String(this.options.getText(item) || '');
            const htmlReplacement = String(this.options.getHtml(item) || '').trim() || plainTextToHtml(plainTextReplacement);
            let replaced = false;

            if (context.type === 'text-control') {
                replaced = replaceInTextControl(context, plainTextReplacement);
            } else if (context.type === 'contenteditable') {
                replaced = replaceInContentEditable(context, htmlReplacement, plainTextReplacement);
            }

            if (!replaced) {
                return;
            }

            if (typeof this.options.onSelect === 'function') {
                this.options.onSelect({
                    item,
                    target: context.element,
                    context,
                    plainText: plainTextReplacement,
                    html: htmlReplacement,
                });
            }

            this.hideDropdown();
            this.handlePossibleUpdate();
        }

        renderDropdown() {
            this.state.list.innerHTML = '';

            this.state.activeItems.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `auto-suggest-item${index === this.state.activeIndex ? ' is-active' : ''}`;
                button.dataset.index = String(index);
                button.setAttribute('role', 'option');
                button.setAttribute('aria-selected', index === this.state.activeIndex ? 'true' : 'false');
                button.innerHTML = `
                    <span class="auto-suggest-title">${escapeHtml(this.options.getTitle(item) || '')}</span>
                    <span class="auto-suggest-preview">${escapeHtml(truncatePreview(this.options.getPreview(item), this.options.previewLength))}</span>
                `;

                button.addEventListener('mouseenter', () => {
                    this.setActiveIndex(index);
                });

                button.addEventListener('pointerdown', (event) => {
                    if (event.button !== 0) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    this.setActiveIndex(index);
                    this.applySuggestion(item);
                });

                this.state.list.appendChild(button);
            });

            this.updateActiveDropdownItemState();
        }

        setActiveIndex(nextIndex) {
            this.state.activeIndex = nextIndex;
            this.updateActiveDropdownItemState();
        }

        updateActiveDropdownItemState() {
            if (!this.state.list) {
                return;
            }

            const buttons = this.state.list.querySelectorAll('[data-index]');

            buttons.forEach((buttonElement) => {
                const button = buttonElement;
                const isActive = Number(button.dataset.index) === this.state.activeIndex;

                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            const activeButton = this.state.list.querySelector(`[data-index="${this.state.activeIndex}"]`);

            if (activeButton && typeof activeButton.scrollIntoView === 'function') {
                activeButton.scrollIntoView({
                    block: 'nearest',
                });
            }
        }

        showDropdown() {
            if (!this.state.dropdown) {
                return;
            }

            this.state.visible = true;
            this.state.dropdown.classList.add('is-visible');
            this.state.dropdown.style.display = 'block';
        }

        hideDropdown() {
            if (!this.state.dropdown) {
                return;
            }

            this.state.visible = false;
            this.state.activeContext = null;
            this.state.activeItems = [];
            this.state.dropdown.classList.remove('is-visible');
            this.state.dropdown.style.display = 'none';
        }

        repositionDropdown() {
            if (!this.state.visible || !this.state.activeContext) {
                return;
            }

            this.positionDropdown(this.state.activeContext.caretRect, this.state.activeContext.element);
        }

        positionDropdown(rect, fallbackElement) {
            const elementRect = rect && rect.bottom ? rect : fallbackElement?.getBoundingClientRect();

            if (!elementRect || !this.state.dropdown) {
                return;
            }

            const viewportPadding = 12;
            const dropdownWidth = Math.min(this.options.dropdownWidth, global.innerWidth - viewportPadding * 2);
            const left = Math.min(
                Math.max(viewportPadding, elementRect.left),
                global.innerWidth - dropdownWidth - viewportPadding
            );
            const top = Math.min(
                elementRect.bottom + 8,
                global.innerHeight - 24
            );

            this.state.dropdown.style.left = `${left}px`;
            this.state.dropdown.style.top = `${top}px`;
            this.state.dropdown.style.width = `${dropdownWidth}px`;
        }
    }

    function buildOptions(options) {
        const merged = {
            selector: DEFAULT_SELECTOR,
            trigger: DEFAULT_TRIGGER,
            maxResults: DEFAULT_MAX_RESULTS,
            previewLength: DEFAULT_PREVIEW_LENGTH,
            dropdownWidth: 460,
            themeClass: DEFAULT_THEME_CLASS,
            ariaLabel: 'Autosuggest suggestions',
            dataSource: null,
            getTitle: (item) => item?.title || '',
            getText: (item) => item?.content || '',
            getHtml: (item) => item?.contentHtml || '',
            getPreview: (item) => item?.content || '',
            getKey: (item) => item?.id || item?.title || '',
            onSelect: null,
            onError: null,
            ...options,
        };

        merged.trigger = normalizeTrigger(merged.trigger);
        merged.maxResults = Number.isFinite(Number(merged.maxResults)) ? Math.max(1, Number(merged.maxResults)) : DEFAULT_MAX_RESULTS;
        merged.previewLength = Number.isFinite(Number(merged.previewLength)) ? Math.max(20, Number(merged.previewLength)) : DEFAULT_PREVIEW_LENGTH;
        merged.dropdownWidth = Number.isFinite(Number(merged.dropdownWidth)) ? Math.max(200, Number(merged.dropdownWidth)) : 460;
        merged.themeClass = String(merged.themeClass || DEFAULT_THEME_CLASS).trim() || DEFAULT_THEME_CLASS;
        merged.selector = normalizeSelector(merged.selector);
        merged.targetMatcher = createTargetMatcher(merged.selector);
        merged.dataSource = normalizeDataSource(merged.dataSource, merged);

        return merged;
    }

    function normalizeSelector(selector) {
        if (selector == null) {
            return DEFAULT_SELECTOR;
        }

        if (typeof selector === 'string') {
            const normalized = selector.trim();
            return normalized || DEFAULT_SELECTOR;
        }

        return selector;
    }

    function normalizeTrigger(trigger) {
        const normalized = String(trigger || DEFAULT_TRIGGER);
        return normalized.length > 0 ? normalized : DEFAULT_TRIGGER;
    }

    function createTargetMatcher(selectorOrElements) {
        if (typeof selectorOrElements === 'string') {
            const selector = selectorOrElements.trim() || DEFAULT_SELECTOR;

            return (element) => Boolean(element instanceof Element && element.matches(selector));
        }

        if (selectorOrElements instanceof Element) {
            return (element) => element === selectorOrElements || selectorOrElements.contains(element);
        }

        if (selectorOrElements && typeof selectorOrElements.length === 'number') {
            const elements = Array.from(selectorOrElements).filter((item) => item instanceof Element);

            return (element) => elements.some((candidate) => candidate === element || candidate.contains(element));
        }

        return (element) => Boolean(
            element instanceof Element &&
            (element.matches('textarea, input') || element.isContentEditable)
        );
    }

    function normalizeDataSource(dataSource, options) {
        if (!dataSource) {
            return null;
        }

        if (typeof dataSource === 'function') {
            return dataSource;
        }

        if (typeof dataSource === 'string') {
            const sourceUrl = dataSource;

            return async ({ keyword, signal }) => {
                const url = new URL(sourceUrl, global.location?.href || 'http://localhost');
                url.searchParams.set('q', keyword);

                const response = await fetch(url.toString(), {
                    method: 'GET',
                    signal: signal || undefined,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.error || 'Unable to load autosuggest items.');
                }

                if (Array.isArray(payload)) {
                    return payload;
                }

                if (Array.isArray(payload.data)) {
                    return payload.data;
                }

                throw new Error('Unexpected autosuggest response shape.');
            };
        }

        if (Array.isArray(dataSource)) {
            return async () => dataSource.slice();
        }

        return options?.dataSource || null;
    }

    function extractTrigger(text, trigger) {
        const normalizedText = String(text || '');
        const normalizedTrigger = normalizeTrigger(trigger);
        const pattern = new RegExp(`${escapeRegExp(normalizedTrigger)}([^\\s]*)$`);
        const match = normalizedText.match(pattern);

        if (!match || typeof match.index !== 'number') {
            return null;
        }

        const tokenStart = match.index;
        const previousChar = tokenStart > 0 ? normalizedText.charAt(tokenStart - 1) : '';

        if (normalizedTrigger === '//' && previousChar === ':') {
            return null;
        }

        return {
            keyword: match[1] || '',
            token: match[0],
        };
    }

    function rankSuggestions(keyword, suggestions, options) {
        const normalizedKeyword = String(keyword || '').trim().toLowerCase();

        const matches = suggestions.filter((item) => {
            const title = String(options.getTitle(item) || '').toLowerCase();

            if (!title) {
                return false;
            }

            return normalizedKeyword === '' ? true : title.includes(normalizedKeyword);
        });

        matches.sort((left, right) => {
            const leftTitle = String(options.getTitle(left) || '').toLowerCase();
            const rightTitle = String(options.getTitle(right) || '').toLowerCase();
            const leftStarts = normalizedKeyword && leftTitle.startsWith(normalizedKeyword);
            const rightStarts = normalizedKeyword && rightTitle.startsWith(normalizedKeyword);

            if (leftStarts !== rightStarts) {
                return leftStarts ? -1 : 1;
            }

            return leftTitle.localeCompare(rightTitle);
        });

        return matches;
    }

    function getNextActiveIndex(previousActiveItem, nextItems, getKey) {
        if (!previousActiveItem || nextItems.length === 0) {
            return 0;
        }

        const previousItemKey = String(getKey(previousActiveItem) || '');
        const matchedIndex = nextItems.findIndex((item) => String(getKey(item) || '') === previousItemKey);

        return matchedIndex >= 0 ? matchedIndex : 0;
    }

    function replaceInTextControl(context, replacement) {
        const element = context.element;
        const nextValue = `${element.value.slice(0, context.start)}${replacement}${element.value.slice(context.end)}`;
        const nextCaret = context.start + replacement.length;

        const prototype = element instanceof HTMLTextAreaElement
            ? HTMLTextAreaElement.prototype
            : HTMLInputElement.prototype;
        const descriptor = Object.getOwnPropertyDescriptor(prototype, 'value');

        if (descriptor?.set) {
            descriptor.set.call(element, nextValue);
        } else {
            element.value = nextValue;
        }

        element.focus();

        if (typeof element.setSelectionRange === 'function') {
            element.setSelectionRange(nextCaret, nextCaret);
        }

        element.dispatchEvent(new InputEvent('input', {
            bubbles: true,
            inputType: 'insertReplacementText',
            data: replacement,
        }));

        return true;
    }

    function replaceInContentEditable(context, htmlReplacement, plainTextReplacement) {
        const root = context.element;
        const range = createContentEditableReplacementRange(context);

        if (!range) {
            return false;
        }

        const selection = global.getSelection();
        root.focus();
        selection.removeAllRanges();
        selection.addRange(range);

        if (tryExecContentEditableInsert(htmlReplacement, plainTextReplacement)) {
            return true;
        }

        return replaceInContentEditableFallback(range, root, plainTextReplacement, htmlReplacement);
    }

    function createContentEditableReplacementRange(context) {
        const root = context.element;
        const startPosition = locateTextPosition(root, context.start, 'forward');
        const endPosition = locateTextPosition(root, context.end, 'backward');

        if (!startPosition || !endPosition) {
            return null;
        }

        const range = document.createRange();
        range.setStart(startPosition.node, startPosition.offset);
        range.setEnd(endPosition.node, endPosition.offset);

        return range;
    }

    function tryExecContentEditableInsert(htmlReplacement, plainTextReplacement) {
        if (typeof document.execCommand !== 'function') {
            return false;
        }

        const html = String(htmlReplacement || '').trim();

        try {
            if (html && document.execCommand('insertHTML', false, html)) {
                return true;
            }
        } catch (_error) {
        }

        try {
            if (document.execCommand('insertText', false, plainTextReplacement)) {
                return true;
            }
        } catch (_error) {
        }

        return false;
    }

    function replaceInContentEditableFallback(range, root, plainTextReplacement, htmlReplacement) {
        range.deleteContents();

        const selection = global.getSelection();
        const caretRange = document.createRange();
        let insertedNode = null;
        const template = document.createElement('template');

        template.innerHTML = String(htmlReplacement || '').trim();

        if (template.content.childNodes.length > 0) {
            insertedNode = template.content.lastChild;
            range.insertNode(template.content);
        } else {
            insertedNode = document.createTextNode(plainTextReplacement);
            range.insertNode(insertedNode);
        }

        moveCaretAfterInsertedNode(caretRange, insertedNode);

        selection.removeAllRanges();
        selection.addRange(caretRange);

        root.focus();
        root.dispatchEvent(new InputEvent('input', {
            bubbles: true,
            inputType: 'insertReplacementText',
            data: plainTextReplacement,
        }));

        return true;
    }

    function moveCaretAfterInsertedNode(range, insertedNode) {
        if (!insertedNode) {
            range.collapse(false);
            return;
        }

        if (insertedNode.nodeType === Node.TEXT_NODE) {
            range.setStart(insertedNode, insertedNode.nodeValue.length);
        } else {
            range.setStartAfter(insertedNode);
        }

        range.collapse(true);
    }

    function getContentEditableTextOffset(root, boundaryNode, boundaryOffset) {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                return node.nodeValue ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            },
        });
        const boundaryRange = document.createRange();
        boundaryRange.setStart(boundaryNode, boundaryOffset);
        boundaryRange.collapse(true);

        let traversed = 0;
        let currentNode = walker.nextNode();

        while (currentNode) {
            if (currentNode === boundaryNode) {
                return traversed + Math.min(boundaryOffset, currentNode.nodeValue.length);
            }

            const textEndRange = document.createRange();
            textEndRange.selectNodeContents(currentNode);
            textEndRange.collapse(false);

            if (textEndRange.compareBoundaryPoints(Range.END_TO_END, boundaryRange) <= 0) {
                traversed += currentNode.nodeValue.length;
                currentNode = walker.nextNode();
                continue;
            }

            break;
        }

        return traversed;
    }

    function locateTextPosition(root, offset, affinity) {
        if (offset < 0) {
            return null;
        }

        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                return node.nodeValue ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            },
        });

        let traversed = 0;
        let currentNode = walker.nextNode();
        let lastNode = null;

        while (currentNode) {
            const length = currentNode.nodeValue.length;

            if (offset < traversed + length) {
                return {
                    node: currentNode,
                    offset: offset - traversed,
                };
            }

            if (offset === traversed + length) {
                const nextNode = walker.nextNode();

                if (affinity === 'forward' && nextNode) {
                    return {
                        node: nextNode,
                        offset: 0,
                    };
                }

                return {
                    node: currentNode,
                    offset: length,
                };
            }

            traversed += length;
            lastNode = currentNode;
            currentNode = walker.nextNode();
        }

        if (lastNode) {
            return {
                node: lastNode,
                offset: lastNode.nodeValue.length,
            };
        }

        return {
            node: root,
            offset: root.childNodes.length,
        };
    }

    function getContentEditableCaretRect(range, root) {
        const collapsedRange = range.cloneRange();
        collapsedRange.collapse(true);
        let rect = collapsedRange.getBoundingClientRect();

        if (rect && (rect.width || rect.height)) {
            return rect;
        }

        const marker = document.createElement('span');
        marker.textContent = '\u200b';
        collapsedRange.insertNode(marker);
        rect = marker.getBoundingClientRect();
        marker.remove();

        const selection = global.getSelection();
        selection.removeAllRanges();
        selection.addRange(collapsedRange);

        return rect || root.getBoundingClientRect();
    }

    function getTextControlCaretRect(element, position) {
        const mirror = document.createElement('div');
        const computed = global.getComputedStyle(element);
        const properties = [
            'borderTopWidth',
            'borderRightWidth',
            'borderBottomWidth',
            'borderLeftWidth',
            'boxSizing',
            'fontFamily',
            'fontSize',
            'fontStretch',
            'fontStyle',
            'fontVariant',
            'fontWeight',
            'letterSpacing',
            'lineHeight',
            'paddingTop',
            'paddingRight',
            'paddingBottom',
            'paddingLeft',
            'textAlign',
            'textIndent',
            'textTransform',
            'whiteSpace',
            'wordSpacing',
            'wordWrap',
            'overflowWrap',
            'width',
        ];

        properties.forEach((property) => {
            mirror.style[property] = computed[property];
        });

        const rect = element.getBoundingClientRect();
        mirror.style.position = 'absolute';
        mirror.style.visibility = 'hidden';
        mirror.style.top = `${global.scrollY + rect.top}px`;
        mirror.style.left = `${global.scrollX + rect.left}px`;
        mirror.style.whiteSpace = element instanceof HTMLInputElement ? 'pre' : 'pre-wrap';
        mirror.style.overflow = 'hidden';
        mirror.style.width = `${element.clientWidth}px`;

        const before = element.value.slice(0, position);
        const after = element.value.slice(position) || '.';
        mirror.textContent = before;

        const marker = document.createElement('span');
        marker.textContent = after[0];
        mirror.appendChild(marker);
        document.body.appendChild(mirror);

        const markerRect = marker.getBoundingClientRect();
        const result = {
            left: markerRect.left,
            top: markerRect.top,
            right: markerRect.right,
            bottom: markerRect.bottom,
            width: markerRect.width,
            height: markerRect.height,
        };

        mirror.remove();
        return result;
    }

    function truncatePreview(value, length) {
        const normalized = String(value || '').replace(/\s+/g, ' ').trim();
        return normalized.length > length ? `${normalized.slice(0, length - 3)}...` : normalized;
    }

    function plainTextToHtml(value) {
        return escapeHtml(String(value || '')).replace(/\n/g, '<br>');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function escapeRegExp(value) {
        return String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function consumeDropdownKeyboardEvent(event) {
        event.preventDefault();
        event.stopPropagation();

        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
    }

    function isTextControl(element) {
        return Boolean(
            element &&
            (element instanceof HTMLTextAreaElement ||
                (element instanceof HTMLInputElement && typeof element.selectionStart === 'number')) &&
            !element.readOnly &&
            !element.disabled
        );
    }

    function ensureSharedStyles() {
        if (document.getElementById(STYLE_ID)) {
            return;
        }

        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .auto-suggest-dropdown {
                position: fixed;
                z-index: 2147483647;
                min-width: 280px;
                max-width: min(460px, calc(100vw - 24px));
                max-height: 320px;
                overflow: auto;
                border-radius: 16px;
                border: 1px solid rgba(81, 52, 25, 0.16);
                background: rgba(255, 252, 246, 0.98);
                box-shadow: 0 22px 44px rgba(43, 29, 14, 0.18);
                backdrop-filter: blur(10px);
                padding: 8px;
                font-family: "Avenir Next", "Segoe UI", sans-serif;
                color: #231a13;
            }

            .auto-suggest-item {
                width: 100%;
                border: 0;
                border-radius: 12px;
                background: transparent;
                color: inherit;
                cursor: pointer;
                text-align: left;
                padding: 12px 14px;
                display: grid;
                gap: 4px;
            }

            .auto-suggest-item:hover,
            .auto-suggest-item.is-active {
                background: rgba(154, 81, 36, 0.12);
            }

            .auto-suggest-title {
                font-size: 0.95rem;
                font-weight: 700;
            }

            .auto-suggest-preview {
                color: #6c5a4a;
                font-size: 0.86rem;
                line-height: 1.4;
            }
        `;

        document.documentElement.appendChild(style);
    }

    const defaultInstance = create();

    return {
        create,
        getDefault() {
            return defaultInstance;
        },
        configure(options) {
            defaultInstance.configure(options || {});
            return this;
        },
        setItems(items) {
            defaultInstance.setItems(items);
            return this;
        },
        setDataSource(dataSource) {
            defaultInstance.setDataSource(dataSource);
            return this;
        },
        setArrayDataSource(items) {
            defaultInstance.setArrayDataSource(items);
            return this;
        },
        setTrigger(trigger) {
            defaultInstance.setTrigger(trigger);
            return this;
        },
        setSelector(selectorOrElements) {
            defaultInstance.setSelector(selectorOrElements);
            return this;
        },
        bind(selectorOrElements) {
            defaultInstance.bind(selectorOrElements);
            return defaultInstance;
        },
        mount(selectorOrElements) {
            defaultInstance.mount(selectorOrElements);
            return defaultInstance;
        },
        refresh() {
            defaultInstance.refresh();
            return defaultInstance;
        },
        destroy() {
            defaultInstance.destroy();
            return this;
        },
    };
});
