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
    var _typeFilter  = 'all';
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
        var fullUrl  = file.public_url || (_cfg.urls.base + '/' + file.url.replace(/^media\//, ''));
        var isImage  = file.type === 'image';
        var safeName = $('<div>').text(file.name).html();
        var safeUrl  = $('<div>').text(fullUrl).html();
        var ext      = _ext(file.name);
        var sizeText = file.human_size || file.size_human || '';

        var thumbHtml = isImage
            ? '<img src="' + fullUrl + '" alt="' + safeName + '" class="mp-file-thumb" loading="lazy">'
            : '<div class="mp-file-icon-area d-flex align-items-center justify-content-center"><i class="' + _iconClass(file.type) + ' fa-2x"></i></div>';

        var metaLine = sizeText + (ext ? ' &bull; ' + ext : '');

        return '<div class="col-xl-3 col-lg-4 col-md-4 col-6">' +
            '<div class="mp-file position-relative"' +
            ' data-id="' + (file.id || '') + '"' +
            ' data-full-url="' + safeUrl + '"' +
            ' data-url="' + $('<div>').text(file.url).html() + '"' +
            ' data-name="' + safeName + '"' +
            ' data-type="' + (file.type || '') + '"' +
            ' data-size="' + sizeText + '"' +
            ' data-dims="' + (file.dimensions || '') + '">' +
            '<div class="mp-check"><i class="fas fa-check"></i></div>' +
            '<div class="mp-select-circle"></div>' +
            '<div class="position-relative overflow-hidden">' + thumbHtml + '</div>' +
            '<div class="mp-file-info">' +
            '<p class="mp-file-name text-truncate mb-0" title="' + safeName + '">' + safeName + '</p>' +
            '<p class="mp-file-size">' + metaLine + '</p>' +
            '</div>' +
            '</div></div>';
    }

    function _renderFolderCard(folder) {
        var safeName = $('<div>').text(folder.name).html();
        var count    = folder.files_count || '';
        return '<div class="col-xl-3 col-lg-4 col-md-4 col-6">' +
            '<div class="mp-folder" data-id="' + folder.id + '" data-name="' + safeName + '">' +
            '<div class="mp-folder-icon-area d-flex align-items-center justify-content-center">' +
            '<i class="fas fa-folder mp-folder-icon"></i></div>' +
            '<div class="mp-file-info">' +
            '<p class="mp-file-name text-truncate mb-0" title="' + safeName + '">' + safeName + '</p>' +
            (count ? '<p class="mp-file-size">' + count + ' archivos</p>' : '<p class="mp-file-size">&nbsp;</p>') +
            '</div></div></div>';
    }

    /* ── Load items ─────────────────────────────────────────── */
    function _loadItems(folderId, search, page) {
        _selected = null;
        _hideDetail();
        _updateUploadContext();
        $('#mp-insert-btn').prop('disabled', true);
        $('#mp-selected-name').html('<i class="fas fa-image me-1 opacity-50"></i>Ningún archivo seleccionado');
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
                } else if (_typeFilter !== 'all') {
                    files = files.filter(function (f) { return f.type === _typeFilter; });
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
        var isImage  = file.type === 'image';
        var safeName = $('<div>').text(file.name).html();
        var ext      = _ext(file.name);
        var sizeText = file.human_size || file.size_human || '';
        var dims     = file.dimensions || '';

        var thumbHtml = isImage
            ? '<img src="' + file.fullUrl + '" class="mp-detail-img">'
            : '<i class="' + _iconClass(file.type) + ' fa-3x p-4"></i>';
        $('#mp-detail-preview').html(thumbHtml);

        var metaHtml = '';
        metaHtml += '<div class="d-flex justify-content-between mb-2"><dt>Nombre</dt><dd class="mb-0 text-truncate ms-2" title="' + safeName + '">' + safeName + '</dd></div>';
        if (dims)     metaHtml += '<div class="d-flex justify-content-between mb-2"><dt>Dimensiones</dt><dd class="mb-0">' + dims + '</dd></div>';
        if (sizeText) metaHtml += '<div class="d-flex justify-content-between mb-2"><dt>Tamaño</dt><dd class="mb-0">' + sizeText + '</dd></div>';
        if (ext)      metaHtml += '<div class="d-flex justify-content-between mb-2"><dt>Tipo</dt><dd class="mb-0">' + ext + '</dd></div>';
        $('#mp-detail-meta-dl').html(metaHtml);
        $('#mp-detail-meta').html('');

        $('#mp-detail-url').val(file.fullUrl);

        var code = isImage
            ? '<img src="' + file.fullUrl + '" alt="' + file.name.replace(/"/g, '') + '" style="max-width:100%">'
            : '<a href="' + file.fullUrl + '" target="_blank">' + file.name + '</a>';
        $('#mp-detail-code').val(code);

        $('#mp-detail').addClass('d-flex').removeClass('d-none');
    }

    function _hideDetail() {
        $('#mp-detail').addClass('d-none').removeClass('d-flex');
        $('#mp-detail-preview, #mp-detail-meta, #mp-detail-meta-dl').html('');
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
        $('#mp-selected-name').html('<i class="fas fa-image me-1 opacity-50"></i>Ningún archivo seleccionado');
        $('#mp-search').val('');
        $('#mp-search-clear').hide();

        $('#mp-sidebar-tabs .mp-tab-btn').each(function () {
            $(this).toggleClass('active', $(this).data('view') === view);
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
            $(this).find('.mp-drop-area').addClass('drag-over');
        });
        $(document).on('dragleave', '#mp-dropzone', function (e) {
            e.preventDefault(); _dragCounter--;
            if (_dragCounter <= 0) {
                _dragCounter = 0;
                $(this).find('.mp-drop-area').removeClass('drag-over');
            }
        });
        $(document).on('dragover', '#mp-dropzone', function (e) { e.preventDefault(); });
        $(document).on('drop', '#mp-dropzone', function (e) {
            e.preventDefault(); _dragCounter = 0;
            $(this).find('.mp-drop-area').removeClass('drag-over');
            var files = e.originalEvent.dataTransfer.files;
            for (var i = 0; i < files.length; i++) { _uploadFile(files[i]); }
        });

        /* Search */
        $(document).on('input', '#mp-search', function () {
            var q = $(this).val();
            $('#mp-search-clear').toggle(q.length > 0);
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(function () { _loadItems(_currentFolderId(), q, 1); }, 400);
        });

        /* Search clear */
        $(document).on('click', '#mp-search-clear', function () {
            $('#mp-search').val('').trigger('input').focus();
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
            $('.mp-file').removeClass('selected');
            $(this).addClass('selected');
            _selected = {
                id:         $(this).data('id'),
                url:        $(this).data('url'),
                fullUrl:    $(this).data('full-url'),
                name:       $(this).data('name'),
                type:       $(this).data('type'),
                human_size: $(this).data('size'),
                dimensions: $(this).data('dims')
            };
            $('#mp-insert-btn').prop('disabled', false);
            $('#mp-selected-name').html('<i class="fas fa-check-circle me-1 text-success"></i>' + $('<div>').text(_selected.name).html());
            _showDetail(_selected);
        });

        /* Double-click to insert immediately */
        $(document).on('dblclick', '.mp-file', function () {
            _selected = {
                id:         $(this).data('id'),
                url:        $(this).data('url'),
                fullUrl:    $(this).data('full-url'),
                name:       $(this).data('name'),
                type:       $(this).data('type'),
                human_size: $(this).data('size'),
                dimensions: $(this).data('dims')
            };
            _confirm();
        });

        /* Insert (footer + detail panel) */
        $(document).on('click', '#mp-insert-btn, #mp-detail-insert-btn', _confirm);

        /* Copy URL */
        $(document).on('click', '#mp-copy-url', function () {
            _copyToClipboard($('#mp-detail-url').val(), $(this));
        });

        /* Copy HTML code */
        $(document).on('click', '#mp-copy-code', function () {
            _copyToClipboard($('#mp-detail-code').val(), $(this));
        });

        /* Type filter tabs */
        $(document).on('click', '.mp-type-tab', function () {
            _typeFilter = $(this).data('type');
            $('.mp-type-tab').removeClass('active');
            $(this).addClass('active');
            _loadItems(_currentFolderId(), $('#mp-search').val(), 1);
        });

        /* Detail panel close */
        $(document).on('click', '#mp-detail-close', function () {
            _hideDetail();
        });

        /* Copy URL action (detail footer) */
        $(document).on('click', '#mp-copy-url-action', function () {
            if (_selected) {
                _copyToClipboard(_selected.fullUrl, $(this));
            }
        });

        /* Download action */
        $(document).on('click', '#mp-download-action', function () {
            if (_selected) {
                var a = document.createElement('a');
                a.href = _selected.fullUrl;
                a.download = _selected.name || '';
                a.target = '_blank';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        });

        /* Delete action */
        $(document).on('click', '#mp-delete-action', function () {
            if (!_selected || !_selected.id) return;
            if (!confirm('¿Eliminar este archivo?')) return;
            $.ajax({
                url: _cfg.urls.base.replace(/\/media$/, '') + '/panel/media/files/' + _selected.id,
                type: 'DELETE',
                data: { _token: _csrf() },
                success: function (res) {
                    if (res.success !== false) {
                        toastr.success('Archivo eliminado');
                        _hideDetail();
                        _selected = null;
                        $('#mp-insert-btn').prop('disabled', true);
                        $('#mp-selected-name').html('<i class="fas fa-image me-1 opacity-50"></i>Ningún archivo seleccionado');
                        _loadItems(_currentFolderId(), $('#mp-search').val(), 1);
                    } else {
                        toastr.error(res.message || 'Error al eliminar');
                    }
                },
                error: function () { toastr.error('Error al eliminar el archivo'); }
            });
        });

        /* Edit media action (opens in new tab) */
        $(document).on('click', '#mp-detail-edit-btn', function () {
            if (_selected && _selected.id) {
                window.open('/panel/media?edit=' + _selected.id, '_blank');
            }
        });

        /* Search clear */
        $(document).on('click', '#mp-search-clear', function () {
            $('#mp-search').val('').trigger('input');
            $(this).hide();
        });
        $(document).on('input', '#mp-search', function () {
            $('#mp-search-clear').toggle($(this).val().length > 0);
        });

        /* Reset on close */
        $(document).on('hidden.bs.modal', '#mp-modal', function () {
            _selected = null;
            _cfg      = {};
            _typeFilter = 'all';
            _hideDetail();
            $('#mp-dropzone').addClass('d-none');
            $('#mp-import-url-form').addClass('d-none');
            $('#mp-search-clear').hide();
            $('.mp-type-tab').removeClass('active').first().addClass('active');
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

        $('#mp-title').html('<i class="fas fa-images me-2"></i>' + _cfg.title);
        _breadcrumbs = [{ id: null, name: 'Todos los archivos' }];
        _selected    = null;
        _currentTab  = 'all_media';
        $('#mp-insert-btn').prop('disabled', true);
        $('#mp-selected-name').html('<i class="fas fa-image me-1 opacity-50"></i>Ningún archivo seleccionado');
        $('#mp-dropzone').addClass('d-none');
        $('#mp-import-url-form').addClass('d-none');
        $('#mp-search').val('');
        $('#mp-breadcrumb').show();

        /* Reset tabs */
        $('#mp-sidebar-tabs .mp-tab-btn').each(function () {
            $(this).toggleClass('active', $(this).data('view') === 'all_media');
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
