import React, { useState } from 'react';
import { Handle, NodeProps, Position } from '@xyflow/react';
import { BackendNode, FlowIssue } from '../types';
import { CAT_GRAY, NODE_COLORS, NODE_ICONS, NODE_LABELS, NODE_WIDTH } from '../constants';
import { handleStyle, nodeActionBtnStyle } from './styles';

// Custom xyflow node used for every step of the flow (start, message, branchItem, ...).
export default function FlowNode({ data, selected }: NodeProps) {
    const node  = (data as any).backendNode as BackendNode;
    const issue = (data as any).issue as FlowIssue | undefined;
    const color = NODE_COLORS[node.type] || '#64748b';
    const icon  = NODE_ICONS[node.type]  || 'fas fa-circle';
    const label = node.label || NODE_LABELS[node.type] || node.type;
    const [hover, setHover] = useState(false);

    // branchItems are oval pills
    if (node.type === 'branchItem') {
        return (
            <div style={{
                background:    selected ? '#eff6ff' : '#fff',
                border:        selected ? '2px solid #3b82f6' : '1.5px solid #cbd5e1',
                borderRadius:  30,
                padding:       '6px 18px',
                fontSize:      13,
                fontWeight:    500,
                color:         '#374151',
                userSelect:    'none',
                cursor:        'pointer',
                whiteSpace:    'nowrap',
                boxShadow:     '0 1px 3px rgba(0,0,0,.06)',
            }}>
                <Handle type="target" position={Position.Top}    style={handleStyle} />
                {label}
                <Handle type="source" position={Position.Bottom} style={handleStyle} />
            </div>
        );
    }

    // start node: compact box — gray icon square + label (bedesk-style)
    if (node.type === 'start') {
        return (
            <div style={{
                background:   '#fff',
                border:       selected ? '1.5px solid #cbd5e1' : '1.5px solid #e2e8f0',
                borderRadius: 12,
                padding:      10,
                width:        NODE_WIDTH,
                display:      'flex',
                alignItems:   'center',
                gap:          10,
                userSelect:   'none',
                cursor:       'pointer',
                boxShadow:    selected ? '0 0 0 3px rgba(98,116,142,.18)' : '0 1px 4px rgba(0,0,0,.08)',
            }}>
                <Handle type="target" position={Position.Top}    style={handleStyle} />
                <div style={{ width: 34, height: 34, borderRadius: 8, background: CAT_GRAY, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <i className="fas fa-play" style={{ color: '#fff', fontSize: 13 }} />
                </div>
                <span style={{ fontSize: 13, fontWeight: 600, color: '#1e293b' }}>{label}</span>
                <Handle type="source" position={Position.Bottom} style={handleStyle} />
            </div>
        );
    }

    // All other nodes — bedesk-style: content on top, footer with a colored
    // icon square + the type name in muted gray, separated by a top border.
    const issueColor = issue?.level === 'error' ? '#ef4444' : '#f59e0b';
    const border = issue
        ? `1.5px solid ${issueColor}`
        : (selected ? '1.5px solid #cbd5e1' : '1.5px solid #e2e8f0');

    return (
        <div
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                background:   '#fff',
                border,
                borderRadius: 12,
                width:        NODE_WIDTH,
                boxShadow:    selected ? '0 0 0 3px rgba(98,116,142,.18)' : '0 1px 4px rgba(0,0,0,.08)',
                cursor:       'pointer',
                userSelect:   'none',
                position:     'relative',
                display:      'flex',
                flexDirection: 'column',
                overflow:     'hidden',
            }}>
            <Handle type="target" position={Position.Top}    style={handleStyle} />

            {/* Issue badge */}
            {issue && (
                <div title={issue.message} style={{
                    position: 'absolute', top: -8, right: -8, width: 20, height: 20, borderRadius: '50%',
                    background: issueColor, color: '#fff', fontSize: 11, zIndex: 2,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    boxShadow: '0 1px 3px rgba(0,0,0,.2)',
                }}>
                    <i className="fas fa-exclamation" />
                </div>
            )}

            {/* Hover actions: duplicate / delete */}
            {hover && (
                <div style={{ position: 'absolute', top: -12, left: '50%', transform: 'translateX(-50%)', display: 'flex', gap: 4, zIndex: 2 }}>
                    <button title="Duplicar" onClick={e => { e.stopPropagation(); window.__chatflowDuplicateNode?.(node.id); }}
                        style={nodeActionBtnStyle}>
                        <i className="fas fa-copy" />
                    </button>
                    <button title="Eliminar" onClick={e => { e.stopPropagation(); window.__chatflowDeleteNode?.(node.id); }}
                        style={nodeActionBtnStyle}>
                        <i className="fas fa-trash" />
                    </button>
                </div>
            )}

            {/* Content body */}
            <div style={{
                padding: '11px 12px', fontSize: 13, color: '#1e293b', fontWeight: 500,
                display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical',
                overflow: 'hidden', wordBreak: 'break-word', lineHeight: 1.35, minHeight: 20,
            }}>
                {label}
            </div>

            {/* Footer: colored icon square + type name */}
            <div style={{ display: 'flex', alignItems: 'center', height: 30, borderTop: '1px solid #f1f5f9' }}>
                <div style={{ width: 30, height: '100%', background: color, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <i className={icon} style={{ color: '#fff', fontSize: 11 }} />
                </div>
                <span style={{ marginLeft: 8, fontSize: 11.5, color: '#94a3b8', fontWeight: 500 }}>
                    {NODE_LABELS[node.type] || node.type}
                </span>
            </div>

            <Handle type="source" position={Position.Bottom} style={handleStyle} />
        </div>
    );
}
