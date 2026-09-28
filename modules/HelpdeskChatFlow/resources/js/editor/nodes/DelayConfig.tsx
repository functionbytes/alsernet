import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function DelayConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <label style={labelStyle}>Segundos de espera</label>
            <input type="number" className="form-control form-control-sm" min={1} max={300}
                value={d.seconds ?? 5}
                onChange={e => setData({ seconds: parseInt(e.target.value) || 5 })} />
            <p style={hintStyle}>Entre 1 y 300 segundos.</p>
        </div>
    );
}
