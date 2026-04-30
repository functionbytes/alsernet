{{-- Page bar: título, contador y acciones principales --}}
<div class="htk-page-bar">
    <div>
        <h2>Tickets</h2>
        <div class="sub">
            Gestiona incidencias, asígnalas y monitorea SLA ·
            <span id="htk-count" class="mono">{{ $tickets->total() }}</span> resultados
        </div>
    </div>
    <div class="right">
        <div class="htk-seg" role="group" aria-label="Cambiar vista">
            <button type="button" class="htk-view-btn active" data-view="list">
                <i class="fas fa-list"></i> Lista
            </button>
            <button type="button" class="htk-view-btn" data-view="kanban">
                <i class="fas fa-columns"></i> Kanban
            </button>
        </div>

        <button type="button" class="htk-btn htk-btn-outline htk-btn-sm"
                data-bs-toggle="modal" data-bs-target="#htk-filters-modal">
            <i class="fas fa-filter"></i> Filtros
        </button>

        <div class="dropdown">
            <button type="button" class="htk-btn htk-btn-outline htk-btn-sm dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download"></i> Exportar
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item"
                       href="{{ route('manager.helpdesk.tickets.export', ['format' => 'csv'] + request()->only(['search', 'status', 'category'])) }}">
                        <i class="fas fa-file-csv me-2"></i>CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item"
                       href="{{ route('manager.helpdesk.tickets.export', ['format' => 'pdf'] + request()->only(['search', 'status', 'category'])) }}">
                        <i class="fas fa-file-pdf me-2"></i>PDF
                    </a>
                </li>
            </ul>
        </div>

        @can('helpdesk.tickets.create')
            <a href="{{ route('manager.helpdesk.tickets.create') }}" class="htk-btn htk-btn-primary htk-btn-sm">
                <i class="fas fa-plus"></i> Nuevo ticket
            </a>
        @endcan
    </div>
</div>
