@php
    $optionsStr = is_array($options) ? collect($options)->map(fn($v, $k) => "{$k}=\"{$v}\"")->implode(' ') : $options;
@endphp
<ul {{ $optionsStr ?? '' }}>
    @foreach ($menu_nodes as $row)
        <li class="{{ $row->has_child ? 'menu-item-has-children' : '' }} {{ $row->css_class }}">
            @if ($row->has_child)
                <span class="menu-expand"></span>
            @endif
            <a href="{{ url($row->url) }}" target="{{ $row->target }}">
                @if ($row->icon)<i class="{{ trim($row->icon) }}"></i> @endif{{ $row->title }}
            </a>
            @if ($row->has_child)
                @include('template::partials.mobile-menu', [
                    'menu_nodes' => $row->children,
                    'menu'       => $menu,
                    'options'    => 'class="dropdown"',
                ])
            @endif
        </li>
    @endforeach
</ul>
