@php
    $shareUrl   = urlencode($canonicalUrl ?? url()->current());
    $shareTitle = urlencode($transTitle ?? $page->title ?? config('app.name'));
    $shareText  = urlencode(strip_tags($transDescription ?? $page->description ?? ''));
@endphp

<div class="social-share d-flex align-items-center gap-2 my-3">
    <span class="text-muted small me-1">Compartir:</span>

    {{-- Facebook --}}
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}"
       target="_blank" rel="noopener noreferrer"
       class="btn btn-sm btn-outline-primary"
       onclick="window.open(this.href,'_blank','width=600,height=400');return false;"
       aria-label="Compartir en Facebook">
        <i class="fab fa-facebook-f"></i>
    </a>

    {{-- Twitter/X --}}
    <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}"
       target="_blank" rel="noopener noreferrer"
       class="btn btn-sm btn-outline-dark"
       onclick="window.open(this.href,'_blank','width=600,height=400');return false;"
       aria-label="Compartir en X (Twitter)">
        <i class="fab fa-x-twitter"></i>
    </a>

    {{-- LinkedIn --}}
    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}"
       target="_blank" rel="noopener noreferrer"
       class="btn btn-sm btn-outline-info"
       onclick="window.open(this.href,'_blank','width=600,height=400');return false;"
       aria-label="Compartir en LinkedIn">
        <i class="fab fa-linkedin-in"></i>
    </a>

    {{-- WhatsApp --}}
    <a href="https://api.whatsapp.com/send?text={{ $shareTitle }}%20{{ $shareUrl }}"
       target="_blank" rel="noopener noreferrer"
       class="btn btn-sm btn-outline-success"
       aria-label="Compartir por WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    {{-- Copiar enlace --}}
    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-copy-link"
            data-url="{{ $canonicalUrl ?? url()->current() }}"
            title="Copiar enlace">
        <i class="fas fa-link"></i>
    </button>
</div>

@push('scripts')
<script>
document.getElementById('btn-copy-link')?.addEventListener('click', function () {
    var btn = this;
    navigator.clipboard.writeText(btn.dataset.url).then(function () {
        var original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.replace('btn-outline-secondary', 'btn-success');
        setTimeout(function () {
            btn.innerHTML = original;
            btn.classList.replace('btn-success', 'btn-outline-secondary');
        }, 2000);
    });
});
</script>
@endpush
