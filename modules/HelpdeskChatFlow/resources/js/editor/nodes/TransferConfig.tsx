import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function TransferConfig({ draft, agents, groups, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Mensaje de transferencia</label>
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.message || 'Un momento, te transfiero con un agente.'}
                    onChange={e => setData({ message: e.target.value })} />
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Asignar a un grupo</label>
                <select className="form-select form-select-sm"
                    value={d.group_id || ''}
                    onChange={e => setData({ group_id: e.target.value ? parseInt(e.target.value) : null })}>
                    <option value="">Cola general (sin grupo)</option>
                    {groups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}
                </select>
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Asignar a un agente</label>
                <select className="form-select form-select-sm"
                    value={d.assignee_id || ''}
                    onChange={e => setData({ assignee_id: e.target.value ? parseInt(e.target.value) : null })}>
                    <option value="">Sin agente específico</option>
                    {agents.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
                </select>
            </div>
            <p style={hintStyle}>Puedes asignar a un grupo, a un agente concreto, o dejar al cliente en la cola general.</p>
        </div>
    );
}
