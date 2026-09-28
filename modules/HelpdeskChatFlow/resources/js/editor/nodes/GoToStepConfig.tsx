import React from 'react';
import { NodeConfigProps } from '../types';
import { NODE_LABELS } from '../constants';
import { hintStyle, labelStyle } from '../components/styles';

export default function GoToStepConfig({ draft, allNodes, setData }: NodeConfigProps) {
    const d = draft.data || {};
    const validTargets = allNodes.filter(n => n.id !== draft.id && n.type !== 'branchItem');
    return (
        <div>
            <label style={labelStyle}>Ir al paso</label>
            <select className="form-select form-select-sm"
                value={d.target_node_id || ''}
                onChange={e => {
                    const target = allNodes.find(n => n.id === e.target.value);
                    setData({
                        target_node_id: e.target.value,
                        target_label:   target?.label || '',
                    });
                }}>
                <option value="">— Seleccionar paso —</option>
                {validTargets.map(n => (
                    <option key={n.id} value={n.id}>
                        {n.label || NODE_LABELS[n.type] || n.type}
                    </option>
                ))}
            </select>
            <p style={hintStyle}>El flow saltará a ese paso (útil para reintentos o bucles).</p>
        </div>
    );
}
