import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function SetAttributeConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Atributo</label>
                <select className="form-select form-select-sm"
                    value={d.attribute || 'priority'}
                    onChange={e => setData({ attribute: e.target.value })}>
                    <option value="priority">Prioridad de la conversación</option>
                    <option value="status">Estado de la conversación</option>
                    <option value="assignee">Asignar agente</option>
                    <option value="custom">Campo personalizado</option>
                </select>
            </div>
            {d.attribute === 'custom' && (
                <div style={{ marginBottom: 8 }}>
                    <label style={labelStyle}>Nombre del campo</label>
                    <input type="text" className="form-control form-control-sm mb-2"
                        placeholder="ej: tipo_cliente"
                        value={d.custom_key || ''}
                        onChange={e => setData({ custom_key: e.target.value })} />
                </div>
            )}
            <label style={labelStyle}>Valor</label>
            <input type="text" className="form-control form-control-sm"
                placeholder="Valor a establecer"
                value={d.value || ''}
                onChange={e => setData({ value: e.target.value })} />
            <p style={hintStyle}>Usa {'{{variable}}'} para usar un valor del contexto.</p>
        </div>
    );
}
