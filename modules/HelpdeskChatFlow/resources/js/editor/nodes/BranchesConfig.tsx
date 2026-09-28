import React from 'react';
import { NodeConfigProps } from '../types';
import { labelStyle } from '../components/styles';

export default function BranchesConfig({ draft, allNodes }: NodeConfigProps) {
    const items = allNodes.filter(n => n.parentId === draft.id && n.type === 'branchItem');
    return (
        <div>
            <label style={labelStyle}>Ramas de condición</label>
            {items.map(item => (
                <div key={item.id} style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 6 }}>
                    <input type="text" className="form-control form-control-sm"
                        defaultValue={item.label}
                        onBlur={e => window.__chatflowUpdateBranchName?.(item.id, e.target.value)} />
                    {item.data?.isElse
                        ? <span className="badge bg-secondary flex-shrink-0">else</span>
                        : <button style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: 2, flexShrink: 0 }}
                            onClick={() => { if (confirm('¿Eliminar esta rama?')) window.__chatflowDeleteNode?.(item.id); }}>
                            <i className="fas fa-times" />
                        </button>
                    }
                </div>
            ))}
            <button className="btn btn-sm btn-outline-primary mt-1"
                onClick={() => window.__chatflowAddBranch?.(draft.id)}>
                <i className="fas fa-plus me-1" />Agregar rama
            </button>
        </div>
    );
}
