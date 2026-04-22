{{-- Knowledge tab partial - loaded via AJAX into #knowledge-container --}}

@php
    $total = $knowledge->count();
    $active = $knowledge->where('is_active', true)->count();
    $inactive = $total - $active;
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 fw-bold">Base de conocimiento</h5>
        <p class="text-muted mb-0 small">Documentos que el agente utiliza para generar respuestas precisas</p>
    </div>
    <button type="button" class="btn btn-primary" id="btn-new-knowledge">
        <i class="fas fa-plus me-1"></i> Nuevo documento
    </button>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Total</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($total) }}</h4>
                <small class="text-muted">Documentos registrados</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Activos</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($active) }}</h4>
                <small class="text-muted">Indexados por el agente</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Inactivos</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($inactive) }}</h4>
                <small class="text-muted">No usados por el agente</small>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
@if($knowledge->isEmpty())
    <div class="text-center py-5">
        <i class="fas fa-brain fa-3x mb-3 text-muted opacity-50"></i>
        <h5 class="fw-bold mb-2">No hay documentos en la base de conocimiento</h5>
        <p class="text-muted mb-4">Agrega documentos, FAQs o articulos para que el agente responda con mas precision.</p>
        <button type="button" class="btn btn-primary" id="btn-new-knowledge-empty">
            <i class="fas fa-plus me-1"></i> Nuevo documento
        </button>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle text-nowrap">
            <thead class="table-light">
                <tr>
                    <th>Titulo</th>
                    <th>Tipo</th>
                    <th>Usos</th>
                    <th>Embedding</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($knowledge as $item)
                    @php
                        $typeLabels = ['document' => 'Documento', 'faq' => 'FAQ', 'article' => 'Articulo', 'manual' => 'Manual', 'url' => 'URL'];
                    @endphp
                    <tr data-count-item>
                        <td>
                            <span class="fw-semibold">{{ Str::limit($item->title, 50) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $typeLabels[$item->type] ?? $item->type }}</span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $item->usage_count ?? 0 }} veces</small>
                        </td>
                        <td>
                            @if($item->embedding_model)
                                <span class="badge bg-success-subtle text-success">Generado</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Sin embedding</span>
                            @endif
                        </td>
                        <td>
                            @if($item->is_active)
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
                                        <a class="dropdown-item knowledge-edit-btn" href="#" data-id="{{ $item->id }}">
                                            Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item knowledge-toggle-btn" href="#"
                                           data-id="{{ $item->id }}"
                                           data-active="{{ $item->is_active ? '1' : '0' }}">
                                            {{ $item->is_active ? 'Desactivar' : 'Activar' }}
                                        </a>
                                    </li>
                                    @unless($item->embedding_model)
                                        <li>
                                            <a class="dropdown-item knowledge-embedding-btn" href="#"
                                               data-id="{{ $item->id }}">
                                                Generar embedding
                                            </a>
                                        </li>
                                    @endunless
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item knowledge-delete-btn" href="#"
                                           data-id="{{ $item->id }}"
                                           data-name="{{ $item->title }}">
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

    // New document
    $(document).on('click', '#btn-new-knowledge, #btn-new-knowledge-empty', function () {
        $('#knowledge_id').val('');
        $('#knowledgeForm')[0].reset();
        $('#knowledgeModalLabel').text('Nuevo documento');
        $('#knowledgeModal').modal('show');
    });

    // Edit document (load via AJAX)
    $(document).on('click', '.knowledge-edit-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');

        $.get('{{ route("manager.helpdesk.ai.knowledge.index") }}/' + id, function (data) {
            $('#knowledge_id').val(data.id);
            $('#knowledge_title').val(data.title);
            $('#knowledge_content').val(data.content);
            $('#knowledge_type').val(data.type);
            $('#knowledge_source_url').val(data.source_url);
            $('#knowledge_source_type').val(data.source_type);
            $('#knowledge_tags').val(data.tags ? data.tags.join(', ') : '');
            $('#knowledge_summary').val(data.summary);
            $('#knowledge_is_active').val(data.is_active ? '1' : '0');
            $('#knowledgeModalLabel').text('Editar documento');
            $('#knowledgeModal').modal('show');
        }).fail(function () {
            toastr.error('Error al cargar el documento', 'Error');
        });
    });

    // Toggle active
    $(document).on('click', '.knowledge-toggle-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const active = $(this).data('active') == '1' ? 0 : 1;

        $.ajax({
            url: '{{ route("manager.helpdesk.ai.knowledge.toggle", "__ID__") }}'.replace('__ID__', id),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: { is_active: active }
        }).done(function () {
            toastr.success(active ? 'Documento activado' : 'Documento desactivado', 'Exito');
            reloadKnowledgeTab();
        }).fail(function () {
            toastr.error('Error al actualizar el documento', 'Error');
        });
    });

    // Generate embedding
    $(document).on('click', '.knowledge-embedding-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');

        $.ajax({
            url: '{{ route("manager.helpdesk.ai.knowledge.generate-embedding", "__ID__") }}'.replace('__ID__', id),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF }
        }).done(function () {
            toastr.success('Embedding generado correctamente', 'Exito');
            reloadKnowledgeTab();
        }).fail(function () {
            toastr.error('Error al generar el embedding', 'Error');
        });
    });

    // Delete
    $(document).on('click', '.knowledge-delete-btn', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#delete-modal .modal-title').text('Eliminar documento: ' + name);
        $('#delete-form').attr('action', '#').off('submit').on('submit', function (ev) {
            ev.preventDefault();
            $.ajax({
                url: '{{ route("manager.helpdesk.ai.knowledge.destroy", "__ID__") }}'.replace('__ID__', id),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF }
            }).done(function () {
                $('#delete-modal').modal('hide');
                toastr.success('Documento eliminado correctamente', 'Exito');
                reloadKnowledgeTab();
            }).fail(function () {
                toastr.error('Error al eliminar el documento', 'Error');
            });
        });
        $('#delete-modal').modal('show');
    });

    // Form submit
    $('#knowledgeForm').off('submit').on('submit', function (e) {
        e.preventDefault();
        const id = $('#knowledge_id').val();
        const url = id
            ? '{{ route("manager.helpdesk.ai.knowledge.update", "__ID__") }}'.replace('__ID__', id)
            : '{{ route("manager.helpdesk.ai.knowledge.store") }}';

        const tagsRaw = $('#knowledge_tags').val().trim();
        const tags = tagsRaw ? tagsRaw.split(',').map(t => t.trim()).filter(Boolean) : [];

        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: url,
            method: id ? 'PUT' : 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: {
                title: $('#knowledge_title').val(),
                content: $('#knowledge_content').val(),
                type: $('#knowledge_type').val(),
                source_url: $('#knowledge_source_url').val(),
                source_type: $('#knowledge_source_type').val(),
                tags: tags,
                summary: $('#knowledge_summary').val(),
                is_active: $('#knowledge_is_active').val(),
            }
        }).done(function (res) {
            $('#knowledgeModal').modal('hide');
            toastr.success(res.message || 'Documento guardado correctamente', 'Exito');
            reloadKnowledgeTab();
        }).fail(function (xhr) {
            if (xhr.status === 422) {
                $.each(xhr.responseJSON.errors, function (field, messages) {
                    $('#knowledge_' + field).addClass('is-invalid')
                        .siblings('.invalid-feedback').text(messages[0]);
                });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar el documento', 'Error');
            }
        });
    });

    function reloadKnowledgeTab() {
        $.get('{{ route("manager.helpdesk.ai.knowledge.index") }}', function (html) {
            $('#knowledge-container').html(html);
            const count = $('#knowledge-container [data-count-item]').length;
            $('#knowledge-count').text(count);
        });
    }
});
</script>
