{{-- Reusable Sidebar Component for Tickets --}}
<div class="left-part border-end w-20 flex-shrink-0 d-none d-lg-block">
    {{-- New Ticket Button --}}
    <div class="px-9 pt-4 pb-3">
        <a href="{{ route('manager.helpdesk.tickets.create') }}" class="btn btn-primary fw-semibold py-8 w-100">
            <i class="fas fa-plus me-2"></i> Nuevo Ticket
        </a>
    </div>

    {{-- Sidebar Menu --}}
    <ul class="list-group mh-n100" data-simplebar>
        {{-- VISTAS Section --}}
        <li class="border-bottom my-3"></li>
        <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
            VISTAS
        </li>
        @forelse($views ?? [] as $view)
            <li class="list-group-item border-0 p-0 mx-9">
                <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                   href="{{ route('manager.helpdesk.tickets.index', ['viewId' => $view->id]) }}">
                    <i class="{{ $view->icon ?? 'fas fa-eye' }} fs-5"></i>{{ $view->name }}
                </a>
            </li>
        @empty
            <li class="list-group-item border-0 p-0 mx-9">
                <span class="text-muted px-3 py-2 d-block">Sin vistas</span>
            </li>
        @endforelse

        {{-- CATEGORÍAS Section --}}
        <li class="border-bottom my-3"></li>
        <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
            CATEGORÍAS
        </li>
        @forelse($categories ?? [] as $category)
            <li class="list-group-item border-0 p-0 mx-9">
                <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                   href="{{ route('manager.helpdesk.tickets.index', ['category' => $category->id]) }}">
                    <i class="{{ $category->icon ?? 'fas fa-tag' }} fs-5" style="color: {{ $category->color ?? '#90bb13' }}"></i>{{ $category->name }}
                </a>
            </li>
        @empty
            <li class="list-group-item border-0 p-0 mx-9">
                <span class="text-muted px-3 py-2 d-block">Sin categorías</span>
            </li>
        @endforelse

        {{-- GRUPOS Section --}}
        <li class="border-bottom my-3"></li>
        <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
            GRUPOS
        </li>
        @forelse($groups ?? [] as $group)
            <li class="list-group-item border-0 p-0 mx-9">
                <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1"
                   href="{{ route('manager.helpdesk.tickets.index', ['group' => $group->id]) }}">
                    <i class="fas fa-users-group fs-5"></i>{{ $group->name }}
                </a>
            </li>
        @empty
            <li class="list-group-item border-0 p-0 mx-9">
                <span class="text-muted px-3 py-2 d-block">Sin grupos</span>
            </li>
        @endforelse
    </ul>
</div>
