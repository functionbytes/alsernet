import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function MessageConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <label style={labelStyle}>Texto del mensaje</label>
            <textarea className="form-control form-control-sm" rows={5}
                value={d.text || ''}
                onChange={e => setData({ text: e.target.value })} />
            <p style={hintStyle}>Usa {'{{variable}}'} para interpolar variables del contexto.</p>

            <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 8 }}>
                <label style={labelStyle}>Plantilla WhatsApp (envío proactivo)</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="nombre_plantilla_aprobada"
                    value={d.whatsapp_template || ''}
                    onChange={e => setData({ whatsapp_template: e.target.value })} />
                {!!d.whatsapp_template && (
                    <div style={{ marginTop: 8 }}>
                        <label style={labelStyle}>Variables de la plantilla</label>
                        {(d.template_vars || []).map((v: string, i: number) => (
                            <div key={i} className="input-group input-group-sm mb-1">
                                <span className="input-group-text">{`{{${i + 1}}}`}</span>
                                <input type="text" className="form-control" value={v}
                                    placeholder="texto o {{variable}}"
                                    onChange={e => {
                                        const vars = [...(d.template_vars || [])];
                                        vars[i] = e.target.value;
                                        setData({ template_vars: vars });
                                    }} />
                                <button className="btn btn-outline-secondary"
                                    onClick={() => setData({ template_vars: (d.template_vars || []).filter((_: any, j: number) => j !== i) })}>
                                    <i className="fas fa-times" />
                                </button>
                            </div>
                        ))}
                        <button className="btn btn-sm btn-outline-primary mt-1"
                            onClick={() => setData({ template_vars: [...(d.template_vars || []), ''] })}>
                            <i className="fas fa-plus me-1" />Variable
                        </button>
                    </div>
                )}
                <p style={hintStyle}>Solo para WhatsApp fuera de la ventana de 24h (recordatorios, seguimientos). Dentro de la ventana se envía el texto normal.</p>
            </div>
        </div>
    );
}
