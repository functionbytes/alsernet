@extends('layouts.theme')

@section('title', 'Etiquetas sociales')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Etiquetas sociales</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="resetTagForm()">
            <i class="fas fa-plus me-2"></i>Nueva etiqueta
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Color</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Usos</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tags as $tag)
                        <tr data-tag-id="{{ $tag->id }}">
                            <td>
                                <span class="badge" style="background-color: {{ $tag->color }}; color: #fff;">
                                    {{ $tag->name }}
                                </span>
                            </td>
                            <td>
                                <span class="d-inline-block rounded-circle" style="width: 20px; height: 20px; background-color: {{ $tag->color }};"></span>
                                <small class="text-muted ms-1">{{ $tag->color }}</small>
                            </td>
                            <td>{{ $tag->description ?? '-' }}</td>
                            <td>
                                @if($tag->is_active)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td>{{ $tag->comments()->count() }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editTag({{ $tag->id }}, '{{ $tag->name }}', '{{ $tag->slug }}', '{{ $tag->color }}', '{{ $tag->description }}', {{ $tag->is_active ? 1 : 0 }})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-tags fa-2x mb-2 d-block"></i>
                                No hay etiquetas configuradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tags->links() }}
        </div>
    </div>

<div class="modal fade" id="tagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="tagForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="tagModalLabel">Nueva etiqueta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control form-control-color" value="#90bb13" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" checked id="tagIsActive">
                        <label class="form-check-label" for="tagIsActive">Activa</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    function resetTagForm() {
        $('#tagForm').attr('action', '{{ route('helpdesksocial.tags.store') }}');
        $('#tagForm').find('input[name="_method"]').remove();
        $('#tagForm')[0].reset();
        $('#tagModalLabel').text('Nueva etiqueta');
    }

    function editTag(id, name, slug, color, description, isActive) {
        $('#tagForm').attr('action', '{{ url('panel/helpdesk/social/tags') }}/' + id);
        if ($('#tagForm').find('input[name="_method"]').length === 0) {
            $('#tagForm').prepend('<input type="hidden" name="_method" value="PUT">');
        }
        $('#tagForm input[name="name"]').val(name);
        $('#tagForm input[name="slug"]').val(slug);
        $('#tagForm input[name="color"]').val(color);
        $('#tagForm textarea[name="description"]').val(description);
        $('#tagForm input[name="is_active"]').prop('checked', isActive === 1);
        $('#tagModalLabel').text('Editar etiqueta');
        $('#tagModal').modal('show');
    }

    window.resetTagForm = resetTagForm;
    window.editTag = editTag;
})();
</script>
@endsection
