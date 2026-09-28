import React from 'react';
import { Panel } from '@xyflow/react';
import { hintStyle, labelStyle } from './styles';
import SettingToggle from './SettingToggle';

interface FlowSettingsPanelProps {
    flowSettings: Record<string, any>;
    setFlowSettings: React.Dispatch<React.SetStateAction<Record<string, any>>>;
    onClose: () => void;
}

// "Ajustes del flow" panel: multilingual/escalation toggles, A/B testing and event trigger.
export default function FlowSettingsPanel({ flowSettings, setFlowSettings, onClose }: FlowSettingsPanelProps) {
    return (
        <Panel position="top-right" style={{ margin: 12 }}>
            <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: 10, boxShadow: '0 4px 16px rgba(0,0,0,.12)', width: 300, padding: 16 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
                    <strong style={{ fontSize: 13 }}>Ajustes del flow</strong>
                    <button onClick={onClose} style={{ border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8' }}><i className="fas fa-times" /></button>
                </div>
                <SettingToggle label="Responder en el idioma del cliente"
                    hint="Detecta el idioma y traduce/responde en él (WhatsApp, Instagram…)."
                    checked={!!flowSettings.multilingual}
                    onChange={v => setFlowSettings(s => ({ ...s, multilingual: v }))} />
                <SettingToggle label="Escalar si el cliente se frustra"
                    hint="Analiza el sentimiento y transfiere a un agente si detecta enfado."
                    checked={!!flowSettings.sentiment_escalation}
                    onChange={v => setFlowSettings(s => ({ ...s, sentiment_escalation: v }))} />
                <SettingToggle label="Permitir 'hablar con un agente'"
                    hint="Palabra clave (agente, humano…) que transfiere desde cualquier punto."
                    checked={flowSettings.escape_enabled !== false}
                    onChange={v => setFlowSettings(s => ({ ...s, escape_enabled: v }))} />
                <SettingToggle label="Resumen IA al transferir"
                    hint="Al pasar a un agente, deja una nota interna con el resumen de la conversación."
                    checked={!!flowSettings.handoff_summary}
                    onChange={v => setFlowSettings(s => ({ ...s, handoff_summary: v }))} />

                <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 4 }}>
                    <label style={{ ...labelStyle, marginBottom: 4 }}>A/B testing</label>
                    <div className="d-flex gap-2">
                        <input type="number" className="form-control form-control-sm" placeholder="ID flow variante B"
                            value={flowSettings.ab_variant_id || ''}
                            onChange={e => setFlowSettings(s => ({ ...s, ab_variant_id: e.target.value ? parseInt(e.target.value) : null }))} />
                        <input type="number" min={1} max={99} className="form-control form-control-sm" style={{ maxWidth: 90 }} placeholder="% B"
                            value={flowSettings.ab_split || ''}
                            onChange={e => setFlowSettings(s => ({ ...s, ab_split: e.target.value ? parseInt(e.target.value) : null }))} />
                    </div>
                    <p style={hintStyle}>Envía un % de conversaciones al flow variante para comparar resultados.</p>
                </div>

                <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 4 }}>
                    <label style={{ ...labelStyle, marginBottom: 4 }}>Disparador por evento (outbound)</label>
                    <select className="form-select form-select-sm"
                        value={flowSettings.business_event || ''}
                        onChange={e => setFlowSettings(s => ({ ...s, business_event: e.target.value || null }))}>
                        <option value="">Ninguno (no proactivo)</option>
                        <option value="abandoned_cart">Carrito abandonado (PrestaShop)</option>
                        <option value="order_status">Cambio de estado de pedido (PrestaShop)</option>
                        <option value="order_ready">Pedido listo (ERP)</option>
                    </select>
                    <p style={hintStyle}>El flow se lanza automáticamente por WhatsApp cuando ocurre el evento. Empieza con un nodo de mensaje (usa plantilla WhatsApp para enviar fuera de la ventana de 24h).</p>
                </div>

                <p style={{ ...hintStyle, marginTop: 10 }}>Recuerda <strong>Guardar</strong> para aplicar los cambios.</p>
            </div>
        </Panel>
    );
}
