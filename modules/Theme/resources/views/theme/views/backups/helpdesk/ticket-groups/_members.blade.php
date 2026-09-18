{{--
    Miembros del grupo, compartido por create y edit.

    Mismo patrón que el modal de características de Suppliers: un buscador que
    añade la fila al seleccionar (sin botón "Agregar" aparte) y una tabla con lo
    ya añadido. Antes era una pila de filas sueltas con el select de prioridad
    flotando al lado del nombre, sin cabeceras que dijeran qué era cada control.

    Espera:
      $users     — colección de agentes asignables (CatalogCacheService::agents())
      $members   — array de ['id' => int, 'priority' => 'primary'|'backup'] ya en el grupo

    La columna del pivot (helpdesk_group_user) se llama conversation_priority,
    no priority: la comparte con el reparto de conversaciones. El nombre corto
    se mantiene aqui dentro por legibilidad; la traduccion la hace la vista que
    incluye este partial.
--}}
@php
    $members = $members ?? [];
    $memberIds = array_column($members, 'id');
@endphp

<h6 class="fw-semibold mb-1">Miembros</h6>
<p class="text-muted small mb-3">Usuarios que pueden recibir tickets asignados a este grupo</p>

<div class="row g-3 mb-4">
    <div class="col-12">

        <p class="text-muted small mb-2">Busca y elige un usuario para anadirlo a la lista.</p>
        <select id="member-picker" class="w-100">
            <option value=""></option>
            @foreach($users as $user)
                <option value="{{ $user->id }}"
                        data-name="{{ trim($user->firstname.' '.$user->lastname) }}"
                        @disabled(in_array($user->id, $memberIds))>
                    {{ trim($user->firstname.' '.$user->lastname) }}
                </option>
            @endforeach
        </select>

        <div class="table-responsive mt-2">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-2">Usuario</th>
                        <th class="py-2">Prioridad</th>
                        <th class="py-2 text-end">Accion</th>
                    </tr>
                </thead>
                <tbody id="members-container">
                    @forelse($members as $member)
                        @php $user = $users->firstWhere('id', $member['id']); @endphp
                        @continue(! $user)
                        <tr class="member-row" data-user-id="{{ $user->id }}">
                            <td class="py-2">
                                <input type="hidden" name="users[]" value="{{ $user->id }}">
                                <span class="fw-semibold">{{ trim($user->firstname.' '.$user->lastname) }}</span>
                            </td>
                            <td class="py-2">
                                <select name="user_priorities[]" class="form-select form-select-sm member-priority">
                                    <option value="primary" @selected(($member['priority'] ?? 'primary') === 'primary')>Principal</option>
                                    <option value="backup" @selected(($member['priority'] ?? 'primary') === 'backup')>Respaldo</option>
                                </select>
                            </td>
                            <td class="py-2 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-member" title="Quitar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="members-empty">
                            <td colspan="3" class="text-muted small">Todavia no hay miembros anadidos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @error('users')
            <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
        @enderror
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    const $picker = $('#member-picker');

    $picker.select2({
        width: '100%',
        placeholder: 'Buscar usuario...',
        allowClear: true,
    });

    // Clase propia y no '.select2': el selector global de create/edit se
    // aplica sobre 'select.select2' y volveria a inicializar estas filas,
    // duplicando el widget. Sin buscador: solo hay dos opciones.
    function initPriority($select) {
        $select.select2({ width: '100%', minimumResultsForSearch: Infinity });
    }

    function refreshEmptyRow() {
        const hasRows = $('#members-container .member-row').length > 0;
        $('#members-empty').toggle(! hasRows);
    }

    // Se anade al elegir en el buscador, como en el modal de caracteristicas:
    // el boton "Agregar" era un segundo paso que no aportaba nada.
    $picker.on('select2:select', function (e) {
        const userId = e.params.data.id;
        if (! userId) {
            return;
        }

        const name = $picker.find('option[value="' + userId + '"]').data('name');

        $('#members-empty').remove();

        $('#members-container').append(
            '<tr class="member-row" data-user-id="' + userId + '">'
            + '<td class="py-2">'
            + '<input type="hidden" name="users[]" value="' + userId + '">'
            + '<span class="fw-semibold"></span>'
            + '</td>'
            + '<td class="py-2">'
            + '<select name="user_priorities[]" class="form-select form-select-sm member-priority">'
            + '<option value="primary">Principal</option>'
            + '<option value="backup">Respaldo</option>'
            + '</select>'
            + '</td>'
            + '<td class="py-2 text-end">'
            + '<button type="button" class="btn btn-sm btn-outline-danger remove-member" title="Quitar">'
            + '<i class="fas fa-trash"></i>'
            + '</button>'
            + '</td>'
            + '</tr>'
        );

        // .text() y no interpolar en el HTML: el nombre viene de la base y
        // podria traer comillas o marcado.
        const $row = $('#members-container .member-row').last();
        $row.find('span.fw-semibold').text(name);
        initPriority($row.find('.member-priority'));

        // Un usuario ya anadido no puede volver a elegirse: se desactiva en el
        // buscador en lugar de avisar despues con un toast.
        $picker.find('option[value="' + userId + '"]').prop('disabled', true);
        $picker.val('').trigger('change');
    });

    $(document).on('click', '.remove-member', function () {
        const $row = $(this).closest('.member-row');
        $picker.find('option[value="' + $row.data('user-id') + '"]').prop('disabled', false);
        $row.remove();
        refreshEmptyRow();
    });

    initPriority($('#members-container .member-priority'));
    refreshEmptyRow();
});
</script>
@endpush
