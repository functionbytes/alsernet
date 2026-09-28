import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

const PRIORITIES: Array<[string, string]> = [
    ['', 'Por defecto (normal)'],
    ['low', 'Baja'],
    ['normal', 'Normal'],
    ['high', 'Alta'],
    ['urgent', 'Urgente'],
];

export default function CreateTicketConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Asunto del ticket</label>
                <input className="form-control form-control-sm"
                    value={d.subject || ''}
                    placeholder="Ej.: Incidencia con el pedido {{numero_pedido}}"
                    onChange={e => setData({ subject: e.target.value })} />
                <p style={hintStyle}>Admite variables {'{{...}}'}. Vacío: se genera a partir de la conversación.</p>
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Prioridad</label>
                <select className="form-select form-select-sm"
                    value={d.priority || ''}
                    onChange={e => setData({ priority: e.target.value || null })}>
                    {PRIORITIES.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                </select>
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Mensaje de confirmación</label>
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.confirmation ?? 'He creado el ticket :number para dar seguimiento a tu solicitud.'}
                    onChange={e => setData({ confirmation: e.target.value })} />
                <p style={hintStyle}><code>:number</code> se sustituye por el número del ticket, que queda también en {'{{created_ticket_number}}'}. Si el módulo de tickets no está activo, el flujo continúa sin crear nada.</p>
            </div>
        </div>
    );
}
