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
                    <option value="order_ref">Pedido (número o referencia de 9 letras)</option>
                    <option value="regex">Expresión regular</option>
                    <option value="enum">Una de una lista</option>
                </select>
                {d.validation === 'order_ref' && (
                    <p style={hintStyle}>Acepta un id numérico o una referencia de PrestaShop (9 letras, sin distinguir mayúsculas); se guarda en mayúsculas.</p>
                )}
                {d.validation === 'regex' && (
                    <>
                        <input type="text" className="form-control form-control-sm mt-2"
                            placeholder="ej: ^[A-Z]{3}-\d{4}$"
                            maxLength={200}
                            value={d.pattern || ''}
                            onChange={e => setData({ pattern: e.target.value })} />
                        <p style={hintStyle}>Sin delimitadores, máx. 200 caracteres. Se rechazan patrones que puedan bloquear el servidor (como <code>(a+)+</code>).</p>
                    </>
                )}
                {d.validation === 'enum' && (
                    <>
                        <textarea className="form-control form-control-sm mt-2" rows={3}
                            placeholder={'Un valor por línea\ndevolución\ncambio\nreclamación'}
                            value={(d.allowed || []).join('\n')}
                            onChange={e => setData({ allowed: e.target.value.split('\n') })}
                            onBlur={e => setData({ allowed: e.target.value.split('\n').map(v => v.trim()).filter(Boolean) })} />
                        <p style={hintStyle}>Sin distinguir mayúsculas ni acentos. Se guarda el valor tal como lo escribas aquí.</p>
                    </>
                )}
                {!!d.validation && d.validation !== 'none' && (
                    <input type="text" className="form-control form-control-sm mt-2"
                        placeholder="Mensaje de error personalizado (opcional)"
                        value={d.error_message || ''}
                        onChange={e => setData({ error_message: e.target.value })} />
                )}
                <p style={hintStyle}>Si no valida, el bot vuelve a preguntar (hasta {d.max_retries || 3} veces, luego transfiere).</p>
            </div>

            <div style={{ marginBottom: 12 }}>
                <label className="d-flex align-items-center gap-2" style={{ fontSize: 13, cursor: 'pointer' }}>
                    <input type="checkbox" checked={!!d.skip_if_set}
                        onChange={e => setData({ skip_if_set: e.target.checked })} />
                    Saltar si ya se sabe
                </label>
                <p style={hintStyle}>Si la variable ya tiene valor (p. ej. <code>customer_email</code> de un cliente verificado), no se pregunta y el flujo sigue.</p>
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
