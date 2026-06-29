{{-- Tabla de tickets (vista lista). El tbody se renderiza desde JS leyendo data-tickets. --}}
<div id="htk-list-wrap" class="htk-list-wrap">
    <table class="htk-table">
        <thead>
            <tr>
                <th class="ps-3" style="width:32px">
                    <input type="checkbox" class="htk-checkbox" id="htk-select-all" />
                </th>
                <th>ID</th>
                <th>Asunto</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Prioridad</th>
                <th>SLA</th>
                <th class="text-center">Asign.</th>
                <th>Actualizado</th>
            </tr>
        </thead>
        <tbody id="htk-tbody"></tbody>
    </table>
</div>
