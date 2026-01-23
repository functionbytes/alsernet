<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1"><i class="ti ti-brain me-2"></i>Base de Conocimiento</h5>
        <p class="text-muted small mb-0">Documentos y contenido que el agente utilizará para generar respuestas precisas</p>
    </div>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-warning" onclick="importFromHelpCenter()">
            <i class="ti ti-download me-2"></i>Importar desde Help Center
        </button>
        <button type="button" class="btn btn-warning" onclick="openKnowledgeModal()">
            <i class="ti ti-plus me-2"></i>Nuevo Documento
        </button>
    </div>
</div>

@if($knowledge->isEmpty())
    <div class="alert alert-info">
        <i class="ti ti-info-circle me-2"></i>
        <strong>No hay documentos en la base de conocimiento</strong>
        <p class="mb-0 mt-2">Añade documentos, FAQs, manuales o artículos para que el agente pueda generar respuestas más precisas y contextuales.</p>
    </div>
@else
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" class="form-control" id="searchKnowledge" placeholder="Buscar en la base de conocimiento...">
        </div>
        <div class="col-md-3">
            <select class="form-select" id="filterType">
                <option value="">Todos los tipos</option>
                <option value="document">Documentos</option>
                <option value="faq">FAQs</option>
                <option value="article">Artículos</option>
                <option value="manual">Manuales</option>
                <option value="url">URLs</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="filterStatus">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>
        </div>
    </div>

    <div class="row">
        @foreach($knowledge as $item)
            <div class="col-md-6 col-lg-4 mb-3" data-count-item>
                <div class="card h-100 {{ $item->is_active ? '' : 'bg-light' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center flex-grow-1">
                                <i class="{{ $item->type_icon }} fs-3 me-2 text-warning"></i>
                                <div>
                                    <h6 class="mb-0">{{ Str::limit($item->title, 40) }}</h6>
                                    <span class="badge bg-light text-dark small">{{ $item->type_label }}</span>
                                </div>
                            </div>
                        </div>

                        <p class="text-muted small mb-2">{{ $item->excerpt }}</p>

                        @if($item->tags)
                            <div class="mb-2">
                                @foreach($item->tags as $tag)
                                    <span class="badge bg-secondary small me-1">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center text-muted small mb-2">
                            <span><i class="ti ti-eye me-1"></i>{{ $item->usage_count }} usos</span>
                            @if($item->embedding_model)
                                <span class="badge bg-success-subtle text-success"><i class="ti ti-vector me-1"></i>Embedding</span>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" {{ $item->is_active ? 'checked' : '' }}
                                    onchange="toggleKnowledge({{ $item->id }}, this.checked)">
                                <label class="form-check-label small">
                                    {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                </label>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-light" onclick="viewKnowledge({{ $item->id }})" data-bs-toggle="tooltip" title="Ver contenido">
                                    <i class="ti ti-eye"></i>
                                </button>
                                <button type="button" class="btn btn-light" onclick="editKnowledge({{ $item->id }})" data-bs-toggle="tooltip" title="Editar">
                                    <i class="ti ti-edit"></i>
                                </button>
                                @if(!$item->embedding_model)
                                    <button type="button" class="btn btn-light text-success" onclick="generateEmbedding({{ $item->id }})" data-bs-toggle="tooltip" title="Generar Embedding">
                                        <i class="ti ti-vector"></i>
                                    </button>
                                @endif
                                <button type="button" class="btn btn-light text-danger" onclick="deleteKnowledge({{ $item->id }})" data-bs-toggle="tooltip" title="Eliminar">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<script>
function openKnowledgeModal(id = null) {
    $('#knowledgeModal').modal('show');
    if (id) {
        $.get(`/dashboard/helpdesk/ai/knowledge/${id}`, function(data) {
            $('#knowledge_id').val(data.id);
            $('#knowledge_title').val(data.title);
            $('#knowledge_content').val(data.content);
            $('#knowledge_type').val(data.type);
            $('#knowledge_source_url').val(data.source_url);
            $('#knowledge_source_type').val(data.source_type);
            $('#knowledge_tags').val(data.tags ? data.tags.join(', ') : '');
            $('#knowledge_summary').val(data.summary);
            $('#knowledge_is_active').prop('checked', data.is_active);
            $('#knowledgeModalTitle').text('Editar Documento');
        });
    } else {
        $('#knowledgeForm')[0].reset();
        $('#knowledge_id').val('');
        $('#knowledgeModalTitle').text('Nuevo Documento');
    }
}

function editKnowledge(id) {
    openKnowledgeModal(id);
}

function viewKnowledge(id) {
    // Implementation for viewing full content
    alert('Ver contenido completo del documento #' + id);
}

function toggleKnowledge(id, isActive) {
    $.post(`{{ route('manager.helpdesk.ai.knowledge.toggle', '') }}/${id}`, {
        _token: '{{ csrf_token() }}',
        is_active: isActive
    }).done(function() {
        showSuccess('Documento ' + (isActive ? 'activado' : 'desactivado') + ' correctamente');
    }).fail(function() {
        showError('Error al actualizar el documento');
    });
}

function generateEmbedding(id) {
    if (confirm('¿Generar embedding para este documento? Esto puede tardar unos momentos.')) {
        $.post(`{{ route('manager.helpdesk.ai.knowledge.generate-embedding', '') }}/${id}`, {
            _token: '{{ csrf_token() }}'
        }).done(function() {
            showSuccess('Embedding generado correctamente');
            setTimeout(() => {
                $('a[href="#tab-knowledge"]').click();
            }, 500);
        }).fail(function() {
            showError('Error al generar el embedding');
        });
    }
}

function deleteKnowledge(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este documento?')) {
        $.ajax({
            url: `{{ route('manager.helpdesk.ai.knowledge.destroy', '') }}/${id}`,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' }
        }).done(function() {
            showSuccess('Documento eliminado correctamente');
            setTimeout(() => {
                $('a[href="#tab-knowledge"]').click();
            }, 500);
        }).fail(function() {
            showError('Error al eliminar el documento');
        });
    }
}

function importFromHelpCenter() {
    // Implementation for importing from help center
    alert('Función de importación desde Help Center - En desarrollo');
}

// Handle knowledge form submission
$('#knowledgeForm').on('submit', function(e) {
    e.preventDefault();

    const formData = {
        title: $('#knowledge_title').val(),
        content: $('#knowledge_content').val(),
        type: $('#knowledge_type').val(),
        source_url: $('#knowledge_source_url').val(),
        source_type: $('#knowledge_source_type').val(),
        tags: $('#knowledge_tags').val().split(',').map(t => t.trim()).filter(t => t),
        summary: $('#knowledge_summary').val(),
        is_active: $('#knowledge_is_active').is(':checked'),
        _token: '{{ csrf_token() }}'
    };

    const knowledgeId = $('#knowledge_id').val();
    const url = knowledgeId
        ? `{{ route('manager.helpdesk.ai.knowledge.update', '') }}/${knowledgeId}`
        : `{{ route('manager.helpdesk.ai.knowledge.store') }}`;
    const method = knowledgeId ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: formData
    }).done(function() {
        $('#knowledgeModal').modal('hide');
        showSuccess('Documento guardado correctamente');
        setTimeout(() => {
            $('a[href="#tab-knowledge"]').click();
        }, 500);
    }).fail(function(xhr) {
        showError(xhr.responseJSON?.message || 'Error al guardar el documento');
    });
});

$('[data-bs-toggle="tooltip"]').tooltip();
</script>
