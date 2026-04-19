<ul>
    @foreach ($menu_nodes as $row)
        <li>
            <a href="{{ $row->full_url }}" target="{{ $row->target ?? '_self' }}">
                {{ $row->display_title }}
            </a>
        </li>
    @endforeach
</ul>
