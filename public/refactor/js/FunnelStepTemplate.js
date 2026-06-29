/**
 * McFunnelStepTemplate — Funnel step template picker.
 * Handles gallery loading, template selection, tab switching, change-template toggle.
 */
class McFunnelStepTemplate {
    constructor(opts) {
        this.galleryEl = document.getElementById(opts.galleryEl);
        this.galleryContent = document.getElementById(opts.galleryContent);
        this.galleryUrl = opts.galleryUrl;
        this.chooseUrl = opts.chooseUrl;
        this.currentFrom = opts.initialFrom || '';
        this.selectingText = opts.selectingText || 'Selecting...';
        this.loadingText = opts.loadingText || 'Loading templates...';

        // Change template button
        var changeBtn = document.getElementById(opts.changeBtnId);
        if (changeBtn) {
            changeBtn.addEventListener('click', function() {
                this.galleryEl.style.display = '';
                this.galleryEl.scrollIntoView({ behavior: 'smooth' });
                this.loadGallery();
            }.bind(this));
        }

        // Tab switching
        var galleryTabs = document.getElementById(opts.galleryTabsId);
        if (galleryTabs) {
            galleryTabs.addEventListener('tab:change', function(e) {
                this.currentFrom = e.detail.tab;
                this.loadGallery();
            }.bind(this));
        }

        // Auto-load gallery if needed
        if (opts.autoLoad) {
            this.loadGallery();
        }
    }

    loadGallery(page) {
        var self = this;
        var params = new URLSearchParams();
        if (this.currentFrom) params.set('from', this.currentFrom);
        if (page) params.set('page', page);

        this.galleryContent.innerHTML = '<div class="mc-wizard-loading">' + this.loadingText + '</div>';

        fetch(this.galleryUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            self.galleryContent.innerHTML = html;
            self.bindGalleryEvents();
        });
    }

    bindGalleryEvents() {
        var self = this;

        // Choose template buttons
        this.galleryContent.querySelectorAll('[data-choose-template]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var uid = this.dataset.chooseTemplate;
                btn.disabled = true;
                btn.textContent = self.selectingText;

                fetch(self.chooseUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ template_uid: uid })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else if (data.message && window.McNotify) {
                        McNotify.error(data.message);
                        btn.disabled = false;
                    }
                });
            });
        });

        // Pagination
        this.galleryContent.querySelectorAll('[data-page]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                self.loadGallery(this.dataset.page);
            });
        });
    }
}

window.McFunnelStepTemplate = McFunnelStepTemplate;
