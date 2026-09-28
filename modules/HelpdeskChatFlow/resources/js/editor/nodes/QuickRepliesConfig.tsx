import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function QuickRepliesConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Pregunta</label>
                <textarea className="form-control form-control-sm" rows={3}
                    value={d.text || ''}
                    onChange={e => setData({ text: e.target.value })} />
            </div>
            <label style={labelStyle}>Opciones</label>
            {(d.options || []).map((opt: string, i: number) => (
                <div key={i} className="input-group input-group-sm mb-1">
                    <input type="text" className="form-control" value={opt}
                        onChange={e => {
                            const opts = [...(d.options || [])];
                            opts[i] = e.target.value;
                            setData({ options: opts });
                        }} />
                    <button className="btn btn-outline-secondary"
                        onClick={() => setData({ options: (d.options || []).filter((_: any, j: number) => j !== i) })}>
                        <i className="fas fa-times" />
                    </button>
                </div>
            ))}
            <button className="btn btn-sm btn-outline-primary mt-1"
                onClick={() => setData({ options: [...(d.options || []), ''] })}>
                <i className="fas fa-plus me-1" />Agregar opción
            </button>
            <div style={{ marginTop: 14 }}>
                <label style={labelStyle}>Guardar respuesta en variable</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="ej: opcion_elegida"
                    value={d.variable_name || ''}
                    onChange={e => setData({ variable_name: e.target.value })} />
            </div>
            <div className="form-check form-switch mt-3">
                <input className="form-check-input" type="checkbox" id="qr-nlu"
                    checked={!!d.use_nlu}
                    onChange={e => setData({ use_nlu: e.target.checked })} />
                <label className="form-check-label" htmlFor="qr-nlu" style={{ fontSize: 13 }}>
                    Entender lenguaje natural (IA)
                </label>
            </div>
            <p style={hintStyle}>Si el cliente no responde con un número, la IA interpreta su intención y elige la opción más cercana.</p>
        </div>
    );
}
