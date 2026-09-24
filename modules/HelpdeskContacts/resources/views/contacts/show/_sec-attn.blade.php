<div class="ctf-sec ctf-sec-attn">
    <div class="ctf-section-title">
        <span class="t">{{ $attnTitle ?? 'Necesita atención' }}</span>
        <span class="n" id="ctf-attn-count">…</span>
        {{-- "Siguiente acción" (mockup A): la acción del primer aviso, la pinta renderCtfAttention(). --}}
        <span class="ctf-next-action d-none" id="ctf-next-action"></span>
    </div>
    <div class="ctf-attn-list" id="ctf-attn-body">
        <div class="ctf-skel-line mb-2"></div>
        <div class="ctf-skel-line"></div>
    </div>
</div>
