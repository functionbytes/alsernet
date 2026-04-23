@php
    $template = caixilhariablanco_get_current_template() ?? '';
    $isHome = $template === 'homepage';
    $isService = request()->is('servicios/*');
@endphp

@if($isHome)
    {{-- Preload hero background image for homepage --}}
    <link rel="preload" as="image" href="{{ asset('themes/caixilhariablanco/images/hero-bg.webp') }}" fetchpriority="high">
    {{-- Preload first slider image (LCP element) --}}
    <link rel="preload" as="image" href="{{ asset('media/slider/header-img4.webp?v=2') }}" fetchpriority="high">
@elseif($isService)
    {{-- Preload service hero background image --}}
    <link rel="preload" as="image" href="{{ asset('pages/images/bg/header-bg1.webp') }}" fetchpriority="high">
@else
    {{-- Preload page header background for inner pages --}}
    <link rel="preload" as="image" href="{{ asset('themes/caixilhariablanco/images/page-header-bg.webp') }}" fetchpriority="high">
@endif

{{-- Critical CSS overrides for accessibility & performance --}}
<style>
  /* Improve contrast for proceso-number (WCAG 3:1 for large text) */
  .proceso-number {
    color: rgba(177, 1, 0, 0.55) !important;
  }

  /* Font display swap to prevent invisible text during load */
  @font-face {
    font-display: swap !important;
  }
</style>
