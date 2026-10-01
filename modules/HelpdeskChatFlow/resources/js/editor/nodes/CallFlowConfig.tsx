import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';
import { useRemoteList } from '../hooks/useRemoteList';

interface Procedure { id: number; name: string; }

export default function CallFlowConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    const urls = window.__chatflowUrls;
    const url = urls?.procedures ? `${urls.procedures}?exclude=${urls.flowId}` : null;
    const { items: procedures, loading, failed } = useRemoteList<Procedure>(url);

    const input: [string, string][] = Object.entries(d.input || {}) as [string, string][];
    const output: string[] = d.output || [];
    const setInput = (rows: [string, string][]) => setData({ input: Object.fromEntries(rows) });

    return (
        <div>
            <label style={labelStyle}>Procedimiento</label>
            <select className="form-select form-select-sm"
                value={d.flow_id || ''}
                onChange={e => {
                    const proc = procedures.find(p => String(p.id) === e.target.value);
                    setData({ flow_id: e.target.value ? parseInt(e.target.value) : null, flow_name: proc?.name || '' });
                }}>
                <option value="">{loading ? 'Cargando…' : '— Seleccionar procedimiento —'}</option>
                {d.flow_id && !procedures.some(p => p.id === d.flow_id) && (
                    <option value={d.flow_id}>{d.flow_name || `Flow #${d.flow_id}`} (no disponible)</option>
                )}
                {procedures.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
            </select>
            {failed && <p style={{ ...hintStyle, color: '#dc2626' }}>No se pudo cargar la lista de procedimientos.</p>}
            {!loading && !failed && procedures.length === 0 && (
                <p style={hintStyle}>No hay procedimientos activos. Crea un flow con activación «Procedimiento» y publícalo.</p>
            )}

            <label style={{ ...labelStyle, marginTop: 10 }}>Datos que se le pasan</label>
            {input.map(([name, template], i) => (
                <div key={i} className="input-group input-group-sm mb-1">
                    <input type="text" className="form-control" placeholder="variable" value={name}
                        onChange={e => setInput(input.map((r, j) => j === i ? [e.target.value, r[1]] : r))} />
                    <input type="text" className="form-control" placeholder="{{valor}}" value={template}
                        onChange={e => setInput(input.map((r, j) => j === i ? [r[0], e.target.value] : r))} />
                    <button className="btn btn-outline-secondary"
                        onClick={() => setInput(input.filter((_, j) => j !== i))}>
                        <i className="fas fa-times" />
                    </button>
                </div>
            ))}
            <button className="btn btn-sm btn-outline-primary"
                onClick={() => setInput([...input, [`var${input.length + 1}`, '']])}>
                <i className="fas fa-plus me-1" />Dato
            </button>
            <p style={hintStyle}>Variable que verá el procedimiento ← valor (admite {'{{variables}}'}). Al volver se restauran, salvo las de salida.</p>

            <label style={{ ...labelStyle, marginTop: 10 }}>Variables que devuelve</label>
            {output.map((name, i) => (
                <div key={i} className="input-group input-group-sm mb-1">
                    <input type="text" className="form-control" value={name}
                        onChange={e => setData({ output: output.map((o, j) => j === i ? e.target.value : o) })} />
                    <button className="btn btn-outline-secondary"
                        onClick={() => setData({ output: output.filter((_, j) => j !== i) })}>
                        <i className="fas fa-times" />
                    </button>
                </div>
            ))}
            <button className="btn btn-sm btn-outline-primary"
                onClick={() => setData({ output: [...output, ''] })}>
                <i className="fas fa-plus me-1" />Variable de salida
            </button>

            <label style={{ ...labelStyle, marginTop: 10 }}>Si el procedimiento no se puede llamar</label>
            <select className="form-select form-select-sm"
                value={d.on_missing || 'continue'}
                onChange={e => setData({ on_missing: e.target.value })}>
                <option value="continue">Continuar con el siguiente paso</option>
                <option value="handoff">Transferir a un agente</option>
            </select>
            <p style={hintStyle}>El flow continúa en el primer paso hijo de este nodo cuando el procedimiento vuelve.</p>
        </div>
    );
}
