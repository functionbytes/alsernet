import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function AiAgentConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Instrucciones del agente</label>
                <textarea className="form-control form-control-sm" rows={3}
                    value={d.instructions || ''}
                    placeholder="Eres un agente de atención. Usa las herramientas cuando ayuden a resolver."
                    onChange={e => setData({ instructions: e.target.value })} />
            </div>
            <label style={labelStyle}>Herramientas disponibles</label>
            <div className="form-check form-switch mb-1">
                <input className="form-check-input" type="checkbox" id="ag-order"
                    checked={d.tool_order_lookup !== false}
                    onChange={e => setData({ tool_order_lookup: e.target.checked })} />
                <label className="form-check-label" htmlFor="ag-order" style={{ fontSize: 13 }}>Consultar pedido (ERP/PrestaShop)</label>
            </div>
            <div className="form-check form-switch mb-2">
                <input className="form-check-input" type="checkbox" id="ag-kb"
                    checked={d.tool_knowledge !== false}
                    onChange={e => setData({ tool_knowledge: e.target.checked })} />
                <label className="form-check-label" htmlFor="ag-kb" style={{ fontSize: 13 }}>Buscar en el centro de ayuda</label>
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Variable con la pregunta</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="last_input"
                    value={d.question_variable || ''}
                    onChange={e => setData({ question_variable: e.target.value })} />
            </div>
            <label style={labelStyle}>Mensaje de respaldo</label>
            <textarea className="form-control form-control-sm" rows={2}
                value={d.fallback_message || ''}
                placeholder="Si no puede resolver, te paso con un agente."
                onChange={e => setData({ fallback_message: e.target.value })} />
            <p style={hintStyle}>El agente decide solo qué herramienta usar (consultar pedido, buscar ayuda), responde o escala a un humano.</p>
        </div>
    );
}
