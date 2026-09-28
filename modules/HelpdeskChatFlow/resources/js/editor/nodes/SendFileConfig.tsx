import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function SendFileConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>URL del archivo</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="https://.../factura.pdf"
                    value={d.file_url || ''}
                    onChange={e => setData({ file_url: e.target.value })} />
                <p style={hintStyle}>Acepta {'{{variable}}'} (ej. una URL de factura del contexto).</p>
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Tipo de archivo</label>
                <select className="form-select form-select-sm"
                    value={d.file_type || 'document'}
                    onChange={e => setData({ file_type: e.target.value })}>
                    <option value="document">Documento (PDF, etc.)</option>
                    <option value="image">Imagen</option>
                    <option value="video">Vídeo</option>
                </select>
            </div>
            <label style={labelStyle}>Texto (opcional)</label>
            <textarea className="form-control form-control-sm" rows={2}
                placeholder="Mensaje que acompaña al archivo"
                value={d.caption || ''}
                onChange={e => setData({ caption: e.target.value })} />
            <p style={hintStyle}>Se envía como adjunto nativo en WhatsApp/Messenger/Instagram.</p>
        </div>
    );
}
