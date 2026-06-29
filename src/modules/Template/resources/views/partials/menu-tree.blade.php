@if($menu_nodes && $menu_nodes->isNotEmpty())
    <ol class="dd-list">
        @foreach($menu_nodes as $item)
            @include('template::partials.menu-node', compact('item', 'menu'))
        @endforeach
    </ol>
@endif
