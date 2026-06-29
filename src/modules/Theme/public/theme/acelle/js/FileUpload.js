/**
 * McFileUpload — Drag-and-drop file upload component with preview.
 *
 * Usage (declarative):
 *   <div class="mc-file-upload"
 *        data-upload-url="/campaigns/{uid}/upload_attachment"
 *        data-remove-url="/campaigns/{uid}/remove_attachment"
 *        data-max-size="25600"
 *        data-accept="*">
 *       <!-- .mc-file-upload-zone is auto-created if missing -->
 *       <!-- .mc-file-upload-list is auto-created if missing -->
 *   </div>
 *
 * Or manual:
 *   new McFileUpload(element, { uploadUrl, removeUrl, maxSize, accept });
 *
 * Events dispatched on the container element:
 *   'fileupload:success' — after a file is uploaded (detail: { attachment })
 *   'fileupload:removed' — after a file is removed (detail: { uid })
 *   'fileupload:error'   — on upload error (detail: { message })
 */
class McFileUpload {
    constructor(el, options = {}) {
        this.el = typeof el === 'string' ? document.querySelector(el) : el;
        if (!this.el) return;

        this.uploadUrl = options.uploadUrl || this.el.dataset.uploadUrl || '';
        this.removeUrl = options.removeUrl || this.el.dataset.removeUrl || '';
        this.downloadUrl = options.downloadUrl || this.el.dataset.downloadUrl || '';
        this.maxSize = parseInt(options.maxSize || this.el.dataset.maxSize || 25600) * 1024; // KB to bytes
        this.accept = options.accept || this.el.dataset.accept || '*';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        this._buildUI();
        this._bindEvents();
    }

    // ----------------------------------------------------------------
    // Static init — auto-discover all [data-upload-url] containers
    // ----------------------------------------------------------------
    static init() {
        document.querySelectorAll('.mc-file-upload[data-upload-url]').forEach(function(el) {
            if (!el._mcFileUpload) {
                el._mcFileUpload = new McFileUpload(el);
            }
        });
    }

    // ----------------------------------------------------------------
    // Build DOM structure
    // ----------------------------------------------------------------
    _buildUI() {
        // Drop zone
        this.zone = this.el.querySelector('.mc-file-upload-zone');
        if (!this.zone) {
            this.zone = document.createElement('div');
            this.zone.className = 'mc-file-upload-zone';
            this.zone.innerHTML =
                '<span class="material-symbols-rounded mc-file-upload-zone-icon">cloud_upload</span>' +
                '<p class="mc-file-upload-zone-title">Drop files here or click to browse</p>' +
                '<p class="mc-file-upload-zone-hint">Max ' + Math.round(this.maxSize / 1024 / 1024) + 'MB per file</p>';
            this.el.appendChild(this.zone);
        }

        // Hidden file input
        this.input = document.createElement('input');
        this.input.type = 'file';
        this.input.multiple = true;
        this.input.style.display = 'none';
        if (this.accept !== '*') this.input.accept = this.accept;
        this.el.appendChild(this.input);

        // File list container
        this.list = this.el.querySelector('.mc-file-upload-list');
        if (!this.list) {
            this.list = document.createElement('div');
            this.list.className = 'mc-file-upload-list';
            this.el.insertBefore(this.list, this.zone);
        }
    }

    // ----------------------------------------------------------------
    // Events
    // ----------------------------------------------------------------
    _bindEvents() {
        var self = this;

        // Click zone → open file dialog
        this.zone.addEventListener('click', function() { self.input.click(); });

        // File selected
        this.input.addEventListener('change', function() {
            self._handleFiles(this.files);
            this.value = ''; // reset so same file can be re-selected
        });

        // Drag & drop
        this.zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            self.zone.classList.add('mc-file-upload-zone--dragover');
        });
        this.zone.addEventListener('dragleave', function() {
            self.zone.classList.remove('mc-file-upload-zone--dragover');
        });
        this.zone.addEventListener('drop', function(e) {
            e.preventDefault();
            self.zone.classList.remove('mc-file-upload-zone--dragover');
            if (e.dataTransfer.files.length) {
                self._handleFiles(e.dataTransfer.files);
            }
        });

        // Remove buttons (delegated, for both server-rendered and JS-added items)
        this.list.addEventListener('click', function(e) {
            var removeBtn = e.target.closest('[data-remove-uid]');
            if (removeBtn) {
                e.preventDefault();
                self._removeFile(removeBtn.dataset.removeUid, removeBtn.closest('.mc-file-item'));
            }
        });
    }

    // ----------------------------------------------------------------
    // Handle selected/dropped files
    // ----------------------------------------------------------------
    _handleFiles(files) {
        for (var i = 0; i < files.length; i++) {
            this._uploadFile(files[i]);
        }
    }

    // ----------------------------------------------------------------
    // Upload a single file
    // ----------------------------------------------------------------
    _uploadFile(file) {
        var self = this;

        // Client-side size check
        if (file.size > this.maxSize) {
            if (window.McNotify) McNotify.error(file.name + ' exceeds the maximum size of ' + Math.round(this.maxSize / 1024 / 1024) + 'MB');
            return;
        }

        // Create preview item immediately
        var item = this._createFileItem({
            name: file.name,
            size: file.size,
            mime: file.type,
            uploading: true,
            file: file,
        });
        this.list.appendChild(item);

        // Build FormData
        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', this.csrfToken);

        // Use XMLHttpRequest for progress tracking
        var xhr = new XMLHttpRequest();
        var progressBar = item.querySelector('.mc-file-item-progress-bar');

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable && progressBar) {
                var pct = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = pct + '%';
            }
        });

        xhr.addEventListener('load', function() {
            var progress = item.querySelector('.mc-file-item-progress');
            if (progress) progress.remove();

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.status === 'success') {
                        // Update item with server data
                        item.classList.remove('mc-file-item--uploading');
                        item.classList.add('mc-file-item--done');
                        if (data.attachment) {
                            var removeBtn = item.querySelector('[data-remove-uid]');
                            if (removeBtn) removeBtn.dataset.removeUid = data.attachment.uid;
                            // Show download button
                            var dlBtn = item.querySelector('.mc-file-item-download');
                            if (dlBtn && self.downloadUrl) {
                                dlBtn.href = self.downloadUrl + '?attachment_uid=' + data.attachment.uid;
                                dlBtn.style.display = '';
                            }
                        }
                        if (window.McNotify) McNotify.success(data.message || 'File uploaded');
                        self.el.dispatchEvent(new CustomEvent('fileupload:success', { detail: data }));
                    } else {
                        self._markError(item, data.message || 'Upload failed');
                    }
                } catch (e) {
                    self._markError(item, 'Invalid server response');
                }
            } else {
                var msg = 'Upload failed';
                try {
                    var errData = JSON.parse(xhr.responseText);
                    msg = errData.message || (errData.errors && errData.errors.file ? errData.errors.file[0] : msg);
                } catch (e) {}
                self._markError(item, msg);
            }
        });

        xhr.addEventListener('error', function() {
            self._markError(item, 'Network error');
        });

        xhr.open('POST', this.uploadUrl);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(fd);
    }

    // ----------------------------------------------------------------
    // Remove a file
    // ----------------------------------------------------------------
    _removeFile(uid, itemEl) {
        var self = this;

        // If no uid (failed upload), just remove DOM
        if (!uid || uid === 'pending') {
            if (itemEl) itemEl.remove();
            return;
        }

        // Confirm
        var doRemove = function() {
            itemEl.classList.add('mc-file-item--removing');

            fetch(self.removeUrl + '?attachment_uid=' + uid, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': self.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    itemEl.style.transition = 'opacity 0.2s, max-height 0.3s';
                    itemEl.style.opacity = '0';
                    itemEl.style.maxHeight = '0';
                    itemEl.style.overflow = 'hidden';
                    setTimeout(function() { itemEl.remove(); }, 300);
                    if (window.McNotify) McNotify.success(data.message || 'Removed');
                    self.el.dispatchEvent(new CustomEvent('fileupload:removed', { detail: { uid: uid } }));
                } else {
                    itemEl.classList.remove('mc-file-item--removing');
                    if (window.McNotify) McNotify.error(data.message || 'Remove failed');
                }
            })
            .catch(function() {
                itemEl.classList.remove('mc-file-item--removing');
                if (window.McNotify) McNotify.error('Network error');
            });
        };

        if (window.McDialog) {
            McDialog.confirm('Remove this file?', doRemove);
        } else {
            doRemove();
        }
    }

    // ----------------------------------------------------------------
    // Create a file item DOM element
    // ----------------------------------------------------------------
    _createFileItem(opts) {
        var item = document.createElement('div');
        item.className = 'mc-file-item' + (opts.uploading ? ' mc-file-item--uploading' : '');

        var ext = this._getExtension(opts.name);
        var category = this._getFileCategory(opts.mime, ext);
        var isImage = category === 'image';

        // Thumbnail or icon
        var thumbHtml = '';
        if (isImage && opts.file) {
            thumbHtml = '<div class="mc-file-item-thumb mc-file-item-thumb--image"><img alt=""></div>';
        } else {
            thumbHtml = '<div class="mc-file-item-thumb mc-file-item-thumb--' + category + '">' +
                '<span class="material-symbols-rounded">' + this._getFileIcon(category) + '</span>' +
                '</div>';
        }

        item.innerHTML =
            thumbHtml +
            '<div class="mc-file-item-info">' +
                '<div class="mc-file-item-name">' + this._escapeHtml(opts.name) + '</div>' +
                '<div class="mc-file-item-meta">' +
                    '<span class="mc-file-item-size">' + this._formatSize(opts.size) + '</span>' +
                    '<span class="mc-badge mc-badge-default mc-file-item-ext">' + ext.toUpperCase() + '</span>' +
                '</div>' +
            '</div>' +
            (opts.uploading
                ? '<div class="mc-file-item-progress"><div class="mc-file-item-progress-bar"></div></div>'
                : '') +
            '<div class="mc-file-item-actions">' +
                '<a href="#" class="mc-file-item-download" title="Download" style="' + (opts.uploading ? 'display:none' : '') + '">' +
                    '<span class="material-symbols-rounded">download</span>' +
                '</a>' +
                '<button type="button" class="mc-file-item-remove" data-remove-uid="' + (opts.uid || 'pending') + '" title="Remove">' +
                    '<span class="material-symbols-rounded">close</span>' +
                '</button>' +
            '</div>';

        // Generate image thumbnail preview
        if (isImage && opts.file) {
            var img = item.querySelector('.mc-file-item-thumb--image img');
            var reader = new FileReader();
            reader.onload = function(e) { img.src = e.target.result; };
            reader.readAsDataURL(opts.file);
        }

        return item;
    }

    // ----------------------------------------------------------------
    // Mark a file item as errored
    // ----------------------------------------------------------------
    _markError(item, message) {
        item.classList.remove('mc-file-item--uploading');
        item.classList.add('mc-file-item--error');
        var meta = item.querySelector('.mc-file-item-meta');
        if (meta) {
            meta.innerHTML = '<span class="mc-file-item-error-msg">' + this._escapeHtml(message) + '</span>';
        }
        if (window.McNotify) McNotify.error(message);
        this.el.dispatchEvent(new CustomEvent('fileupload:error', { detail: { message: message } }));
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------
    _getExtension(name) {
        var parts = name.split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '?';
    }

    _getFileCategory(mime, ext) {
        if (/^image\//.test(mime)) return 'image';
        if (/^video\//.test(mime)) return 'video';
        if (/^audio\//.test(mime)) return 'audio';
        if (/pdf/.test(mime) || ext === 'pdf') return 'pdf';
        if (/spreadsheet|excel|csv/.test(mime) || /^(xlsx?|csv|ods)$/.test(ext)) return 'sheet';
        if (/presentation|powerpoint/.test(mime) || /^(pptx?|odp)$/.test(ext)) return 'slide';
        if (/msword|document|rtf/.test(mime) || /^(docx?|odt|rtf|txt|md)$/.test(ext)) return 'doc';
        if (/zip|rar|tar|gz|7z/.test(mime) || /^(zip|rar|tar|gz|7z|bz2)$/.test(ext)) return 'archive';
        return 'file';
    }

    _getFileIcon(category) {
        var icons = {
            image: 'image',
            video: 'videocam',
            audio: 'audio_file',
            pdf: 'picture_as_pdf',
            sheet: 'table_chart',
            slide: 'slideshow',
            doc: 'description',
            archive: 'folder_zip',
            file: 'insert_drive_file',
        };
        return icons[category] || 'insert_drive_file';
    }

    _formatSize(bytes) {
        if (!bytes) return '0 B';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    _escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// Expose globally
window.McFileUpload = McFileUpload;
