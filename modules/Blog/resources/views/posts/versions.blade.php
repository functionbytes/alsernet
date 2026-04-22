@extends('layouts.theme')

@section('title', 'Historial de versiones — ' . $post->title)

@section('content')
    @include('core::components.card', ['title' => 'Historial de versiones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Header --}}
        <div class="card mb-3">
            <div class="card-header p-3 bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $post->title }}</h5>
                        <p class="small mb-0 text-muted">
                            {{ $versions->total() }} {{ $versions->total() === 1 ? 'versión guardada' : 'versiones guardadas' }}
                        </p>
                    </div>
                    <a href="{{ route('blog.posts.edit', $post) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver al post
                    </a>
                </div>
            </div>
        </div>

        {{-- Versions table --}}
        <div class="card mb-3">
            <div class="card-body p-0">
                @if($versions->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No hay versiones guardadas para este post.</p>
                        <small class="text-muted">Las versiones se crean automáticamente al guardar cambios.</small>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Título</th>
                                    <th>Autor</th>
                                    <th>Resumen de cambios</th>
                                    <th>Fecha</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($versions as $version)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-secondary-subtle text-secondary fw-semibold">
                                                v{{ $version->version_number }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-truncate" style="max-width: 220px;">
                                                {{ $version->title }}
                                            </div>
                                            <small class="text-muted">/blog/{{ $version->slug }}</small>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ $version->author?->full_name ?? 'Sistema' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ $version->change_summary ?? '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted" title="{{ $version->created_at->format('d/m/Y H:i:s') }}">
                                                {{ $version->created_at->format('d/m/Y H:i') }}
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $version->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary btn-preview-version me-1"
                                                    data-version="{{ $version->version_number }}"
                                                    data-title="{{ e($version->title) }}"
                                                    data-content="{{ e(strip_tags($version->content)) }}"
                                                    data-excerpt="{{ e($version->excerpt) }}"
                                                    data-author="{{ $version->author?->full_name ?? 'Sistema' }}"
                                                    data-date="{{ $version->created_at->format('d/m/Y H:i') }}"
                                                    data-summary="{{ e($version->change_summary ?? '—') }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-info btn-diff-version me-1"
                                                    data-post="{{ $post->id }}"
                                                    data-version="{{ $version->id }}"
                                                    title="Ver diferencias con version actual">
                                                <i class="fas fa-code-compare"></i>
                                            </button>
                                            <form method="POST"
                                                  action="{{ route('blog.posts.versions.restore', [$post, $version]) }}"
                                                  class="d-inline restore-form">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-primary restore-btn"
                                                        data-version="{{ $version->version_number }}">
                                                    <i class="fas fa-rotate-left"></i> Restaurar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($versions->hasPages())
                        <div class="px-4 py-3 border-top">
                            {{ $versions->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

    </div>

    {{-- Diff modal --}}
    <div class="modal fade" id="versionDiffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modal-diff-label">Diferencias con version actual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-6 border-end">
                            <div class="bg-danger-subtle px-3 py-2 border-bottom">
                                <small class="fw-semibold text-danger">Version anterior</small>
                                <small class="text-muted ms-2" id="diff-version-info"></small>
                            </div>
                            <div class="p-3">
                                <p class="text-muted mb-1 fw-semibold">Titulo</p>
                                <p id="diff-old-title" class="mb-3 border rounded p-2 bg-white"></p>
                                <p class="text-muted mb-1 fw-semibold">Contenido</p>
                                <div id="diff-old-content" class="bg-white rounded border p-3 small"
                                     style="max-height:400px; overflow-y:auto; white-space:pre-wrap; font-family:monospace;"></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-success-subtle px-3 py-2 border-bottom">
                                <small class="fw-semibold text-success">Version actual</small>
                            </div>
                            <div class="p-3">
                                <p class="text-muted mb-1 fw-semibold">Titulo</p>
                                <p id="diff-new-title" class="mb-3 border rounded p-2 bg-white"></p>
                                <p class="text-muted mb-1 fw-semibold">Contenido</p>
                                <div id="diff-new-content" class="bg-white rounded border p-3 small"
                                     style="max-height:400px; overflow-y:auto; white-space:pre-wrap; font-family:monospace;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview modal --}}
    <div class="modal fade" id="versionPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modal-version-label">Vista previa versión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Autor</p>
                            <p class="fw-semibold mb-0" id="modal-author"></p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Fecha</p>
                            <p class="fw-semibold mb-0" id="modal-date"></p>
                        </div>
                        <div class="col-12">
                            <p class="text-muted mb-1">Resumen de cambios</p>
                            <p class="mb-0" id="modal-summary"></p>
                        </div>
                    </div>
                    <div class="border-top pt-3">
                        <p class="text-muted mb-1">Extracto</p>
                        <p id="modal-excerpt" class="text-muted mb-3"></p>
                        <p class="text-muted mb-1">Contenido (texto plano)</p>
                        <div id="modal-content"
                             class="bg-light rounded p-3 small"
                             style="max-height: 350px; overflow-y: auto; white-space: pre-wrap;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Preview modal
    $(document).on('click', '.btn-preview-version', function () {
        var $btn = $(this);
        $('#modal-version-label').text('Vista previa versión v' + $btn.data('version'));
        $('#modal-author').text($btn.data('author'));
        $('#modal-date').text($btn.data('date'));
        $('#modal-summary').text($btn.data('summary'));
        $('#modal-excerpt').text($btn.data('excerpt') || '—');
        $('#modal-content').text($btn.data('content') || '—');
        $('#versionPreviewModal').modal('show');
    });

    // Diff modal
    $(document).on('click', '.btn-diff-version', function () {
        var postId    = $(this).data('post');
        var versionId = $(this).data('version');

        $.ajax({
            url: '/blog/posts/' + postId + '/versions/' + versionId + '/diff',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#modal-diff-label').text('Diferencias — v' + res.version_number + ' vs actual');
                $('#diff-version-info').text(res.created_at + ' por ' + res.author);
                $('#diff-old-title').text(res.version.title);
                $('#diff-new-title').text(res.current.title);
                $('#diff-old-content').text(res.version.content);
                $('#diff-new-content').text(res.current.content);
                $('#versionDiffModal').modal('show');
            },
            error: function () { toastr.error('Error al cargar las diferencias'); }
        });
    });

    // Restore confirmation
    $(document).on('click', '.restore-btn', function (e) {
        e.preventDefault();
        var version = $(this).data('version');
        var $form = $(this).closest('form');
        if (true) {
            return;
        }
        $form.submit();
    });

});
</script>
@endpush
