@extends('page::components.layouts.master')

@section('title', '[Vista previa] ' . ($page->seo_title ?? $page->title))
@section('meta_description', $page->seo_description ?? $page->description)
@section('meta_keywords', $page->seo_keywords)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
.preview-bar {
    position: sticky;
    top: 0;
    z-index: 1030;
    background: #fff;
    border-bottom: 3px solid #90bb13;
    box-shadow: 0 1px 6px rgba(0,0,0,.08);
}
.preview-bar .badge-preview {
    background: #90bb13;
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .06em;
    padding: 3px 8px;
    border-radius: 4px;
    vertical-align: middle;
}
.preview-bar .badge-unpublished {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffc107;
    font-size: .65rem;
    padding: 3px 8px;
    border-radius: 4px;
}
.preview-watermark {
    position: fixed;
    bottom: 20px;
    right: 20px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .1em;
    color: rgba(144, 187, 19, .35);
    background: rgba(255,255,255,.9);
    padding: 6px 14px;
    border-radius: 6px;
    border: 1px solid rgba(144, 187, 19, .25);
    z-index: 1000;
    pointer-events: none;
    user-select: none;
}
.preview-content {
    padding: 2rem 0;
}
@media print {
    .preview-bar, .preview-watermark { display: none !important; }
}
</style>
@endpush

@section('content')

{{-- Barra de vista previa --}}
<div class="preview-bar">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center py-2 gap-3">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <span class="badge-preview flex-shrink-0">VISTA PREVIA</span>
                <span class="fw-semibold text-truncate" style="font-size:.9rem">{{ $page->title }}</span>
                @if(!$page->isPublished())
                    <span class="badge-unpublished flex-shrink-0">No publicada</span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-3 flex-shrink-0 text-muted" style="font-size:.8rem">
                <span>Expira {{ $previewToken->getExpiresInHuman() }}</span>
                <span>{{ $previewToken->viewed_count }} {{ $previewToken->viewed_count === 1 ? 'vista' : 'vistas' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Contenido de la página --}}
<div class="preview-content">
    <div class="container">
        @include($contentView, ['page' => $page])
    </div>
</div>

{{-- Marca de agua --}}
<div class="preview-watermark">PREVIEW</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Los formularios están deshabilitados en modo vista previa.');
        });
    });
});
</script>
@endpush
