import React from 'react';
import { Condition, NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

type ValueKind = 'none' | 'text' | 'number' | 'range' | 'list' | 'regex';

// The 15 operators of EvaluatesBranchConditions (backend), in the order shown.
export const CONDITION_OPERATORS: { value: string; label: string; kind: ValueKind }[] = [
    { value: '=', label: 'es igual a', kind: 'text' },
    { value: '!=', label: 'no es igual a', kind: 'text' },
    { value: '>', label: 'es mayor que', kind: 'number' },
    { value: '>=', label: 'es mayor o igual que', kind: 'number' },
    { value: '<', label: 'es menor que', kind: 'number' },
    { value: '<=', label: 'es menor o igual que', kind: 'number' },
    { value: 'between', label: 'está entre', kind: 'range' },
    { value: 'contains', label: 'contiene', kind: 'text' },
    { value: 'starts_with', label: 'empieza por', kind: 'text' },
    { value: 'ends_with', label: 'termina en', kind: 'text' },
    { value: 'in', label: 'es uno de', kind: 'list' },
    { value: 'not_in', label: 'no es ninguno de', kind: 'list' },
    { value: 'is_empty', label: 'está vacío', kind: 'none' },
    { value: 'not_empty', label: 'no está vacío', kind: 'none' },
    { value: 'regex', label: 'cumple la expresión regular', kind: 'regex' },
];

const kindOf = (operator: string): ValueKind =>
    CONDITION_OPERATORS.find(o => o.value === operator)?.kind ?? 'text';

const asText = (value: Condition['value'] | undefined): string =>
    Array.isArray(value) ? value.join(', ') : (value ?? '');

const asPair = (value: Condition['value'] | undefined): [string, string] => {
    const parts = Array.isArray(value) ? value : String(value ?? '').split(',').map(p => p.trim());
    return [parts[0] ?? '', parts[1] ?? ''];
};

// Value that suits an operator when it changes: lists and ranges are arrays,
// everything else a string, and valueless operators drop it.
function valueFor(operator: string, previous: Condition['value'] | undefined): Condition['value'] {
    switch (kindOf(operator)) {
        case 'none': return '';
        case 'range': return asPair(previous);
        case 'list': return asText(previous).split(',').map(v => v.trim()).filter(Boolean);
        default: return asText(previous);
    }
}

function ValueField({ cond, onChange }: { cond: Condition; onChange: (value: Condition['value']) => void }) {
    switch (kindOf(cond.operator)) {
        case 'none':
            return <div style={{ ...hintStyle, marginTop: 0, alignSelf: 'center' }}>No necesita valor</div>;
        case 'range': {
            const [min, max] = asPair(cond.value);
            return (
                <div className="d-flex gap-1">
                    <input type="number" className="form-control form-control-sm" placeholder="mín."
                        value={min} onChange={e => onChange([e.target.value, max])} />
                    <input type="number" className="form-control form-control-sm" placeholder="máx."
                        value={max} onChange={e => onChange([min, e.target.value])} />
                </div>
            );
        }
        case 'list':
            return (
                <input type="text" className="form-control form-control-sm" placeholder="valor1, valor2, valor3"
                    value={asText(cond.value)}
                    onChange={e => onChange(e.target.value.split(',').map(v => v.trim()))}
                    onBlur={e => onChange(e.target.value.split(',').map(v => v.trim()).filter(Boolean))} />
            );
        case 'number':
            return (
                <input type="number" className="form-control form-control-sm" placeholder="número"
                    value={asText(cond.value)} onChange={e => onChange(e.target.value)} />
            );
        case 'regex':
            return (
                <input type="text" className="form-control form-control-sm" placeholder="^[A-Z]{9}$"
                    value={asText(cond.value)} onChange={e => onChange(e.target.value)} />
            );
        default:
            return (
                <input type="text" className="form-control form-control-sm" placeholder="valor"
                    value={asText(cond.value)} onChange={e => onChange(e.target.value)} />
            );
    }
}

export default function BranchItemConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    if (d.isElse) {
        return <div className="alert alert-secondary p-2" style={{ fontSize: 12 }}>Rama por defecto (Else). Se ejecuta si ninguna otra condición coincide.</div>;
    }
    const conditions: Condition[] = d.conditions || [];
    const match: 'all' | 'any' = d.match === 'any' ? 'any' : 'all';

    const updateCond = (i: number, patch: Partial<Condition>) => {
        setData({ conditions: conditions.map((c, idx) => idx === i ? { ...c, ...patch } : c) });
    };

    return (
        <div>
            <label style={labelStyle}>Cuándo se cumple esta rama</label>
            <select className="form-select form-select-sm mb-2"
                value={match} onChange={e => setData({ match: e.target.value })}>
                <option value="all">Todas las condiciones (Y)</option>
                <option value="any">Alguna de las condiciones (O)</option>
            </select>

            <label style={labelStyle}>Condiciones</label>
            {conditions.map((c, i) => (
                <div key={i} style={{ marginBottom: 8, paddingBottom: 8, borderBottom: '1px dashed #e2e8f0' }}>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 24px', gap: 4, marginBottom: 4 }}>
                        <input type="text" className="form-control form-control-sm" placeholder="variable (ej: pedido.estado)"
                            value={c.variable} onChange={e => updateCond(i, { variable: e.target.value })} />
                        <button style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: '0 2px' }}
                            title="Quitar condición"
                            onClick={() => setData({ conditions: conditions.filter((_, j) => j !== i) })}>
                            <i className="fas fa-times" />
                        </button>
                    </div>
                    <select className="form-select form-select-sm mb-1"
                        value={c.operator}
                        onChange={e => updateCond(i, { operator: e.target.value, value: valueFor(e.target.value, c.value) })}>
                        {CONDITION_OPERATORS.map(op => <option key={op.value} value={op.value}>{op.label}</option>)}
                    </select>
                    <ValueField cond={c} onChange={value => updateCond(i, { value })} />
                </div>
            ))}
            <button className="btn btn-sm btn-outline-primary mt-1"
                onClick={() => setData({ conditions: [...conditions, { variable: '', operator: '=', value: '' }] })}>
                <i className="fas fa-plus me-1" />Agregar condición
            </button>
            <p style={hintStyle}>
                La variable puede ser una ruta con puntos para leer datos guardados por una petición HTTP (p. ej. <code>pedido.estado</code>).
            </p>
        </div>
    );
}
