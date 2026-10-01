import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';
import { useRemoteList } from '../hooks/useRemoteList';

interface ActionParam { name: string; type?: string; description?: string; required?: boolean; }
interface CatalogAction {
    key: string; name: string; description?: string; type: string;
    parameters: ActionParam[]; write: boolean;
}

export default function AiActionConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    const { items: actions, loading, failed } = useRemoteList<CatalogAction>(window.__chatflowUrls?.actionsCatalog);
    const action = actions.find(a => a.key === d.action_key);
    const args: Record<string, string> = d.args || {};
    const saveTo = d.save_to || 'accion';

    return (
        <div>
            <label style={labelStyle}>Acción del catálogo</label>
            <select className="form-select form-select-sm"
                value={d.action_key || ''}
                onChange={e => setData({ action_key: e.target.value, args: {} })}>
                <option value="">{loading ? 'Cargando…' : '— Seleccionar acción —'}</option>
                {d.action_key && !action && !loading && <option value={d.action_key}>{d.action_key} (no disponible)</option>}
                {actions.map(a => <option key={a.key} value={a.key}>{a.name}</option>)}
            </select>
            {!window.__chatflowUrls?.actionsCatalog && (
                <p style={{ ...hintStyle, color: '#dc2626' }}>El catálogo de acciones IA no está disponible.</p>
            )}
            {failed && <p style={{ ...hintStyle, color: '#dc2626' }}>No se pudo cargar el catálogo.</p>}
            {action?.description && <p style={hintStyle}>{action.description}</p>}

            {action && action.parameters.length > 0 && (
                <div style={{ marginTop: 10 }}>
                    <label style={labelStyle}>Parámetros</label>
                    {action.parameters.map(p => (
                        <div key={p.name} className="mb-2">
                            <label className="form-label mb-1" style={{ fontSize: 12 }}>
                                {p.name}{p.required && <span className="text-danger"> *</span>}
                            </label>
                            <input type="text" className="form-control form-control-sm"
                                placeholder={`{{variable}}`}
                                value={args[p.name] || ''}
                                onChange={e => setData({ args: { ...args, [p.name]: e.target.value } })} />
                            {p.description && <p style={hintStyle}>{p.description}</p>}
                        </div>
                    ))}
                    <p style={hintStyle}>Admite {'{{variables}}'}. Un valor que es solo una variable conserva su tipo.</p>
                </div>
            )}

            {action?.write && (
                <div className="alert alert-warning py-2 px-2 mt-2 mb-2" style={{ fontSize: 12 }}>
                    <i className="fas fa-triangle-exclamation me-1" />
                    Esta acción modifica datos: solo se ejecuta si la variable de confirmación vale «sí», «true» u «ok».
                    Pregunta antes al cliente (Capturar input o Respuestas rápidas).
                </div>
            )}
            <label style={{ ...labelStyle, marginTop: 10 }}>
                Variable de confirmación{action?.write && <span className="text-danger"> *</span>}
            </label>
            <input type="text" className={`form-control form-control-sm${action?.write && !d.confirmed_variable ? ' is-invalid' : ''}`}
                placeholder="confirmacion"
                value={d.confirmed_variable || ''}
                onChange={e => setData({ confirmed_variable: e.target.value })} />

            <label style={{ ...labelStyle, marginTop: 10 }}>Guardar resultado en</label>
            <input type="text" className="form-control form-control-sm" placeholder="accion"
                value={d.save_to || ''}
                onChange={e => setData({ save_to: e.target.value })} />
            <p style={hintStyle}>
                Deja {'{{' + saveTo + '}}'}, {'{{' + saveTo + '_ok}}'}, {'{{' + saveTo + '_status}}'} y {'{{' + saveTo + '_campo}}'}.
            </p>

            <label style={{ ...labelStyle, marginTop: 10 }}>Si la acción falla</label>
            <select className="form-select form-select-sm"
                value={d.on_error || 'continue'}
                onChange={e => setData({ on_error: e.target.value })}>
                <option value="continue">Continuar con el siguiente paso</option>
                <option value="handoff">Transferir a un agente</option>
            </select>

            <div className="form-check form-switch mt-3 mb-2">
                <input className="form-check-input" type="checkbox" id="aiact-msg"
                    checked={!!d.show_message}
                    onChange={e => setData({ show_message: e.target.checked })} />
                <label className="form-check-label" htmlFor="aiact-msg" style={{ fontSize: 13 }}>
                    Enviar un mensaje con el resultado
                </label>
            </div>
            {d.show_message && (
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.message_template || ''}
                    placeholder={`Resultado: {{${saveTo}}}`}
                    onChange={e => setData({ message_template: e.target.value })} />
            )}
        </div>
    );
}
