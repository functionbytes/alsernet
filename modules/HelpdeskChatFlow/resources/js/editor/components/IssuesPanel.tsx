import React from 'react';
import { Panel } from '@xyflow/react';
import { FlowIssue } from '../types';

interface IssuesPanelProps {
    errors: FlowIssue[];
    warnings: FlowIssue[];
    onClose: () => void;
    onSelectNode: (nodeId: string) => void;
}

// "Problemas del flow" panel: lists validation errors/warnings, click to select the node.
export default function IssuesPanel({ errors, warnings, onClose, onSelectNode }: IssuesPanelProps) {
    return (
        <Panel position="bottom-left" style={{ margin: 12 }}>
            <div style={{
                background: '#fff', border: '1px solid #e2e8f0', borderRadius: 10,
                boxShadow: '0 4px 16px rgba(0,0,0,.12)', width: 320, maxHeight: 280, overflow: 'auto',
            }}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 14px', borderBottom: '1px solid #f1f5f9' }}>
                    <strong style={{ fontSize: 13 }}>Problemas del flow</strong>
                    <button onClick={onClose} style={{ border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8' }}>
                        <i className="fas fa-times" />
                    </button>
                </div>
                <div style={{ padding: '6px 0' }}>
                    {[...errors, ...warnings].map((issue, i) => (
                        <div key={i}
                            onClick={() => issue.nodeId && onSelectNode(issue.nodeId)}
                            style={{
                                display: 'flex', alignItems: 'flex-start', gap: 8, padding: '7px 14px',
                                cursor: issue.nodeId ? 'pointer' : 'default', fontSize: 12.5, color: '#334155',
                            }}
                            onMouseEnter={e => (e.currentTarget.style.background = '#f8fafc')}
                            onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}>
                            <i className={`fas fa-${issue.level === 'error' ? 'circle-exclamation' : 'triangle-exclamation'}`}
                                style={{ color: issue.level === 'error' ? '#ef4444' : '#f59e0b', marginTop: 2 }} />
                            <span>{issue.message}</span>
                        </div>
                    ))}
                </div>
            </div>
        </Panel>
    );
}
