import React, { useEffect, useRef, useState } from 'react';
import { Handle, NodeProps, Position } from '@xyflow/react';
import { ADDABLE_TYPES, NODE_COLORS, NODE_ICONS, NODE_LABELS } from '../constants';
import { handleStyle } from './styles';

// Custom xyflow node: the "+" button between steps that opens a menu to add a new node type.
export default function AddStepNode({ data }: NodeProps) {
    const parentId = (data as any).parentId as string;
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;
        const handler = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as HTMLElement)) setOpen(false);
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [open]);

    const handleAdd = (type: string) => {
        setOpen(false);
        window.__chatflowAddNode?.(type, parentId);
    };

    return (
        <div ref={ref} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', position: 'relative' }}>
            <Handle type="target" position={Position.Top}    style={handleStyle} />
            <button
                onClick={e => { e.stopPropagation(); setOpen(o => !o); }}
                style={{
                    width: 28, height: 28, borderRadius: '50%',
                    border: '2px solid #90bb13',
                    background: open ? '#90bb13' : '#fff',
                    color:      open ? '#fff'    : '#90bb13',
                    fontSize: 18, lineHeight: 1, cursor: 'pointer',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    boxShadow: 'none',
                    transition: 'background .15s, color .15s',
                    padding: 0,
                }}
                title="Agregar paso"
            >
                +
            </button>
            <Handle type="source" position={Position.Bottom} style={handleStyle} />

            {open && (
                <div style={{
                    position: 'absolute', top: 38, left: '50%', transform: 'translateX(-50%)',
                    background: '#fff', border: '1px solid #e2e8f0', borderRadius: 10,
                    boxShadow: '0 8px 24px rgba(0,0,0,.14)', zIndex: 9999,
                    minWidth: 210, padding: '6px 0',
                }}>
                    <div style={{ padding: '4px 12px 6px', fontSize: 11, fontWeight: 700, color: '#94a3b8', letterSpacing: '.06em', textTransform: 'uppercase' }}>
                        Agregar paso
                    </div>
                    {ADDABLE_TYPES.map(t => (
                        <button
                            key={t}
                            onClick={() => handleAdd(t)}
                            style={{
                                display: 'flex', alignItems: 'center', gap: 10,
                                width: '100%', padding: '7px 12px',
                                background: 'none', border: 'none',
                                cursor: 'pointer', textAlign: 'left',
                                fontSize: 13, color: '#334155',
                            }}
                            onMouseEnter={e => (e.currentTarget.style.background = '#f8fafc')}
                            onMouseLeave={e => (e.currentTarget.style.background = 'none')}
                        >
                            <span style={{ width: 24, height: 24, borderRadius: 6, background: NODE_COLORS[t], display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                                <i className={NODE_ICONS[t]} style={{ color: '#fff', fontSize: 11 }} />
                            </span>
                            {NODE_LABELS[t]}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
