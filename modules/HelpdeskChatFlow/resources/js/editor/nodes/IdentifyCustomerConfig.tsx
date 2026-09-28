import React from 'react';
import { NodeConfigProps } from '../types';
import { labelStyle } from '../components/styles';

export default function IdentifyCustomerConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    const sources: string[] = d.sources || ['erp'];
    const toggleSrc = (src: string) => {
        const next = sources.includes(src)
            ? sources.filter((s: string) => s !== src)
            : [...sources, src];
        setData({ sources: next });
    };
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Pregunta al cliente</label>
                <textarea className="form-control form-control-sm" rows={3}
                    value={d.question || 'Para identificarte, escribe tu email, teléfono o documento.'}
                    onChange={e => setData({ question: e.target.value })} />
            </div>
            <label style={labelStyle}>Fuentes de verificación</label>
            {[['erp', 'ERP'], ['ps', 'PrestaShop']].map(([v, l]) => (
                <div key={v} className="form-check mb-1">
                    <input className="form-check-input" type="checkbox" id={`src-${v}`}
                        checked={sources.includes(v)} onChange={() => toggleSrc(v)} />
                    <label className="form-check-label" htmlFor={`src-${v}`}>{l}</label>
                </div>
            ))}
            <div style={{ marginTop: 10, marginBottom: 8 }}>
                <label style={labelStyle}>Mensaje si se identifica</label>
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.found_message || '¡Perfecto, {{customer_name}}!'}
                    onChange={e => setData({ found_message: e.target.value })} />
            </div>
            <div style={{ marginBottom: 8 }}>
                <label style={labelStyle}>Mensaje si no se encuentra</label>
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.not_found_message || 'No encontramos tu dato. Intenta de nuevo.'}
                    onChange={e => setData({ not_found_message: e.target.value })} />
            </div>
            <div style={{ marginBottom: 8 }}>
                <label style={labelStyle}>Intentos máximos</label>
                <input type="number" className="form-control form-control-sm" min={1} max={10}
                    value={d.max_attempts ?? 3}
                    onChange={e => setData({ max_attempts: parseInt(e.target.value) || 3 })} />
            </div>
            <div className="form-check">
                <input className="form-check-input" type="checkbox" id="transfer-failure"
                    checked={!!d.transfer_on_failure}
                    onChange={e => setData({ transfer_on_failure: e.target.checked })} />
                <label className="form-check-label" htmlFor="transfer-failure">Transferir a agente si agota intentos</label>
            </div>
        </div>
    );
}
