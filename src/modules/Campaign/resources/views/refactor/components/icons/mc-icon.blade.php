{{--
    Refactor Custom Icon System (ported subset for Campaign Page Templates).
    Usage: @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'home', 'size' => 24, 'class' => ''])

    Thin 1.5px strokes in currentColor with subtle var(--color-teal) accents.
    Unknown icons fall back to Material Symbols Rounded.
--}}
@php
    $size = $size ?? 24;
    $class = $class ?? '';
@endphp

<span class="mc-icon {{ $class }}" style="width:{{ $size }}px;height:{{ $size }}px;display:inline-flex;align-items:center;justify-content:center;">
@switch($icon)

@case('add')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
    <line x1="12" y1="8" x2="12" y2="16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="8" y1="12" x2="16" y2="12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <circle cx="12" cy="12" r="3" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('arrow-left')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M19 12H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <path d="M11 6L5 12L11 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
@break

@case('calendar')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="8" y1="3" x2="8" y2="7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="16" y1="3" x2="16" y2="7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <rect x="7" y="13" width="3" height="3" rx="0.5" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('check')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M5 13L9.5 17.5L19 6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
@break

@case('chevron-left')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
@break

@case('chevron-right')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
@break

@case('code')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M8 9L4 12L8 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M16 9L20 12L16 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <line x1="14" y1="6" x2="10" y2="18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <circle cx="12" cy="12" r="1" fill="var(--color-teal)" opacity="0.5"/>
</svg>
@break

@case('copy')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect x="9" y="9" width="11" height="12" rx="1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M6 15H5C4.44772 15 4 14.5523 4 14V4C4 3.44772 4.44772 3 5 3H14C14.5523 3 15 3.44772 15 4V5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <rect x="12" y="12" width="5" height="1" rx="0.5" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('delete')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M6 7H18L17 20C17 20.5523 16.5523 21 16 21H8C7.44772 21 7 20.5523 7 20L6 7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <line x1="4" y1="7" x2="20" y2="7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <path d="M9 7V4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
</svg>
@break

@case('edit')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M16.474 5.408L18.592 7.526L8.367 17.751L5.5 18.5L6.249 15.633L16.474 5.408Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <line x1="14.5" y1="7.5" x2="16.5" y2="9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <circle cx="6" cy="18" r="0.8" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('eye')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M2 12C2 12 5.5 5 12 5C18.5 5 22 12 22 12C22 12 18.5 19 12 19C5.5 19 2 12 2 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
    <circle cx="12" cy="12" r="1" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('file-text')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M14 3H6C5.44772 3 5 3.44772 5 4V20C5 20.5523 5.44772 21 6 21H18C18.5523 21 19 20.5523 19 20V8L14 3Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M14 3V8H19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <line x1="9" y1="13" x2="15" y2="13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="9" y1="17" x2="13" y2="17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <circle cx="14" cy="8" r="0.8" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('grid')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="6.5" cy="6.5" r="1" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('info')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
    <line x1="12" y1="11" x2="12" y2="17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <circle cx="12" cy="8" r="1" fill="currentColor"/>
</svg>
@break

@case('layers')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="12" cy="7" r="1.5" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('list')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <line x1="9" y1="6" x2="20" y2="6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="9" y1="12" x2="20" y2="12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="9" y1="18" x2="20" y2="18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <circle cx="5" cy="6" r="1.5" fill="var(--color-teal)" opacity="0.75"/>
    <circle cx="5" cy="12" r="1.5" stroke="currentColor" stroke-width="1.5" fill="none"/>
    <circle cx="5" cy="18" r="1.5" stroke="currentColor" stroke-width="1.5" fill="none"/>
</svg>
@break

@case('more-v')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="12" cy="6" r="1.5" fill="currentColor"/>
    <circle cx="12" cy="12" r="1.5" fill="currentColor"/>
    <circle cx="12" cy="18" r="1.5" fill="currentColor"/>
</svg>
@break

@case('palette')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21C12.5523 21 13 20.5523 13 20V19C13 18.4477 13.4477 18 14 18H16C18.2091 18 20 16.2091 20 14C20 8.47715 16.4183 3 12 3Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="8" cy="10" r="1.5" fill="currentColor" opacity="0.5"/>
    <circle cx="12" cy="8" r="1.5" fill="var(--color-teal)" opacity="0.75"/>
    <circle cx="16" cy="10" r="1.5" fill="currentColor" opacity="0.5"/>
</svg>
@break

@case('search')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/>
    <line x1="16.65" y1="16.65" x2="21" y2="21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <circle cx="11" cy="11" r="2" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('star')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 3L14.5 9L21 9.5L16 14L17.5 21L12 17.5L6.5 21L8 14L3 9.5L9.5 9L12 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
    <circle cx="12" cy="12" r="2" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@case('type')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <line x1="4" y1="6" x2="20" y2="6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="12" y1="6" x2="12" y2="19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="8" y1="19" x2="16" y2="19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <circle cx="12" cy="6" r="0.8" fill="var(--color-teal)" opacity="0.75"/>
</svg>
@break

@default
{{-- Fallback: render as Material Symbols Rounded --}}
<span class="material-symbols-rounded" style="font-size:{{ $size }}px;line-height:1;">{{ $icon }}</span>
@endswitch
</span>
