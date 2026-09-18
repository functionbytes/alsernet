{{-- Tabla reutilizable de plantillas — generales y personales comparten el mismo layout.
     $canManage decide el checkbox de selección masiva y Editar/Eliminar; "Duplicar como
     mía" no depende de él — cualquier fila que aparece aquí ya es visible para el
     usuario (TicketTemplatePolicy::view()), así que se puede duplicar siempre. --}}
@if($templates->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    @if($canManage)
                        <th width="3%"><input type="checkbox" id="select-all-{{ $group }}" class="form-check-input" aria-label="Seleccionar todas las plantillas"></th>
                    @endif
                    <th>Nombre</th>
                    <th>Asunto</th>
                    <th>Categoría</th>
                    <th>Prioridad</th>
                    <th class="text-center">Estado</th>
                    {{-- Ya no depende de $canManage: "Duplicar" (dentro del
                         dropdown) está disponible para cualquier plantilla
                         visible aquí, tenga o no el usuario permiso para
                         editar/borrar la original — es justo lo que permite
                         partir de una general sin ese permiso. --}}
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $template)
                    <tr>
                        @if($canManage)
                            <td><input type="checkbox" class="form-check-input bulk-checkbox-{{ $group }}" value="{{ $template->id }}" aria-label="Seleccionar plantilla {{ $template->name }}"></td>
                        @endif
                        <td>
                            <div class="fw-semibold">{{ $template->name }}</div>
                            @if($template->description)
                                <small class="text-muted">{{ $template->description }}</small>
                            @endif
                        </td>
                        <td>
                            <small>{{ $template->subject }}</small>
                        </td>
                        <td>
                            @if($template->category)
                                <span class="badge bg-light text-dark border">{{ $template->category->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $priorityLabels = ['low' => 'Baja', 'normal' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente'];
                            @endphp
                            @if($template->priority && isset($priorityLabels[$template->priority]))
                                <span class="badge bg-light text-dark border">{{ $priorityLabels[$template->priority] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($template->is_active)
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <form method="POST" action="{{ route('manager.helpdesk.ticket-templates.duplicate', $template->id) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                Duplicar como mía
                                            </button>
                                        </form>
                                    </li>
                                    @if($canManage)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('manager.helpdesk.ticket-templates.edit', $template->id) }}">
                                                Editar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item delete-btn" href="#"
                                               data-bs-toggle="modal"
                                               data-bs-target="#delete-modal"
                                               data-url="{{ route('manager.helpdesk.ticket-templates.destroy', $template->id) }}"
                                               data-title="Eliminar plantilla: {{ $template->name }}">
                                                Eliminar
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($templates->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="text-muted small">
                Mostrando {{ $templates->firstItem() }} - {{ $templates->lastItem() }} de {{ $templates->total() }}
            </div>
            <div>
                {{ $templates->appends(request()->input())->links() }}
            </div>
        </div>
    @endif
@else
    <div class="text-center py-5">
        <i class="fas fa-file-alt fa-3x mb-3 text-muted opacity-50"></i>
        <h5 class="fw-bold mb-2">No hay plantillas aqui</h5>
        <p class="text-muted mb-4">Crea una plantilla para agilizar la creacion de tickets</p>
        <a href="{{ route('manager.helpdesk.ticket-templates.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva plantilla
        </a>
    </div>
@endif
