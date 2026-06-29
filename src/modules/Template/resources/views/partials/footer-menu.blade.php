@if($menu_nodes && $menu_nodes->isNotEmpty())
    <h3>{{ $menu->name }}</h3>
    <ul>
        @foreach($menu_nodes as $item)
            <li>
                <a href="{{ $item->full_url }}" target="{{ $item->target ?? '_self' }}">
                    {{ $item->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endif
