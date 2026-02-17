{{-- Barra de vista previa (sticky) --}}
<div class="preview-bar" style="position: sticky; top: 0; z-index: 1030; background: #fff; border-bottom: 3px solid #90bb13; box-shadow: 0 1px 6px rgba(0,0,0,.08);">
    <div class="container" style="max-width: 100%; padding: 0 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0;">
                <span style="background: #90bb13; color: #fff; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em; padding: 3px 8px; border-radius: 4px; flex-shrink: 0;">VISTA PREVIA</span>
                <span style="font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; font-size: 0.9rem;">{{ $page->title }}</span>
                @if(!$page->isPublished())
                    <span style="background: #fff3cd; color: #856404; border: 1px solid #ffc107; font-size: 0.65rem; padding: 3px 8px; border-radius: 4px; flex-shrink: 0;">No publicada</span>
                @endif
            </div>
            <div style="display: flex; align-items: center; gap: 1rem; flex-shrink: 0; color: #6c757d; font-size: 0.8rem;">
                <span>Expira {{ $previewToken->getExpiresInHuman() }}</span>
                <span>{{ $previewToken->viewed_count }} {{ $previewToken->viewed_count === 1 ? 'vista' : 'vistas' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Contenido de la página --}}
<div class="preview-content" style="padding: 2rem 0;">
    <div class="container">
        @if($page->content)
            {!! $page->content !!}
        @else
            <p class="text-muted text-center">Este page no tiene contenido.</p>
        @endif
    </div>
</div>

{{-- Marca de agua de preview --}}
<div class="preview-watermark" style="position: fixed; bottom: 20px; right: 20px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; color: rgba(144, 187, 19, .35); background: rgba(255,255,255,.9); padding: 6px 14px; border-radius: 6px; border: 1px solid rgba(144, 187, 19, .25); z-index: 1000; pointer-events: none; user-select: none;">
    PREVIEW
</div>

{{-- Estilos y scripts de preview --}}
@push('styles')
<style>
    @media print {
        .preview-bar, .preview-watermark { display: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Deshabilitar formularios en preview
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Los formularios están deshabilitados en modo vista previa.');
        });
    });

    // Deshabilitar enlaces externos (opcional)
    // document.querySelectorAll('a:not([href^="#"])').forEach(function (link) {
    //     link.addEventListener('click', function (e) {
    //         e.preventDefault();
    //         alert('Los enlaces están deshabilitados en modo vista previa.');
    //     });
    // });
});
</script>
@endpush
