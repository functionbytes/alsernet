@props([
    'title' => '¿Qué permisos se solicitan?',
    'icon' => 'fas fa-shield-alt',
    'iconColor' => 'text-info',
    'permissions' => [],
    'variant' => 'light', // light, primary, success, warning, info
])

<div class="bg-{{ $variant }} rounded-3 p-4 {{ $attributes->get('class', 'mb-4') }}">
    <div class="d-flex">
        @if($icon)
            <div class="me-3">
                <i class="{{ $icon }} fa-2x {{ $iconColor }}"></i>
            </div>
        @endif
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-2">{{ $title }}</h6>

            @if(count($permissions) > 0)
                <ul class="mb-0 ps-3">
                    @foreach($permissions as $permission)
                        <li class="{{ !$loop->last ? 'mb-1' : 'mb-0' }}">{{ $permission }}</li>
                    @endforeach
                </ul>
            @else
                {{ $slot }}
            @endif
        </div>
    </div>
</div>
