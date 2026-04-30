{{-- Modal de filtros avanzados (Bootstrap modal centrado) --}}
<div class="modal fade htk-filters-modal" id="htk-filters-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="get" id="htk-filters-form" action="{{ route('manager.helpdesk.tickets.index') }}">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-filter me-2 text-muted"></i>Filtros avanzados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="filter-group">
                        <label for="htk-f-search">Búsqueda</label>
                        <input type="search" id="htk-f-search" name="search" class="form-control"
                               value="{{ request('search') }}"
                               placeholder="ID, asunto o mensaje…"/>
                    </div>

                    <div class="filter-group">
                        <label for="htk-f-status">Estado</label>
                        <select id="htk-f-status" name="status" class="form-select">
                            <option value="">Todos</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st->id }}" @selected(request('status') == $st->id)>
                                    {{ $st->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="htk-f-priority">Prioridad</label>
                        <select id="htk-f-priority" name="priority" class="form-select">
                            <option value="">Todas</option>
                            <option value="urgent" @selected(request('priority') === 'urgent')>Urgente</option>
                            <option value="high" @selected(request('priority') === 'high')>Alta</option>
                            <option value="normal" @selected(request('priority') === 'normal')>Normal</option>
                            <option value="low" @selected(request('priority') === 'low')>Baja</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="htk-f-category">Categoría</label>
                        <select id="htk-f-category" name="category" class="form-select">
                            <option value="">Todas</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="htk-f-group">Grupo</label>
                        <select id="htk-f-group" name="group" class="form-select">
                            <option value="">Todos</option>
                            @foreach($groups as $grp)
                                <option value="{{ $grp->id }}" @selected(request('group') == $grp->id)>
                                    {{ $grp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="htk-f-assignee">Agente asignado</label>
                        <select id="htk-f-assignee" name="assignee" class="form-select">
                            <option value="">Todos</option>
                            <option value="me" @selected(request('assignee') === 'me')>Asignados a mí</option>
                            <option value="unassigned" @selected(request('assignee') === 'unassigned')>Sin asignar</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" @selected(request('assignee') == $agent->id)>
                                    {{ trim($agent->firstname.' '.$agent->lastname) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="htk-f-source">Canal</label>
                        <select id="htk-f-source" name="source" class="form-select">
                            <option value="">Todos</option>
                            <option value="email" @selected(request('source') === 'email')>Email</option>
                            <option value="widget" @selected(request('source') === 'widget')>Widget</option>
                            <option value="wa" @selected(request('source') === 'wa')>WhatsApp</option>
                            <option value="fb" @selected(request('source') === 'fb')>Facebook</option>
                            <option value="ig" @selected(request('source') === 'ig')>Instagram</option>
                            <option value="agent" @selected(request('source') === 'agent')>Agente</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="htk-f-sla">Estado SLA</label>
                        <select id="htk-f-sla" name="sla_status" class="form-select">
                            <option value="">Todos</option>
                            <option value="ok" @selected(request('sla_status') === 'ok')>Dentro del SLA</option>
                            <option value="warn" @selected(request('sla_status') === 'warn')>Próximo a vencer</option>
                            <option value="breach" @selected(request('sla_status') === 'breach')>Vencido</option>
                        </select>
                    </div>

                    <div class="filter-group form-check">
                        <input type="checkbox" id="htk-f-archived" name="archived" value="1"
                               class="form-check-input" @checked(request('archived'))>
                        <label class="form-check-label" for="htk-f-archived">
                            Incluir tickets archivados
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <div class="d-flex flex-column w-100 gap-2">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-check me-2"></i>Aplicar filtros
                        </button>
                        <a href="{{ route('manager.helpdesk.tickets.index') }}" class="btn btn-light w-100">
                            <i class="fas fa-rotate-left me-2"></i>Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
