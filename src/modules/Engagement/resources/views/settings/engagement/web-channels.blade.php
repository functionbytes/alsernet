@extends('layouts.theme')

@section('title', 'Canales web — Engagement')

@push('css')
<style>
    .lc-header {
        background: linear-gradient(135deg, #90bb13 0%, #7b0000 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    .lc-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .lc-header p  { opacity: .9; margin: 0; font-size: .9rem; }
</style>
@endpush

@section('content')

    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-globe me-2"></i>Canales web</h4>
                <p>Gestiona canales web del módulo Engagement</p>
            </div>
            <a href="{{ route('settings.engagement.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver a ajustes
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0">Inbox:</label>
            <select id="inboxSelector" class="form-select w-auto">
                @foreach($inboxes as $inbox)
                    <option value="{{ $inbox->id }}">{{ $inbox->name }}</option>
                @endforeach
            </select>
            @can('engagement.web_channels.create')
            <button class="btn btn-primary ms-auto" id="btnNewChannel">
                <i class="fas fa-plus me-1"></i>Nuevo canal
            </button>
            @endcan
            <button class="btn btn-outline-secondary" id="btnToggleTrashed">
                <i class="fas fa-trash me-1"></i><span id="trashLabel">Papelera</span>
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="channelsGrid"></div>
        </div>
    </div>


{{-- Modal --}}
<div class="modal fade" id="channelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="channelModalTitle">Nuevo canal web</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="channelId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre</label>
                        <input type="text" id="channelName" class="form-control" placeholder="Ej: Web principal">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Token <span class="text-danger">*</span></label>
                        <input type="text" id="channelToken" class="form-control" placeholder="SHA256 o UUID">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Dominio</label>
                        <input type="text" id="channelDomain" class="form-control" placeholder="https://ejemplo.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="channelActive" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-column gap-2">
                <button type="button" class="btn btn-primary w-100" id="btnSaveChannel">Guardar canal</button>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const routes = {
        index:     '{{ route('settings.engagement.web-channels.index') }}',
        store:     '{{ route('settings.engagement.web-channels.store') }}',
        update:    (id) => '{{ route('settings.engagement.web-channels.update', ['webChannel' => '__ID__']) }}'.replace('__ID__', id),
        destroy:   (id) => '{{ route('settings.engagement.web-channels.destroy', ['webChannel' => '__ID__']) }}'.replace('__ID__', id),
        trashed:   '{{ route('settings.engagement.web-channels.trashed') }}',
        restore:   (id) => '{{ route('settings.engagement.web-channels.restore', ['id' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let showTrashed = false;

    const grid = $('#channelsGrid').dxDataGrid({
        dataSource: {
            load: () => {
                const url = showTrashed ? routes.trashed : routes.index;
                return $.get(url, { inbox_id: getInboxId() }).then(r => r.data);
            },
        },
        columns: [
            { dataField: 'name', caption: 'Nombre' },
            { dataField: 'website_token', caption: 'Token', width: 240 },
            { dataField: 'domain', caption: 'Dominio', width: 200 },
            { dataField: 'is_active', caption: 'Estado', width: 100,
              cellTemplate: (el, info) => $(el).html(
                info.value
                  ? '<span class="badge bg-success">Activo</span>'
                  : '<span class="badge bg-secondary">Inactivo</span>'
              ) },
            { caption: 'Acciones', width: 120, alignment: 'center',
              cellTemplate: (el, info) => {
                if (showTrashed) {
                    $(el).html(`<button class="btn btn-sm btn-outline-success" data-action="restore" data-id="${info.data.id}"><i class="fas fa-undo"></i> Restaurar</button>`);
                    return;
                }
                const wrap = $('<div class="dropdown">');
                wrap.html(`
                  <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-action="edit" data-id="${info.data.id}">Editar</a></li>
                    <li><a class="dropdown-item text-danger" href="#" data-action="delete" data-id="${info.data.id}">Eliminar</a></li>
                  </ul>`);
                $(el).append(wrap);
              }
            },
        ],
        showBorders: true,
        paging: { pageSize: 20 },
        searchPanel: { visible: true, placeholder: 'Buscar canales...' },
        noDataText: 'No hay canales configurados.',
    }).dxDataGrid('instance');

    function getInboxId() { return $('#inboxSelector').val(); }
    $('#inboxSelector').on('change', () => grid.refresh());

    function openModal(data = null) {
        $('#channelId').val(data?.id ?? '');
        $('#channelModalTitle').text(data ? 'Editar canal' : 'Nuevo canal web');
        $('#channelName').val(data?.name ?? '');
        $('#channelToken').val(data?.website_token ?? '').prop('disabled', !!data);
        $('#channelDomain').val(data?.domain ?? '');
        $('#channelActive').val(data?.is_active ? '1' : '0');
        $('#channelModal').modal('show');
    }

    $('#btnNewChannel').on('click', () => openModal());

    $(document).on('click', '[data-action="edit"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.get(routes.index, { inbox_id: getInboxId() }).done(r => {
            const item = r.data.find(x => x.id == id);
            if (item) openModal(item);
        });
    });

    $('#btnSaveChannel').on('click', function () {
        const id = $('#channelId').val();
        const payload = {
            inbox_id: parseInt(getInboxId()),
            name: $('#channelName').val().trim() || null,
            website_token: $('#channelToken').val().trim(),
            domain: $('#channelDomain').val().trim() || null,
            is_active: $('#channelActive').val() === '1',
        };

        const url    = id ? routes.update(id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            $('#channelModal').modal('hide');
            grid.refresh();
            toastr.success('Canal guardado correctamente.');
        }).fail(xhr => {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                toastr.error(Object.values(errors).flat().join('<br>'));
            } else {
                toastr.error('Error al guardar el canal.');
            }
        });
    });

    $(document).on('click', '[data-action="delete"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!confirm('¿Eliminar este canal?')) return;
        $.ajax({
            url: routes.destroy(id), method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            grid.refresh();
            toastr.success('Canal eliminado.');
        }).fail(() => toastr.error('No se pudo eliminar el canal.'));
    });

    $(document).on('click', '[data-action="restore"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({
            url: routes.restore(id), method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            grid.refresh();
            toastr.success('Canal restaurado.');
        }).fail(() => toastr.error('No se pudo restaurar el canal.'));
    });

    $('#btnToggleTrashed').on('click', function () {
        showTrashed = !showTrashed;
        $('#trashLabel').text(showTrashed ? 'Activos' : 'Papelera');
        $(this).toggleClass('btn-outline-secondary btn-secondary');
        grid.refresh();
    });
});
</script>
@endpush
