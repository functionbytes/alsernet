import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function CloseConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <label style={labelStyle}>Mensaje de despedida</label>
            <textarea className="form-control form-control-sm" rows={3}
                value={d.farewell || ''}
                placeholder="Opcional: mensaje final al cliente"
                onChange={e => setData({ farewell: e.target.value })} />
            <p style={hintStyle}>La conversación se cerrará automáticamente.</p>
        </div>
    );
}
