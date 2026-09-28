import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function AiResponseConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Instrucciones para la IA</label>
                <textarea className="form-control form-control-sm" rows={3}
                    value={d.instructions || ''}
                    placeholder="Eres un asistente de atención al cliente. Responde breve y amable en español."
                    onChange={e => setData({ instructions: e.target.value })} />
            </div>
            <div className="form-check form-switch mb-2">
                <input className="form-check-input" type="checkbox" id="ai-kb"
                    checked={d.use_knowledge_base !== false}
                    onChange={e => setData({ use_knowledge_base: e.target.checked })} />
                <label className="form-check-label" htmlFor="ai-kb" style={{ fontSize: 13 }}>
                    Usar centro de conocimiento (RAG)
                </label>
            </div>
            <div className="form-check form-switch mb-2">
                <input className="form-check-input" type="checkbox" id="ai-memory"
                    checked={d.use_memory !== false}
                    onChange={e => setData({ use_memory: e.target.checked })} />
                <label className="form-check-label" htmlFor="ai-memory" style={{ fontSize: 13 }}>
                    Recordar la conversación (memoria)
                </label>
            </div>
            {d.use_knowledge_base !== false && (
                <div style={{ marginBottom: 12 }}>
                    <label style={labelStyle}>Artículos a recuperar</label>
                    <input type="number" className="form-control form-control-sm" min={1} max={10}
                        value={d.kb_results ?? 4}
                        onChange={e => setData({ kb_results: parseInt(e.target.value) || 4 })} />
                </div>
            )}
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Variable con la pregunta</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="last_input"
                    value={d.question_variable || ''}
                    onChange={e => setData({ question_variable: e.target.value })} />
                <p style={hintStyle}>De qué variable del contexto sale la pregunta del cliente.</p>
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Guardar respuesta en (opcional)</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="ej: respuesta_ia"
                    value={d.save_to || ''}
                    onChange={e => setData({ save_to: e.target.value })} />
            </div>
            <label style={labelStyle}>Mensaje de respaldo</label>
            <textarea className="form-control form-control-sm" rows={2}
                value={d.fallback_message || ''}
                placeholder="Si la IA no puede responder, te paso con un agente."
                onChange={e => setData({ fallback_message: e.target.value })} />
        </div>
    );
}
