<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Clave</th>
                <th>Formato</th>
                <th>Permiso</th>
                <th class="text-center">Requerido</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $attribute)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $attribute->name }}</div>
                        @if($attribute->description)
                            <small class="text-muted">{{ $attribute->description }}</small>
                        @endif
                    </td>
                    <td>
                        <code class="text-muted">{{ $attribute->key }}</code>
                    </td>
                    <td>
                        @php
                            $formatLabels = [
                                'text'          => ['label' => 'Texto',          'icon' => 'fas fa-font',           'color' => 'primary'],
                                'textarea'      => ['label' => 'Área de texto',  'icon' => 'far fa-rectangle-list', 'color' => 'info'],
                                'number'        => ['label' => 'Número',         'icon' => 'fas fa-hashtag',        'color' => 'success'],
                                'switch'        => ['label' => 'Interruptor',    'icon' => 'fas fa-toggle-on',      'color' => 'warning'],
                                'rating'        => ['label' => 'Calificación',   'icon' => 'fas fa-star',           'color' => 'danger'],
                                'select'        => ['label' => 'Selección',      'icon' => 'fas fa-list',           'color' => 'secondary'],
                                'checkboxGroup' => ['label' => 'Checkboxes',     'icon' => 'far fa-square-check',   'color' => 'dark'],
                                'date'          => ['label' => 'Fecha',          'icon' => 'far fa-calendar',       'color' => 'purple'],
                            ];
                            $fmt = $formatLabels[$attribute->format]
                                ?? ['label' => $attribute->format, 'icon' => 'far fa-circle-question', 'color' => 'muted'];
                        @endphp
                        <span class="badge bg-{{ $fmt['color'] }}-subtle text-{{ $fmt['color'] }}">
                            <i class="{{ $fmt['icon'] }}"></i> {{ $fmt['label'] }}
                        </span>
                    </td>
                    <td>
                        @php
                            $permLabels = [
                                'userCanView'  => ['label' => 'Usuario ver',    'color' => 'info'],
                                'userCanEdit'  => ['label' => 'Usuario editar', 'color' => 'success'],
                                'agentCanEdit' => ['label' => 'Agente editar',  'color' => 'primary'],
                            ];
                            $perm = $permLabels[$attribute->permission]
                                ?? ['label' => $attribute->permission, 'color' => 'muted'];
                        @endphp
                        <span class="badge bg-{{ $perm['color'] }}-subtle text-{{ $perm['color'] }}">
                            {{ $perm['label'] }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($attribute->required)
                            <span class="badge bg-light-subtle text-black">Sí</span>
                        @else
                            <span class="text-muted">No</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-inline-block">
                            <input type="checkbox"
                                   class="form-check-input attribute-toggle"
                                   role="switch"
                                   data-id="{{ $attribute->id }}"
                                   data-url="{{ route('settings.helpdesk.attributes.toggle', $attribute->id) }}"
                                   {{ $attribute->active ? 'checked' : '' }}>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="dropdown">
                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-vertical"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item"
                                       href="{{ route('settings.helpdesk.attributes.edit', $attribute->id) }}">
                                        Editar
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST"
                                          action="{{ route('settings.helpdesk.attributes.destroy', $attribute->id) }}"
                                          class="needs-confirm"
                                          data-confirm-msg="¿Estás seguro de eliminar este atributo? Esta acción no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item">
                                            Eliminar
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <i class="fas fa-cog fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted mb-2">No hay atributos creados</h5>
                        <p class="text-muted mb-4">Crea tu primer atributo personalizado para extender la funcionalidad</p>
                        <a href="{{ route('settings.helpdesk.attributes.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Crear primer atributo
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
