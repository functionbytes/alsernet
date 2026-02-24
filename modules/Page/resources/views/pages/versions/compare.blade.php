@extends('layouts.theme')

@section('page_title', 'Comparar versiones — ' . $page->title)

@section('content')

    @include('core::components.card', ['title' => 'Versiones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Cabecera --}}
        <div class="card mb-3">
            <div class="card-header p-3 bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Comparar versiones de "{{ $page->title }}"</h5>
                        <p class="small mb-0 text-muted">
                            <span class="badge bg-secondary-subtle text-secondary me-1">v{{ $version1->version_number }}</span>
                            vs
                            <span class="badge bg-primary-subtle text-primary ms-1">v{{ $version2->version_number }}</span>
                            @php $changedCount = collect($differences)->where('changed', true)->count(); @endphp
                            @if($has_changes)
                                · <span class="text-warning fw-semibold">{{ $changedCount }} {{ $changedCount === 1 ? 'cambio' : 'cambios' }}</span>
                            @else
                                · <span class="text-success fw-semibold">Sin cambios</span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('pages.versions.index', $page->id) }}" class="btn btn-secondary">
                        Volver
                    </a>
                </div>
            </div>
        </div>

        {{-- Tarjetas de versión --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-secondary-subtle text-secondary">v{{ $version1->version_number }}</span>
                            <span class="fw-semibold">Version antigua</span>
                        </div>
                        <p class=" text-muted mb-1">{{ $version1->created_at->format('d/m/Y H:i') }} · {{ $version1->created_at->diffForHumans() }}</p>
                        <p class="text-muted mb-0">
                            @if($version1->user)
                                {{ $version1->user->full_name ?? $version1->user->name }}
                            @else
                                Sistema
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary">v{{ $version2->version_number }}</span>
                            <span class="fw-semibold">Version nueva</span>
                        </div>
                        <p class=" text-muted mb-1">{{ $version2->created_at->format('d/m/Y H:i') }} · {{ $version2->created_at->diffForHumans() }}</p>
                        <p class="text-muted mb-0">
                            @if($version2->user)
                                {{ $version2->user->full_name ?? $version2->user->name }}
                            @else
                                Sistema
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cambios campo por campo --}}
        @if($has_changes)
            @foreach($differences as $field => $diff)
                @if($diff['changed'])
                <div class="card mb-3">
                    <div class="card-header p-3 bg-white border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0 fw-bold">{{ $diff['label'] }}</h5>
                            <span class="badge bg-warning-subtle text-warning">Modificado</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="row g-0">
                            <div class="col-md-6 border-end">
                                <div class="p-3">
                                    <p class="small fw-semibold text-muted mb-2">v{{ $version1->version_number }} — Anterior</p>
                                    <div class="diff-old rounded p-3">
                                        @if($field === 'content')
                                            <div class="content-preview">
                                                {!! $diff['old_value'] ?? '<em class="text-muted">Sin contenido</em>' !!}
                                            </div>
                                        @else
                                            <p class="mb-0 small">{{ $diff['old_value'] ?? '-' }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3">
                                    <p class="small fw-semibold text-muted mb-2">v{{ $version2->version_number }} — Nueva</p>
                                    <div class="diff-new rounded p-3">
                                        @if($field === 'content')
                                            <div class="content-preview">
                                                {!! $diff['new_value'] ?? '<em class="text-muted">Sin contenido</em>' !!}
                                            </div>
                                        @else
                                            <p class="mb-0 small">{{ $diff['new_value'] ?? '-' }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        @else
            <div class="card mb-3">
                <div class="card-body text-center py-4">
                    <p class="text-muted mb-0">Las versiones son idénticas. No se encontraron diferencias.</p>
                </div>
            </div>
        @endif

        {{-- Campos sin cambios --}}
        @if(collect($differences)->where('changed', false)->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Campos sin cambios</h5>
                <p class="small mb-0 text-muted">Estos campos son idénticos en ambas versiones</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Campo</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($differences as $field => $diff)
                                @if(!$diff['changed'])
                                <tr>
                                    <td class="fw-semibold small">{{ $diff['label'] }}</td>
                                    <td class="small">
                                        @if($field === 'content')
                                            <span class="text-muted">{{ Str::limit(strip_tags($diff['old_value'] ?? ''), 100) }}</span>
                                        @else
                                            {{ $diff['old_value'] ?? '-' }}
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Acciones de restauración --}}
        <div class="card mb-3">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Restaurar version</h5>
                <p class="small mb-0 text-muted">La versión actual se guardará automáticamente antes de restaurar</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100 d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-secondary-subtle text-secondary me-1">v{{ $version1->version_number }}</span>
                                <span class="fw-semibold">Version antigua</span>
                            </div>
                            <p class=" text-muted mb-1">{{ $version1->created_at->format('d/m/Y H:i') }}</p>
                            <p class=" text-muted mb-3">
                                @if($version1->user) {{ $version1->user->full_name ?? $version1->user->name }}
                                @else Sistema @endif
                            </p>
                            <div class="mt-auto">
                                <form method="POST"
                                      action="{{ route('pages.versions.restore', [$page->id, $version1->id]) }}"
                                      id="restoreForm1">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary w-100 restore-btn"
                                            data-version="{{ $version1->version_number }}">
                                        Restaurar v{{ $version1->version_number }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100 d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-primary-subtle text-primary me-1">v{{ $version2->version_number }}</span>
                                <span class="fw-semibold">Version nueva</span>
                            </div>
                            <p class=" text-muted mb-1">{{ $version2->created_at->format('d/m/Y H:i') }}</p>
                            <p class=" text-muted mb-3">
                                @if($version2->user) {{ $version2->user->full_name ?? $version2->user->name }}
                                @else Sistema @endif
                            </p>
                            <div class="mt-auto">
                                <form method="POST"
                                      action="{{ route('pages.versions.restore', [$page->id, $version2->id]) }}"
                                      id="restoreForm2">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100 restore-btn"
                                            data-version="{{ $version2->version_number }}">
                                        Restaurar v{{ $version2->version_number }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('css')
<style>
.diff-old {
    background-color: #fff5f5;
    border-left: 3px solid #dc3545;
    min-height: 60px;
    max-height: 400px;
    overflow-y: auto;
}
.diff-new {
    background-color: #f0fff4;
    border-left: 3px solid #28a745;
    min-height: 60px;
    max-height: 400px;
    overflow-y: auto;
}
.content-preview {
    font-size: 0.875rem;
    line-height: 1.6;
}
.content-preview img { max-width: 100%; height: auto; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    $('.restore-btn').on('click', function (e) {
        e.preventDefault();
        const version = $(this).data('version');
        const $form = $(this).closest('form');
        if (!confirm('¿Restaurar v' + version + '? La versión actual se guardará automáticamente.')) return;
        $form.submit();
    });

    @if(session('success'))
    toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
    toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
