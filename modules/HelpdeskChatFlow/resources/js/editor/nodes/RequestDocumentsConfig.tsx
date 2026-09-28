import React from 'react';
import { NodeConfigProps } from '../types';
import { DOC_TYPE_LABELS } from '../constants';
import { labelStyle } from '../components/styles';

export default function RequestDocumentsConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    const selected: string[] = d.doc_types || [];
    const toggle = (key: string) => {
        const next = selected.includes(key)
            ? selected.filter((t: string) => t !== key)
            : [...selected, key];
        setData({ doc_types: next });
    };
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Mensaje de solicitud</label>
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.text || 'Por favor sube los siguientes documentos:'}
                    onChange={e => setData({ text: e.target.value })} />
            </div>
            <label style={labelStyle}>Documentos requeridos</label>
            {Object.entries(DOC_TYPE_LABELS).map(([key, lbl]) => (
                <div key={key} className="form-check mb-1">
                    <input className="form-check-input" type="checkbox" id={`doc-${key}`}
                        checked={selected.includes(key)} onChange={() => toggle(key)} />
                    <label className="form-check-label" htmlFor={`doc-${key}`}>{lbl}</label>
                </div>
            ))}
            <div style={{ marginTop: 10 }}>
                <label style={labelStyle}>Guardar lista en variable</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="ej: documentos_subidos"
                    value={d.variable_name || 'uploaded_docs'}
                    onChange={e => setData({ variable_name: e.target.value })} />
            </div>
        </div>
    );
}
