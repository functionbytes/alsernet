<ul {!! $options !!}>
    @foreach ($menu_nodes as $row)
        @php
            $hasChildren = $row->children->isNotEmpty();
            $isActive = $row->isActive() || $row->hasActiveChild();
        @endphp
        <li class="{{ $row->css_class }}{{ $isActive ? ' active' : '' }}{{ $hasChildren ? ' menu-item-has-children' : '' }}">
            <a href="{{ $row->full_url }}" target="{{ $row->target ?? '_self' }}" class="{{ $isActive ? 'active' : '' }}">
                @if ($row->icon)
                    <i class="{{ $row->icon }}"></i>
                @endif
                {{ $row->display_title }}
                @if ($hasChildren)
                    @if ($row->parent_id) <i class="fa fa-chevron-right"></i> @else <i class="fa fa-chevron-down"></i> @endif
                @endif
            </a>
            @if ($hasChildren)
                @include('template::partials.main-menu', [
                    'menu_nodes' => $row->children,
                    'menu' => $menu,
                    'options' => 'class="'.($row->parent_id ? 'level-menu level-menu-modify' : 'sub-menu').'"',
                ])
            @endif
        </li>
    @endforeach
</ul>
