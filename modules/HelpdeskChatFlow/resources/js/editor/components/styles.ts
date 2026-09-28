import React from 'react';

// Shared inline style objects reused across the canvas nodes, the properties
// panel and the per-node-type config fields.

export const handleStyle = { background: 'transparent', border: 'none', width: 8, height: 8 };

export const labelStyle: React.CSSProperties = {
    display: 'block', fontSize: 12, fontWeight: 600, color: '#64748b',
    marginBottom: 4, textTransform: 'uppercase', letterSpacing: '.04em',
};

export const hintStyle: React.CSSProperties = { fontSize: 11, color: '#94a3b8', marginTop: 3 };

export const nodeActionBtnStyle: React.CSSProperties = {
    width: 24, height: 24, borderRadius: 6, border: '1px solid #e2e8f0',
    background: '#fff', color: '#64748b', fontSize: 11, cursor: 'pointer',
    display: 'flex', alignItems: 'center', justifyContent: 'center',
    boxShadow: '0 1px 3px rgba(0,0,0,.12)',
};

export function btnStyle(bg: string): React.CSSProperties {
    return {
        background: bg, color: '#fff', border: 'none', borderRadius: 6,
        padding: '6px 12px', fontSize: 12, fontWeight: 600, cursor: 'pointer',
        display: 'inline-flex', alignItems: 'center', whiteSpace: 'nowrap',
    };
}

export const iconBtnStyle: React.CSSProperties = {
    background: '#fff', color: '#475569', border: '1px solid #e2e8f0', borderRadius: 6,
    width: 30, height: 30, fontSize: 12, cursor: 'pointer',
    display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
};
