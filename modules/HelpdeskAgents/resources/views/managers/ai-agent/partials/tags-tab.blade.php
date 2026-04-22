{{-- Tags tab partial - loaded via AJAX into #tags-container --}}

@php
    $total = $tags->count();
    $active = $tags->where('is_active', true)->count();
    $inactive = $total - $active;
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 fw-bold">Tags del agente</h5>
        <p class="text-muted mb-0 small">Los tags categorizan conversaciones y modifican el comportamiento del agente</p>
    </div>
    <button type="button" class="btn btn-primary" id="btn-new-tag">
        <i class="fas fa-plus me-1"></i> Nuevo tag
    </button>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Total</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($total) }}</h4>
                <small class="text-muted">Tags registrados</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Activos</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($active) }}</h4>
                <small class="text-muted">Disponibles para el agente</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Inactivos</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($inactive) }}</h4>
                <small class="text-muted">Sin efecto en el agente</small>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
@if($tags->isEmpty())
    <div class="text-center py-5">
        <i class="fas fa-tags fa-3x mb-3 text-muted opacity-50"></i>
        <h5 class="fw-bold mb-2">No hay tags configurados</h5>
        <p class="text-muted mb-4">Crea tu primer tag para categorizar conversaciones y personalizar el comportamiento del agente.</p>
        <button type="button" class="btn btn-primary" id="btn-new-tag-empty">
            <i class="fas fa-plus me-1"></i> Nuevo tag
        </button>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle text-nowrap">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tags as $tag)
                    <tr data-count-item>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block flex-shrink-0"
                                      style="width:12px;height:12px;background-color:{{ $tag->color ?? '#90bb13' }};"></span>
                                <span class="fw-semibold">{{ $tag->name }}</span>
                            </div>
                        </td>
                        <td>
                            <small class="text-muted">{{ $tag->description ? Str::limit($tag->description, 60) : '—' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $tag->priority ?? 0 }}</span>
                        </td>
                        <td>
                            @if($tag->is_active)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item tag-edit-btn" href="#"
                                           data-id="{{ $tag->id }}"
                                           data-name="{{ $tag->name }}"
                                           data-description="{{ $tag->description }}"
                                           data-color="{{ $tag->color ?? '#90bb13' }}"
                                           data-icon="{{ $tag->icon }}"
                                           data-priority="{{ $tag->priority ?? 0 }}"
                                           data-system-prompt="{{ $tag->system_prompt_addition }}"
                                           data-is-active="{{ $tag->is_active ? '1' : '0' }}">
                                            Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item tag-toggle-btn" href="#"
                                           data-id="{{ $tag->id }}"
                                           data-active="{{ $tag->is_active ? '1' : '0' }}">
                                            {{ $tag->is_active ? 'Desactivar' : 'Activar' }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item tag-delete-btn" href="#"
                                           data-id="{{ $tag->id }}"
                                           data-name="{{ $tag->name }}">
                                            Eliminar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<script>
$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    // Open modal for new tag
    $(document).on('click', '#btn-new-tag, #btn-new-tag-empty', function () {
        $('#tag_id').val('');
        $('#tagForm')[0].reset();
        $('#tag_color').val('#90bb13');
        $('#tagModalLabel').text('Nuevo tag');
        $('#tagModal').modal('show');
    });

    // Open modal to edit tag (data embedded in row)
    $(document).on('click', '.tag-edit-btn', function (e) {
        e.preventDefault();
        const d = $(this).data();
        $('#tag_id').val(d.id);
        $('#tag_name').val(d.name);
        $('#tag_description').val(d.description);
        $('#tag_color').val(d.color);
        $('#tag_icon').val(d.icon);
        $('#tag_priority').val(d.priority);
        $('#tag_system_prompt_addition').val(d.systemPrompt);
        $('#tag_is_active').val(d.isActive ? '1' : '0');
        $('#tagModalLabel').text('Editar tag');
        $('#tagModal').modal('show');
    });

    // Toggle active
    $(document).on('click', '.tag-toggle-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const active = $(this).data('active') == '1' ? 0 : 1;

        $.ajax({
            url: '{{ route("manager.helpdesk.ai.tags.toggle", "__ID__") }}'.replace('__ID__', id),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: { is_active: active }
        }).done(function () {
            toastr.success(active ? 'Tag activado' : 'Tag desactivado', 'Exito');
            reloadTagsTab();
        }).fail(function () {
            toastr.error('Error al actualizar el tag', 'Error');
        });
    });

    // Delete
    $(document).on('click', '.tag-delete-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#delete-modal .modal-title').text('Eliminar tag: ' + name);
        $('#delete-form').attr('action', '#').off('submit').on('submit', function (ev) {
            ev.preventDefault();
            $.ajax({
                url: '{{ route("manager.helpdesk.ai.tags.destroy", "__ID__") }}'.replace('__ID__', id),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF }
            }).done(function () {
                $('#delete-modal').modal('hide');
                toastr.success('Tag eliminado correctamente', 'Exito');
                reloadTagsTab();
            }).fail(function () {
                toastr.error('Error al eliminar el tag', 'Error');
            });
        });
        $('#delete-modal').modal('show');
    });

    // Form submit (create / update)
    $('#tagForm').off('submit').on('submit', function (e) {
        e.preventDefault();
        const id = $('#tag_id').val();
        const url = id
            ? '{{ route("manager.helpdesk.ai.tags.update", "__ID__") }}'.replace('__ID__', id)
            : '{{ route("manager.helpdesk.ai.tags.store") }}';

        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: url,
            method: id ? 'PUT' : 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: {
                name: $('#tag_name').val(),
                description: $('#tag_description').val(),
                color: $('#tag_color').val(),
                icon: $('#tag_icon').val(),
                priority: $('#tag_priority').val(),
                system_prompt_addition: $('#tag_system_prompt_addition').val(),
                is_active: $('#tag_is_active').val(),
            }
        }).done(function (res) {
            $('#tagModal').modal('hide');
            toastr.success(res.message || 'Tag guardado correctamente', 'Exito');
            reloadTagsTab();
        }).fail(function (xhr) {
            if (xhr.status === 422) {
                $.each(xhr.responseJSON.errors, function (field, messages) {
                    $('#tag_' + field).addClass('is-invalid')
                        .siblings('.invalid-feedback').text(messages[0]);
                });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar el tag', 'Error');
            }
        });
    });

    function reloadTagsTab() {
        $.get('{{ route("manager.helpdesk.ai.tags.index") }}', function (html) {
            $('#tags-container').html(html);
            const count = $('#tags-container [data-count-item]').length;
            $('#tags-count').text(count);
        });
    }
});
</script>
