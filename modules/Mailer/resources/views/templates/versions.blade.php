@extends('layouts.theme')

@section('page_title', 'Historial de versiones: ' . $template->name)

@section('content')

    @include('core::components.card', [
        'title' => 'Historial de versiones — ' . $template->name,
    ])

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fs-4 me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle fs-4 me-2"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-bottom p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Versiones guardadas</h5>
                    <small class="text-muted">Cada vez que guardas el template se crea una versión anterior</small>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    {{-- Language selector --}}
                    @if ($langs->count() > 1)
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <select name="lang_id" class="form-select form-select-sm js-auto-submit" style="width: auto;">
                                @foreach ($langs as $lang)
                                    <option value="{{ $lang->id }}" @selected($lang->id == $langId)>
                                        {{ $lang->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    <a href="{{ route('mailers.templates.edit', $template->uid) }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Volver al editor
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if ($versions->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-history fa-3x mb-3 opacity-50"></i>
                    <p class="mb-0">No hay versiones guardadas para este idioma aún.</p>
                    <small>Las versiones se crean automáticamente al guardar cambios.</small>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 60px;">#</th>
                                <th>Asunto</th>
                                <th>Nota del cambio</th>
                                <th>Guardado por</th>
                                <th>Fecha</th>
                                <th style="width: 160px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($versions as $version)
                                <tr>
                                    <td class="ps-3 text-muted">{{ $version->id }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 250px;"
                                              title="{{ $version->subject }}">
                                            {{ $version->subject ?: '(sin asunto)' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($version->change_note)
                                            <span class="text-muted">{{ $version->change_note }}</span>
                                        @else
                                            <span class="text-muted fst-italic">Sin nota</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($version->author)
                                            <span class="small">{{ $version->author->name }}</span>
                                        @else
                                            <span class="text-muted fst-italic">Sistema</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="small text-muted" title="{{ $version->created_at->format('d/m/Y H:i:s') }}">
                                            {{ $version->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm me-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#diffModal{{ $version->id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <form method="POST"
                                              action="{{ route('mailers.templates.versions.restore', [$template->uid, $version->id]) }}"
                                              class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-primary btn-sm"
                                                    data-confirm="¿Restaurar esta version? Se guardara el contenido actual antes de restaurar."
                                                    data-confirm-title="Restaurar version"
                                                    data-confirm-btn-class="btn-warning"
                                                    data-confirm-btn-text="Restaurar">
                                                <i class="fas fa-undo me-1"></i>Restaurar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($versions->hasPages())
                    <div class="d-flex justify-content-end p-3">
                        {{ $versions->appends(['lang_id' => $langId])->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Diff modals --}}
    @foreach ($versions as $version)
        <div class="modal fade" id="diffModal{{ $version->id }}" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Versión #{{ $version->id }}
                            <small class="text-muted ms-2">{{ $version->created_at->format('d/m/Y H:i') }}</small>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted text-uppercase">Asunto</label>
                            <div class="border rounded p-2 bg-light">{{ $version->subject ?: '(sin asunto)' }}</div>
                        </div>
                        <div>
                            <label class="form-label fw-semibold text-muted text-uppercase">Contenido</label>
                            <pre class="border rounded p-3 bg-light small" style="max-height: 500px; overflow: auto; white-space: pre-wrap; word-break: break-all;">{{ $version->content }}</pre>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <form method="POST"
                              action="{{ route('mailers.templates.versions.restore', [$template->uid, $version->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary"
                                    data-confirm="¿Restaurar esta version?"
                                    data-confirm-title="Restaurar version"
                                    data-confirm-btn-class="btn-warning"
                                    data-confirm-btn-text="Restaurar">
                                <i class="fas fa-undo me-1"></i>Restaurar esta versión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@include('mailer::partials.confirm-modal')

@endsection
