@extends('layouts.theme')

@section('title', 'Moderación de comentarios')

@section('page_header')
    @include('core::components.card', ['title' => 'Moderación de comentarios'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Moderacion de comentarios</h5>
                        <p class="small mb-0 text-muted">Aprueba, marca como spam o elimina comentarios del blog</p>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Todos los comentarios</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Pendientes</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['pending']) }}</h4>
                                <small class="text-muted">Esperando revision</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Aprobados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['approved']) }}</h4>
                                <small class="text-muted">Visibles en el sitio</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Spam</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['spam']) }}</h4>
                                <small class="text-muted">Marcados como spam</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('blog.comments.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por autor, email o contenido..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="status" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pendiente</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Aprobado</option>
                                <option value="spam"     {{ request('status') === 'spam'     ? 'selected' : '' }}>Spam</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('blog.comments.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($comments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Autor</th>
                                    <th>Post</th>
                                    <th>Comentario</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comments as $comment)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $comment->id }}"></td>
                                        <td style="min-width:140px;">
                                            <div class="fw-semibold small">{{ $comment->author_name }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">{{ $comment->author_email }}</div>
                                            @if($comment->parent_id)
                                                <span class="badge bg-secondary-subtle text-secondary mt-1" style="font-size:.7rem;">
                                                    <i class="fas fa-reply me-1"></i>Respuesta
                                                </span>
                                            @endif
                                        </td>
                                        <td style="min-width:120px; max-width:180px;">
                                            @if($comment->post)
                                                <a href="{{ $comment->post->url }}" target="_blank" class="small text-decoration-none">
                                                    {{ \Illuminate\Support\Str::limit($comment->post->title, 40) }}
                                                </a>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td style="max-width:280px;">
                                            <p class="small mb-0">{{ \Illuminate\Support\Str::limit($comment->content, 100) }}</p>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $comment->status->badgeClass() }}-subtle text-{{ $comment->status->badgeClass() }}">
                                                {{ $comment->status->label() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if($comment->status !== \Modules\Blog\Enums\CommentStatus::Approved)
                                                        <li>
                                                            <a class="dropdown-item action-btn" href="javascript:void(0)"
                                                               data-id="{{ $comment->id }}" data-action="approve">
                                                                Aprobar
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if($comment->status !== \Modules\Blog\Enums\CommentStatus::Spam)
                                                        <li>
                                                            <a class="dropdown-item action-btn" href="javascript:void(0)"
                                                               data-id="{{ $comment->id }}" data-action="spam">
                                                                Marcar como spam
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('blog.comments.destroy', $comment) }}"
                                                           data-title="Eliminar comentario de: {{ $comment->author_name }}">
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
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-comments fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('status'))
                                    No se encontraron comentarios
                                @else
                                    No hay comentarios para mostrar
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('status'))
                                    No hay resultados para los criterios de busqueda
                                @else
                                    Los comentarios del blog apareceran aqui
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            @if($comments->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $comments->firstItem() }} - {{ $comments->lastItem() }} de {{ $comments->total() }} comentarios
                        </div>
                        <div>
                            {{ $comments->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara la accion sobre <strong><span data-bulk-count>0</span> comentario(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar accion...</option>
                            <option value="approve">Aprobar</option>
                            <option value="spam">Marcar como spam</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una accion.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un comentario.'); return; }
        

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('blog.comments.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' comentario(s) actualizados.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $(document).on('click', '.action-btn', function () {
        var $btn   = $(this);
        var id     = $btn.data('id');
        var action = $btn.data('action');

        $.ajax({
            url: '/panel/blog/comments/' + id + '/' + action,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) {
                    toastr.success(action === 'approve' ? 'Comentario aprobado' : 'Marcado como spam');
                    location.reload();
                }
            },
            error: function () {
                toastr.error('Error al procesar la accion');
            }
        });
    });

    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
