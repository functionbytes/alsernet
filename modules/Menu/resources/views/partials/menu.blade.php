@if($menu_nodes && $menu_nodes->isNotEmpty())
    <ol class="dd-list">
        @foreach($menu_nodes as $item)
            @include('menu::partials.node', compact('item', 'menu'))
        @endforeach
    </ol>
@endif
