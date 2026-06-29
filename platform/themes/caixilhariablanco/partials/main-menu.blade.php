<div class="nav-menu-wrapper">
    <ul class="navbar-nav mr-auto" id="menu">
        @foreach ($menu_nodes as $row)
            <li class="nav-item{{ $row->children->isNotEmpty() ? ' submenu' : '' }}{{ $row->isActive() ? ' active' : '' }}{{ $row->css_class ? ' ' . $row->css_class : '' }}">
                <a class="nav-link{{ $row->isActive() ? ' active' : '' }}" href="{{ $row->full_url }}" target="{{ $row->target ?? '_self' }}">
                    @if ($row->icon)
                        <i class="{{ $row->icon }}"></i>
                    @endif
                    {{ $row->display_title }}
                </a>

                @if ($row->children->isNotEmpty())
                    <ul class="mega-menu-service">
                        @foreach ($row->children as $child)
                            <li class="nav-item{{ $child->isActive() ? ' active' : '' }}">
                                <a class="mega-menu-service-single" href="{{ $child->full_url }}" target="{{ $child->target ?? '_self' }}">
                                    @if ($child->icon)
                                        <span class="mega-menu-service-icon"><i class="{{ $child->icon }}"></i></span>
                                    @endif
                                    <span class="mega-menu-service-title">{{ $child->display_title }}</span>
                                    <span class="mega-menu-service-nav">
                                        <i class="fa-solid fa-chevron-right"></i><i class="fa-solid fa-chevron-right"></i>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</div>
