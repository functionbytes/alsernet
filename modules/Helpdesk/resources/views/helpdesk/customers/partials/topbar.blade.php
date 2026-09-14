<div class="bv-page-topbar">
    <div class="bv-brand">
        <div class="bv-brand-mark">{{ strtoupper(substr(config('app.name', 'H'), 0, 1)) }}</div>
        <span>{{ config('app.name') }}</span>
    </div>
    <div class="bv-crumb">
        <i class="fas fa-chevron-right bv-text-faded-10"></i>
        <a href="{{ route('manager.helpdesk.customers.index') }}" class="text-decoration-none bv-text-muted">Contactos</a>
        @if(request('search'))
            <i class="fas fa-chevron-right bv-text-faded-10"></i>
            <span class="current fw-semibold bv-text-strong">Resultados</span>
        @endif
    </div>

    {{-- Global search --}}
    <form method="GET" action="{{ route('manager.helpdesk.customers.index') }}" class="d-flex flex-grow-1 bv-max-w-420">
        <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">
        <div class="bv-list-search w-100">
            <i class="fas fa-magnifying-glass bv-text-12"></i>
            <input type="search" name="search" placeholder="Buscar contacto por nombre, email, teléfono…"
                   value="{{ request('search') }}" class="bv-input-bare">
        </div>
    </form>

    <a href="{{ route('manager.helpdesk.customers.create') }}" class="bv-th-pill text-decoration-none">
        <i class="fas fa-plus"></i> Nuevo contacto
    </a>
</div>
