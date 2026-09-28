import React from 'react';
import { Condition, NodeConfigProps } from '../types';
import { labelStyle } from '../components/styles';

export default function BranchItemConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    if (d.isElse) {
        return <div className="alert alert-secondary p-2" style={{ fontSize: 12 }}>Rama por defecto (Else). Se ejecuta si ninguna otra condición coincide.</div>;
    }
    const conditions: Condition[] = d.conditions || [];
    const updateCond = (i: number, patch: Partial<Condition>) => {
        setData({ conditions: conditions.map((c, idx) => idx === i ? { ...c, ...patch } : c) });
    };
    return (
        <div>
            <label style={labelStyle}>Condiciones (todas deben cumplirse)</label>
            {conditions.map((c, i) => (
                <div key={i} style={{ display: 'grid', gridTemplateColumns: '1fr 80px 1fr 24px', gap: 4, marginBottom: 6 }}>
                    <input type="text" className="form-control form-control-sm" placeholder="variable"
                        value={c.variable} onChange={e => updateCond(i, { variable: e.target.value })} />
                    <select className="form-select form-select-sm"
                        value={c.operator} onChange={e => updateCond(i, { operator: e.target.value })}>
                        {['=', '!=', '>', '<', 'contains'].map(op => <option key={op}>{op}</option>)}
                    </select>
                    <input type="text" className="form-control form-control-sm" placeholder="valor"
                        value={c.value} onChange={e => updateCond(i, { value: e.target.value })} />
                    <button style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: '0 2px' }}
                        onClick={() => setData({ conditions: conditions.filter((_, j) => j !== i) })}>
                        <i className="fas fa-times" />
                    </button>
                </div>
            ))}
            <button className="btn btn-sm btn-outline-primary mt-1"
                onClick={() => setData({ conditions: [...conditions, { variable: '', operator: '=', value: '' }] })}>
                <i className="fas fa-plus me-1" />Agregar condición
            </button>
        </div>
    );
}
