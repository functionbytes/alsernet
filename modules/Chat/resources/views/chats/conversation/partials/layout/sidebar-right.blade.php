{{-- Variables: $conversation, $statuses, $teams, $users, $priorities, $labelsByTitle, $macros --}}
<div class="app-chat-offcanvas border-start d-flex flex-column">
    <!-- Cabecera del sidebar -->
    <div class="p-9 py-3 border-bottom chat-meta-user chat-active flex-shrink-0">
        <h5 class="text-dark mb-0 fs-5">Detalles del contacto</h5>
    </div>

    <!-- Contenido del sidebar -->
    <div class="flex-grow-1 overflow-y-auto">
        <div class="chat-box email-box customer-box p-9">

            <!-- Informacion del cliente -->
            <div class="d-flex align-items-center gap-3 mb-3">
                @if($conversation->customer?->avatar_url)
                    <img src="{{ $conversation->customer->avatar_url }}"
                         alt="{{ $conversation->customer->name }}"
                         width="56"
                         height="56"
                         class="rounded-circle">
                @else
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                         style="width: 56px; height: 56px; font-size: 24px;">
                        {{ strtoupper(substr($conversation->customer?->name ?? 'C', 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h6 class="fw-semibold mb-0">{{ $conversation->customer?->name ?? 'Cliente desconocido' }}</h6>
                    <p class="mb-0 small text-muted">Cliente</p>
                </div>
            </div>

            @if($conversation->customer?->email)
                <p class="mb-2">
                    <small><strong>Email:</strong></small><br>
                    <small class="text-muted">{{ $conversation->customer->email }}</small>
                </p>
            @endif

            @if($conversation->customer?->phone_number)
                <p class="mb-3">
                    <small><strong>Telefono:</strong></small><br>
                    <small class="text-muted">{{ $conversation->customer->phone_number }}</small>
                </p>
            @endif

            <div class="d-flex gap-2">
                @if($conversation->customer?->id)
                    <button class="btn btn-sm btn-primary" onclick="window.location.href='{{ route('chat.customers.edit', $conversation->customer->id) }}'">
                        <i class="fas fa-pencil"></i> Editar
                    </button>
                @endif
            </div>

            <hr class="my-3">

            <!-- Detalles de la conversacion -->
            <div>
                <h6 class="mb-3">Detalles de Conversacion</h6>

                <div class="mb-2">
                    <strong class="d-block small mb-1">Canal:</strong>
                    <span class="badge bg-info">{{ $conversation->inbox?->name ?? 'Sin canal' }}</span>
                </div>
                <div class="mb-2">
                    <strong class="d-block small mb-1">Estado:</strong>
                    @if($conversation->status)
                        <span class="badge bg-info">{{ $conversation->status->name }}</span>
                    @else
                        <span class="badge bg-secondary">Sin estado</span>
                    @endif
                </div>
                <div class="mb-2">
                    <strong class="d-block small mb-1">Prioridad:</strong>
                    @if($conversation->priority)
                        <span class="badge bg-info">{{ $conversation->priority->name }}</span>
                    @else
                        <span class="badge bg-secondary">Sin prioridad</span>
                    @endif
                </div>
                <div class="mb-2">
                    <strong class="d-block small mb-1">Creada:</strong>
                    <p class="text-muted">{{ $conversation->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div>
                    <strong class="d-block small mb-1">Ultima actividad:</strong>
                    <p class="text-muted">{{ $conversation->last_activity_at?->diffForHumans() ?? 'N/A' }}</p>
                </div>
            </div>

            <hr class="my-4">

            <!-- Informacion de sesion -->
            @if($conversation->conversationSession)
                <div>
                    <h6 class="mb-3">Informacion de sesion</h6>

                    <div class="mb-2">
                        <strong class="d-block small mb-1">Token de sesion:</strong>
                        <p class="text-muted">{{ substr($conversation->conversationSession->token, 0, 16) }}...</p>
                    </div>

                    @if($conversation->conversationSession->session_id)
                        <div class="mb-2">
                            <strong class="d-block small mb-1">ID de sesion:</strong>
                            <p class="text-muted">{{ $conversation->conversationSession->session_id }}</p>
                        </div>
                    @endif

                    <div class="mb-2">
                        <strong class="d-block small mb-1">Estado de sesion:</strong>
                        @if($conversation->conversationSession->active)
                            <span class="badge bg-success">Activa</span>
                        @else
                            <span class="badge bg-secondary">Inactiva</span>
                        @endif
                    </div>

                    <div class="mb-2">
                        <strong class="d-block small mb-1">Ultima actividad:</strong>
                        <small class="text-muted">{{ $conversation->conversationSession->last_activity_at?->diffForHumans() ?? 'N/A' }}</small>
                    </div>

                    @if($conversation->conversationSession->session_data && is_array($conversation->conversationSession->session_data) && count($conversation->conversationSession->session_data) > 0)
                        <div class="mb-2">
                            <strong class="d-block small mb-1">Datos de sesion:</strong>
                            <div class="p-2 mt-1">
                                @foreach($conversation->conversationSession->session_data as $key => $value)
                                    <div class="small">
                                        <span class="text-primary fw-semibold">{{ ucfirst($key) }}:</span>
                                        <span class="text-muted">
                                            @if(is_array($value))
                                                {{ json_encode($value) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <hr class="my-4">
            @endif

            <!-- Acciones rapidas -->
            <div>
                <h6 class="mb-4 fw-semibold">Acciones rapidas</h6>

                <!-- Cambiar estado -->
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-2">Estado</label>
                    <select name="status" class="form-control select2 select2">
                        <option value="">Seleccionar estado</option>
                        @if(isset($statuses))
                            @foreach($statuses as $status)
                                <option value="{{ $status->slug }}" {{ $conversation->status?->id === $status->id ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Asignar equipo -->
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-2">Equipo</label>
                    <select name="team_id" class="form-control select2 select2">
                        <option value="">Sin equipo</option>
                        @if(isset($teams) && count($teams) > 0)
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" {{ $conversation->team_id === $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Asignar agente -->
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-2">Asignar a</label>
                    <select name="assignee_id" class="form-control select2 select2">
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $conversation->assignee_id === $user->id ? 'selected' : '' }}>
                                {{ $user->firstname }} {{ $user->lastname }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Actualizar prioridad -->
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-2">Prioridad</label>
                    <select name="priority" class="form-control select2 select2">
                        <option value="">Seleccionar prioridad</option>
                        @if(isset($priorities))
                            @foreach($priorities as $priority)
                                <option value="{{ $priority->slug }}" {{ $conversation->priority?->id === $priority->id ? 'selected' : '' }}>
                                    {{ $priority->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Etiquetas -->
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-2">Etiquetas</label>
                    @php
                        $conversationLabelsList = $conversation->cached_label_list ? explode(',', $conversation->cached_label_list) : [];
                    @endphp
                    <select name="labels[]" class="form-control select2 select2" multiple data-placeholder="Seleccionar etiquetas...">
                        @foreach($labelsByTitle as $label)
                            <option value="{{ $label->name }}"
                                    {{ in_array($label->name, $conversationLabelsList) ? 'selected' : '' }}
                                    data-color="{{ $label->color ?? '#6c757d' }}">
                                {{ $label->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Macros -->
                @if($macros && count($macros) > 0)
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2">Macros</label>
                        <select class="form-control select2 select2" id="macro-selector" data-placeholder="Seleccionar macro...">
                            <option value="">Seleccionar macro...</option>
                            @foreach($macros as $macro)
                                <option value="{{ $macro->id }}">{{ $macro->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
