{{-- Lista de conversaciones (Alvarez/Refined v4) --}}
<div class="bv-list">
    <div class="bv-list-head">
        <div class="bv-list-search">
            <i class="fas fa-magnifying-glass bv-list-search-icon"></i>
            <input type="text" id="bv-search-input" placeholder="Buscar conversaciones…" autocomplete="off">
            <kbd class="bv-list-search-kbd">F</kbd>
        </div>
        <div class="bv-list-actions">
            <button class="btn-ico" data-bv-modal="filter" title="Filtros">
                <i class="fas fa-filter"></i>
            </button>
            <div class="bv-sort-wrap">
                <button class="btn-ico" id="bv-btn-sort" title="Ordenar">
                    <i class="fas fa-arrow-up-arrow-down"></i>
                </button>
                <div class="bv-sort-menu" id="bv-sort-menu">
                    <div class="bv-sort-menu-head">Ordenar por</div>
                    <button class="bv-sort-opt on" data-sort="newest">
                        <i class="bv-sort-opt-ico fas fa-arrow-down-wide-short"></i>
                        <span class="bv-sort-opt-label">Más reciente</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                    <button class="bv-sort-opt" data-sort="oldest">
                        <i class="bv-sort-opt-ico fas fa-arrow-up-wide-short"></i>
                        <span class="bv-sort-opt-label">Más antiguo</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                    <button class="bv-sort-opt" data-sort="priority">
                        <i class="bv-sort-opt-ico fas fa-fire"></i>
                        <span class="bv-sort-opt-label">Prioridad</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                    <div class="bv-sort-menu-sep"></div>
                    <button class="bv-sort-opt" data-sort="unassigned">
                        <i class="bv-sort-opt-ico fas fa-user-slash"></i>
                        <span class="bv-sort-opt-label">Sin asignar</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                    <button class="bv-sort-opt" data-sort="unread">
                        <i class="bv-sort-opt-ico far fa-envelope"></i>
                        <span class="bv-sort-opt-label">Sin leer</span>
                        <i class="bv-sort-opt-check fas fa-check"></i>
                    </button>
                </div>
            </div>
            <button class="btn-ico" title="Más">
                <i class="fas fa-ellipsis"></i>
            </button>
        </div>
    </div>

    <div class="bv-conv-list" id="bv-conv-list">
        @php
            $groupLabels = [
                'today' => 'HOY',
                'yesterday' => 'AYER',
                'week' => 'ESTA SEMANA',
                'older' => 'ANTERIORES',
            ];
            $inboxGroups = $inboxGroups ?? collect();
            $hasAny = collect($inboxGroups)->flatten(1)->isNotEmpty();
        @endphp

        @foreach($groupLabels as $key => $label)
            @if(! empty($inboxGroups[$key]))
                <div class="bv-list-group-label">
                    <span>{{ $label }}</span>
                    <span class="c">{{ count($inboxGroups[$key]) }} conversaciones</span>
                </div>

                @foreach($inboxGroups[$key] as $conv)
                    @include('helpdesk::managers.inbox.partials.conv-item', ['conv' => $conv])
                @endforeach
            @endif
        @endforeach

        @unless($hasAny)
            <div class="bv-empty-state">
                <i class="far fa-inbox bv-empty-state-icon"></i>
                <div class="bv-empty-state-title">No hay conversaciones</div>
                <div class="bv-empty-state-sub">Inicia una nueva conversación o ajusta los filtros</div>
            </div>
        @endunless
    </div>
</div>
