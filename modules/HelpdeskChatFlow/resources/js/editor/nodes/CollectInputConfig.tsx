import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function CollectInputConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Pregunta</label>
                <textarea className="form-control form-control-sm" rows={3}
                    value={d.question || ''}
                    onChange={e => setData({ question: e.target.value })} />
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Guardar en variable</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="ej: nombre_cliente"
                    value={d.variable_name || ''}
                    onChange={e => setData({ variable_name: e.target.value })} />
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Validar como</label>
                <select className="form-select form-select-sm"
                    value={d.validation || 'none'}
                    onChange={e => setData({ validation: e.target.value })}>
                    <option value="none">Sin validación</option>
                    <option value="email">Email</option>
                    <option value="phone">Teléfono</option>
                    <option value="number">Número</option>
                </select>
                <p style={hintStyle}>Si no valida, el bot vuelve a preguntar (hasta {d.max_retries || 3} veces, luego transfiere).</p>
            </div>

            <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 4 }}>
                <label style={labelStyle}>Si el cliente no responde…</label>
                <div className="d-flex gap-2 align-items-center mb-2">
                    <input type="number" min={0} className="form-control form-control-sm" style={{ maxWidth: 90 }}
                        placeholder="min"
                        value={d.timeout_minutes || ''}
                        onChange={e => setData({ timeout_minutes: e.target.value ? parseInt(e.target.value) : null })} />
                    <span style={hintStyle}>minutos de espera</span>
                </div>
                {!!d.timeout_minutes && (
                    <>
                        <select className="form-select form-select-sm mb-2"
                            value={d.timeout_action || 'close'}
                            onChange={e => setData({ timeout_action: e.target.value })}>
                            <option value="close">Cerrar la conversación</option>
                            <option value="retry">Volver a preguntar</option>
                            <option value="transfer">Pasar a un agente</option>
                        </select>
                        <textarea className="form-control form-control-sm" rows={2}
                            placeholder="Mensaje al agotarse el tiempo (opcional). Ej: ¿Sigues ahí? 👋"
                            value={d.timeout_message || ''}
                            onChange={e => setData({ timeout_message: e.target.value })} />
                        {d.timeout_action === 'retry' && (
                            <div className="d-flex gap-2 align-items-center mt-2">
                                <input type="number" min={1} max={5} className="form-control form-control-sm" style={{ maxWidth: 90 }}
                                    value={d.timeout_retries || 1}
                                    onChange={e => setData({ timeout_retries: e.target.value ? parseInt(e.target.value) : 1 })} />
                                <span style={hintStyle}>reintentos antes de cerrar</span>
                            </div>
                        )}
                    </>
                )}
                <p style={hintStyle}>Útil para evitar conversaciones colgadas a mitad del bot.</p>
            </div>
        </div>
    );
}
