import React from 'react';
import { NodeConfigProps } from '../types';
import { labelStyle } from '../components/styles';

export default function ActionConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <label style={labelStyle}>Acción a ejecutar</label>
            <select className="form-select form-select-sm"
                value={d.action_type || 'assign_agent'}
                onChange={e => setData({ action_type: e.target.value })}>
                <option value="assign_agent">Asignar agente</option>
                <option value="change_status">Cambiar estado</option>
                <option value="add_tag">Agregar etiqueta</option>
            </select>
        </div>
    );
}
