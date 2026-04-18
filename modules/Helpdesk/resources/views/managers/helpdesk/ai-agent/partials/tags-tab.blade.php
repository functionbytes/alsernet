<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1"><i class="fa-solid fa-tags me-2"></i>Gestión de Tags</h5>
        <p class="text-muted mb-0">Los tags permiten categorizar conversaciones y modificar el comportamiento del agente</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openTagModal()">
        <i class="fa-solid fa-plus me-2"></i>Nuevo Tag
    </button>
</div>

@if($tags->isEmpty())
    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info me-2"></i>
        <strong>No hay tags configurados</strong>
        <p class="mb-0 mt-2">Crea tu primer tag para empezar a categorizar conversaciones y personalizar el comportamiento del agente.</p>
    </div>
@else
    <div class="row">
        @foreach($tags as $tag)
            <div class="col-md-6 col-lg-4 mb-3" data-count-item>
                <div class="card h-100 {{ $tag->is_active ? '' : 'bg-light' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                @if($tag->icon)
                                    <i class="{{ $tag->icon }} fs-4 me-2" style="color: {{ $tag->color }}"></i>
                                @endif
                                <h6 class="mb-0">{{ $tag->name }}</h6>
                            </div>
                            <span class="badge" style="background-color: {{ $tag->color }}">
                                Priority: {{ $tag->priority }}
                            </span>
                        </div>

                        @if($tag->description)
                            <p class="text-muted mb-2">{{ Str::limit($tag->description, 80) }}</p>
                        @endif

                        @if($tag->system_prompt_addition)
                            <div class="alert alert-light py-2 px-2 mb-2">
                                <small class="text-muted"><i class="fa-solid fa-code me-1"></i>{{ Str::limit($tag->system_prompt_addition, 60) }}</small>
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" {{ $tag->is_active ? 'checked' : '' }}
                                    onchange="toggleTag({{ $tag->id }}, this.checked)">
                                <label class="form-check-label small">
                                    {{ $tag->is_active ? 'Activo' : 'Inactivo' }}
                                </label>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-light" onclick="editTag({{ $tag->id }})" data-bs-toggle="tooltip" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-light text-danger" onclick="deleteTag({{ $tag->id }})" data-bs-toggle="tooltip" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
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
function openTagModal(id = null) {
    $('#tagModal').modal('show');
    if (id) {
        // Load tag data
        $.get(`/dashboard/helpdesk/ai/tags/${id}`, function(data) {
            $('#tag_id').val(data.id);
            $('#tag_name').val(data.name);
            $('#tag_description').val(data.description);
            $('#tag_color').val(data.color);
            $('#tag_icon').val(data.icon).trigger('input');
            $('#tag_priority').val(data.priority);
            $('#tag_system_prompt_addition').val(data.system_prompt_addition);
            $('#tag_is_active').prop('checked', data.is_active);
            $('#tagModalTitle').text('Editar Tag');
        });
    } else {
        $('#tagForm')[0].reset();
        $('#tag_id').val('');
        $('#tag_color').val('#90bb13');
        $('#tagModalTitle').text('Nuevo Tag');
    }
}

function editTag(id) {
    openTagModal(id);
}

function toggleTag(id, isActive) {
    $.post(`{{ route('manager.helpdesk.ai.tags.toggle', '') }}/${id}`, {
        _token: '{{ csrf_token() }}',
        is_active: isActive
    }).done(function() {
        showSuccess('Tag ' + (isActive ? 'activado' : 'desactivado') + ' correctamente');
    }).fail(function() {
        showError('Error al actualizar el tag');
    });
}

function deleteTag(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este tag?')) {
        $.ajax({
            url: `{{ route('manager.helpdesk.ai.tags.destroy', '') }}/${id}`,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' }
        }).done(function() {
            showSuccess('Tag eliminado correctamente');
            $('a[href="#tab-tags"]').tab('show');
            setTimeout(() => {
                $('a[href="#tab-tags"]').click();
            }, 500);
        }).fail(function() {
            showError('Error al eliminar el tag');
        });
    }
}

// Handle tag form submission
$('#tagForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const tagId = $('#tag_id').val();
    const url = tagId
        ? `{{ route('manager.helpdesk.ai.tags.update', '') }}/${tagId}`
        : `{{ route('manager.helpdesk.ai.tags.store') }}`;
    const method = tagId ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: Object.fromEntries(formData),
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    }).done(function() {
        $('#tagModal').modal('hide');
        showSuccess('Tag guardado correctamente');
        setTimeout(() => {
            $('a[href="#tab-tags"]').click();
        }, 500);
    }).fail(function(xhr) {
        showError(xhr.responseJSON?.message || 'Error al guardar el tag');
    });
});

// Initialize tooltips
$('[data-bs-toggle="tooltip"]').tooltip();
</script>
