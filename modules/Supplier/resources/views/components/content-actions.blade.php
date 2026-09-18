<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-outline-primary view-content" data-id="{{ $content->uid }}" title="Ver detalle">
        <i class="fas fa-eye"></i>
    </button>

    @if ($content->status === 'pending')
        <button type="button" class="btn btn-outline-success approve-content" data-id="{{ $content->uid }}" title="Aprobar">
            <i class="fas fa-check"></i>
        </button>
        <button type="button" class="btn btn-outline-danger reject-content" data-id="{{ $content->uid }}" title="Rechazar">
            <i class="fas fa-times"></i>
        </button>
    @endif

    @if ($content->status === 'approved')
        <button type="button" class="btn btn-outline-info publish-content" data-id="{{ $content->uid }}" title="Publicar">
            <i class="fas fa-paper-plane"></i>
        </button>
    @endif

    <button type="button" class="btn btn-outline-warning regenerate-content" data-id="{{ $content->uid }}" title="Regenerar">
        <i class="fas fa-redo"></i>
    </button>
</div>
