@props(['location', 'class' => ''])

@if($menu)
    <ul {{ $attributes->merge(['class' => $class]) }}>
        @foreach($items as $item)
            @include('template::components.menu-item', ['item' => $item])
        @endforeach
    </ul>
@endif
