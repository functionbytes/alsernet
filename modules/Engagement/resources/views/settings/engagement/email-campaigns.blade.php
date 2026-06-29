@extends('layouts.theme')

@section('title', 'Campañas de email — Engagement')

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
                <h4><i class="fas fa-envelope me-2"></i>Campañas de email</h4>
                <p>Gestiona campañas de email marketing con segmentación avanzada</p>
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
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnToggleTrashed">
                    <i class="fas fa-trash me-1"></i><span id="trashLabel">Papelera</span>
                </button>
                @can('engagement.email_campaigns.create')
                <button class="btn btn-primary" id="btnNewCampaign">
                    <i class="fas fa-plus me-1"></i>Nueva campaña
                </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="campaignsGrid"></div>
        </div>
    </div>


{{-- Modal --}}
<div class="modal fade" id="campaignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="campaignModalTitle">Nueva campaña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="campaignId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="campaignName" class="form-control" placeholder="Ej: Newsletter mensual">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Asunto <span class="text-danger">*</span></label>
                        <input type="text" id="campaignSubject" class="form-control" placeholder="Ej: Novedades de mayo">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Remitente</label>
                        <input type="text" id="campaignFromName" class="form-control" placeholder="Nombre">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email remitente</label>
                        <input type="email" id="campaignFromEmail" class="form-control" placeholder="hola@ejemplo.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Proveedor</label>
                        <select id="campaignProvider" class="form-select">
                            <option value="mailchimp">Mailchimp</option>
                            <option value="sendgrid">SendGrid</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">ID lista (proveedor)</label>
                        <input type="text" id="campaignListId" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">HTML contenido</label>
                        <textarea id="campaignHtml" class="form-control" rows="4" placeholder="<html>...</html>"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Texto plano</label>
                        <textarea id="campaignText" class="form-control" rows="3" placeholder="Versión texto..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="campaignStatus" class="form-select">
                            <option value="draft">Borrador</option>
                            <option value="scheduled">Programado</option>
                            <option value="sent">Enviado</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Programar para</label>
                        <input type="datetime-local" id="campaignScheduled" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-column gap-2">
                <button type="button" class="btn btn-primary w-100" id="btnSaveCampaign">Guardar campaña</button>
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
        index:     '{{ route('settings.engagement.email-campaigns.index') }}',
        store:     '{{ route('settings.engagement.email-campaigns.store') }}',
        update:    (id) => '{{ route('settings.engagement.email-campaigns.update', ['emailCampaign' => '__ID__']) }}'.replace('__ID__', id),
        destroy:   (id) => '{{ route('settings.engagement.email-campaigns.destroy', ['emailCampaign' => '__ID__']) }}'.replace('__ID__', id),
        send:      (id) => '{{ route('settings.engagement.email-campaigns.send', ['emailCampaign' => '__ID__']) }}'.replace('__ID__', id),
        syncStats: (id) => '{{ route('settings.engagement.email-campaigns.sync-stats', ['emailCampaign' => '__ID__']) }}'.replace('__ID__', id),
        trashed:   '{{ route('settings.engagement.email-campaigns.trashed') }}',
        restore:   (id) => '{{ route('settings.engagement.email-campaigns.restore', ['id' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const canDelete = {{ auth()->user()->can('engagement.email_campaigns.delete') ? 'true' : 'false' }};
    let showTrashed = false;

    const grid = $('#campaignsGrid').dxDataGrid({
        dataSource: {
            load: () => {
                const url = showTrashed ? routes.trashed : routes.index;
                return $.get(url, { inbox_id: getInboxId() }).then(r => r.data);
            },
        },
        columns: [
            { dataField: 'name', caption: 'Nombre' },
            { dataField: 'subject', caption: 'Asunto', width: 200 },
            { dataField: 'provider', caption: 'Proveedor', width: 120 },
            { dataField: 'status', caption: 'Estado', width: 110,
              cellTemplate: (el, info) => {
                  const map = { draft: 'bg-secondary', scheduled: 'bg-warning text-dark', sent: 'bg-success' };
                  const label = { draft: 'Borrador', scheduled: 'Programado', sent: 'Enviado' };
                  $(el).html(`<span class="badge ${map[info.value] || 'bg-secondary'}">${label[info.value] || info.value}</span>`);
              }
            },
            { dataField: 'scheduled_at', caption: 'Programado', width: 160,
              cellTemplate: (el, info) => $(el).text(info.value ? new Date(info.value).toLocaleString() : '-') },
            { caption: 'Acciones', width: 160, alignment: 'center',
              cellTemplate: (el, info) => {
                if (showTrashed) {
                    $(el).html(`<button class="btn btn-sm btn-outline-success" data-action="restore" data-id="${info.data.id}"><i class="fas fa-undo"></i> Restaurar</button>`);
                    return;
                }
                const t = info.data;
                let btns = `<button class="btn btn-sm btn-light me-1" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>`;
                let items = `<li><a class="dropdown-item" href="#" data-action="edit" data-id="${t.id}">Editar</a></li>`;
                if (t.status === 'draft' || t.status === 'scheduled') {
                    items += `<li><a class="dropdown-item" href="#" data-action="send" data-id="${t.id}"><i class="fas fa-paper-plane text-primary me-1"></i>Enviar ahora</a></li>`;
                }
                if (t.provider_campaign_id) {
                    items += `<li><a class="dropdown-item" href="#" data-action="sync" data-id="${t.id}"><i class="fas fa-sync text-info me-1"></i>Sincronizar stats</a></li>`;
                }
                if (canDelete) {
                    items += `<li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-danger" href="#" data-action="delete" data-id="${t.id}">Eliminar</a></li>`;
                }
                $(el).html(`<div class="dropdown">${btns}<ul class="dropdown-menu dropdown-menu-end">${items}</ul></div>`);
              }
            },
        ],
        showBorders: true,
        paging: { pageSize: 20 },
        searchPanel: { visible: true, placeholder: 'Buscar campañas...' },
        noDataText: 'No hay campañas configuradas.',
    }).dxDataGrid('instance');

    function getInboxId() { return $('#inboxSelector').val(); }
    $('#inboxSelector').on('change', () => grid.refresh());

    function openModal(data = null) {
        $('#campaignId').val(data?.id ?? '');
        $('#campaignModalTitle').text(data ? 'Editar campaña' : 'Nueva campaña');
        $('#campaignName').val(data?.name ?? '');
        $('#campaignSubject').val(data?.subject ?? '');
        $('#campaignFromName').val(data?.from_name ?? '');
        $('#campaignFromEmail').val(data?.from_email ?? '');
        $('#campaignProvider').val(data?.provider ?? 'mailchimp');
        $('#campaignListId').val(data?.provider_list_id ?? '');
        $('#campaignHtml').val(data?.html_content ?? '');
        $('#campaignText').val(data?.text_content ?? '');
        $('#campaignStatus').val(data?.status ?? 'draft');
        $('#campaignScheduled').val(data?.scheduled_at ? data.scheduled_at.slice(0, 16) : '');
        $('#campaignModal').modal('show');
    }

    $('#btnNewCampaign').on('click', () => openModal());

    $(document).on('click', '[data-action="edit"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.get(routes.index, { inbox_id: getInboxId() }).done(r => {
            const item = r.data.find(x => x.id == id);
            if (item) openModal(item);
        });
    });

    $('#btnSaveCampaign').on('click', function () {
        const id = $('#campaignId').val();
        const payload = {
            inbox_id: parseInt(getInboxId()),
            name: $('#campaignName').val().trim(),
            subject: $('#campaignSubject').val().trim(),
            from_name: $('#campaignFromName').val().trim() || null,
            from_email: $('#campaignFromEmail').val().trim() || null,
            provider: $('#campaignProvider').val(),
            provider_list_id: $('#campaignListId').val().trim() || null,
            html_content: $('#campaignHtml').val().trim() || null,
            text_content: $('#campaignText').val().trim() || null,
            status: $('#campaignStatus').val(),
            scheduled_at: $('#campaignScheduled').val() || null,
        };

        const url    = id ? routes.update(id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            $('#campaignModal').modal('hide');
            grid.refresh();
            toastr.success('Campaña guardada correctamente.');
        }).fail(xhr => {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                toastr.error(Object.values(errors).flat().join('<br>'));
            } else {
                toastr.error('Error al guardar la campaña.');
            }
        });
    });

    $(document).on('click', '[data-action="send"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!confirm('¿Enviar esta campaña ahora?')) return;
        $.ajax({ url: routes.send(id), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { grid.refresh(); toastr.success('Campaña enviada.'); })
            .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error al enviar.'));
    });

    $(document).on('click', '[data-action="sync"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({ url: routes.syncStats(id), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { grid.refresh(); toastr.success('Stats sincronizados.'); })
            .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error al sincronizar.'));
    });

    $(document).on('click', '[data-action="delete"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!confirm('¿Eliminar esta campaña?')) return;
        $.ajax({
            url: routes.destroy(id), method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            grid.refresh();
            toastr.success('Campaña eliminada.');
        }).fail(() => toastr.error('No se pudo eliminar la campaña.'));
    });

    $(document).on('click', '[data-action="restore"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({ url: routes.restore(id), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { grid.refresh(); toastr.success('Campaña restaurada.'); })
            .fail(() => toastr.error('No se pudo restaurar la campaña.'));
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
