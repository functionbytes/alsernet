import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function AddTagConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <label style={labelStyle}>Etiquetas a agregar</label>
            {(d.tags || []).map((tag: string, i: number) => (
                <div key={i} className="input-group input-group-sm mb-1">
                    <input type="text" className="form-control" value={tag}
                        onChange={e => {
                            const tags = [...(d.tags || [])];
                            tags[i] = e.target.value;
                            setData({ tags });
                        }} />
                    <button className="btn btn-outline-secondary"
                        onClick={() => setData({ tags: (d.tags || []).filter((_: any, j: number) => j !== i) })}>
                        <i className="fas fa-times" />
                    </button>
                </div>
            ))}
            <button className="btn btn-sm btn-outline-primary mt-1"
                onClick={() => setData({ tags: [...(d.tags || []), ''] })}>
                <i className="fas fa-plus me-1" />Agregar etiqueta
            </button>
            <p style={{ ...hintStyle, marginTop: 8 }}>Las etiquetas se agregan a la conversación y al cliente.</p>
        </div>
    );
}
