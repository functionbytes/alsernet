import React, { useEffect, useState } from 'react';
import { AssignOption, BackendNode } from '../types';
import { NODE_COLORS, NODE_ICONS, NODE_LABELS } from '../constants';
import { collectFlowVariables, highlightVars, nodeMessageText } from '../utils';
import { NODE_CONFIG_REGISTRY } from '../nodes';
import { hintStyle, labelStyle } from './styles';

interface NodePropertiesPanelProps {
    node:     BackendNode;
    allNodes: BackendNode[];
    agents:   AssignOption[];
    groups:   AssignOption[];
    onUpdate: (updated: BackendNode) => void;
    onDelete: (id: string) => void;
    onClose:  () => void;
}

// Side panel shown when a node is selected: name, per-type config fields
// (looked up in NODE_CONFIG_REGISTRY), live preview and available variables.
export default function NodePropertiesPanel({ node, allNodes, agents, groups, onUpdate, onDelete, onClose }: NodePropertiesPanelProps) {
    const [draft, setDraft] = useState<BackendNode>(() => structuredClone(node));

    useEffect(() => { setDraft(structuredClone(node)); }, [node.id]);

    const setData = (patch: Record<string, any>) =>
        setDraft(d => ({ ...d, data: { ...d.data, ...patch } }));

    const color = NODE_COLORS[node.type] || '#64748b';
    const icon  = NODE_ICONS[node.type]  || 'fas fa-circle';
    const ConfigFields = NODE_CONFIG_REGISTRY[draft.type];
    const previewText = nodeMessageText(draft);

    return (
        <div style={{ width: 340, height: '100%', background: '#fff', borderLeft: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column', fontFamily: 'inherit' }}>

            {/* Header */}
            <div style={{ padding: '14px 16px', borderBottom: '1px solid #e2e8f0', display: 'flex', alignItems: 'center', gap: 10 }}>
                <div style={{ width: 32, height: 32, borderRadius: 8, background: color, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <i className={icon} style={{ color: '#fff', fontSize: 13 }} />
                </div>
                <div style={{ flex: 1, fontWeight: 600, fontSize: 14, color: '#1e293b' }}>
                    {NODE_LABELS[node.type] || node.type}
                </div>
                <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', fontSize: 16, padding: 4 }}>
                    <i className="fas fa-times" />
                </button>
            </div>

            {/* Body */}
            <div style={{ flex: 1, overflowY: 'auto', padding: 16 }}>
                <div style={{ marginBottom: 14 }}>
                    <label style={labelStyle}>Nombre del paso</label>
                    <input
                        type="text"
                        className="form-control form-control-sm"
                        value={draft.label}
                        onChange={e => setDraft(d => ({ ...d, label: e.target.value }))}
                    />
                </div>
                {ConfigFields && (
                    <ConfigFields draft={draft} allNodes={allNodes} agents={agents} groups={groups} setData={setData} />
                )}

                {/* Live preview */}
                {previewText !== null && String(previewText).trim() !== '' && (
                    <div style={{ marginTop: 18 }}>
                        <label style={labelStyle}>Vista previa</label>
                        <div style={{ background: '#e6ddd4', borderRadius: 10, padding: 10 }}>
                            <div style={{
                                background: '#d9fdd3', borderRadius: 8, padding: '8px 10px', fontSize: 12.5,
                                color: '#111827', whiteSpace: 'pre-wrap', wordBreak: 'break-word',
                                boxShadow: '0 1px 1px rgba(0,0,0,.08)', maxWidth: '90%',
                            }}>
                                {highlightVars(String(previewText))}
                            </div>
                        </div>
                        <p style={hintStyle}>Así se ve en un canal de texto (WhatsApp, Instagram…). En web el cliente lo recibe igual.</p>
                    </div>
                )}

                {/* Available variables */}
                <div style={{ marginTop: 18 }}>
                    <label style={labelStyle}>Variables disponibles</label>
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5 }}>
                        {collectFlowVariables(allNodes).map(v => (
                            <button key={v} type="button" title="Copiar"
                                onClick={() => {
                                    navigator.clipboard?.writeText(`{{${v}}}`);
                                    (window as any).toastr?.info(`{{${v}}} copiado`, '', { timeOut: 1000 });
                                }}
                                style={{
                                    border: '1px solid #e2e8f0', borderRadius: 20, padding: '2px 9px',
                                    background: '#f8fafc', color: '#475569', fontSize: 11, cursor: 'pointer',
                                    fontFamily: 'monospace',
                                }}>
                                {`{{${v}}}`}
                            </button>
                        ))}
                    </div>
                    <p style={hintStyle}>Haz clic para copiar y pégala en cualquier campo de texto.</p>
                </div>
            </div>

            {/* Footer */}
            <div style={{ padding: '12px 16px', borderTop: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column', gap: 8 }}>
                <button
                    onClick={() => onUpdate(draft)}
                    style={{ background: '#90bb13', color: '#fff', border: 'none', borderRadius: 6, padding: '8px 0', fontWeight: 600, cursor: 'pointer', width: '100%', fontSize: 13 }}
                >
                    Guardar
                </button>
                <button onClick={onClose} style={{ background: '#f1f5f9', color: '#475569', border: 'none', borderRadius: 6, padding: '8px 0', cursor: 'pointer', width: '100%', fontSize: 13 }}>
                    Cancelar
                </button>
                {node.type !== 'start' && (
                    <button
                        onClick={() => { if (confirm('¿Eliminar este nodo y todos sus pasos siguientes?')) onDelete(node.id); }}
                        style={{ background: 'none', color: '#ef4444', border: '1px solid #fecaca', borderRadius: 6, padding: '6px 0', cursor: 'pointer', width: '100%', fontSize: 12 }}
                    >
                        <i className="fas fa-trash me-1" /> Eliminar nodo
                    </button>
                )}
            </div>
        </div>
    );
}
