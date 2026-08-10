{{-- Modal de filtros avanzados — mismo design system (.tkt-modal-*) que el
     resto de modales de la pantalla (Aplazar/Fusionar/Vincular/etc.), en vez
     del modal Bootstrap crudo que quedaba desentonando. Sigue siendo un
     <form method="get"> normal (navega con querystring), solo cambia el
     envoltorio visual — no se generó con openModal() porque los <option
     selected> ya vienen resueltos por el servidor. --}}
<div class="tkt-modal-backdrop" id="tkt-filters-modal-backdrop">
    <div class="tkt-modal w-md">
        <form method="get" id="htk-filters-form" action="{{ route('manager.helpdesk.tickets.index') }}">
            <div class="tkt-modal-head">
                <div class="tkt-modal-icon"><i class="fa-solid fa-sliders"></i></div>
                <div style="flex:1;min-width:0">
                    <div class="tkt-modal-title">Filtros avanzados</div>
                </div>
                <button type="button" class="tkt-modal-close" id="tkt-filters-modal-close"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="tkt-modal-body">
                <div class="tkt-field">
                    <label class="tkt-label" for="htk-f-search">Búsqueda</label>
                    <input type="search" id="htk-f-search" name="search" class="tkt-input"
                           value="{{ request('search') }}"
                           placeholder="ID, asunto o mensaje…"/>
                </div>

                <div class="tkt-field-row">
                    <div class="tkt-field">
                        <label class="tkt-label" for="htk-f-status">Estado</label>
                        <select id="htk-f-status" name="status" class="tkt-select">
                            <option value="">Todos</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st->id }}" @selected(request('status') == $st->id)>
                                    {{ $st->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tkt-field">
                        <label class="tkt-label" for="htk-f-priority">Prioridad</label>
                        <select id="htk-f-priority" name="priority" class="tkt-select">
                            <option value="">Todas</option>
                            <option value="urgent" @selected(request('priority') === 'urgent')>Urgente</option>
                            <option value="high" @selected(request('priority') === 'high')>Alta</option>
                            <option value="normal" @selected(request('priority') === 'normal')>Normal</option>
                            <option value="low" @selected(request('priority') === 'low')>Baja</option>
                        </select>
                    </div>
                </div>

                <div class="tkt-field-row">
                    <div class="tkt-field">
                        <label class="tkt-label" for="htk-f-category">Categoría</label>
                        <select id="htk-f-category" name="category" class="tkt-select">
                            <option value="">Todas</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tkt-field">
                        <label class="tkt-label" for="htk-f-group">Grupo</label>
                        <select id="htk-f-group" name="group" class="tkt-select">
                            <option value="">Todos</option>
                            @foreach($groups as $grp)
                                <option value="{{ $grp->id }}" @selected(request('group') == $grp->id)>
                                    {{ $grp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="tkt-field">
                    <label class="tkt-label" for="htk-f-assignee">Agente asignado</label>
                    <select id="htk-f-assignee" name="assignee" class="tkt-select">
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

                <div class="tkt-field-row">
                    <div class="tkt-field">
                        <label class="tkt-label" for="htk-f-source">Canal</label>
                        <select id="htk-f-source" name="source" class="tkt-select">
                            <option value="">Todos</option>
                            <option value="email" @selected(request('source') === 'email')>Email</option>
                            <option value="widget" @selected(request('source') === 'widget')>Widget</option>
                            <option value="wa" @selected(request('source') === 'wa')>WhatsApp</option>
                            <option value="fb" @selected(request('source') === 'fb')>Facebook</option>
                            <option value="ig" @selected(request('source') === 'ig')>Instagram</option>
                            <option value="agent" @selected(request('source') === 'agent')>Agente</option>
                            <option value="formulario" @selected(request('source') === 'formulario')>Formulario</option>
                        </select>
                    </div>
                    <div class="tkt-field">
                        <label class="tkt-label" for="htk-f-sla">Estado SLA</label>
                        <select id="htk-f-sla" name="sla_status" class="tkt-select">
                            <option value="">Todos</option>
                            <option value="ok" @selected(request('sla_status') === 'ok')>Dentro del SLA</option>
                            <option value="warn" @selected(request('sla_status') === 'warn')>Próximo a vencer</option>
                            <option value="breach" @selected(request('sla_status') === 'breach')>Vencido</option>
                        </select>
                    </div>
                </div>

                <div class="tkt-field">
                    <label class="tkt-label" for="htk-f-tags">Etiquetas <span class="hint">separadas por coma</span></label>
                    <input type="text" id="htk-f-tags" name="tag" class="tkt-input"
                           value="{{ request('tag') }}"
                           placeholder="Ej: vip, reembolso"/>
                </div>

                <div class="tkt-field-row">
                    <div class="tkt-field">
                        <label class="tkt-label" for="htk-f-created-from">Creado desde</label>
                        <input type="date" id="htk-f-created-from" name="created_from" class="tkt-input"
                               value="{{ request('created_from') }}"/>
                    </div>
                    <div class="tkt-field">
                        <label class="tkt-label" for="htk-f-created-to">Creado hasta</label>
                        <input type="date" id="htk-f-created-to" name="created_to" class="tkt-input"
                               value="{{ request('created_to') }}"/>
                    </div>
                </div>

                <label class="tkt-check">
                    <input type="checkbox" id="htk-f-archived" name="archived" value="1" @checked(request('archived'))>
                    Incluir tickets archivados
                </label>
            </div>

            <div class="tkt-modal-foot">
                <button type="submit" class="tkt-btn tkt-btn-primary"><i class="fa-solid fa-check"></i> Aplicar filtros</button>
                <a href="{{ route('manager.helpdesk.tickets.index') }}" class="tkt-btn">Limpiar</a>
            </div>
        </form>
    </div>
</div>
