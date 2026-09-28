import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function HttpRequestConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div className="d-flex gap-2 mb-2">
                <select className="form-select form-select-sm" style={{ maxWidth: 110 }}
                    value={d.method || 'GET'}
                    onChange={e => setData({ method: e.target.value })}>
                    <option>GET</option>
                    <option>POST</option>
                    <option>PUT</option>
                    <option>PATCH</option>
                    <option>DELETE</option>
                </select>
                <input type="text" className="form-control form-control-sm"
                    placeholder="https://api.ejemplo.com/recurso"
                    value={d.url || ''}
                    onChange={e => setData({ url: e.target.value })} />
            </div>
            <p style={hintStyle}>Puedes usar {'{{variables}}'} en la URL, headers y body.</p>

            <label style={{ ...labelStyle, marginTop: 10 }}>Cabeceras</label>
            {(d.headers || []).map((h: any, i: number) => (
                <div key={i} className="input-group input-group-sm mb-1">
                    <input type="text" className="form-control" placeholder="Nombre" value={h.key || ''}
                        onChange={e => {
                            const headers = [...(d.headers || [])];
                            headers[i] = { ...headers[i], key: e.target.value };
                            setData({ headers });
                        }} />
                    <input type="text" className="form-control" placeholder="Valor" value={h.value || ''}
                        onChange={e => {
                            const headers = [...(d.headers || [])];
                            headers[i] = { ...headers[i], value: e.target.value };
                            setData({ headers });
                        }} />
                    <button className="btn btn-outline-secondary"
                        onClick={() => setData({ headers: (d.headers || []).filter((_: any, j: number) => j !== i) })}>
                        <i className="fas fa-times" />
                    </button>
                </div>
            ))}
            <button className="btn btn-sm btn-outline-primary mb-2"
                onClick={() => setData({ headers: [...(d.headers || []), { key: '', value: '' }] })}>
                <i className="fas fa-plus me-1" />Cabecera
            </button>

            {['POST', 'PUT', 'PATCH'].includes(d.method || 'GET') && (
                <div style={{ marginBottom: 10 }}>
                    <label style={labelStyle}>Body (JSON)</label>
                    <textarea className="form-control form-control-sm" rows={3}
                        style={{ fontFamily: 'monospace', fontSize: 12 }}
                        value={typeof d.body === 'string' ? d.body : ''}
                        placeholder='{"email": "{{customer_email}}"}'
                        onChange={e => setData({ body: e.target.value })} />
                </div>
            )}

            <div style={{ marginBottom: 10 }}>
                <label style={labelStyle}>Extraer del JSON (ruta)</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="ej: data.estado o results.0.id"
                    value={d.response_path || ''}
                    onChange={e => setData({ response_path: e.target.value })} />
            </div>
            <div style={{ marginBottom: 10 }}>
                <label style={labelStyle}>Guardar en variable</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="http_response"
                    value={d.save_to || ''}
                    onChange={e => setData({ save_to: e.target.value })} />
                <p style={hintStyle}>También guarda {'{{save_to}}_ok'} y {'{{save_to}}_status'}.</p>
            </div>
            <div className="form-check form-switch mb-2">
                <input className="form-check-input" type="checkbox" id="http-msg"
                    checked={!!d.show_message}
                    onChange={e => setData({ show_message: e.target.checked })} />
                <label className="form-check-label" htmlFor="http-msg" style={{ fontSize: 13 }}>
                    Enviar un mensaje con el resultado
                </label>
            </div>
            {d.show_message && (
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.message_template || ''}
                    placeholder="Tu pedido está: {{http_response}}"
                    onChange={e => setData({ message_template: e.target.value })} />
            )}
        </div>
    );
}
