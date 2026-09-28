import React from 'react';
import { Panel } from '@xyflow/react';
import { btnStyle, iconBtnStyle } from './styles';

interface ToolbarProps {
    flowName: string;
    onFlowNameChange: (value: string) => void;
    search: string;
    onSearchChange: (value: string) => void;
    onUndo: () => void;
    onRedo: () => void;
    onToggleSettings: () => void;
    onSave: () => void;
    saving: boolean;
    dirty: boolean;
    issuesCount: number;
    hasErrors: boolean;
    onToggleIssues: () => void;
    isActive: boolean;
    onPublish: () => void;
    onOpenTestPanel: () => void;
    indexUrl: string;
    onNavigateAway: (e: React.MouseEvent) => void;
}

// Top toolbar: flow name, node search, undo/redo, save/publish and issue count.
export default function Toolbar({
    flowName, onFlowNameChange, search, onSearchChange, onUndo, onRedo, onToggleSettings,
    onSave, saving, dirty, issuesCount, hasErrors, onToggleIssues, isActive, onPublish,
    onOpenTestPanel, indexUrl, onNavigateAway,
}: ToolbarProps) {
    return (
        <Panel position="top-center" style={{ background: 'transparent', pointerEvents: 'none', width: '100%', margin: 0, padding: 0 }}>
            <div style={{
                display: 'flex', alignItems: 'center', gap: 8,
                background: '#fff', borderBottom: '1px solid #e2e8f0',
                padding: '8px 16px', pointerEvents: 'auto',
                boxShadow: '0 1px 4px rgba(0,0,0,.06)',
            }}>
                <input
                    type="text"
                    value={flowName}
                    onChange={e => onFlowNameChange(e.target.value)}
                    style={{ border: '1px solid #e2e8f0', borderRadius: 6, padding: '5px 10px', fontSize: 13, width: 250, outline: 'none' }}
                    placeholder="Nombre del flow..."
                />
                <div style={{ position: 'relative', display: 'inline-flex', alignItems: 'center' }}>
                    <i className="fas fa-search" style={{ position: 'absolute', left: 9, color: '#94a3b8', fontSize: 11 }} />
                    <input
                        type="search"
                        value={search}
                        onChange={e => onSearchChange(e.target.value)}
                        placeholder="Buscar nodo…"
                        style={{ border: '1px solid #e2e8f0', borderRadius: 6, padding: '5px 8px 5px 26px', fontSize: 12, width: 150, outline: 'none' }}
                    />
                </div>
                <button onClick={onUndo} title="Deshacer (Ctrl+Z)" style={iconBtnStyle}>
                    <i className="fas fa-rotate-left" />
                </button>
                <button onClick={onRedo} title="Rehacer (Ctrl+Y)" style={iconBtnStyle}>
                    <i className="fas fa-rotate-right" />
                </button>
                <button onClick={onToggleSettings} title="Ajustes del flow" style={iconBtnStyle}>
                    <i className="fas fa-gear" />
                </button>
                <button onClick={onSave} disabled={saving} style={btnStyle('#475569')}>
                    <i className="fas fa-save" style={{ marginRight: 5 }} />
                    {saving ? 'Guardando…' : 'Guardar'}
                </button>
                {dirty && (
                    <span title="Cambios sin guardar" style={{ fontSize: 11, color: '#f59e0b', fontWeight: 600, display: 'inline-flex', alignItems: 'center', gap: 4 }}>
                        <span style={{ width: 7, height: 7, borderRadius: '50%', background: '#f59e0b', display: 'inline-block' }} />
                        Sin guardar
                    </span>
                )}
                {issuesCount > 0 && (
                    <button onClick={onToggleIssues}
                        style={btnStyle(hasErrors ? '#ef4444' : '#f59e0b')}
                        title="Ver problemas del flow">
                        <i className="fas fa-triangle-exclamation" style={{ marginRight: 5 }} />
                        {issuesCount}
                    </button>
                )}
                {!isActive && (
                    <button onClick={onPublish} disabled={saving} style={btnStyle('#90bb13')}>
                        <i className="fas fa-rocket" style={{ marginRight: 5 }} />Publicar
                    </button>
                )}
                <button
                    onClick={onOpenTestPanel}
                    style={{ ...btnStyle('#4e6ef5'), marginLeft: 'auto' }}
                >
                    <i className="fas fa-flask" style={{ marginRight: 5 }} />Probar flow
                </button>
                <a href={indexUrl} style={{ ...btnStyle('#64748b'), textDecoration: 'none' }} onClick={onNavigateAway}>
                    <i className="fas fa-arrow-left" style={{ marginRight: 5 }} />Volver
                </a>
            </div>
        </Panel>
    );
}
