@extends('layouts.theme')

@section('title', 'Posts')

@section('content')
    @include('core::components.card', ['title' => 'Blog - Posts'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Posts del blog</h5>
                        <p class="small mb-0 text-muted">Administra las publicaciones del blog</p>
                    </div>
                    <div class="d-flex gap-2">
                        @php
                            $hasActiveFilters = collect($filters ?? [])->filter(fn($v) => !empty($v))->isNotEmpty();
                        @endphp
                        @if($hasActiveFilters)
                            <a href="{{ route('blog.posts.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                        @can('create', \Modules\Blog\Models\BlogPost::class)
                            <a href="{{ route('blog.posts.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo post
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total posts</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total'] ?? 0) }}</h4>
                                <small class="text-muted">Registrados en el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Publicados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['published'] ?? 0) }}</h4>
                                <small class="text-muted">Visibles en el sitio</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Borradores</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['draft'] ?? 0) }}</h4>
                                <small class="text-muted">Sin publicar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Pendientes</h6>
                                <h4 class="mb-1 fw-bold text-warning">{{ number_format($stats['pending'] ?? 0) }}</h4>
                                <small class="text-muted">En revisión</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('blog.posts.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-2 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por título o descripción..."
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                        </div>
                        <select class="form-select select2" name="status" style="min-width:170px; max-width:170px;">
                            <option value="">Todos los estados</option>
                            <option value="draft"     {{ ($filters['status'] ?? '') === 'draft'     ? 'selected' : '' }}>Borrador</option>
                            <option value="published" {{ ($filters['status'] ?? '') === 'published' ? 'selected' : '' }}>Publicado</option>
                            <option value="pending"   {{ ($filters['status'] ?? '') === 'pending'   ? 'selected' : '' }}>Pendiente</option>
                        </select>
                        <select class="form-select select2" name="category" style="min-width:200px; max-width:200px;">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($filters['category'] ?? '') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        <select class="form-select select2" name="translation_status" style="min-width:200px; max-width:200px;">
                            <option value="">Todas las traducciones</option>
                            <option value="complete"   {{ ($filters['translation_status'] ?? '') === 'complete'   ? 'selected' : '' }}>Completas</option>
                            <option value="incomplete" {{ ($filters['translation_status'] ?? '') === 'incomplete' ? 'selected' : '' }}>Incompletas</option>
                            <option value="stale"      {{ ($filters['translation_status'] ?? '') === 'stale'      ? 'selected' : '' }}>Obsoletas</option>
                            <option value="none"       {{ ($filters['translation_status'] ?? '') === 'none'       ? 'selected' : '' }}>Sin traducciones</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="min-width:45px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            {{-- List --}}
            <div class="card-body">
                @if($posts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Título</th>
                                    <th>Categorías</th>
                                    <th>Autor</th>
                                    <th class="text-center">Vistas</th>
                                    <th>Estado</th>
                                    <th>Traducciones</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($posts as $post)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $post->id }}"></td>
                                        <td>
                                            <a href="{{ route('blog.posts.edit', $post->id) }}" class="text-decoration-none fw-semibold">
                                                {{ $post->title }}
                                            </a>
                                            <div class="text-muted" style="font-size:.75rem;">
                                                <i class="fas fa-clock me-1"></i>{{ $post->reading_time }} min de lectura
                                            </div>
                                        </td>
                                        <td>
                                            @forelse($post->categories as $cat)
                                                <span class="badge bg-secondary-subtle">{{ $cat->name }}</span>
                                            @empty
                                                <small class="text-muted">Sin categoría</small>
                                            @endforelse
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $post->user?->name ?? '—' }}</small>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-light text-muted border">{{ number_format($post->views) }}</span>
                                        </td>
                                        <td>
                                            <div class="mt-1 d-flex gap-1 flex-wrap">
                                                <span class="{{ $post->status->badgeClass() }}">
                                                    {{ $post->status->label() }}
                                                </span>
                                                @if($post->is_featured)
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        <i class="fas fa-star me-1"></i>Destacado
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $supportedLocales = \Modules\Blog\Services\BlogPostService::getSupportedLocales();
                                            @endphp
                                            <div class="d-flex gap-1 flex-wrap">
                                                @foreach($supportedLocales as $loc)
                                                    @php
                                                        $trans = $post->translations->firstWhere('locale', $loc);
                                                        if (!$trans) {
                                                            $tBadge = 'bg-danger';
                                                            $tIcon  = 'fas fa-times';
                                                            $tTip   = strtoupper($loc) . ': sin traducción';
                                                        } elseif ($trans->status?->value === 'stale') {
                                                            $tBadge = 'bg-warning';
                                                            $tIcon  = 'fas fa-exclamation-triangle';
                                                            $tTip   = strtoupper($loc) . ': obsoleta';
                                                        } elseif ($trans->status?->value === 'auto_translated') {
                                                            $tBadge = 'bg-info';
                                                            $tIcon  = 'fas fa-robot';
                                                            $tTip   = strtoupper($loc) . ': auto-traducida';
                                                        } elseif ($trans->status?->value === 'reviewed') {
                                                            $tBadge = 'bg-success';
                                                            $tIcon  = 'fas fa-check-circle';
                                                            $tTip   = strtoupper($loc) . ': revisada';
                                                        } else {
                                                            $tBadge = 'bg-primary';
                                                            $tIcon  = 'fas fa-pen';
                                                            $tTip   = strtoupper($loc) . ': manual';
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $tBadge }}" style="font-size:.6rem" title="{{ $tTip }}">
                                                        <i class="{{ $tIcon }}" style="font-size:.5rem"></i> {{ strtoupper($loc) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $post->published_at ? $post->published_at->format('d/m/Y') : $post->created_at->format('d/m/Y') }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted"
                                                   data-bs-toggle="dropdown"
                                                   data-bs-auto-close="true"
                                                   data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="{{ $post->url }}" class="dropdown-item" target="_blank">
                                                            Ver
                                                        </a>
                                                    </li>
                                                    @can('update', $post)
                                                        <li>
                                                            <a href="{{ route('blog.posts.edit', $post->id) }}" class="dropdown-item">
                                                                Editar
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('create', \Modules\Blog\Models\BlogPost::class)
                                                        <li>
                                                            <button type="button" class="dropdown-item duplicate-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#duplicate-modal"
                                                                    data-url="{{ route('blog.posts.duplicate', $post->id) }}"
                                                                    data-title="{{ $post->title }}">
                                                                Duplicar
                                                            </button>
                                                        </li>
                                                    @endcan
                                                    @can('publish', $post)
                                                        @if($post->status->value !== 'published')
                                                            <li>
                                                                <button type="button" class="dropdown-item toggle-status-btn"
                                                                        data-id="{{ $post->id }}"
                                                                        data-action="publish">
                                                                    Publicar
                                                                </button>
                                                            </li>
                                                        @else
                                                            <li>
                                                                <button type="button" class="dropdown-item toggle-status-btn"
                                                                        data-id="{{ $post->id }}"
                                                                        data-action="unpublish">
                                                                    Despublicar
                                                                </button>
                                                            </li>
                                                        @endif
                                                    @endcan
                                                    @can('delete', $post)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button type="button" class="dropdown-item delete-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#delete-modal"
                                                                    data-url="{{ route('blog.posts.destroy', $post->id) }}"
                                                                    data-title="Eliminar post: {{ $post->title }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    @endcan
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
                        <i class="fas fa-newspaper fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay posts para mostrar</h5>
                        <p class="text-muted mb-4">
                            @if($hasActiveFilters)
                                No se encontraron resultados con los filtros aplicados
                            @else
                                Crea tu primer post para el blog
                            @endif
                        </p>
                        @if(!$hasActiveFilters)
                            @can('create', \Modules\Blog\Models\BlogPost::class)
                                <a href="{{ route('blog.posts.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Crear primer post
                                </a>
                            @endcan
                        @endif
                    </div>
                @endif
            </div>

            @if($posts->hasPages())
                <div class="card-footer">{{ $posts->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> post(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="publish">Publicar</option>
                            <option value="unpublish">Despublicar</option>
                            <option value="translate_all">Traducir todos los idiomas</option>
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

    {{-- Modal duplicar --}}
    <div id="duplicate-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form id="duplicate-form" method="POST" action="">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Duplicar post</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="display-4 text-primary mb-3">
                            <i class="fas fa-copy"></i>
                        </div>
                        <h4 class="my-0">¿Duplicar este post?</h4>
                        <p class="text-muted" id="duplicate-modal-subtitle">Se creará una copia en estado borrador.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                Confirmar duplicación
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('core::components.delete')
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Flash messages
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Delete modal
    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Duplicate modal
    $('.duplicate-btn').on('click', function () {
        $('#duplicate-modal-subtitle').text('Se creará una copia de "' + $(this).data('title') + '" en estado borrador.');
        $('#duplicate-form').attr('action', $(this).data('url'));
    });

    // Toggle publish/unpublish
    $(document).on('click', '.toggle-status-btn', function () {
        var id     = $(this).data('id');
        var action = $(this).data('action');
        var url    = action === 'publish'
            ? '{{ url("panel/blog/posts") }}/' + id + '/publish'
            : '{{ url("panel/blog/posts") }}/' + id + '/unpublish';

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message);
                    location.reload();
                } else {
                    toastr.error(res.message || 'Error al procesar la acción');
                }
            },
            error: function () { toastr.error('Error al procesar la acción'); }
        });
    });

    // Bulk actions
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

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un post.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' post(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('blog.posts.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || res.count + ' post(s) actualizados.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});
</script>
@endpush
