import React from 'react';
import { NodeConfigProps } from '../types';
import { labelStyle } from '../components/styles';

export default function EndConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Acción al finalizar</label>
                <select className="form-select form-select-sm"
                    value={d.action || 'close'}
                    onChange={e => setData({ action: e.target.value })}>
                    <option value="close">Cerrar conversación</option>
                    <option value="transfer_to_agent">Transferir a agente</option>
                </select>
            </div>
            <label style={labelStyle}>Mensaje de despedida</label>
            <textarea className="form-control form-control-sm" rows={3}
                value={d.farewell || ''}
                placeholder="Opcional: mensaje final al cliente"
                onChange={e => setData({ farewell: e.target.value })} />
        </div>
    );
}
