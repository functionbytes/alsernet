/**
 * MediaPicker - Componente reutilizable de galería de medios.
 *
 * Uso:
 *
 *   window.MediaPicker.open({
 *       urls: {
 *           list:    '/panel/media/list',
 *           upload:  '/panel/media/files/upload',
 *           urlUpload: '/panel/media/files/upload-url',
 *           setDisk: '/panel/media/set-disk',
 *           folders: '/panel/media/folders/create',
 *           base:    '/media',
 *       },
 *       filter: 'image',                // 'image' | 'all' (default: 'all')
 *       title:  'Galería de medios',    // opcional
 *       onSelect: function (url, file) { ... }
 *   });
 */
window.MediaPicker = (function ($) {

    var _modal       = null;
    var _cfg         = {};
    var _breadcrumbs = [];
    var _selected    = null;
    var _searchTimer = null;
    var _currentTab  = 'all_media';
    var _dragCounter = 0;

    /* ── Helpers ────────────────────────────────────────────── */
    function _ext(name) {
        return (name || '').split('.').pop().toUpperCase().substring(0, 4);
    }

    function _iconClass(type) {
        var map = {
            image:    'fa-image text-primary',
            video:    'fa-video text-danger',
            document: 'fa-file-alt text-info',
            archive:  'fa-file-archive text-warning'
        };
        return 'fas ' + (map[type] || 'fa-file text-muted');
    }

    function _csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    /* ── Modal init (HTML ya está en el DOM via Blade partial) ── */
    function _buildModal() {}

    /* ── Breadcrumbs ────────────────────────────────────────── */
    function _renderBreadcrumbs() {
        if (_currentTab !== 'all_media') {
            $('#mp-breadcrumb ol').html('');
            return;
        }
        var $ol = $('#mp-breadcrumb ol').empty();
        $.each(_breadcrumbs, function (i, crumb) {
            if (i === _breadcrumbs.length - 1) {
                $ol.append('<li class="breadcrumb-item active">' + crumb.name + '</li>');
            } else {
                $ol.append('<li class="breadcrumb-item"><a href="#" class="mp-crumb text-primary text-decoration-none" data-index="' + i + '">' + crumb.name + '</a></li>');
            }
        });
    }

    function _updateUploadContext() {
        var last = _breadcrumbs[_breadcrumbs.length - 1];
        var label = (!last || !last.id) ? 'Raíz' : last.name;
        $('#mp-upload-context').text(label);
    }

    function _currentFolderId() {
        if (_currentTab !== 'all_media') return null;
        return _breadcrumbs.length > 0 ? _breadcrumbs[_breadcrumbs.length - 1].id : null;
    }

    /* ── Card rendering ─────────────────────────────────────── */
    function _renderFileCard(file) {
        var fullUrl = file.public_url || (_cfg.urls.base + '/' + file.url.replace(/^media\//, ''));
        var isImage = file.type === 'image';
        var thumb   = isImage
            ? '<img src="' + fullUrl + '" alt="' + file.name + '" class="card-img-top" style="height:140px; object-fit:cover;" loading="lazy">'
            : '<div class="d-flex align-items-center justify-content-center bg-light" style="height:140px;"><i class="' + _iconClass(file.type) + ' fa-2x"></i></div>';

        var ext      = _ext(file.name);
        var sizeText = file.human_size || file.size_human || '';
        var safeName = $('<div>').text(file.name).html();
        var safeUrl  = $('<div>').text(fullUrl).html();

        return '<div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-6">' +
            '<div class="card mp-file rounded-2 position-relative border" style="cursor:pointer;"' +
            ' data-full-url="' + safeUrl + '"' +
            ' data-url="' + $('<div>').text(file.url).html() + '"' +
            ' data-name="' + safeName + '"' +
            ' data-type="' + (file.type || '') + '">' +
            '<div class="position-relative overflow-hidden rounded-top">' + thumb + '</div>' +
            '<div class="card-body p-2 px-3">' +
            '<p class="fw-semibold mb-1 text-truncate small" title="' + safeName + '">' + safeName + '</p>' +
            '<div class="d-flex align-items-center justify-content-between">' +
            '<span class="text-muted" style="font-size:11px;">' + sizeText + '</span>' +
            '<span class="badge bg-primary-subtle text-primary" style="font-size:10px;">' + ext + '</span>' +
            '</div></div></div></div>';
    }

    function _renderFolderCard(folder) {
        var safeName = $('<div>').text(folder.name).html();
        return '<div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-6">' +
            '<div class="card mp-folder rounded-2 border" style="cursor:pointer;"' +
            ' data-id="' + folder.id + '" data-name="' + safeName + '">' +
            '<div class="d-flex align-items-center justify-content-center bg-warning-subtle" style="height:100px;">' +
            '<i class="fas fa-folder fa-3x text-warning"></i></div>' +
            '<div class="card-body p-2 px-3">' +
            '<p class="fw-semibold mb-0 text-truncate small" title="' + safeName + '">' + safeName + '</p>' +
            '</div></div></div>';
    }

    /* ── Load items ─────────────────────────────────────────── */
    function _loadItems(folderId, search, page) {
        _selected = null;
        _hideDetail();
        _updateUploadContext();
        $('#mp-insert-btn').prop('disabled', true);
        $('#mp-selected-name').text('Ningún archivo seleccionado');
        $('#mp-grid').html(
            '<div class="col-12 text-center py-5">' +
            '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>' +
            '</div>'
        );

        var params = { page: page || 1, per_page: 24, view: _currentTab };
        if (search)   params.search    = search;
        if (folderId) params.folder_id = folderId;

        $.get(_cfg.urls.list, params)
            .done(function (data) {
                var html    = '';
                var folders = (_currentTab === 'all_media') ? (data.folders || []) : [];
                $.each(folders, function (_, f) { html += _renderFolderCard(f); });

                var files = data.files || [];
                if (_cfg.filter === 'image') {
                    files = files.filter(function (f) { return f.type === 'image'; });
                }
                $.each(files, function (_, f) { html += _renderFileCard(f); });

                if (!folders.length && !files.length) {
                    html = '<div class="col-12 text-center py-5 text-muted">' +
                        '<i class="fas fa-folder-open fa-2x mb-3 d-block"></i>' +
                        '<p class="mb-0">No hay archivos en esta ubicación</p></div>';
                }

                $('#mp-grid').html(html);

                /* Pagination */
                var p = data.pagination || {};
                $('#mp-body .mp-pagination').remove();
                if (p.last_page > 1) {
                    var fArg = folderId ? ',' + folderId : ',null';
                    var sArg = ",'" + (search || '') + "'";
                    var pg   = '<div class="d-flex justify-content-center align-items-center gap-2 mt-3 mp-pagination">';
                    if (page > 1)           pg += '<button class="btn btn-sm btn-outline-secondary" onclick="MediaPicker._nav(' + (page - 1) + sArg + fArg + ')"><i class="fas fa-chevron-left"></i></button>';
                    pg += '<span class="text-muted small">' + page + ' / ' + p.last_page + '</span>';
                    if (page < p.last_page) pg += '<button class="btn btn-sm btn-outline-secondary" onclick="MediaPicker._nav(' + (page + 1) + sArg + fArg + ')"><i class="fas fa-chevron-right"></i></button>';
                    pg += '</div>';
                    $('#mp-grid').after(pg);
                }

                _renderBreadcrumbs();
            })
            .fail(function () {
                $('#mp-grid').html(
                    '<div class="col-12 text-center py-5 text-danger">' +
                    '<i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>Error al cargar archivos</div>'
                );
            });
    }

    /* ── Detail panel ───────────────────────────────────────── */
    function _showDetail(file) {
        var isImage = file.type === 'image';
        var thumb   = isImage
            ? '<img src="' + file.fullUrl + '" class="img-fluid" style="max-height:130px; object-fit:contain;">'
            : '<i class="' + _iconClass(file.type) + ' fa-3x"></i>';
        $('#mp-detail-preview').html(thumb);

        var ext = _ext(file.name);
        $('#mp-detail-meta').html(
            '<p class="fw-semibold mb-1 small text-truncate" title="' + $('<div>').text(file.name).html() + '">' +
            $('<div>').text(file.name).html() + '</p>' +
            '<span class="badge bg-primary-subtle text-primary">' + ext + '</span>'
        );

        $('#mp-detail-url').val(file.fullUrl);

        var code = isImage
            ? '<img src="' + file.fullUrl + '" alt="' + file.name.replace(/"/g, '') + '" style="max-width:100%">'
            : '<a href="' + file.fullUrl + '" target="_blank">' + file.name + '</a>';
        $('#mp-detail-code').val(code);

        $('#mp-detail').addClass('d-flex').removeClass('d-none');
    }

    function _hideDetail() {
        $('#mp-detail').addClass('d-none').removeClass('d-flex');
        $('#mp-detail-preview, #mp-detail-meta').html('');
        $('#mp-detail-url, #mp-detail-code').val('');
    }

    /* ── Upload file ─────────────────────────────────────────── */
    function _uploadFile(file) {
        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', _csrf());
        if (_currentFolderId()) fd.append('folder_id', _currentFolderId());

        $('#mp-upload-progress').removeClass('d-none');
        $('#mp-progress-bar').css('width', '0%');
        $('#mp-upload-status').text('Subiendo...');

        $.ajax({
            url:         _cfg.urls.upload,
            type:        'POST',
            data:        fd,
            processData: false,
            contentType: false,
            xhr: function () {
                var x = $.ajaxSettings.xhr();
                x.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        $('#mp-progress-bar').css('width', Math.round(e.loaded / e.total * 100) + '%');
                    }
                });
                return x;
            },
            success: function (res) {
                if (res.success) {
                    toastr.success('Archivo subido correctamente');
                    $('#mp-upload-status').text('Completado');
                    _switchTab('all_media');
                } else {
                    toastr.error(res.message || 'Error al subir el archivo');
                    $('#mp-upload-status').text('Error');
                }
            },
            error: function () {
                toastr.error('Error al subir el archivo');
                $('#mp-upload-status').text('Error');
            },
            complete: function () {
                setTimeout(function () { $('#mp-upload-progress').addClass('d-none'); }, 2500);
                $('#mp-file-input').val('');
            }
        });
    }

    /* ── Import from URL ─────────────────────────────────────── */
    function _importFromUrl(url) {
        if (!url) return;
        $('#mp-upload-progress').removeClass('d-none');
        $('#mp-progress-bar').css('width', '60%');
        $('#mp-upload-status').text('Importando...');

        $.ajax({
            url:  _cfg.urls.urlUpload,
            type: 'POST',
            data: { _token: _csrf(), url: url, folder_id: _currentFolderId() || '' },
            success: function (res) {
                if (res.success) {
                    toastr.success('Archivo importado correctamente');
                    $('#mp-import-url-input').val('');
                    $('#mp-import-url-form').addClass('d-none');
                    $('#mp-upload-status').text('Completado');
                    _switchTab('all_media');
                } else {
                    toastr.error(res.message || 'Error al importar');
                    $('#mp-upload-status').text('Error');
                }
            },
            error: function () {
                toastr.error('Error al importar desde URL');
                $('#mp-upload-status').text('Error');
            },
            complete: function () {
                setTimeout(function () { $('#mp-upload-progress').addClass('d-none'); }, 2500);
            }
        });
    }

    /* ── Change disk ─────────────────────────────────────────── */
    function _changeDisk(disk) {
        $.post(_cfg.urls.setDisk, { _token: _csrf(), disk: disk })
            .done(function () {
                _switchTab('all_media');
            })
            .fail(function () {
                toastr.error('Error al cambiar el disco');
            });
    }

    /* ── Create folder ───────────────────────────────────────── */
    function _createFolder() {
        $('#mp-folder-name').val('');
        $('#mp-folder-error').addClass('d-none').text('');
        var folderModal = new bootstrap.Modal(document.getElementById('mp-folder-modal'));
        folderModal.show();
        setTimeout(function () { $('#mp-folder-name').focus(); }, 300);
    }

    function _submitCreateFolder() {
        var name = $('#mp-folder-name').val().trim();
        if (!name) {
            $('#mp-folder-error').text('El nombre es obligatorio.').removeClass('d-none');
            return;
        }
        var $btn = $('#mp-folder-save').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Creando...');
        $.post(_cfg.urls.folders, {
            _token:    _csrf(),
            name:      name,
            parent_id: _currentFolderId() || ''
        })
        .done(function (res) {
            if (res.success || res.id) {
                toastr.success('Carpeta creada');
                bootstrap.Modal.getInstance(document.getElementById('mp-folder-modal')).hide();
                _loadItems(_currentFolderId(), '', 1);
            } else {
                $('#mp-folder-error').text(res.message || 'Error al crear la carpeta').removeClass('d-none');
            }
        })
        .fail(function () {
            $('#mp-folder-error').text('Error al crear la carpeta').removeClass('d-none');
        })
        .always(function () {
            $('#mp-folder-save').prop('disabled', false).html('<i class="fas fa-folder-plus me-1"></i>Crear carpeta');
        });
    }

    /* ── Tab switching ──────────────────────────────────────── */
    function _switchTab(view) {
        _currentTab  = view;
        _breadcrumbs = [{ id: null, name: 'Todos los archivos' }];
        _selected    = null;
        _hideDetail();
        $('#mp-insert-btn').prop('disabled', true);
        $('#mp-selected-name').text('Ningún archivo seleccionado');
        $('#mp-search').val('');

        $('#mp-sidebar-tabs .mp-tab-btn').each(function () {
            var active = $(this).data('view') === view;
            $(this).toggleClass('active bg-primary text-white', active)
                   .toggleClass('text-dark', !active);
        });
        $('#mp-breadcrumb').toggle(view === 'all_media');
        _loadItems(null, '', 1);
    }

    /* ── Confirm selection ───────────────────────────────────── */
    function _confirm() {
        if (!_selected || !_cfg.onSelect) return;
        _cfg.onSelect(_selected.fullUrl, _selected);
        _modal.hide();
    }

    /* ── Copy to clipboard ───────────────────────────────────── */
    function _copyToClipboard(text, $btn) {
        if (!navigator.clipboard) return;
        navigator.clipboard.writeText(text).then(function () {
            var orig = $btn.html();
            $btn.html('<i class="fas fa-check me-1"></i>Copiado')
                .addClass('btn-success').removeClass('btn-outline-secondary');
            setTimeout(function () {
                $btn.html(orig).removeClass('btn-success').addClass('btn-outline-secondary');
            }, 1500);
        });
    }

    /* ── Bind events ─────────────────────────────────────────── */
    function _bindEvents() {
        /* Tabs */
        $(document).on('click', '.mp-tab-btn', function () {
            _switchTab($(this).data('view'));
        });

        /* Disk change */
        $(document).on('change', '#mp-disk-select', function () {
            _changeDisk($(this).val());
        });

        /* New folder - open modal */
        $(document).on('click', '#mp-new-folder-btn', function () {
            _createFolder();
        });

        /* New folder - save (button + Enter key) */
        $(document).on('click', '#mp-folder-save', function () {
            _submitCreateFolder();
        });
        $(document).on('keydown', '#mp-folder-name', function (e) {
            if (e.key === 'Enter') _submitCreateFolder();
        });

        /* Upload toggle */
        $(document).on('click', '#mp-upload-btn', function () {
            $('#mp-dropzone').toggleClass('d-none');
            if (!$('#mp-dropzone').hasClass('d-none')) {
                $('#mp-import-url-form').addClass('d-none');
            }
        });

        /* File input */
        $(document).on('change', '#mp-file-input', function () {
            var files = this.files;
            for (var i = 0; i < files.length; i++) { _uploadFile(files[i]); }
        });

        /* Import from URL toggle */
        $(document).on('click', '#mp-import-url-btn', function () {
            $('#mp-import-url-form').toggleClass('d-none');
            if (!$('#mp-import-url-form').hasClass('d-none')) {
                $('#mp-import-url-input').focus();
            }
        });
        $(document).on('click', '#mp-import-url-cancel', function () {
            $('#mp-import-url-form').addClass('d-none');
            $('#mp-import-url-input').val('');
        });
        $(document).on('click', '#mp-import-url-submit', function () {
            _importFromUrl($('#mp-import-url-input').val().trim());
        });
        $(document).on('keydown', '#mp-import-url-input', function (e) {
            if (e.key === 'Enter') _importFromUrl($(this).val().trim());
        });

        /* Drag & drop */
        $(document).on('dragenter', '#mp-dropzone', function (e) {
            e.preventDefault(); _dragCounter++;
            $(this).find('.upload-zone-modern').css({ 'border-color': '#90bb13', 'background': '#e8f5d0' });
        });
        $(document).on('dragleave', '#mp-dropzone', function (e) {
            e.preventDefault(); _dragCounter--;
            if (_dragCounter <= 0) {
                _dragCounter = 0;
                $(this).find('.upload-zone-modern').css({ 'border-color': '', 'background': '' });
            }
        });
        $(document).on('dragover', '#mp-dropzone', function (e) { e.preventDefault(); });
        $(document).on('drop', '#mp-dropzone', function (e) {
            e.preventDefault(); _dragCounter = 0;
            $(this).find('.upload-zone-modern').css({ 'border-color': '', 'background': '' });
            var files = e.originalEvent.dataTransfer.files;
            for (var i = 0; i < files.length; i++) { _uploadFile(files[i]); }
        });

        /* Search */
        $(document).on('input', '#mp-search', function () {
            var q = $(this).val();
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(function () { _loadItems(_currentFolderId(), q, 1); }, 400);
        });

        /* Folder navigation */
        $(document).on('click', '.mp-folder', function () {
            _breadcrumbs.push({ id: $(this).data('id'), name: $(this).data('name') });
            _loadItems($(this).data('id'), '', 1);
        });

        /* Breadcrumb navigation */
        $(document).on('click', '.mp-crumb', function (e) {
            e.preventDefault();
            var idx = parseInt($(this).data('index'));
            _breadcrumbs = _breadcrumbs.slice(0, idx + 1);
            _loadItems(_currentFolderId(), '', 1);
        });

        /* File selection */
        $(document).on('click', '.mp-file', function () {
            $('.mp-file').removeClass('border-primary shadow');
            $(this).addClass('border-primary shadow');
            _selected = {
                url:     $(this).data('url'),
                fullUrl: $(this).data('full-url'),
                name:    $(this).data('name'),
                type:    $(this).data('type')
            };
            $('#mp-insert-btn').prop('disabled', false);
            $('#mp-selected-name').text(_selected.name);
            _showDetail(_selected);
        });

        /* Double-click to insert immediately */
        $(document).on('dblclick', '.mp-file', function () {
            _selected = {
                url:     $(this).data('url'),
                fullUrl: $(this).data('full-url'),
                name:    $(this).data('name'),
                type:    $(this).data('type')
            };
            _confirm();
        });

        /* Insert */
        $(document).on('click', '#mp-insert-btn', _confirm);

        /* Copy URL */
        $(document).on('click', '#mp-copy-url', function () {
            _copyToClipboard($('#mp-detail-url').val(), $(this));
        });

        /* Copy HTML code */
        $(document).on('click', '#mp-copy-code', function () {
            _copyToClipboard($('#mp-detail-code').val(), $(this));
        });

        /* Reset on close */
        $(document).on('hidden.bs.modal', '#mp-modal', function () {
            _selected = null;
            _cfg      = {};
            _hideDetail();
            $('#mp-dropzone').addClass('d-none');
            $('#mp-import-url-form').addClass('d-none');
        });
    }

    /* ── Public API ──────────────────────────────────────────── */
    function open(options) {
        _cfg = $.extend({
            filter: 'all',
            title:  'Gestor de medios',
            urls:   {}
        }, options);

        /* Defaults para URLs */
        _cfg.urls = $.extend({
            list:      '/panel/media/list',
            upload:    '/panel/media/files/upload',
            urlUpload: '/panel/media/files/upload-url',
            setDisk:   '/panel/media/set-disk',
            folders:   '/panel/media/folders/create',
            base:      '/media'
        }, _cfg.urls);

        if (!_modal) {
            _buildModal();
            _bindEvents();
            _modal = new bootstrap.Modal(document.getElementById('mp-modal'));
        }

        $('#mp-title').html('<i class="fas fa-images me-2 text-primary"></i>' + _cfg.title);
        _breadcrumbs = [{ id: null, name: 'Todos los archivos' }];
        _selected    = null;
        _currentTab  = 'all_media';
        $('#mp-insert-btn').prop('disabled', true);
        $('#mp-selected-name').text('Ningún archivo seleccionado');
        $('#mp-dropzone').addClass('d-none');
        $('#mp-import-url-form').addClass('d-none');
        $('#mp-search').val('');
        $('#mp-breadcrumb').show();

        /* Reset tabs */
        $('#mp-sidebar-tabs .mp-tab-btn').each(function () {
            var active = $(this).data('view') === 'all_media';
            $(this).toggleClass('active bg-primary text-white', active)
                   .toggleClass('text-dark', !active);
        });

        _hideDetail();
        _loadItems(null, '', 1);
        _modal.show();
    }

    /* Pagination helper (llamado desde onclick inline) */
    function _nav(page, search, folderId) {
        _loadItems(folderId, search, page);
    }

    return { open: open, _nav: _nav };

})(jQuery);
