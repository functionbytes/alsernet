import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function CsatConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Pregunta de valoración</label>
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.question || ''}
                    placeholder="¿Cómo valorarías nuestra atención?"
                    onChange={e => setData({ question: e.target.value })} />
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Escala</label>
                <select className="form-select form-select-sm"
                    value={d.scale || '1-5'}
                    onChange={e => setData({ scale: e.target.value })}>
                    <option value="1-5">Estrellas 1 a 5</option>
                    <option value="1-10">Numérica 1 a 10</option>
                    <option value="thumbs">Pulgar arriba / abajo</option>
                </select>
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Mensaje de agradecimiento</label>
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.thanks_message || ''}
                    placeholder="¡Gracias por tu valoración!"
                    onChange={e => setData({ thanks_message: e.target.value })} />
            </div>
            <label style={labelStyle}>Guardar nota en</label>
            <input type="text" className="form-control form-control-sm"
                placeholder="csat_score"
                value={d.variable_name || ''}
                onChange={e => setData({ variable_name: e.target.value })} />
            <p style={hintStyle}>El cliente responde con el número. Funciona en todos los canales (WhatsApp, Messenger, Instagram, web).</p>
        </div>
    );
}
