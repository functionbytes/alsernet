@props(['location', 'class' => ''])

@php
    use Modules\Menu\Models\Menu;

    $menu = Menu::where('location', $location)
        ->where('status', true)
        ->with(['items' => function ($query) {
            $query->with('children');
        }])
        ->first();
@endphp

@if($menu)
    <ul {{ $attributes->merge(['class' => $class]) }}>
        @foreach($menu->items as $item)
            @include('template::components.menu-item', ['item' => $item])
        @endforeach
    </ul>
@endif
