@php
    $compareCount = count(session('ecommerce_compare', []));
@endphp

<a href="{{ route('ecommerce.compare.index') }}"
   id="compareFloating"
   class="compare-floating-btn{{ $compareCount > 0 ? '' : ' d-none' }}"
   aria-label="Ver comparacion de productos">
    <i class="fas fa-code-compare"></i>
    <span class="badge bg-warning text-dark js-compare-count">{{ $compareCount }}</span>
    <span class="d-none d-md-inline ms-1">Comparar</span>
</a>

<style>
.compare-floating-btn {
    position: fixed;
    bottom: 90px;
    right: 24px;
    background: #1a2030;
    color: #fff;
    padding: 10px 18px;
    border-radius: 50px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    z-index: 9997;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.compare-floating-btn:hover {
    color: #fff;
    background: #2d3447;
    transform: translateY(-2px);
}
.compare-floating-btn .badge { font-size: 12px; }
@media (max-width: 575px) {
    .compare-floating-btn {
        bottom: 76px;
        right: 16px;
        padding: 8px 12px;
    }
}
</style>
