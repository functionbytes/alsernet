<ul {!! $options !!}>
    @php $menu_nodes->loadMissing('metadata'); @endphp
    @foreach ($menu_nodes as $key => $row)
        <li class="{{ $row->css_class }}">
            <a href="{{ url($row->url) }}" @if ($row->active) class="active" @endif target="{{ $row->target }}">
                {!! $row->icon_html !!}{{ $row->title }}
                @if ($row->has_child)
                    @if ($row->parent_id) <i class="fa fa-chevron-right"></i> @else <i class="fa fa-chevron-down"></i> @endif
                @endif
            </a>
            @if ($row->has_child)
                {!!
                    Menu::generateMenu([
                        'menu'       => $menu,
                        'view'       => 'main-menu',
                        'options'    => ['class' => $row->parent_id ? 'level-menu level-menu-modify' : 'sub-menu'],
                        'menu_nodes' => $row->child,
                    ])
                !!}
            @endif
        </li>
    @endforeach
</ul>
